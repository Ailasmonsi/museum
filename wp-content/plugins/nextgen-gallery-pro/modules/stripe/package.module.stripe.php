<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_Stripe_Checkout_Ajax extends Mixin
{
    protected function round_for_stripe($amount)
    {
        return round(bcmul($amount, 100, 2), 2, PHP_ROUND_HALF_UP);
    }
    protected function load_stripe()
    {
        if (!class_exists('Stripe') && !class_exists('\\Stripe\\Stripe')) {
            include_once 'stripe-php-7.67.0/init.php';
        }
    }
    /**
     * @return array
     */
    public function stripe_session_start_action()
    {
        try {
            $checkout = new C_NextGen_Pro_Checkout();
            $order = NULL;
            $settings = C_NextGen_Settings::get_instance();
            if (empty($settings->ecommerce_stripe_webhook_secret)) {
                $this->stripe_set_endpoint_secret_action(FALSE);
                $settings->load();
            }
            $this->load_stripe();
            // Identify ourselves to Stripe
            \Stripe\Stripe::setAppInfo('NextGen Gallery Pro', NGG_PRO_PLUGIN_VERSION, 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/');
            \Stripe\Stripe::setApiKey($settings->ecommerce_stripe_key_private);
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'awaiting_payment', 'stripe_checkout', __("Order submitted to Stripe for processing", 'nextgen-gallery-pro'), FALSE);
            // save_order() returns an array that does not include the order object itself, just an ID
            $order_mapper = C_Order_Mapper::get_instance();
            $order = $order_mapper->find_by_hash($result['order'], TRUE);
            // In case there is an error on the Stripe payment details page or the user cancel the order on said
            // page and returns to the site we must remove the order that has been created. This key prevents just
            // anybody from deleting unpaid orders if they happen to know the order hash
            $order->stripe_cancel_nonce = wp_create_nonce('ngg_pro_stripe_cancel_key');
            $order_mapper->save($order);
            /** @var C_NextGen_Pro_Cart $cart */
            $cart = $result['cart'];
            /** @var array $customer */
            $customer = $result['customer'];
            $total = $cart->get_total_array();
            $items = $cart->get_items();
            $line_items = [];
            $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
            // Stripe cannot / will not allow discounts to apply in line items so IF a coupon is present
            // we simply pass the entire cart as a single line item.
            if (!empty($total['discount'])) {
                $str = _n('%d item from %s', '%d items from %s', count($items), 'nextgen-gallery-pro');
                $description = sprintf($str, count($items), $settings->get('ecommerce_studio_name'));
                $line_items[] = ['amount' => $this->round_for_stripe($total['total']), 'currency' => $currency['code'], 'description' => $description, 'name' => $settings->get('ecommerce_studio_name'), 'quantity' => 1];
            } else {
                // No coupons are part of the cart; pass the products, taxes, and shipping as their own line items
                foreach ($items as $item) {
                    // Stripe will throw an error if we include any free items
                    if ($item->price === 0) {
                        continue;
                    }
                    $line_items[] = ['amount' => $this->round_for_stripe($item->price), 'currency' => $currency['code'], 'description' => $item->image->alttext, 'images' => [$item->image->thumbnail_url], 'name' => $item->title, 'quantity' => $item->quantity];
                }
                if (!empty($total['taxes'])) {
                    $line_items[] = ['amount' => $this->round_for_stripe($total['taxes']), 'currency' => $currency['code'], 'name' => __('Taxes', 'nextgen-gallery-pro'), 'quantity' => 1];
                }
                if (!empty($total['shipping'])) {
                    $line_items[] = ['amount' => $this->round_for_stripe($total['shipping']), 'currency' => $currency['code'], 'name' => __('Shipping', 'nextgen-gallery-pro'), 'quantity' => 1];
                }
            }
            $session = \Stripe\Checkout\Session::create(['cancel_url' => site_url('/?ngg_stripe_cancel=1&order=' . $order->hash . '&key=' . $order->stripe_cancel_nonce), 'client_reference_id' => $order->hash, 'customer_email' => $customer['email'], 'line_items' => $line_items, 'payment_method_types' => ['card'], 'success_url' => site_url('/?ngg_stripe_rtn=1&order=' . $order->hash)]);
            return ['id' => $session->id];
            // The card was declined
        } catch (\Stripe\Error\Card $exception) {
            return $this->handle_stripe_exception($exception, $order, TRUE, FALSE);
            // Too many requests made too quickly
        } catch (\Stripe\Error\RateLimit $exception) {
            // TODO: Notify the admin or try again
            return $this->handle_stripe_exception($exception, $order, FALSE);
            // Invalid parameters were sent to the API server
        } catch (\Stripe\Error\InvalidRequest $exception) {
            return $this->handle_stripe_exception($exception, $order, FALSE);
            // Authentication with Stripe failed
        } catch (\Stripe\Error\Authentication $exception) {
            return $this->handle_stripe_exception($exception, $order, FALSE);
            // Network communication with Stripe failed
        } catch (\Stripe\Error\ApiConnection $exception) {
            return $this->handle_stripe_exception($exception, $order, FALSE);
            // Display a generic error to the user
        } catch (\Stripe\Error\Base $exception) {
            return $this->handle_stripe_exception($exception, $order, TRUE, FALSE);
            // Generic PHP exceptions not thrown by the Stripe SDK
        } catch (Exception $exception) {
            return $this->handle_exception($exception, $order, FALSE, TRUE);
        }
    }
    /**
     * @param Exception $exception
     * @param C_NextGen_Pro_Order $order
     * @param bool $override_message Show the customer the message from Stripe itself. This should only happen if the card is declined.
     * @return array
     */
    protected function handle_stripe_exception($exception, $order, $override_message = FALSE, $notify = TRUE)
    {
        if (isset($order) && $order) {
            $order->destroy();
        }
        $retval = ['error' => __('An error has occurred while processing your order. Please try again later.', 'nextgen-gallery-pro')];
        if ($override_message) {
            $body = $exception->getJsonBody();
            $retval['error'] = $body['error']['message'];
        }
        if ($notify) {
            $body = $exception->getJsonBody();
            $message = __("An error has occurred while processing an order on your website with a Stripe payment. Please include the following information when filing a bug report:", "nextgen-gallery-pro");
            $message .= "\n\n";
            $message .= "Status: %%status%%\n";
            $message .= "Type: %%type%%\n";
            $message .= "Code: %%code%%\n";
            $message .= "Message: %%message%%\n";
            $mailman = C_Nextgen_Mail_Manager::get_instance();
            $content = $mailman->create_content();
            $subject = __('Site error while processing a NextGen Pro order via Stripe', 'nextgen-gallery-pro');
            $content->set_subject($subject);
            $content->set_property('status', $exception->getHttpStatus());
            $content->set_property('type', $body['error']['type']);
            $content->set_property('code', $exception->getStripeCode());
            $content->set_property('message', $exception->getMessage());
            $content->load_template($message);
            $mailman->send(M_NextGen_Pro_Ecommerce::get_studio_email_address(), $subject, $content);
        }
        return $retval;
    }
    /**
     * @param Exception $exception
     * @param C_NextGen_Pro_Order $order
     * @param bool $override_message
     * @param bool $notify
     * @return array
     */
    protected function handle_exception(\Exception $exception, $order, $override_message = FALSE, $notify = TRUE)
    {
        if (isset($order) && $order !== FALSE) {
            $order->destroy();
        }
        $retval = ['error' => __('An error has occurred while processing your order. Please try again later.', 'nextgen-gallery-pro')];
        if ($override_message) {
            $retval['error'] = $exception->getMessage();
        }
        if ($notify) {
            $message = __("An error has occurred while processing an order on your website with a Stripe payment. Please include the following information when filing a bug report:", "nextgen-gallery-pro");
            $message .= "\n\n";
            $message .= "Status: %%status%%\n";
            $message .= "Message: %%message%%\n";
            $mailman = C_Nextgen_Mail_Manager::get_instance();
            $content = $mailman->create_content();
            $subject = __('Site error while processing a NextGen Pro order via Stripe', 'nextgen-gallery-pro');
            $content->set_subject($subject);
            $content->set_property('status', $exception->getHttpStatus());
            $content->set_property('message', $exception->getMessage());
            $content->load_template($message);
            $mailman->send(M_NextGen_Pro_Ecommerce::get_studio_email_address(), $subject, $content);
        }
        return $retval;
    }
    public function stripe_poll_for_missed_webhook_events_action($admin = TRUE)
    {
        if ($admin && !current_user_can('administrator')) {
            return ['error' => __('This request requires an authenticated administrator', 'nextgen-gallery-pro')];
        }
        $settings = C_NextGen_Settings::get_instance();
        $this->load_stripe();
        // Identify ourselves to Stripe
        \Stripe\Stripe::setAppInfo('NextGen Gallery Pro', NGG_PRO_PLUGIN_VERSION, 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/');
        \Stripe\Stripe::setApiKey($settings->ecommerce_stripe_key_private);
        try {
            $events = \Stripe\Event::all(['type' => 'checkout.session.completed', 'created' => ['gte' => time() - 48 * 60 * 60]]);
            $count = 0;
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $order_mapper = C_Order_Mapper::get_instance();
            foreach ($events->autoPagingIterator() as $event) {
                $order = C_Order_Mapper::get_instance()->find_by_hash($event->data->object->client_reference_id, TRUE);
                if ($order->status !== 'paid') {
                    $checkout->mark_as_paid($order, TRUE);
                    unset($order->stripe_cancel_nonce);
                    $order_mapper->save($order);
                    $count++;
                }
            }
            return ['updated' => $count];
        } catch (Exception $exception) {
            return $this->handle_exception($exception, FALSE, TRUE, FALSE);
        }
    }
    public function stripe_set_endpoint_secret_action($triggered_by_admin = TRUE)
    {
        if (!current_user_can('administrator')) {
            return ['error' => __('This request requires an authenticated administrator', 'nextgen-gallery-pro')];
        }
        try {
            $settings = C_NextGen_Settings::get_instance();
            $this->load_stripe();
            // Identify ourselves to Stripe
            \Stripe\Stripe::setAppInfo('NextGen Gallery Pro', NGG_PRO_PLUGIN_VERSION, 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/');
            \Stripe\Stripe::setApiKey($settings->get('ecommerce_stripe_key_private'));
            $our_endpoint_url = $settings->ajax_url . '&action=stripe_webhook_handler';
            $our_other_endpoint_url = $our_endpoint_url . '_action';
            // We can't retrieve the endpoint secret; it is given to us ONLY as a result of WebhookEndpoint::create()
            // Remove possible duplicate endpoints that we can't use due to lacking the secret:
            foreach (\Stripe\WebhookEndpoint::all() as $endpoint) {
                if ($endpoint->url === $our_endpoint_url || $endpoint->url == $our_other_endpoint_url) {
                    $endpoint->delete();
                }
            }
            $endpoint = \Stripe\WebhookEndpoint::create(['url' => $our_endpoint_url, 'enabled_events' => ['checkout.session.completed'], 'api_version' => '2019-08-14']);
            $settings->ecommerce_stripe_webhook_secret = $endpoint->secret;
            $settings->save();
            return ['finished' => TRUE];
        } catch (Exception $exception) {
            return $this->handle_exception($exception, FALSE, $triggered_by_admin, FALSE);
        }
    }
    // Wrapper for compatibility reasons
    // By mistake, we were registering the webhook as /?photocrati_ajax=1&action=stripe_webhook_handler_action
    // instead of ?photocrati_ajax=1&action=stripe_webhook_handler
    public function stripe_webhook_handler_action_action()
    {
        $this->stripe_webhook_handler_action();
    }
    public function stripe_webhook_handler_action()
    {
        try {
            $event = NULL;
            $settings = C_NextGen_Settings::get_instance();
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            $payload = @file_get_contents('php://input');
            $this->load_stripe();
            // Identify ourselves to Stripe
            \Stripe\Stripe::setAppInfo('NextGen Gallery Pro', NGG_PRO_PLUGIN_VERSION, 'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/');
            \Stripe\Stripe::setApiKey($settings->ecommerce_stripe_key_private);
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $settings->ecommerce_stripe_webhook_secret);
            // Handle the checkout.session.completed event
            if ($event->type === 'checkout.session.completed') {
                $order_mapper = C_Order_Mapper::get_instance();
                $checkout = C_NextGen_Pro_Checkout::get_instance();
                $order = $order_mapper->find_by_hash($event->data->object->client_reference_id, TRUE);
                $checkout->mark_as_paid($order, TRUE);
                unset($order->stripe_cancel_nonce);
                $order_mapper->save($order);
                // We delay the order notification until here in case the above stripe_session_start_action() fails: we don't
                // want to send the customer a notification that they've placed an order if the process failed
                // as their order will have been automatically purged. Only send this email if everything has finished
                // correctly without errors
                $checkout->send_order_notification($order);
                http_response_code(200);
                exit;
            }
        } catch (\UnexpectedValueException $exception) {
            $this->handle_stripe_exception($exception, FALSE, FALSE, TRUE);
            http_response_code(400);
            exit;
        } catch (\Stripe\Error\SignatureVerification $exception) {
            $this->handle_stripe_exception($exception, FALSE, FALSE, TRUE);
            $this->stripe_poll_for_missed_webhook_events_action(FALSE);
            http_response_code(400);
            exit;
        } catch (\Exception $exception) {
            $this->handle_exception($exception, FALSE, FALSE, TRUE);
            http_response_code(400);
            exit;
        }
        // Last cleanup in case an event besides checkout.session.completed is sent
        http_response_code(400);
        exit;
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_Stripe_Checkout_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        if ($this->is_stripe_enabled()) {
            $buttons[] = 'stripe_checkout';
        }
        return $buttons;
    }
    function is_stripe_enabled()
    {
        return C_NextGen_Settings::get_instance()->ecommerce_stripe_enable;
    }
    /**
     * @return stdClass
     */
    function get_i18n_strings()
    {
        $i18n = $this->call_parent('get_i18n_strings');
        $i18n->pay_with_card = __('Pay with Card', 'nextgen-gallery-pro');
        return $i18n;
    }
    /**
     * @param bool $include_private_key
     * @return array
     */
    function get_stripe_vars($include_private_key = FALSE)
    {
        $settings = C_NextGen_Settings::get_instance();
        $retval = ['name' => html_entity_decode(get_bloginfo('name')), 'key' => $settings->ecommerce_stripe_key_public, 'currency' => C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency]['code']];
        if ($include_private_key) {
            $retval['private_key'] = $settings->ecommerce_stripe_key_private;
        }
        return $retval;
    }
    function enqueue_stripe_checkout_resources()
    {
        wp_enqueue_script('stripe-checkout', 'https://js.stripe.com/v3');
        wp_enqueue_script('ngg_stripe_checkout_button', $this->object->get_static_url('photocrati-stripe#button.js'), ['jquery', 'stripe-checkout']);
    }
    /**
     * @return string
     */
    function _render_stripe_checkout_button()
    {
        return $this->object->render_partial('photocrati-stripe#button', ['i18n' => $this->get_i18n_strings(), 'stripe_vars' => json_encode($this->get_stripe_vars())], TRUE);
    }
}
/**
 * @property C_Form $object
 */
class A_Stripe_Checkout_Form extends Mixin
{
    public function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'nextgen_pro_ecommerce_stripe_enable';
        $fields[] = 'nextgen_pro_ecommerce_stripe_key_public';
        $fields[] = 'nextgen_pro_ecommerce_stripe_key_private';
        $fields[] = 'nextgen_pro_ecommerce_stripe_webhook_secret';
        return $fields;
    }
    public function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        wp_enqueue_script('ngg_pro_stripe_form_js', $this->object->get_static_url('photocrati-stripe#form.js'));
    }
    function save_action()
    {
        $this->call_parent('save_action');
        $settings = C_NextGen_Settings::get_instance();
        /** @var array $new_settings */
        $new_settings = $this->object->param('stripe');
        if (!$new_settings) {
            return;
        }
        foreach ($new_settings as $key => $value) {
            $settings->set("ecommerce_stripe_{$key}", $value);
        }
        $settings->save();
        if (!$settings->get('ecommerce_stripe_enable')) {
            return;
        }
        $key_private = $settings->get('ecommerce_stripe_key_private');
        $webhook_secret = $settings->get('ecommerce_stripe_webhook_secret');
        $webhook_hash = $settings->get('ecommerce_stripe_webhook_hash');
        $hash = md5($key_private . '|' . home_url());
        if (empty($webhook_secret) || empty($webhook_hash) || $webhook_hash !== $hash) {
            /** @var A_Stripe_Checkout_Ajax $controller */
            $controller = C_Ajax_Controller::get_instance();
            $result = $controller->stripe_set_endpoint_secret_action();
            if (isset($result['finished']) && $result['finished'] === TRUE) {
                $settings->set('ecommerce_stripe_webhook_hash', $hash);
            } else {
                $settings->set('ecommerce_stripe_webhook_secret', NULL);
                $settings->set('ecommerce_stripe_webhook_hash', NULL);
                print "<div class='error'><p>{$result['error']}</p></div>";
            }
            $settings->save();
        }
    }
    public function _render_nextgen_pro_ecommerce_stripe_enable_field($model)
    {
        $model = new stdClass();
        $model->name = 'stripe';
        return $this->object->_render_radio_field($model, 'enable', __('Enable Stripe', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_stripe_enable, __('Not all currencies are supported by all payment gateways. Please be sure to confirm your desired currency is supported by Stripe', 'nextgen-gallery-pro'));
    }
    public function _render_nextgen_pro_ecommerce_stripe_key_public_field($model)
    {
        $model = new stdClass();
        $model->name = 'stripe';
        return $this->object->_render_text_field($model, 'key_public', __('<strong>Public key</strong>', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_stripe_key_public, '', !C_NextGen_Settings::get_instance()->ecommerce_stripe_enable ? TRUE : FALSE);
    }
    public function _render_nextgen_pro_ecommerce_stripe_key_private_field($model)
    {
        $model = new stdClass();
        $model->name = 'stripe';
        return $this->object->_render_text_field($model, 'key_private', __('<strong>Private key</strong>', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_stripe_key_private, '', !C_NextGen_Settings::get_instance()->ecommerce_stripe_enable ? TRUE : FALSE);
    }
}
class C_Stripe_TLS12_Check_Notification
{
    function is_renderable()
    {
        $settings = C_NextGen_Settings::get_instance();
        // Only check if Stripe payment gateway is enabled
        if (!$settings->ecommerce_stripe_enable) {
            return FALSE;
        }
        // Determine if CURL supports TLS 1.1
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            return FALSE;
        }
        return TRUE;
    }
    function render()
    {
        return __('Stripe no longer supports API requests made with TLS 1.0. Please contact your systems administrator to enable TLS 1.2 support on your host.', 'nextgen-gallery-pro');
    }
    function get_css_class()
    {
        return 'error';
    }
    function is_dismissable()
    {
        return TRUE;
    }
    function dismiss($code)
    {
        return array('handled' => TRUE);
    }
}