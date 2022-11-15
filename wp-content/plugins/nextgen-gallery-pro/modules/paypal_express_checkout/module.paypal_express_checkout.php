<?php
/**
{
	Module: photocrati-paypal_express_checkout
}
**/
class M_PayPal_Express_Checkout extends C_Base_Module
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
			'photocrati-paypal_express_checkout',
			'PayPal Express Checkout',
			'Provides integration with PayPal Express Checkout',
			'3.7.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
		);

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Paypal_Express_Checkout_Installer');
	}

	function _register_adapters()
	{
        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_PayPal_Express_Checkout_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_PayPal_Express_Checkout_Ajax');
        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_PayPal_Express_Checkout_Button');
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
    function use_print_lab_test_service($use_test_mode=FALSE, $order=NULL)
    {
        return ($order->payment_gateway == 'paypal_express' && C_NextGen_Settings::get_instance()->ecommerce_paypal_sandbox)
            ? TRUE : $use_test_mode;
    }

	function initialize()
    {
        parent::initialize();

        if (class_exists('C_Admin_Requirements_Manager'))
        {
            C_Admin_Requirements_Manager::get_instance()->add(
                'paypal_express_curl_requirement',
                'phpext',
                array($this, 'check_curl_requirement'),
                array('message' => __('cURL is required for PayPal Express support to function', 'nextgen-gallery-pro'))
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
		if (isset($_REQUEST['ngg_ppxc_rtn']))
        {
            $checkout = C_NextGen_Pro_Checkout::get_instance();
			try {
				$order = $checkout->finish_paypal_express_order();
				if ($order->status == 'fraud') {
					wp_die(__('This order has been marked as fraud and has been reported.', 'nextgen-gallery-pro'));
				}
				if ($order->status != 'paid') {
					wp_die(__("We're sorry, but something went wrong processing your order. Please try again.", 'nextgen-gallery-pro'));
				}
				else {
					$checkout->redirect_to_thank_you_page($order);
					throw new E_Clean_Exit();
				}

			}
			catch (E_NggProPaymentExpressError $ex) {
				wp_die($ex->getMessage());
			}
		}
        // Process cancelled PayPal order
		elseif (isset($_REQUEST['ngg_ppxc_ccl'])) {
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $checkout->redirect_to_cancel_page();
		}
	}

    public function return_own_installer($installers)
    {
        $installers[] = 'C_Paypal_Express_Checkout_Installer';
        return $installers;
    }

	function get_type_list()
	{
        return array(
            'A_PayPal_Express_Checkout_Ajax'   => 'adapter.paypal_express_checkout_ajax.php',
            'A_PayPal_Express_Checkout_Button' => 'adapter.paypal_express_checkout_button.php',
            'A_PayPal_Express_Checkout_Form'   => 'adapter.paypal_express_checkout_form.php'
        );
	}
}

class C_Paypal_Express_Checkout_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_paypal_enable'    => '0',
            'ecommerce_paypal_sandbox'   => 1,
            'ecommerce_paypal_email'     => '',
            'ecommerce_paypal_username'  => '',
            'ecommerce_paypal_password'  => '',
            'ecommerce_paypal_signature' => ''
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_PayPal_Express_Checkout;
