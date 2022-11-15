<?php
/* { Module: photocrati-coupons } */

define('NGG_PRO_COUPONS', 'photocrati-coupons');

class M_NextGen_Pro_Coupons extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri =  '',
                    $context = FALSE)
    {
        parent::define(
            NGG_PRO_COUPONS,
            'NextGEN Pro Coupons',
            'Provides coupons',
            '3.1.3',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com',
            $context
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Pro_Coupon_Installer');
    }

    function _register_adapters()
    {
        // Register the admin / management form
        $this->get_registry()->add_adapter('I_Component_Factory', 'A_Coupon_Factory');
        if (M_Attach_To_Post::is_atp_url() || is_admin())
        {
            $this->get_registry()->add_adapter('I_Form', 'A_Coupon_Admin_Form', 'ngg_coupon_admin');
            $this->get_registry()->add_adapter('I_Form', 'A_Coupon_Admin_Settings_Form', NGG_PRO_ECOMMERCE_OPTIONS_FORM);
        }
    }

    function _register_hooks()
    {
        // This class handles creating the page & everything to do with its (saving / deleting) actions
        C_Coupon_Admin_Page::get_instance()->register_hooks();

        add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_action('ngg_order_saved', array($this, 'add_order_metadata'), 10, 2);

        if (M_Attach_To_Post::is_atp_url() || is_admin())
            add_action('admin_init', array($this, 'register_forms'));

    }

    // Used to quickly determine how many orders have been placed with any given coupon
    function add_order_metadata($post_id, $order)
    {
        if (!empty($order->cart['coupon']))
            update_post_meta($post_id, 'ngg_coupon_id', $order->cart['coupon']['id']);
    }

    function register_forms()
    {
        $forms = C_Form_Manager::get_instance();
        $forms->add_form('ngg_pro_coupons', 'ngg_coupon_admin');
    }

    static function get_cart_currency($order)
    {
        if (is_array($order->cart)) {   
            return $order->cart['currency'];
        }
        return $order->cart->get_currency();
    }

    static function get_order_discount_amount($order, $use_fontawesome = FALSE)
    {
        if (empty($order->cart['coupon']) || !is_array($order->cart['coupon']))
            return '';

        $coupon = $order->cart['coupon'];
        if ($coupon['discount_type'] == 'flat')
        {
            return M_NextGen_Pro_Ecommerce::get_formatted_price(
                $coupon['discount_amount'],
                self::get_cart_currency($order),
                $use_fontawesome
            );
        }
        else if ($coupon['discount_type'] == 'percent') {
            return $coupon['discount_amount'] . '%';
        }
    }

    static function are_coupons_enabled()
    {
        return !C_NextGen_Settings::get_instance()->get('ecommerce_coupons_disabled', FALSE);
    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_NextGen_Pro_Coupon_Installer';
        return $installers;
    }

    function get_type_list()
    {
        return array(
            'A_Coupon_Admin_Form'          => 'adapter.coupon_admin_form.php',
            'A_Coupon_Admin_Settings_Form' => 'adapter.coupon_admin_settings_form.php',
            'A_Coupon_Factory'             => 'adapter.coupon_factory.php',
            'C_Coupon'                     => 'class.coupon.php',
            'C_Coupon_Admin_Page'          => 'class.coupon_admin_page.php',
            'C_Coupon_Mapper'              => 'class.coupon_mapper.php'
        );
    }

}

class C_NextGen_Pro_Coupon_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_coupons_disabled' => FALSE
        ));

        $this->set_groups(array('ecommerce'));
    }
}

new M_NextGen_Pro_Coupons;
