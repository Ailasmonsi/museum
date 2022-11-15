<?php
require_once __DIR__ . '/vendor/autoload.php';
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Webhook;
use PayPal\Api\WebhookEventType;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\VerifyWebhookSignature;
/**
 * @property C_Ajax_Controller $object
 */
class A_PayPal_Checkout_Ajax extends Mixin
{
    /**
     * @return array
     */
    protected function get_credentials()
    {
        $settings = C_NextGen_Settings::get_instance();
        return ['client_id' => $settings->get('ecommerce_paypal_checkout_client_id'), 'client_secret' => $settings->get('ecommerce_paypal_checkout_client_secret')];
    }
    /**
     * Returns the client needed for PayPal methods
     * @return ApiContext
     */
    protected function get_context()
    {
        $credentials = $this->get_credentials();
        $context = new ApiContext(new OAuthTokenCredential($credentials['client_id'], $credentials['client_secret']));
        $config = ['log.LogEnabled' => FALSE, 'log.FileName' => 'PayPal.log', 'log.LogLevel' => 'DEBUG'];
        $config = apply_filters('ngg_pro_paypal_checkout_logging', $config);
        $settings = C_NextGen_Settings::get_instance();
        $sandbox = $settings->get('ecommerce_paypal_checkout_sandbox');
        $config['mode'] = $sandbox ? 'sandbox' : 'live';
        $config['cache.enabled'] = FALSE;
        $context->setConfig($config);
        return $context;
    }
    /**
     * Returns the client needed for PayPalCheckoutSdk methods
     *
     * @return PayPalHttpClient
     */
    protected function get_client()
    {
        $credentials = $this->get_credentials();
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->get('ecommerce_paypal_checkout_sandbox')) {
            $environment = new SandboxEnvironment($credentials['client_id'], $credentials['client_secret']);
        } else {
            $environment = new ProductionEnvironment($credentials['client_id'], $credentials['client_secret']);
        }
        $client = new PayPalHttpClient($environment);
        return $client;
    }
    /**
     * @param ApiContext $context
     * @return Webhook
     */
    protected function create_webhook($context)
    {
        $hook = new Webhook();
        $hook->setUrl($this->get_webhook_url());
        $hook->setEventTypes([new WebhookEventType('{"name":"PAYMENT.CAPTURE.COMPLETED"}')]);
        $new_hook = $hook->create($context);
        $settings = C_NextGen_Settings::get_instance();
        $settings->set('ecommerce_paypal_checkout_notification_id', $new_hook->getId());
        $settings->save();
        return $new_hook;
    }
    /**
     * @return string
     */
    public function get_webhook_url()
    {
        $settings = C_NextGen_Settings::get_instance();
        return $settings->get('ajax_url') . "&action=paypal_checkout_webhook_listener";
    }
    /**
     * Finds existing registration or registers this site in order to listen for order notifications. It is important
     * that we have stored the notification-id from PayPal as it is necessary to verify the authenticity of incoming
     * notifications from PayPal when an order capture is completed.
     * @return array
     */
    public function paypal_checkout_register_webhook_action()
    {
        if (!current_user_can('administrator')) {
            return ['error' => __('This request requires an authenticated administrator', 'nextgen-gallery-pro')];
        }
        try {
            $settings = C_NextGen_Settings::get_instance();
            $context = $this->get_context();
            $hooks = Webhook::getAllWithParams([], $context)->getWebhooks();
            // There are no registered webhook listeners - create ours
            if (empty($hooks)) {
                $this->create_webhook($context);
                return ['finished' => TRUE];
            } else {
                // Determine if there's an existing webhook we don't have the ID
                $url = $this->get_webhook_url();
                $found = FALSE;
                foreach ($hooks as $hook) {
                    /** @var Webhook $hook */
                    if ($hook->getUrl() !== $url) {
                        continue;
                    }
                    foreach ($hook->getEventTypes() as $type) {
                        /** @var WebhookEventType $type */
                        if ($type->getName() === 'PAYMENT.CAPTURE.COMPLETED') {
                            if ($settings->get('ecommerce_paypal_checkout_notification_id') !== $hook->getId()) {
                                $found = TRUE;
                                $settings->set('ecommerce_paypal_checkout_notification_id', $hook->getId());
                                $settings->save();
                            }
                            return ['finished' => TRUE];
                        }
                    }
                }
                // We have existing hooks but none that we want right now
                if (!$found) {
                    $this->create_webhook($context);
                }
                // We didn't found our own URL, so register it now
                return ['finished' => TRUE];
            }
        } catch (PayPalConnectionException $exception) {
            $data = json_decode($exception->getData());
            if (isset($data->error)) {
                return ['error' => sprintf(__('An unexpected problem occurred contacting PayPal.com to register your site for payment notifications: %s', 'nextgen-gallery-pro'), $data->error_description)];
            }
            $url = 'https://developer.paypal.com/docs/integration/direct/webhooks/rest-webhooks/#to-use-the-dashboard-to-subscribe-to-events';
            if ($data->name === 'WEBHOOK_URL_ALREADY_EXISTS') {
                return ['error' => sprintf(__('NextGen Pro cannot register your site URL with PayPal to receive payment notifications. You must use the PayPal <a href="%s" target="_blank">developer dashboard</a> and do this manually.', 'nextgen-gallery-pro'), $url)];
            } else {
                if ($data->name === 'WEBHOOK_NUMBER_LIMIT_EXCEEDED') {
                    return ['error' => sprintf(__('PayPal only allows 10 payment notification URL to be registered and you have reached that limit. You must use the PayPal <a href="%s" target="_blank">developer dashboard</a> to remove an existing registered URL before attempting again.', 'nextgen-gallery-pro'), $url)];
                }
            }
            return ['error' => __('An unexpected problem occurred contacting PayPal.com', 'nextgen-gallery-pro')];
        } catch (Exception $exception) {
            return ['error' => __('An unexpected problem occurred contacting PayPal.com', 'nextgen-gallery-pro')];
        }
    }
    /**
     * Creates a NextGen order and makes the funds authorization request to PayPal
     * @return array
     */
    public function paypal_checkout_create_order_action()
    {
        try {
            $checkout = new C_NextGen_Pro_Checkout();
            $settings = C_NextGen_Settings::get_instance();
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'awaiting_payment', 'paypal_checkout', __('Order submitted to PayPal for processing', 'nextgen-gallery-pro'), FALSE);
            // save_order() returns an array that does not include the order object itself, just an ID
            $order_mapper = C_Order_Mapper::get_instance();
            $order = $order_mapper->find_by_hash($result['order'], TRUE);
            // In case there is an error on the PayPal payment details page or the user cancel the order on said
            // page and returns to the site we must remove the order that has been created. This key prevents just
            // anybody from deleting unpaid orders if they happen to know the order hash
            $order->paypal_checkout_cancel_nonce = wp_create_nonce('ngg_paypal_checkout_cancel_key');
            $order_mapper->save($order);
            // Where the browser is sent to cancel the creation of this order: this will delete the NGG order!
            $cancel_url = $settings->get('ajax_url') . '&action=paypal_checkout_cancel_order';
            $cancel_url .= '&order_id=' . $order->hash;
            $cancel_url .= '&key=' . $order->paypal_checkout_cancel_nonce;
            // Where the browser is sent once the process is complete
            $return_url = $settings->get('ajax_url') . '&action=paypal_checkout_order_complete';
            $return_url .= '&order_id=' . $order->hash;
            /** @var C_NextGen_Pro_Cart $cart */
            $cart = $result['cart'];
            /** @var array $customer */
            $customer = $result['customer'];
            $totals = $cart->get_total_array();
            $items = $cart->get_items();
            $currency = C_NextGen_Pro_Currencies::$currencies[$settings->get('ecommerce_currency')];
            // In case the admin has neglected to register their webhook URL in the wp-admin
            if (empty($settings->get('ecommerce_paypal_checkout_notification_id'))) {
                $this->paypal_checkout_register_webhook_action();
                $settings->load();
            }
            $breakdown = ['item_total' => ['currency_code' => $currency['code'], 'value' => (string) bcadd($totals['subtotal'], $totals['discount'], $currency['exponent'])]];
            if (!empty($totals['taxes'])) {
                $breakdown['tax_total'] = ['currency_code' => $currency['code'], 'value' => (string) $totals['taxes']];
            }
            if (!empty($totals['shipping'])) {
                $breakdown['shipping'] = ['currency_code' => $currency['code'], 'value' => (string) $totals['shipping']];
            }
            if (!empty($totals['discount'])) {
                $breakdown['discount'] = ['currency_code' => $currency['code'], 'value' => (string) $totals['discount']];
            }
            $items_array = [];
            foreach ($items as $item) {
                if (empty($item->title)) {
                    $item->title = ' ';
                }
                // MINIMUM of one character
                $items_array[] = ['name' => $this->_sub($item->title, 127), 'description' => $this->_sub($item->image->alttext, 127), 'unit_amount' => ['currency_code' => $currency['code'], 'value' => (string) $item->price], 'quantity' => $item->quantity, 'category' => $item->category === 'ngg_category_digital_downloads' ? 'DIGITAL_GOODS' : 'PHYSICAL_GOODS'];
            }
            $context = ['brand_name' => $this->_sub($settings->get('ecommerce_studio_name'), 127), 'cancel_url' => $cancel_url, 'return_url' => $return_url, 'landing_page' => 'BILLING', 'locale' => str_replace('_', '-', get_locale()), 'shipping_preferences' => $cart->has_shippable_items() ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING'];
            $purchase_units = [
                'amount' => ['breakdown' => $breakdown, 'currency_code' => $currency['code'], 'value' => (string) $totals['total']],
                'custom_id' => $order->hash,
                // max 127 characters
                'description' => $this->_sub(sprintf(__('Order from %s', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_name')), 127),
                'items' => $items_array,
                'reference_id' => $order->hash,
                // max 256 characters
                'soft_descriptor' => $this->_sub($settings->get('ecommerce_studio_name'), 22),
            ];
            if ($cart->has_shippable_items()) {
                $purchase_units['shipping'] = ['shipping_type' => 'SHIPPING', 'name' => ['full_name' => $this->_sub($customer['name'], 300)], 'address' => ['address_line_1' => $this->_sub($customer['street_address'], 300), 'address_line_2' => $this->_sub($customer['address_line'], 300), 'admin_area_2' => $this->_sub($customer['city'], 120), 'admin_area_1' => $this->_sub($customer['state'], 300), 'postal_code' => $this->_sub($customer['zip'], 60), 'country_code' => $this->_sub($customer['country'], 2)]];
            }
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = ['intent' => 'CAPTURE', 'application_context' => $context, 'purchase_units' => [$purchase_units]];
            $response = $this->get_client()->execute($request);
            /** @var stdClass $response->result */
            return ['paypal_order_id' => $response->result->id];
        } catch (\BraintreeHttp\HttpException $exception) {
            return ['error' => __('An unexpected problem occurred when contacting PayPal.com to create your order. Please try again later.', 'nextgen-gallery-pro')];
        } catch (Exception $exception) {
            return ['error' => __('An unexpected problem occurred when contacting PayPal.com to create your order. Please try again later.', 'nextgen-gallery-pro')];
        }
    }
    /**
     * Requests the capture of funds previously authorized
     *
     * @return array
     */
    public function paypal_checkout_finalize_action()
    {
        try {
            $client = $this->get_client();
            $order_id = $this->object->param('order_id');
            $request = new OrdersCaptureRequest($order_id);
            $request->prefer('return=representation');
            $response = $client->execute($request);
            $result = $response->result;
            $mapper = C_Order_Mapper::get_instance();
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $order = $mapper->find_by_hash($result->purchase_units[0]->reference_id, TRUE);
            $retval = ['confirmation_url' => $checkout->get_thank_you_page_url($order->hash, TRUE)];
            /** @var stdClass $result */
            if ($result->status === 'COMPLETED') {
                unset($order->paypal_checkout_cancel_nonce);
                $mapper->save($order);
                $checkout->mark_as_paid($order, TRUE);
                $checkout->send_order_notification($order);
                $retval['payment_captured'] = TRUE;
            } else {
                $retval['payment_captured'] = FALSE;
            }
            return $retval;
        } catch (\BraintreeHttp\HttpException $exception) {
            return ['error' => __('An unexpected problem occurred when contacting PayPal.com. Please contact the site owner to complete your purchase.', 'nextgen-gallery-pro')];
        }
    }
    /**
     * Handles PAYMENT.CAPTURE.COMPLETED notifications from PayPal's servers
     */
    public function paypal_checkout_webhook_listener_action()
    {
        try {
            $settings = C_NextGen_Settings::get_instance();
            $unparsed_response = file_get_contents('php://input');
            $headers = array_change_key_case($this->get_headers(), CASE_UPPER);
            $signatureVerification = new VerifyWebhookSignature();
            $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
            $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
            $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
            $signatureVerification->setWebhookId($settings->get('ecommerce_paypal_checkout_notification_id'));
            $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
            $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
            $signatureVerification->setRequestBody($unparsed_response);
            $output = $signatureVerification->post($this->get_context());
            if ($output->verification_status === 'SUCCESS') {
                $response = json_decode($unparsed_response);
                if ($response->resource->final_capture === TRUE) {
                    $mapper = C_Order_Mapper::get_instance();
                    $checkout = C_NextGen_Pro_Checkout::get_instance();
                    $order = $mapper->find_by_hash($response->resource->custom_id, TRUE);
                    if (!empty($order->paypal_checkout_cancel_nonce)) {
                        unset($order->paypal_checkout_cancel_nonce);
                        $mapper->save($order);
                    }
                    // Most PayPal orders will already be captured before the notification is received so we should
                    // attempt to avoid sending a second notification to the customer
                    if ($order->status !== 'paid') {
                        $checkout->mark_as_paid($order, TRUE);
                        $checkout->send_order_notification($order);
                    }
                }
            }
        } catch (Exception $exception) {
            return;
            // there's not much to do here
        }
    }
    /**
     * It seems unlikely any users will hit this endpoint: while PayPal's modal popup does show a link to cancel
     * the order-in-progress and return to the site the modal window is closed immediately on clicking and the browser
     * does not request the cancel_url parameter established above (and handled by here). Just in case however..
     */
    public function paypal_checkout_cancel_order_action()
    {
        $order_id = $this->object->param('order_id');
        $nonce = $this->object->param('key');
        if (empty($order_id) || empty($nonce)) {
            exit;
        }
        $order = C_Order_Mapper::get_instance()->find_by_hash($order_id, TRUE);
        if (!empty($order) && !empty($order->paypal_checkout_cancel_nonce) && $order->paypal_checkout_cancel_nonce === $nonce && $order->status == "unpaid" && $order->payment_gateway == "paypal_checkout") {
            $order->destroy();
            wp_redirect(home_url());
            exit;
        }
    }
    /**
     * Most users should never be sent to this URL as the client side JS will handle the redirect after we
     * attempt to capture the authorized funds. Just in case though..
     */
    public function paypal_checkout_order_complete_action()
    {
        C_NextGen_Pro_Checkout::get_instance()->redirect_to_thank_you_page($this->object->param('order_id'));
        exit;
    }
    /**
     * PayPal expects the array of HTTP headers to be specially formatted for its notification signature verification
     *
     * @return array
     */
    protected function get_headers()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
    /**
     * A wrapper to substr()
     *
     * @param string $string
     * @param int $length
     * @return false|string
     */
    protected function _sub($string, $length)
    {
        // Remove spaces so that we can cram as much information in as possible
        if (strlen($string) > $length) {
            $string = str_replace(' ', '', $string);
        }
        return substr($string, 0, $length);
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_PayPal_Checkout_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        if ($this->is_paypal_checkout_enabled()) {
            $buttons[] = 'paypal_checkout';
        }
        return $buttons;
    }
    function is_paypal_checkout_enabled()
    {
        // If the notification ID is missing we cannot load the necessary paypal JS SDK from PayPal's servers
        $settings = C_NextGen_Settings::get_instance();
        $notification_id = $settings->get('ecommerce_paypal_checkout_notification_id');
        if (!$notification_id) {
            return FALSE;
        }
        return C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_enable;
    }
    public function enqueue_paypal_checkout_resources()
    {
        $currency = C_NextGen_Pro_Currencies::$currencies[C_NextGen_Settings::get_instance()->get('ecommerce_currency')]['code'];
        $client_id = C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_client_id;
        wp_enqueue_script(
            'paypal-checkout',
            "https://www.paypal.com/sdk/js?currency={$currency}&client-id={$client_id}",
            [],
            NULL,
            // MUST BE AN EMPTY STRING or PayPal will refuse to serve this file
            TRUE
        );
        wp_enqueue_script('nextgen_pro_paypal_checkout_js', $this->object->get_static_url('photocrati-paypal_checkout#button.js'), ['jquery'], FALSE, TRUE);
        wp_enqueue_style('nextgen_pro_paypal_checkout_css', $this->object->get_static_url('photocrati-paypal_checkout#button.css'), [], FALSE);
    }
    /**
     * @return stdClass
     */
    function get_i18n_strings()
    {
        $i18n = $this->call_parent('get_i18n_strings');
        $i18n->pay_with_card = __('Pay with PayPal', 'nextgen-gallery-pro');
        return $i18n;
    }
    /**
     * @return string
     */
    function _render_paypal_checkout_button()
    {
        return $this->object->render_partial('photocrati-paypal_checkout#button', ['i18n' => $this->get_i18n_strings()], TRUE);
    }
}
/**
 * @property C_Form $object
 */
class A_PayPal_Checkout_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'nextgen_pro_ecommerce_paypal_checkout_enable';
        $fields[] = 'nextgen_pro_ecommerce_paypal_checkout_sandbox';
        $fields[] = 'nextgen_pro_ecommerce_paypal_checkout_client_id';
        $fields[] = 'nextgen_pro_ecommerce_paypal_checkout_client_secret';
        $fields[] = 'nextgen_pro_ecommerce_paypal_checkout_instructions';
        return $fields;
    }
    function save_action()
    {
        $this->call_parent('save_action');
        $settings = C_NextGen_Settings::get_instance();
        $new_settings = $this->object->param('paypal_checkout');
        if (!$new_settings) {
            return;
        }
        foreach ($new_settings as $key => $value) {
            $settings->set("ecommerce_paypal_checkout_{$key}", $value);
        }
        $settings->save();
        if (!$settings->get('ecommerce_paypal_checkout_enable')) {
            return;
        }
        $sandbox = $settings->get('ecommerce_paypal_checkout_sandbox');
        $client_id = $settings->get('ecommerce_paypal_checkout_client_id');
        $client_secret = $settings->get('ecommerce_paypal_checkout_client_secret');
        $notification_id = $settings->get('ecommerce_paypal_checkout_notification_id');
        $notification_hash = $settings->get('ecommerce_paypal_checkout_notification_hash');
        $hash = md5($client_id . '|' . $client_secret . '|' . home_url() . '|' . $sandbox);
        if (empty($notification_id) || empty($notification_hash) || $notification_hash !== $hash) {
            $controller = C_Ajax_Controller::get_instance();
            $result = $controller->paypal_checkout_register_webhook_action();
            if (isset($result['finished']) && $result['finished'] === TRUE) {
                $settings->set('ecommerce_paypal_checkout_notification_hash', $hash);
            } else {
                $settings->set('ecommerce_paypal_checkout_notification_id', NULL);
                $settings->set('ecommerce_paypal_checkout_notification_hash', NULL);
                print "<div class='error'><p>{$result['error']}</p></div>";
            }
            $settings->save();
        }
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        wp_enqueue_script('ngg_pro_paypal_checkout_form_js', $this->object->get_static_url('photocrati-paypal_checkout#form.js'));
        wp_enqueue_style('ngg_pro_paypal_checkout_form_css', $this->object->get_static_url('photocrati-paypal_checkout#form.css'));
    }
    function _render_nextgen_pro_ecommerce_paypal_checkout_enable_field($model)
    {
        $model = new stdClass();
        $model->name = 'paypal_checkout';
        return $this->object->_render_radio_field($model, 'enable', __('Enable PayPal Checkout', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_enable, __('Not all currencies are supported by all payment gateways. Please be sure to confirm your desired currency is supported by PayPal Checkout', 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_paypal_checkout_sandbox_field()
    {
        $model = new stdClass();
        $model->name = 'paypal_checkout';
        return $this->object->_render_radio_field($model, 'sandbox', __('Use Sandbox', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_sandbox, '', !C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_enable ? TRUE : FALSE);
    }
    function _render_nextgen_pro_ecommerce_paypal_checkout_client_id_field($model)
    {
        $model = new stdClass();
        $model->name = 'paypal_checkout';
        return $this->object->_render_text_field($model, 'client_id', __('<strong>Client ID</strong>', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_client_id, '', !C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_enable ? TRUE : FALSE);
    }
    function _render_nextgen_pro_ecommerce_paypal_checkout_client_secret_field($model)
    {
        $model = new stdClass();
        $model->name = 'paypal_checkout';
        return $this->object->_render_text_field($model, 'client_secret', __('<strong>Client secret</strong>', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_client_secret, '', !C_NextGen_Settings::get_instance()->ecommerce_paypal_checkout_enable ? TRUE : FALSE);
    }
    public function _render_nextgen_pro_ecommerce_paypal_checkout_instructions_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $url = 'https://www.imagely.com/docs/how-to-setup-paypal-checkout/';
        $i18n = ['setup_instructions' => sprintf(__('See our <a href="%s" target="_blank"">documentation</a> on setting up PayPal Checkout.', 'nextgen-gallery-pro'), $url)];
        return $this->object->render_partial('photocrati-paypal_checkout#admin_instructions', ['i18n' => $i18n, 'hidden' => !$settings->ecommerce_paypal_checkout_enable ? TRUE : FALSE], TRUE);
    }
}
class C_PayPal_Checkout_TLS_Notification
{
    public function is_renderable()
    {
        // Only check if PayPal Checkout payment gateway is enabled
        if (!C_NextGen_Settings::get_instance()->get('ecommerce_paypal_checkout_enable')) {
            return FALSE;
        }
        // Determine if CURL supports TLS 1.1
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            return FALSE;
        }
        return TRUE;
    }
    public function render()
    {
        return __('PayPal Checkout does not support API requests made with TLS 1.1 or older. Please contact your systems administrator to enable TLS 1.2 support on your host.', 'nextgen-gallery-pro');
    }
    public function get_css_class()
    {
        return 'error';
    }
    public function is_dismissable()
    {
        return TRUE;
    }
    public function dismiss($code)
    {
        return array('handled' => TRUE);
    }
}