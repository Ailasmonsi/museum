<?php
/**
{
    Module: photocrati-test_gateway
}
 **/
class M_Photocrati_Test_Gateway extends C_Base_Module
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
            'photocrati-test_gateway',
            'Test gateway',
            'Provides a test payment gateway',
            '3.7.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Test_Gateway_Installer');
    }

    function _register_hooks()
    {
        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_filter('ngg_pro_lab_test_mode', [$this, 'use_test_lab_order_service'], 10, 2);
    }

    function _register_adapters()
    {
        $registry = $this->get_registry();
        if (is_admin())
            $registry->add_adapter('I_Form', 'A_Test_Gateway_Checkout_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $registry->add_adapter('I_NextGen_Pro_Checkout', 'A_Test_Gateway_Checkout_Button');
        $registry->add_adapter('I_Ajax_Controller',      'A_Test_Gateway_Checkout_Ajax');
    }

    /**
     * @param array $installers
     * @return array
     */
    public function return_own_installer($installers)
    {
        $installers[] = 'C_Test_Gateway_Installer';
        return $installers;
    }

    /**
     * @param bool $retval
     * @param C_NextGen_Pro_Order $order
     * @return bool
     */
    function use_test_lab_order_service($retval, $order)
    {
        return $order->payment_gateway === 'test_gateway' ? TRUE : $retval;
    }

    /**
     * @return array
     */
    function get_type_list()
    {
        return array(
            'A_Test_Gateway_Checkout_Ajax'   => 'adapter.test_gateway_checkout_ajax.php',
            'A_Test_Gateway_Checkout_Button' => 'adapter.test_gateway_checkout_button.php',
            'A_Test_Gateway_Checkout_Form'   => 'adapter.test_gateway_checkout_form.php'
        );
    }
}

class C_Test_Gateway_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_test_gateway_enable' => '0'
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_Photocrati_Test_Gateway;
