<?php
/**
{
    Module: photocrati-paypal_standard
}
**/

class M_PayPal_Standard extends C_Base_Module
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
            'photocrati-paypal_standard',
            'PayPal Standard',
            'Provides integration with PayPal Standard',
            '3.7.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Paypal_Standard_Installer');
    }

    function _register_adapters()
    {
        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_PayPal_Standard_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_PayPal_Standard_Ajax');
        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_PayPal_Standard_Button');
    }

    function _register_hooks()
    {
        add_action('init', array(&$this, 'process_paypal_responses'));
        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_filter('ngg_pro_lab_test_mode', [$this, 'use_print_lab_test_service'], 10, 2);
    }

    /**
     * @param bool $use_test_mode
     * @param C_NextGen_Pro_Order $order
     * @return bool
     */
    function use_print_lab_test_service($use_test_mode = FALSE, $order = NULL)
    {
        return ($order->payment_gateway === 'paypal_standard' && C_NextGen_Settings::get_instance()->ecommerce_paypal_std_sandbox)
            ? TRUE : $use_test_mode;
    }

    function initialize()
    {
        parent::initialize();

        if (class_exists('C_Admin_Requirements_Manager'))
        {
            C_Admin_Requirements_Manager::get_instance()->add(
                'paypal_standard_curl_requirement',
                'phpext',
                array($this, 'check_curl_requirement'),
                array('message' => __('cURL is required for PayPal support to function', 'nextgen-gallery-pro'))
            );
        }
    }

    public function check_curl_requirement()
    {
        return function_exists('curl_init');
    }

    function process_paypal_responses()
    {
        // Process return from PayPal
        if (isset($_REQUEST['ngg_pstd_rtn']))
        {
            C_NextGen_Pro_Checkout::get_instance()->create_paypal_standard_order();
        }
        // Process cancelled PayPal order
        elseif (isset($_REQUEST['ngg_pstd_cnl'])) {
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $checkout->redirect_to_cancel_page();

        }
        // Process IPN notications
        elseif (isset($_REQUEST['ngg_pstd_nfy'])) {
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $checkout->paypal_ipn_listener();
        }

    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_PayPal_Standard_Installer';
        return $installers;
    }

    function get_type_list()
    {
        return array(
            'A_PayPal_Standard_Button' => 'adapter.paypal_standard_button.php',
            'A_PayPal_Standard_Form'   => 'adapter.paypal_standard_form.php',
            'A_PayPal_Standard_Ajax'   => 'adapter.paypal_standard_ajax.php'
        );
    }
}

class C_PayPal_Standard_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_paypal_std_enable'  => 0,
            'ecommerce_paypal_std_sandbox' => 1,
            'ecommerce_paypal_std_email'   => ''
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_PayPal_Standard;
