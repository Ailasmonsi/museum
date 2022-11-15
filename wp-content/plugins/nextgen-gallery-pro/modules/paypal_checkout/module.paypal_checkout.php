<?php
/**
{
    Module: photocrati-paypal_checkout
}
**/
class M_Photocrati_PayPal_Checkout extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri = '',
                    $context = FALSE)
    {
        parent::define(
            'photocrati-paypal_checkout',
            'PayPal Checkout',
            'Provides integration with PayPal Checkout payment gateway',
            '3.7.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_PayPal_Checkout_Installer');
    }

    function _register_adapters()
    {
        if (!$this->php_version_check())
            return;

        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_PayPal_Checkout_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_PayPal_Checkout_Button');
        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_PayPal_Checkout_Ajax');
    }

    function _register_hooks()
    {
        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_filter('ngg_pro_lab_test_mode', [$this, 'use_print_lab_test_service'], 10, 2);

        C_Admin_Notification_Manager::get_instance()->add('paypal_checkout_tls12_check', new C_PayPal_Checkout_TLS_Notification());
        C_Admin_Notification_Manager::get_instance()->add('paypal_checkout_tls12_check', 'C_PayPal_Checkout_Webhook_Notification');

        $requirements = C_Admin_Requirements_Manager::get_instance();
        $requirements->add(
            'paypal_checkout_curl_requirement',
            'phpext',
            array($this, 'check_curl_requirement'),
            array('message' => __('cURL is required for PayPal Checkout support to function', 'nextgen-gallery-pro'))
        );
        $requirements->add(
            'paypal_checkout_json_requirement',
            'phpext',
            array($this, 'check_json_requirement'),
            array('message' => __('JSON is required for PayPal Checkout support to function', 'nextgen-gallery-pro'))
        );
    }

    function initialize()
    {
        parent::initialize();

        // TODO: add checks for both SDK's requirements
        C_Admin_Requirements_Manager::get_instance()->add(
            'paypal_checkout_php_version_requirement',
            'phpver',
            array($this, 'php_version_check'),
            array('message' => __('PHP 5.6.0 is required for PayPal Checkout to function. Your settings have been preserved but PayPal Checkout will remain disabled until your PHP version is upgraded to at least 5.6.0.', 'nextgen-gallery-pro'))
        );
    }

    /**
     * @param bool $use_test_mode
     * @param C_NextGen_Pro_Order $order
     * @return bool
     */
    function use_print_lab_test_service($use_test_mode = FALSE, $order = NULL)
    {
        return ($order->payment_gateway === 'paypal_checkout' && C_NextGen_Settings::get_instance()->get('ecommerce_paypal_checkout_sandbox'))
            ? TRUE : $use_test_mode;
    }

    public function php_version_check()
    {
        if (version_compare(PHP_VERSION, '5.6.0', '<'))
            return FALSE;
        return TRUE;
    }

    public function check_curl_requirement()
    {
        return function_exists('curl_init');
    }

    public function check_json_requirement()
    {
        return function_exists('json_decode');
    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_PayPal_Checkout_Installer';
        return $installers;
    }

    function get_type_list()
    {
        return array(
            'A_PayPal_Checkout_Ajax'   => 'adapter.paypal_checkout_ajax.php',
            'A_PayPal_Checkout_Button' => 'adapter.paypal_checkout_button.php',
            'A_PayPal_Checkout_Form'   => 'adapter.paypal_checkout_form.php',
            'C_PayPal_Checkout_TLS_Notification'     => 'class.paypal_checkout_tls_notification.php',
            'C_PayPal_Checkout_Webhook_Notification' => 'class.paypal_checkout_webhook_notification.php'
        );
    }
}

class C_PayPal_Checkout_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_paypal_checkout_enable'            => '0',
            'ecommerce_paypal_checkout_sandbox'           => '1',
            'ecommerce_paypal_checkout_client_id'         => '',
            'ecommerce_paypal_checkout_client_secret'     => '',
            'ecommerce_paypal_checkout_notification_id'   => '',
            'ecommerce_paypal_checkout_notification_hash' => '',
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_Photocrati_PayPal_Checkout;
