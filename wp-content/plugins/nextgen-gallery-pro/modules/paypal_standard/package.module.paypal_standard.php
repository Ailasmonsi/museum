<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_PayPal_Standard_Ajax extends Mixin
{
    function paypal_standard_order_action()
    {
        $checkout = new C_NextGen_Pro_Checkout();
        try {
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'awaiting_payment', 'paypal_standard', __("The order has been submitted to PayPal, and we're waiting for a response from PayPal to indicate that the payment has been made.", 'nextgen-gallery-pro'));
            /** @var C_NextGen_Pro_Cart $cart */
            $cart = $result['cart'];
            /** @var array $customer */
            $customer = $result['customer'];
            $result['total'] = $cart->get_total();
            if (!empty($result['cart_array']['coupon'])) {
                $coupon = $result['cart_array']['coupon'];
                if ($coupon['discount_type'] == 'flat') {
                    $result['discount_amount_cart'] = $coupon['discount_amount'];
                } else {
                    if ($coupon['discount_type'] == 'percent') {
                        $result['discount_rate_cart'] = $coupon['discount_amount'];
                    }
                }
            }
            if ($cart->has_shippable_items()) {
                $result['shipping_enabled'] = TRUE;
                $result['shipping_street_address'] = $customer['street_address'];
                $result['shipping_address_line'] = $customer['address_line'];
                $result['shipping_city'] = $customer['city'];
                $result['shipping_state'] = $customer['state'];
                $result['shipping_zip'] = $customer['zip'];
                $result['shipping_country'] = $customer['country'];
                $result['shipping_phone'] = $customer['phone'];
            } else {
                $result['shipping_enabled'] = FALSE;
            }
            return $result;
        } catch (Exception $error) {
            return array('error' => $error->getMessage());
        }
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_PayPal_Standard_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        if ($this->is_paypal_standard_enabled()) {
            $buttons[] = 'paypal_standard';
        }
        return $buttons;
    }
    function is_sandbox_mode()
    {
        return C_NextGen_Settings::get_instance()->get('ecommerce_paypal_std_sandbox', TRUE);
    }
    function get_paypal_url()
    {
        return $this->is_sandbox_mode() ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    }
    function is_paypal_standard_enabled()
    {
        return C_NextGen_Settings::get_instance()->get('ecommerce_paypal_std_enable', FALSE) ? TRUE : FALSE;
    }
    function _get_paypal_currency_code()
    {
        $settings = C_NextGen_Settings::get_instance();
        return C_NextGen_Pro_Currencies::$currencies[$settings['ecommerce_currency']]['code'];
    }
    function enqueue_paypal_standard_resources()
    {
        wp_enqueue_script('ngg_paypal_standard_button', $this->object->get_static_url('photocrati-paypal_standard#button.js'), ['jquery']);
    }
    function _render_paypal_standard_button()
    {
        return $this->object->render_partial('photocrati-paypal_standard#button', array('value' => __('Pay with PayPal', 'nextgen-gallery-pro'), 'currency' => $this->_get_paypal_currency_code(), 'email' => C_NextGen_Settings::get_instance()->ecommerce_paypal_std_email, 'continue_shopping_url' => $this->object->get_continue_shopping_url(), 'return_url' => site_url('/?ngg_pstd_rtn=1'), 'notify_url' => site_url('/?ngg_pstd_nfy=1'), 'cancel_url' => site_url('/?ngg_pstd_cnl=1'), 'paypal_url' => $this->get_paypal_url(), 'processing_msg' => __('Processing...', 'nextgen-gallery-pro')), TRUE);
    }
    function is_pdt_enabled()
    {
        return FALSE;
        // TODO: finish PDT support
        return strlen(trim(C_NextGen_Settings::get_instance()->get('ecommerce_paypal_std_pdt_token', ''))) > 1;
    }
    function create_paypal_standard_order()
    {
        $order_mapper = C_Order_Mapper::get_instance();
        if ($order = $order_mapper->find_by_hash($this->param('order'))) {
            $order->paypal_data = $_REQUEST;
            // If PDT is available, use it to verify the order
            if ($this->is_pdt_enabled()) {
                // TODO: Use PDT to verify order
                $this->object->mark_as_paid($order);
            }
            // Redirect the user
            if ($order->status == 'paid') {
                $this->object->redirect_to_thank_you_page($order);
            } else {
                $this->object->redirect_to_order_verification_page($order->hash);
            }
        }
    }
    function update_order_status($order, $total, $customer_name, $email, $shipping_street_address, $shipping_city, $shipping_state, $shipping_zip, $shipping_country, $phone)
    {
        $retval = $order;
        // Has fraud been detected?
        $cart = new C_NextGen_Pro_Cart($order->cart);
        if ($cart->get_total() == $total) {
            if ($customer_name) {
                $order->customer_name = $customer_name;
            }
            if ($email) {
                $order->email = $email;
            }
            if ($shipping_street_address) {
                $order->shipping_street_address = $shipping_street_address;
            }
            if ($shipping_city) {
                $order->shipping_city = $shipping_city;
            }
            if ($shipping_state) {
                $order->shipping_state = $shipping_state;
            }
            if ($shipping_zip) {
                $order->shipping_zip = $shipping_zip;
            }
            if ($shipping_country) {
                $order->shipping_country = $shipping_country;
            }
            $this->object->mark_as_paid($order);
            $retval = $order;
        } else {
            $this->object->mark_as_fraud($order, TRUE, __("The billed amount and the amount received are not the same", 'nextgen-gallery-pro'));
        }
        return $retval;
    }
    function paypal_ipn_listener()
    {
        if (!headers_sent()) {
            header('HTTP/1.1 200 Ok');
        }
        // STEP 1: read POST data
        // Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
        // Instead, read raw POST data from the input stream.
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }
        // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&{$key}={$value}";
        }
        // STEP 2: Validate the IPN
        if (isset($_REQUEST['custom'])) {
            $response = '';
            $response = wp_remote_post($this->get_paypal_url(), array('httpversion' => '1.1', 'headers' => array('Connection: Close'), 'body' => $req, 'sslverify' => FALSE));
            if (is_array($response) && isset($response['body'])) {
                $response = $response['body'];
            } else {
                error_log("There was a problem sending the IPN back to PayPal to verify");
            }
            if ($response) {
                $order_mapper = C_Order_Mapper::get_instance();
                if ($order = $order_mapper->find_by_hash($_REQUEST['custom'], TRUE)) {
                    if ($order->status != 'verified' && $order->status != 'paid') {
                        $order->status = 'awaiting_payment';
                        if (stripos($response, 'VERIFIED') === FALSE) {
                            $order->status = "fraud";
                        } else {
                            if ($myPost['payment_status'] == 'Completed') {
                                $order = $this->update_order_status($order, isset($_REQUEST['mc_gross']) ? $_REQUEST['mc_gross'] : 0.0, isset($_REQUEST['first_name']) && isset($_REQUEST['last_name']) ? $_REQUEST['first_name'] . ' ' . $_REQUEST['last_name'] : '', isset($_REQUEST['payer_email']) ? $_REQUEST['payer_email'] : '', isset($_REQUEST['address_street']) ? $_REQUEST['address_street'] : '', isset($_REQUEST['address_city']) ? $_REQUEST['address_city'] : '', isset($_REQUEST['address_state']) ? $_REQUEST['address_state'] : '', isset($_REQUEST['address_zip']) ? $_REQUEST['address_zip'] : '', isset($_REQUEST['address_country']) ? $_REQUEST['address_country'] : '', isset($_REQUEST['contact_phone']) ? $_REQUEST['contact_phone'] : '');
                            }
                        }
                    }
                }
            }
        }
        throw new E_Clean_Exit();
    }
}
/**
 * @property C_Form $object
 */
class A_PayPal_Standard_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'paypal_std_enable';
        $fields[] = 'paypal_std_https';
        // $fields[] = 'paypal_std_currencies_supported';
        $fields[] = 'paypal_std_sandbox';
        $fields[] = 'paypal_std_email';
        return $fields;
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        wp_enqueue_script('ngg_pro_paypal_std_form', $this->object->get_static_url('photocrati-paypal_standard#form.js'));
    }
    function _render_paypal_std_enable_field()
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_radio_field($model, 'paypal_std_enable', __('Enable PayPal Standard', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_std_enable, __('Not all currencies are supported by all payment gateways. Please be sure to confirm your desired currency is supported by PayPal', 'nextgen-gallery-pro'));
    }
    /**
     * @return string
     */
    function _render_paypal_std_https_field()
    {
        if (is_ssl()) {
            return '';
        }
        return $this->object->render_partial('photocrati-paypal_standard#admin_form_https_warning_field', array('warning' => __('<strong>Warning:</strong> HTTPS was not detected. Because PayPal will not send IPN events over unencrypted connections NextGen cannot verify orders placed via PayPal Standard without the use of HTTPS.', 'nextgen-gallery-pro'), 'hidden' => C_NextGen_Settings::get_instance()->ecommerce_paypal_std_enable ? FALSE : TRUE), TRUE);
    }
    function _render_paypal_std_sandbox_field()
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_radio_field($model, 'paypal_std_sandbox', __('Use Sandbox?', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_std_sandbox, '', !C_NextGen_Settings::get_instance()->ecommerce_paypal_std_enable ? TRUE : FALSE);
    }
    function _render_paypal_std_email_field()
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'paypal_std_email', __('<strong>Email</strong>', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_paypal_std_email, __('Only PayPal Premier and Business accounts are supported', 'nextgen-gallery-pro'), !C_NextGen_Settings::get_instance()->ecommerce_paypal_std_enable ? TRUE : FALSE);
    }
    function _render_paypal_std_currencies_supported_field()
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        $supported = array('CAD', 'EUR', 'GBP', 'USD', 'JPY', 'AUD', 'NZD', 'CHF', 'HKD', 'SGD', 'SEK', 'DKK', 'PLN', 'NOK', 'HUF', 'CZK', 'ILS', 'MXN', 'BRL', 'MYR', 'PHP', 'TWD', 'THB', 'TRY', 'RUB');
        if (!in_array($currency['code'], $supported)) {
            $message = __('PayPal does not support your currently chosen currency', 'nextgen-gallery-pro');
            return "<tr id='tr_ecommerce_paypal_std_currencies_supported'><td colspan='2'>{$message}</td></tr>";
        }
    }
}