<?php
/**
{
	Module: photocrati-stripe
}
**/
class M_Photocrati_Stripe extends C_Base_Module
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
			'photocrati-stripe',
			'Stripe',
			'Provides integration with Stripe payment gateway',
			'3.7.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
		);

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Stripe_Installer');
	}

    /**
     * @return bool False if PHP_VERSION is not 5.4.0 or higher
     */
	public function php_version_check()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<'))
            return FALSE;
        return TRUE;
    }

	function _register_adapters()
	{
	    if (!$this->php_version_check())
	        return;

        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_Stripe_Checkout_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_Stripe_Checkout_Button');

        // Used by the admin form to register webhook endpoints
        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_Stripe_Checkout_Ajax');
	}

    function _register_hooks()
    {
        // Possibly warn users that TLS 1.2 is necessary for Stripe API calls
        $notices = C_Admin_Notification_Manager::get_instance();
        $notices->add(
            'stripe_tls12_check',
            new C_Stripe_TLS12_Check_Notification()
        );

        if ($this->php_version_check())
            add_action('admin_enqueue_scripts', array($this, 'add_check_for_missed_webhooks_button'));

        add_action('init', array($this, 'route'));
        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_filter('ngg_pro_lab_test_mode', [$this, 'use_print_lab_test_service'], 10, 2);
    }

    function is_test_key($field)
    {
        $val = C_NextGen_Settings::get_instance()->$field;
        return  is_string($val) && $val && strpos($val, 'test') !== FALSE;
    }

    /**
     * @param bool $use_test_mode
     * @param C_NextGen_Pro_Order $order
     * @return bool
     */
    function use_print_lab_test_service($use_test_mode = FALSE, $order = NULL)
    {
        return $order->payment_gateway === 'stripe' && (
            $this->is_test_key('ecommerce_stripe_key_private') || $this->is_test_key('ecommerce_stripe_key_public')
            ) ? TRUE : $use_test_mode;
    }

    public function add_check_for_missed_webhooks_button()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/edit.php?post_type=ngg_order') === FALSE)
            return;

        if (!current_user_can('administrator'))
            return;

        $router = C_Router::get_instance();
        wp_enqueue_script(
            'ngg_pro_stripe_manage_orders_js',
            $router->get_static_url('photocrati-stripe#manage_orders.js'),
            array(),
            FALSE,
            TRUE
        );
        wp_enqueue_style(
            'ngg_pro_stripe_manage_orders_css',
            $router->get_static_url('photocrati-stripe#manage_orders.css')
        );

        wp_localize_script(
            'ngg_pro_stripe_manage_orders_js',
            'ngg_pro_stripe_manage_orders_i18n',
            array(
                'name'       => __('Check stripe', 'nextgen-gallery-pro'),
                'processing' => __('Processing...', 'nextgen-gallery-pro'),
                'zero'       => __('No unprocessed payments were found.', 'nextgen-gallery-pro'),
                'count'      => __('%d order(s) have been processed and marked as paid. This page will now reload to display the updated order information.', 'nextgen-gallery-pro'),
                'title'      => __('In case Stripe failed to connect to your site to mark an order as paid this button will manually check with Stripe for any missed payments made within the last 48 hours.', 'nextgen-gallery-pro')
            )
        );
    }

    function initialize()
    {
        parent::initialize();

        if (class_exists('C_Admin_Requirements_Manager'))
        {
            C_Admin_Requirements_Manager::get_instance()->add(
                'stripe_curl_requirement',
                'phpext',
                array($this, 'check_curl_requirement'),
                array('message' => __('cURL is required for Stripe support to function', 'nextgen-gallery-pro'))
            );
            C_Admin_Requirements_Manager::get_instance()->add(
                'stripe_json_requirement',
                'phpext',
                array($this, 'check_json_requirement'),
                array('message' => __('JSON is required for Stripe support to function', 'nextgen-gallery-pro'))
            );
            C_Admin_Requirements_Manager::get_instance()->add(
                'stripe_multibyte_requirement',
                'phpext',
                array($this, 'check_multibyte_requirement'),
                array('message' => __('Multibyte is required for Stripe support to function', 'nextgen-gallery-pro'))
            );
            C_Admin_Requirements_Manager::get_instance()->add(
                'stripe_php_version_requirement',
                'phpver',
                array($this, 'php_version_check'),
                array('message' => __('PHP 5.4.0 is required for Stripe to function. Your settings have been preserved but Stripe will remain disabled until your PHP version is upgraded to at least 5.4.0.', 'nextgen-gallery-pro'))
            );
        }
    }

    public function check_curl_requirement()
    {
        return function_exists('curl_init');
    }

    public function check_json_requirement()
    {
        return function_exists('json_decode');
    }

    public function check_multibyte_requirement()
    {
        return function_exists('mb_detect_encoding');
    }

    function route()
    {
        if (isset($_REQUEST['ngg_stripe_rtn']) && isset($_REQUEST['order']))
        {
            $checkout = C_NextGen_Pro_Checkout::get_instance();
            $checkout->redirect_to_thank_you_page($_REQUEST['order']);
        }
        else if(isset($_REQUEST['ngg_stripe_cancel']) && !empty($_REQUEST['order']) && !empty($_REQUEST['key']))
        {
            $order = C_Order_Mapper::get_instance()->find_by_hash($_REQUEST['order'], TRUE);
            if (!empty($order)
            &&  !empty($order->stripe_cancel_nonce)
            &&  $order->stripe_cancel_nonce == $_REQUEST['key']
            &&  $order->status == "unpaid"
            &&  $order->payment_gateway == "stripe_checkout")
            {
                $order->destroy();
                wp_redirect(home_url());
                exit();
            }
        }
    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_Stripe_Installer';
        return $installers;
    }

	function get_type_list()
	{
        return array(
            'A_Stripe_Checkout_Ajax'			=> 'adapter.stripe_checkout_ajax.php',
            'A_Stripe_Checkout_Button'			=> 'adapter.stripe_checkout_button.php',
            'A_Stripe_Checkout_Form'            => 'adapter.stripe_checkout_form.php',
            'C_Stripe_TLS12_Check_Notification' => 'class.stripe_tls12_check_notification.php'
        );
	}
}

class C_Stripe_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_stripe_enable'         => '0',
            'ecommerce_stripe_key_public'     => '',
            'ecommerce_stripe_key_private'    => '',
            'ecommerce_stripe_webhook_secret' => '',
            'ecommerce_stripe_webhook_hash'   => ''
        ));
        $this->set_groups(array('ecommerce'));
    }
}

new M_Photocrati_Stripe;
