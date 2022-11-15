<?php
/** {
    Module: photocrati-free_gateway
} */
class M_Free_Gateway extends C_Base_Module
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
            'photocrati-free_gateway',
            'Free gateway',
            'Handles orders with a free total amount',
            '3.3.0',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com'
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_Free_Gateway_Installer');
    }

    function _register_adapters()
    {
        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_Free_Checkout_Ajax');
        $this->get_registry()->add_adapter('I_NextGen_Pro_Checkout', 'A_Free_Checkout_Button');
    }

    function _register_hooks()
    {
    }

    function get_type_list()
    {
        return array(
            'A_Free_Checkout_Button' => 'adapter.free_checkout_button.php',
            'A_Free_Checkout_Ajax'   => 'adapter.free_checkout_ajax.php'
        );
    }
}

class C_Free_Gateway_Installer
{
    function install()
    {
    }
}

new M_Free_Gateway;
