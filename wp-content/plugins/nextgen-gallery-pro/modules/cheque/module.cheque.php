<?php
/**
{
    Module: photocrati-cheque
}
 **/
class M_Photocrati_cheque extends C_Base_Module
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
            'photocrati-cheque',
            'Pay by check',
            'Allows users to pay by mail with a check',
            '3.3.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Cheque_Installer');
    }

    function _register_adapters()
    {
        if (is_admin())
            $this->get_registry()->add_adapter('I_Form', 'A_Cheque_Checkout_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);

        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_Cheque_Checkout_Ajax');
        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_Cheque_Checkout_Button');
    }

    function _register_hooks()
    {
        add_filter('ngg_order_details', array($this, 'add_cheque_reminder_to_order_details'), 10, 2);
        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
    }

    function add_cheque_reminder_to_order_details($text, $order)
    {
        if (($order->status == 'unverified' || $order->status == 'awaiting_payment')
        &&  $order->payment_gateway == 'cheque'
        &&  !empty(C_NextGen_Settings::get_instance()->ecommerce_cheque_instructions))
        {
            $text .= C_NextGen_Settings::get_instance()->ecommerce_cheque_instructions;
        }
        return $text;
    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_Cheque_Installer';
        return $installers;
    }

    function get_type_list()
    {
        return array(
            'A_Cheque_Checkout_Ajax'   => 'adapter.cheque_checkout_ajax.php',
            'A_Cheque_Checkout_Button' => 'adapter.cheque_checkout_button.php',
            'A_Cheque_Checkout_Form'   => 'adapter.cheque_checkout_form.php'
        );
    }
}

class C_Cheque_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_cheque_enable' => '0',
            'ecommerce_cheque_instructions' => __("<p>Thanks very much for your purchase! We'll be in touch shortly via email to confirm your order and to provide details on payment.</p>", 'nextgen-gallery-pro')
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_Photocrati_cheque;
