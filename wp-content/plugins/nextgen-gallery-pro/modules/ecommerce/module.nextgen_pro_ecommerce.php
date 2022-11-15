<?php

define('NGG_ECOMMERCE_TRIGGER', 'photocrati-ecommerce');

// Page IDs
define('NGG_PRO_ECOMMERCE_OPTIONS_PAGE',      'ngg_ecommerce_options');
define('NGG_PRO_ECOMMERCE_INSTRUCTIONS_PAGE', 'ngg-ecommerce-instructions-page');
define('NGG_PRO_PRICELIST_CATEGORY_PAGE', 'ngg-pricelist-category-page');

// Form IDs
define('NGG_PRO_PAYMENT_GATEWAY_FORM',                      'ngg-payment-gateways');
define('NGG_PRO_ECOMMERCE_OPTIONS_FORM',                    'ngg-ecommerce-options');
define('NGG_PRO_ECOMMERCE_INSTRUCTIONS_FORM',               'ngg-ecommerce-instructions');
define('NGG_PRO_ECOMMERCE_PRINTLAB_FORM',                   'ngg-ecommerce-printlab');
define('NGG_PRO_MAIL_FORM',                                 'ngg-mail');

// Pricelist sources
define('NGG_PRO_WHCC_PRICELIST_SOURCE',                     'ngg_whcc_pricelist');
define('NGG_PRO_MANUAL_PRICELIST_SOURCE',                   'ngg_manual_pricelist');
define('NGG_PRO_DIGITAL_DOWNLOADS_SOURCE',                  'ngg_digital_downloads');

// Pricelist categories
define('NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS',      'ngg_category_digital_downloads');
define('NGG_PRO_ECOMMERCE_CATEGORY_PRINTS',                 'ngg_category_prints');
define('NGG_PRO_ECOMMERCE_CATEGORY_CANVAS',                 'ngg_category_canvas');
define('NGG_PRO_ECOMMERCE_CATEGORY_MOUNTED_PRINTS',         'ngg_category_mounted_prints');
define('NGG_PRO_ECOMMERCE_CATEGORY_METAL_PRINTS',           'ngg_category_metal_prints');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_FREE',            'ngg_free_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_ECONOMY',         'ngg_economy_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD',        'ngg_standard_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_EXPEDITED',       'ngg_expedited_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_PRIORITY',        'ngg_priority_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_CANADA',          'ngg_canada_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_INTERNATIONAL',   'ngg_international_shipping');
define('NGG_PRO_ECOMMERCE_SHIPPING_METHOD_MANUAL',          'ngg_manual_shipping');

define('NGG_PRO_ECOMMERCE_MODULE_VERSION',                  '3.8.0');

if (!defined('NGG_PRO_ECOMMERCE_DEFAULT_MARKUP')) {
    define('NGG_PRO_ECOMMERCE_DEFAULT_MARKUP',                  300);
}

if (!defined('NGG_PRO_WHCC_CATALOG_TTL'))
    define('NGG_PRO_WHCC_CATALOG_TTL', 86400); // 24 hours

class M_NextGen_Pro_Ecommerce extends C_Base_Module
{
    static $_license_check_cache = NULL;

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
            'photocrati-nextgen_pro_ecommerce',
            'Ecommerce',
            'Provides ecommerce capabilities for the NextGEN Pro Lightbox',
	        NGG_PRO_ECOMMERCE_MODULE_VERSION,
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/',
            'Imagely',
            'https://www.imagely.com',
            $context
        );

        C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Pro_Ecommerce_Installer');
    }

    function initialize()
    {
        parent::initialize();

        // Add lightbox components
        M_NextGen_Pro_Lightbox::add_component('photocrati-add_to_cart', 'C_NextGen_Pro_Add_To_Cart');

        // Add trigger
        $triggers = C_Displayed_Gallery_Trigger_Manager::get_instance();
        $triggers->add(NGG_ECOMMERCE_TRIGGER, 'C_NextGen_Pro_Ecommerce_Trigger');

        C_NextGen_Shortcode_Manager::add('ngg_pro_cart_count', array($this, 'render_cart_count'));
        C_NextGen_Shortcode_Manager::add('ngg_pro_checkout', array($this, 'render_checkout_form'));
        C_NextGen_Shortcode_Manager::add('ngg_pro_digital_downloads', array($this, 'render_digital_downloads'));
        C_NextGen_Shortcode_Manager::add('ngg_pro_order_details', array($this, 'render_order_details'));
        C_NextGen_Shortcode_Manager::add('ngg_pro_verify_order', array($this, 'render_order_verification'));

        // The following must be delayed until the init action so that their strings can be translated; it is advised
        // to not invoke load_plugin_textdomain() until the init action so that other plugins may load and register their filters
        //
        // IMPORTANT: this must fire at priority 3 or higher (so that translations will be loaded) but under 10 otherwise
        // NGG's ajax module will serve requests before this listener executes
        add_action('init', function() {
            // Add pricelist sources
            $sources = C_Pricelist_Source_Manager::get_instance();
            $sources->register(NGG_PRO_WHCC_PRICELIST_SOURCE, C_Pricelist_Source_WHCC::get_info());
            $sources->register(NGG_PRO_MANUAL_PRICELIST_SOURCE, C_Pricelist_Source_Manual::get_info());
            $sources->register(NGG_PRO_DIGITAL_DOWNLOADS_SOURCE, C_Pricelist_Source_Download::get_info());

            // Add pricelist categories
            $categories = C_Pricelist_Category_Manager::get_instance();
            $categories->register(NGG_PRO_ECOMMERCE_CATEGORY_PRINTS, array(
                'title'  => __('Prints', 'nextgen-gallery-pro'),
                'source' => array(NGG_PRO_MANUAL_PRICELIST_SOURCE, NGG_PRO_WHCC_PRICELIST_SOURCE)
            ));
            $categories->register(NGG_PRO_ECOMMERCE_CATEGORY_CANVAS, array(
                'title'  => __('Canvas', 'nextgen-gallery-pro'),
                'source' => array(NGG_PRO_MANUAL_PRICELIST_SOURCE, NGG_PRO_WHCC_PRICELIST_SOURCE)
            ));
            $categories->register(NGG_PRO_ECOMMERCE_CATEGORY_MOUNTED_PRINTS, array(
                'title'  => __('Mounted Prints', 'nextgen-gallery-pro'),
                'source' => array(NGG_PRO_MANUAL_PRICELIST_SOURCE, NGG_PRO_WHCC_PRICELIST_SOURCE)
            ));
            // $categories->register(NGG_PRO_ECOMMERCE_CATEGORY_METAL_PRINTS, array(
            //  'title'  => __('Metal Prints', 'nextgen-gallery-pro'),
            //  'source' => array(NGG_PRO_MANUAL_PRICELIST_SOURCE, NGG_PRO_WHCC_PRICELIST_SOURCE)
            // ));
            $categories->register(NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS, array(
                'title'  => __('Digital Downloads', 'nextgen-gallery-pro'),
                'source' => array(NGG_PRO_DIGITAL_DOWNLOADS_SOURCE)
            ));

            // Add Generic shipping methods. NGG Pro is composed of generic shipping methods, which are associated to
            // to real shipping methods for a particular source. To describe why this is needed, it's best to look at an
            // example situation
            //
            // A cart has three items in it:
            // a) 4x6 manual print
            // b) 4x8 whcc print
            // c) 5x7 bayphoto print
            //
            // Item A can be shipped using only one method - the "Standard" method.
            // Item B can be shipped using "Economy USPS", "3 Days or Less", and "Next Day Saver"
            // Item C can be shipped using "UPS Standard" and "UPS Priority"
            //
            // On the checkout page, "How would you like to ship your order?" We could break up the items into groups that
            // have compatible shipping methods, and then prompt the customer to which the desired shipping method for each
            // group of items. Instead, we ask for one shipping method - a generic one. And under the hood, we select the most
            // appropriate shipping method for each item.
            $shipping_methods = C_Pricelist_Shipping_Method_Manager::get_instance();
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_MANUAL,       array('title' => __('Standard Shipping', 'nextgen-gallery-pro'), 'universal' => TRUE));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_FREE,         array('title' => __('Free Shipping',     'nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD,     array('title' => __('Standard Shipping', 'nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_ECONOMY,      array('title' => __('Economy Shipping',  'nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_EXPEDITED,    array('title' => __('Expedited Shipping','nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_PRIORITY,     array('title' => __('Priority Shipping', 'nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_CANADA,       array('title' => __('Standard Shipping', 'nextgen-gallery-pro')));
            $shipping_methods->register(NGG_PRO_ECOMMERCE_SHIPPING_METHOD_INTERNATIONAL,array('title' => __('Standard Shipping', 'nextgen-gallery-pro')));

        }, 3);

	    $notices_manager = C_Admin_Notification_Manager::get_instance();
	    $notices_manager->add('currency_conversion', 'C_Currency_Conversion_Notice');
	    $notices_manager->add('invalid_license', 'C_Invalid_License_Notice');
        $notices_manager->add('non_https', 'C_Non_HTTPS_Notice');
    }

    static function update_print_lab_items()
    {
        
    }

    function _register_adapters()
    {
	    $this->get_registry()->add_adapter('I_Component_Factory', 'A_Pricelist_Factory');

        if (M_Attach_To_Post::is_atp_url() || is_admin())
        {
	        $this->get_registry()->add_adapter('I_NextGen_Admin_Page', 'A_Ecommerce_Options_Controller', NGG_PRO_ECOMMERCE_OPTIONS_PAGE);
	        $this->get_registry()->add_adapter('I_NextGen_Admin_Page', 'A_Ecommerce_Instructions_Controller', NGG_PRO_ECOMMERCE_INSTRUCTIONS_PAGE);

	        $this->get_registry()->add_adapter('I_Form', 'A_Manual_Pricelist_Settings_Form', NGG_PRO_MANUAL_PRICELIST_SOURCE);

	        $this->get_registry()->add_adapter('I_Form', 'A_Digital_Downloads_Form', NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS);
	        $this->get_registry()->add_adapter('I_Form', 'A_Print_Category_Form', NGG_PRO_ECOMMERCE_CATEGORY_PRINTS);
	        $this->get_registry()->add_adapter('I_Form', 'A_Print_Category_Form', NGG_PRO_ECOMMERCE_CATEGORY_CANVAS);
	        $this->get_registry()->add_adapter('I_Form', 'A_Print_Category_Form', NGG_PRO_ECOMMERCE_CATEGORY_MOUNTED_PRINTS);
	        //$this->get_registry()->add_adapter('I_Form', 'A_Print_Category_Form', NGG_PRO_ECOMMERCE_CATEGORY_METAL_PRINTS);


            $this->get_registry()->add_adapter('I_Form', 'A_Ecommerce_Instructions_Form', NGG_PRO_ECOMMERCE_INSTRUCTIONS_FORM);
            $this->get_registry()->add_adapter('I_Form', 'A_Ecommerce_Options_Form', NGG_PRO_ECOMMERCE_OPTIONS_FORM);
            $this->get_registry()->add_adapter('I_Form', 'A_Payment_Gateway_Form', NGG_PRO_PAYMENT_GATEWAY_FORM);
            $this->get_registry()->add_adapter('I_Form', 'A_Ecommerce_Printlab_Form', NGG_PRO_ECOMMERCE_PRINTLAB_FORM);
            $this->get_registry()->add_adapter('I_Form', 'A_NextGen_Pro_Lightbox_Mail_Form', NGG_PRO_MAIL_FORM);
            $this->get_registry()->add_adapter('I_Form', 'A_Ecommerce_Pro_Lightbox_Form', NGG_PRO_LIGHTBOX);

	        $this->get_registry()->add_adapter('I_Page_Manager',   'A_Ecommerce_Pages');
        }

	    // Adds the 'pricelist id' attribute column to galleries, images
	    $this->get_registry()->add_adapter('I_Gallery_Mapper', 'A_Pricelist_Datamapper_Column');
	    $this->get_registry()->add_adapter('I_Image_Mapper',   'A_Pricelist_Datamapper_Column');

        $this->get_registry()->add_adapter('I_Component_Factory', 'A_Ecommerce_Factory');
	    $this->get_registry()->add_adapter('I_Display_Type_Mapper', 'A_Ecommerce_Display_Type_Mapper');

        $this->get_registry()->add_adapter('I_Ajax_Controller', 'A_Ecommerce_Ajax');
        $this->get_registry()->add_adapter('I_Display_Type_Controller', 'A_NplModal_Ecommerce_Overrides');
    }

    function _register_utilities()
    {
        $this->get_registry()->add_utility('I_Nextgen_Mail_Manager', 'C_Nextgen_Mail_Manager');
    }

    function _register_hooks()
    {
        add_action('init', array($this, 'register_post_types'), 1);
        // add_action('current_screen', array($this, 'init_wizards'), 20);
	    add_filter('posts_results', array($this, 'serve_ecommerce_pages'), 10, 2);
	    add_action('wp_enqueue_scripts', array($this, 'enqueue_resources'), 9);
		add_filter('wp_nav_menu_objects', array($this, 'nav_menu_objects'), 10, 2);
		add_action('before_delete_post', array($this, 'before_delete_post'));

	    add_filter('ngg_pro_settings_reset_installers', array($this, 'return_own_installer'));
        add_filter('ngg_pro_lightbox_sidebar_data_request', array($this, 'add_pro_lightbox_cart_sidebar_data'));

        if (M_Attach_To_Post::is_atp_url() || is_admin())
        {
            add_action('admin_init', array($this, 'register_forms'));

             // TODO - why are we using the init action if this is in the is_admin() conditional block?
	        add_action('init', array($this, 'register_display_type_settings'), (PHP_INT_MAX-1));
	        add_filter('ngg_manage_gallery_fields', array($this, 'add_gallery_pricelist_field'), 20, 2);
	        add_filter('ngg_manage_images_number_of_columns', array($this, 'add_ecommerce_column'));
	        add_action('admin_init', array($this, 'redirect_to_manage_pricelist_page'));
            add_action('admin_menu', array($this, 'add_parent_menu'), 15);
	        add_action('admin_init', array($this, 'enqueue_backend_resources'));

	        // TODO - Need to figure out if this is needed after mge
	        add_filter('get_edit_post_link', array(&$this, 'custom_edit_link'));

            // Tweak our custom post type UIs
            if (strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/edit.php') !== FALSE
            &&  isset($_REQUEST['post_type'])
            &&  in_array($_REQUEST['post_type'], array('ngg_pricelist', 'ngg_order')))
            {
                add_action('admin_enqueue_scripts', array($this, 'enqueue_fontawesome'));
                add_action('admin_enqueue_scripts', array($this, 'dequeue_autosave'));

                add_filter('post_row_actions', array($this, 'hide_quick_edit_link'), 10, 2);

                add_filter('handle_bulk_actions-edit-ngg_pricelist', array($this, 'handle_pricelist_bulk_actions'), 10, 3);
                add_filter('bulk_actions-edit-ngg_order', array($this, 'set_bulk_actions'));
                add_filter('bulk_actions-edit-ngg_pricelist', array($this, 'set_pricelist_bulk_actions'));

                add_filter('views_edit-ngg_order', array($this, 'remove_post_status_views'));
                add_filter('views_edit-ngg_pricelist', array($this, 'remove_post_status_views'));
	            add_action('admin_init', array($this, 'duplicate_pricelist'));

                if ($_REQUEST['post_type'] == 'ngg_order')
                {
                    add_action('restrict_manage_posts', array(&$this, 'filter_orders_restrict_manage_posts'));
                    if (isset($_REQUEST['action']) && $_REQUEST['action'] == -1)
                        add_action('pre_get_posts', array(&$this, 'filter_orders_pre_get_posts'));
                    add_filter('manage_ngg_order_posts_columns', array(&$this, 'order_columns'));
                    add_action('manage_ngg_order_posts_custom_column', array(&$this, 'output_order_column'), 10, 2);
                    add_filter('manage_edit-ngg_order_sortable_columns', array(&$this, 'order_columns'));

	                if (isset($_REQUEST['action']) && $_REQUEST['action'] == -1)
		                add_action('pre_get_posts', array($this, 'filter_orders_pre_get_posts'));
	                if (isset($_REQUEST['s']))
		                add_filter('get_search_query', array($this, 'restore_search_filter'));
                }

                if ($_REQUEST['post_type'] === 'ngg_pricelist')
                    add_action('admin_enqueue_scripts', array($this, 'delete_pricelist_warning'));

                // We want the Manage Pricelists page to be overwritten with our form used for
                // creating new pricelists
                if (isset($_REQUEST['ngg_edit'])) {
                    if (isset($_REQUEST['action'])) $_REQUEST['ngg_action'] = $_REQUEST['action'];
                    unset($_REQUEST['action']);
                    unset($_POST['action']);
                    add_action('all_admin_notices', array(&$this, 'buffer_for_manage_pricelist_page'), PHP_INT_MAX-1);
                    add_action('in_admin_footer', array(&$this, 'render_manage_pricelist_page'));
                }

                // Add the ability to change order status via bulk actions
                if (strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/edit.php') !== FALSE
                    &&  isset($_REQUEST['post_type'])
                    &&  $_REQUEST['post_type'] == 'ngg_order') {
                    add_filter("bulk_actions-edit-ngg_order", array($this, 'order_bulk_actions'));
                    add_action('admin_notices',               array($this, 'order_bulk_action_notices'));
                    add_action('load-edit.php',               array($this, 'process_order_bulk_actions'));
                }
            }
        }

        // Flush the cart when the order is complete
        if (isset($_REQUEST['ngg_order_complete']))
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_flush_cart'));
    }

    public function delete_pricelist_warning()
    {
        wp_localize_script('jquery', 'ngg_pro_pricelists_i18n', [
            'confirm' => __("Are you sure you wish to continue? This will not use the trash and these pricelists and all of their items will be permanently deleted.")
        ]);
        wp_add_inline_script('jquery', 'jQuery(function($){
            jQuery("#posts-filter #doaction").click(function(e){
            
                if (confirm(ngg_pro_pricelists_i18n.confirm)) {
                    $(this).parent("#posts-filter").submit();
                    return true;
                }
                else {
                    e.preventDefault();
                    return false;
                }
            });
        });');
    }

    /**
     * This method is triggered when a pricelist is deleted and deletes all of that pricelist's child items
     * @param int $post_id
     */
    public function before_delete_post($post_id)
    {
        global $post_type;
        global $wpdb;

        if ('ngg_pricelist' !== $post_type)
            return;

        $statement = $wpdb->prepare(
            "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE `meta_key` = 'pricelist_id' AND `meta_value` = %s",
            $post_id
        );

        $pricelist_items = $wpdb->get_col($statement);

        foreach ($pricelist_items as $item_id) {
            wp_delete_post($item_id, TRUE);
        }
    }

	static function get_license($product)
	{
		return C_Component_Registry::get_instance()->get_module('photocrati-auto_update')->get_license($product);
	}

	static function check_ecommerce_requirements()
	{
		$retval = array();

		$settings         = C_NextGen_Settings::get_instance();
		$pricelist_mapper = C_Pricelist_Mapper::get_instance();


		// Selected ecommerce pages for Checkout, Confirmation, Cancel, and Digital Downloads
		$retval['ecommerce_pages'] = (
			$settings->get('ecommerce_page_checkout') &&
			$settings->get('ecommerce_page_thanks') &&
			$settings->get('ecommerce_page_cancel') &&
			$settings->get('ecommerce_page_digital_downloads')
		);


		// Provided studio name and address
		$retval['studio_address'] = (
			$settings->get('ecommerce_studio_name') &&
			$settings->get('ecommerce_studio_street_address') &&
			$settings->get('ecommerce_studio_city') &&
			$settings->get('ecommerce_home_country') &&
			$settings->get('ecommerce_home_state') &&
            $settings->get('ecommerce_home_zip') &&
            $settings->get('ecommerce_studio_email')
		);

		// Setup a payment gateway
		$retval['payment_gateway'] = (
               intval($settings->get('ecommerce_paypal_checkout_enable'))
			|| intval($settings->get('ecommerce_paypal_enable'))
			|| intval($settings->get('ecommerce_paypal_std_enable'))
			|| intval($settings->get('ecommerce_stripe_enable'))
			|| intval($settings->get('ecommerce_cheque_enable'))
			|| intval($settings->get('ecommerce_test_gateway_enable'))
		);

		// Enabled image backups and resizing
		$retval['image_settings'] = (
			intval($settings->get('imgBackup')) && TRUE
        );
        
        // Enabled image resizing on upload
        $retval['image_resizing'] = intval($settings->get('imgAutoResize')) && TRUE;

		// Enabled Pro Lightbox
		$retval['pro_lightbox'] = (
			$settings->get('thumbEffect') == 'photocrati-nextgen_pro_lightbox'
		);

		// Created Pricelist
		$retval['pricelist_created'] = (
			$pricelist_mapper->count() > 0
        );

		// Associated the pricelist
		$query = new WP_Query(array(
            'post_type'  => 'ngg_gallery',
            'post_status'=> 'any',
			'meta_query' => array(
				'key'       => 'pricelist_id',
				'value'     => 0,
				'compare'   => '>='
			),
			'fields'    => 'ids'
        ));

        $retval['pricelist_associated'] = FALSE;
        if ($query->have_posts())
        {
            foreach ($query->get_posts() as $post_id) {
                if ($retval['pricelist_associated'])
                    break;
                if (($pricelist_id = intval(get_post_meta($post_id, 'pricelist_id', TRUE))))
                {
                    if (($pricelist = $pricelist_mapper->find($pricelist_id)))
                    {
                        if ($pricelist->post_status != 'trash')
                            $retval['pricelist_associated'] = TRUE;
                    }
                }
            }
        }

		// Has an active license
        $retval['active_license'] = (
            M_NextGen_Pro_Ecommerce::is_valid_license()
        );

		// Has a credit card on file
		$retval['card_on_file'] = (
		    $settings->get('stripe_cus_id', FALSE)
		);

		// Has print lab items added to a pricelist
		$lab_sources = array();
		$sources = C_Pricelist_Source_Manager::get_instance();
		foreach ($sources->get_ids() as $source_id) {
			if ($sources->get($source_id, 'lab_fulfilled'))
			    $lab_sources[] = $source_id;
        }
        
		$query = new WP_Query(array(
            'fields'     => 'ids',
            'post_type'  => 'ngg_pricelist_item',
            'post_status' => 'any',
			'meta_query' => array(
				'key'   => 'source',
				'value' => join(',', $lab_sources),
				'compare'=> 'IN'
			),
		));

		$retval['has_printlab_items'] = (
			$query->have_posts()
		);

		// Has SSL enabled
        $retval['has_ssl'] = is_ssl() ? TRUE : FALSE;

        // Is ecommerce ready?
        $retval['manual_ecommerce_ready'] = (
            $retval['ecommerce_pages']
            && $retval['studio_address']
            && $retval['payment_gateway']
            && $retval['pro_lightbox']
            && $retval['pricelist_created']
            && $retval['pricelist_associated']
            && $retval['has_ssl']
        );

        $retval['download_ecommerce_ready'] = (
            $retval['manual_ecommerce_ready']
            && $retval['image_settings']
        );

        // Is print lab ready?
        $retval['printlab_ecommerce_ready'] = (
            $retval['manual_ecommerce_ready']
            && $retval['image_settings']
            && $retval['has_printlab_items']
            && $retval['card_on_file']
            && $retval['active_license']
            && $retval['image_resizing']
        );

		return $retval;
    }

    /**
     * Determines whether the NGG Pro Wizard can run
     */
    static function can_pro_wizard_run()
    {
        // return method_exists('M_NextGen_Admin', 'is_block_editor');

        return FALSE;
    }

	function init_wizards()
	{
        if (self::can_pro_wizard_run()) {
            $is_block_editor = M_NextGen_Admin::is_block_editor();
            $number_galleries = C_Gallery_Mapper::get_instance()->count();
            $manage_galleries_text = $number_galleries == 0 ? __('After your images have uploaded, click "Manage Galleries" to see your galleries.', 'nextgen-gallery-pro') : __('Click "Manage Galleries" to see your galleries.', 'nextgen-gallery-pro');

            $wizards = C_NextGEN_Wizard_Manager::get_instance();
            
            $wizard = $wizards->add_wizard('nextgen.ecommerce.all_requirements');
            $wizard->set_active(true);
            $wizard->add_step('start');
            $wizard->set_step_text('start', __('Hello, this wizard will guide you through the set up of NextGEN Pro Ecommerce.', 'nextgen-gallery-pro'));
            
            /* Configure ecommerce options */ 

            $wizard->add_step('ecommerce_options_menu');
            $wizard->set_step_text('ecommerce_options_menu', __('Click "Ecommerce Options" to access NextGEN Ecommerce Options.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('ecommerce_options_menu', '#toplevel_page_ngg_ecommerce_options .wp-submenu a[href*="admin.php?page=ngg_ecommerce_options"]', 'right center', 'left center');
            $wizard->set_step_view('ecommerce_options_menu', '#toplevel_page_ngg_ecommerce_options .wp-submenu a[href*="admin.php?page=ngg_ecommerce_options"]');
            
            $wizard->add_step('select_page_checkout');
            $wizard->set_step_text('select_page_checkout', __('Select a Checkout page or use "Create New" to automatically create one.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('select_page_checkout', '#tr_ecommerce_page_checkout #ecommerce_page_checkout', 'right center', 'left center');
            $wizard->set_step_view('select_page_checkout', '#tr_ecommerce_page_checkout #ecommerce_page_checkout');
            $wizard->set_step_optional('select_page_checkout', true);
            // $wizard->set_step_condition('select_page_checkout', 'wait', '3000');
            
            $wizard->add_step('select_page_thank_you');
            $wizard->set_step_text('select_page_thank_you', __('Select a Thank You page or use "Create New" to automatically create one.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('select_page_thank_you', '#tr_ecommerce_page_thanks #ecommerce_page_thanks', 'right center', 'left center');
            $wizard->set_step_view('select_page_thank_you', '#tr_ecommerce_page_thanks #ecommerce_page_thanks');
            $wizard->set_step_optional('select_page_thank_you', true);
            
            $wizard->add_step('select_page_cancel');
            $wizard->set_step_text('select_page_cancel', __('Select a Cancel page or use "Create New" to automatically create one.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('select_page_cancel', '#tr_ecommerce_page_cancel #ecommerce_page_cancel', 'right center', 'left center');
            $wizard->set_step_view('select_page_cancel', '#tr_ecommerce_page_cancel #ecommerce_page_cancel');
            $wizard->set_step_optional('select_page_cancel', true);
            
            $wizard->add_step('select_page_digital_downloads');
            $wizard->set_step_text('select_page_digital_downloads', __('Select a Digital Downloads page or use "Create New" to automatically create one.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('select_page_digital_downloads', '#tr_ecommerce_page_digital_downloads #ecommerce_page_digital_downloads', 'right center', 'left center');
            $wizard->set_step_view('select_page_digital_downloads', '#tr_ecommerce_page_digital_downloads #ecommerce_page_digital_downloads');
            $wizard->set_step_optional('select_page_digital_downloads', true);
            
            $wizard->add_step('input_studio_name');
            $wizard->set_step_text('input_studio_name', __('Enter your Studio Name.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_name', '#tr_ecommerce_studio_name input#ecommerce_studio_name', 'right center', 'left center');
            $wizard->set_step_view('input_studio_name', '#tr_ecommerce_studio_name input#ecommerce_studio_name');
            $wizard->set_step_optional('input_studio_name', true);
            
            $wizard->add_step('input_studio_address');
            $wizard->set_step_text('input_studio_address', __('Enter your Studio Address.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_address', '#tr_ecommerce_studio_street_address input#ecommerce_studio_street_address', 'right center', 'left center');
            $wizard->set_step_view('input_studio_address', '#tr_ecommerce_studio_street_address input#ecommerce_studio_street_address');
            $wizard->set_step_optional('input_studio_address', true);
            
            $wizard->add_step('input_studio_city');
            $wizard->set_step_text('input_studio_city', __('Enter your Studio City.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_city', '#tr_ecommerce_studio_city input#ecommerce_studio_city', 'right center', 'left center');
            $wizard->set_step_view('input_studio_city', '#tr_ecommerce_studio_city input#ecommerce_studio_city');
            $wizard->set_step_optional('input_studio_city', true);
            
            $wizard->add_step('input_studio_country');
            $wizard->set_step_text('input_studio_country', __('Enter your Studio Country.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_country', '#tr_ecommerce_home_country #ecommerce_home_country', 'right center', 'left center');
            $wizard->set_step_view('input_studio_country', '#tr_ecommerce_home_country #ecommerce_home_country');
            $wizard->set_step_optional('input_studio_country', true);
            
            $wizard->add_step('input_studio_state');
            $wizard->set_step_text('input_studio_state', __('Enter your Studio State.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_state', '#tr_ecommerce_home_state select#ecommerce_home_state:visible', 'right center', 'left center');
            $wizard->set_step_view('input_studio_state', '#tr_ecommerce_home_state select#ecommerce_home_state:visible');
            $wizard->set_step_optional('input_studio_state', true);
            $wizard->set_step_lazy('input_studio_state', true);
            
            $wizard->add_step('input_studio_zip');
            $wizard->set_step_text('input_studio_zip', __('Enter your Studio Postal Code.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_zip', '#tr_ecommerce_home_zip input#ecommerce_home_zip', 'right center', 'left center');
            $wizard->set_step_view('input_studio_zip', '#tr_ecommerce_home_zip input#ecommerce_home_zip');
            $wizard->set_step_optional('input_studio_zip', true);

            $wizard->add_step('input_studio_email');
            $wizard->set_step_text('input_studio_email', __('Enter your Studio email.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_studio_email', '#tr_ecommerce_studio_email input#ecommerce_studio_email', 'right center', 'left center');
            $wizard->set_step_view('input_studio_email', '#tr_ecommerce_studio_email input#ecommerce_studio_email');
            $wizard->set_step_optional('input_studio_email', true);

            $wizard->add_step('save_ecom_options_1');
            $wizard->set_step_text('save_ecom_options_1', __('Scroll up and click the "Save Options" button to save your changes.', 'nextgen-gallery-pro'));
            // $wizard->set_step_target('save_ecom_options_1', 'button.ngg_save_settings_button', 'top center', 'bottom left');
            $wizard->set_step_view('save_ecom_options_1', 'button.ngg_save_settings_button');

            /* Configure payment gateway and credit card */

            $wizard->add_step('payment_gateway_tab');
            $wizard->set_step_text('payment_gateway_tab', __('Click the "Payment Gateway" tab.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('payment_gateway_tab', '.ngg_page_content_menu a[data-id="ngg-payment-gateways"]', 'right center', 'left center');
            $wizard->set_step_view('payment_gateway_tab', '.ngg_page_content_menu a[data-id="ngg-payment-gateways"]');
            
            $wizard->add_step('payment_gateway_enable');
            $wizard->set_step_text('payment_gateway_enable', __('Please configure at least one payment gateway. For quick testing, enable the test gateway.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('payment_gateway_enable', '.ngg_page_content_main [data-id="ngg-payment-gateways"] table', 'top center', 'bottom center');
            $wizard->set_step_view('payment_gateway_enable', '.ngg_page_content_main [data-id="ngg-payment-gateways"] table');
            $wizard->set_step_optional('payment_gateway_enable', true);

            $wizard->add_step('save_ecom_options_2');
            $wizard->set_step_text('save_ecom_options_2', __('Click the "Save Options" button to save your changes.', 'nextgen-gallery-pro'));
            // $wizard->set_step_target('save_ecom_options_2', 'button.ngg_save_settings_button', 'top center', 'bottom left');
            $wizard->set_step_view('save_ecom_options_2', 'button.ngg_save_settings_button');

            $wizard->add_step('printlab_integration_tab');
            $wizard->set_step_text('printlab_integration_tab', __('Click the "Print Lab Integration" tab.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('printlab_integration_tab', '.ngg_page_content_menu a[data-id="ngg-ecommerce-printlab"]', 'right center', 'left center');
            $wizard->set_step_view('printlab_integration_tab', '.ngg_page_content_menu a[data-id="ngg-ecommerce-printlab"]');
            
            $wizard->add_step('printlab_card_input');
            $wizard->set_step_text('printlab_card_input', __('Enter your Credit Card information and click Update. Note: Your card will not be charged. It is stored securely at Stripe and will only be charged to cover your portion of the cost of future print lab orders.', 'nextgen-gallery-pro'));
            // $wizard->set_step_target('printlab_card_input', '#stripe_credit_card_form', 'top right', 'right center');
            $wizard->set_step_view('printlab_card_input', '#stripe_credit_card_form');
            $wizard->set_step_optional('printlab_card_input', true);

            /* Create a pricelist */

            $wizard->add_step('ecommerce_manage_pricelist_menu');
            $wizard->set_step_text('ecommerce_manage_pricelist_menu', __('Click "Manage Pricelists" to add a pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('ecommerce_manage_pricelist_menu', '#toplevel_page_ngg_ecommerce_options .wp-submenu a[href*="edit.php?post_type=ngg_pricelist"]', 'right center', 'left center');
            $wizard->set_step_view('ecommerce_manage_pricelist_menu', '#toplevel_page_ngg_ecommerce_options .wp-submenu a[href*="edit.php?post_type=ngg_pricelist"]');
            
            $wizard->add_step('ecommerce_pricelist_add');
            $wizard->set_step_text('ecommerce_pricelist_add', __('Click "Add New" to create a new pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('ecommerce_pricelist_add', '.ngg_page_content_main a[href*="post-new.php?post_type=ngg_pricelist"]', 'right center', 'left center');
            $wizard->set_step_view('ecommerce_pricelist_add', '.ngg_page_content_main a[href*="post-new.php?post_type=ngg_pricelist"]');
            
            $wizard->add_step('input_pricelist_title');
            $wizard->set_step_text('input_pricelist_title', __('Enter a name for your pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_pricelist_title', 'input#title', 'bottom center', 'top center');
            $wizard->set_step_view('input_pricelist_title', 'input#title');

            $wizard->add_step('add_product');
            $wizard->set_step_text('add_product', __('Click "Add Product" to add products to your pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('add_product', '.ngg_pricelist_actions a[href*="inlineId=new_product_parent"]', 'bottom center', 'top center');
            $wizard->set_step_view('add_product', '.ngg_pricelist_actions a[href*="inlineId=new_product_parent"]');

            $wizard->add_step('add_whcc');
            $wizard->set_step_text('add_whcc', __('For now, click "WHCC Prints" to add print items from WHCC, a pro lab. This will allow you to use automatic print fulfillment. Later, you can return to add manual fulfillment items or digital downloads if you like.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('add_whcc', '#TB_window .ngg_whcc_pricelist', 'right center', 'left center');
            $wizard->set_step_view('add_whcc', '#TB_window .ngg_whcc_pricelist');
            $wizard->set_step_lazy('add_whcc', true);

            $wizard->add_step('add_product_submit');
            $wizard->set_step_text('add_product_submit', __('We have pre-selected popular WHCC print items for you. Now click "Add Product" to add these items to your pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('add_product_submit', '#new_product_button_add', 'top center', 'bottom left');
            $wizard->set_step_view('add_product_submit', '#new_product_button_add');
            $wizard->set_step_lazy('add_product_submit', true);
            $wizard->set_step_condition('add_product_submit', 'wait', '500');

            $wizard->add_step('save_pricelist');
            $wizard->set_step_text('save_pricelist', __('Scroll down and click the "Save" button to save your Pricelist.', 'nextgen-gallery-pro'));
            // $wizard->set_step_target('save_pricelist', 'button[value*="Save"]', 'top center', 'bottom left');
            $wizard->set_step_view('save_pricelist', 'button[value*="Save"]');
            $wizard->set_step_condition('save_pricelist', 'wait', '3000');

            /* Configure general options */

            $wizard->add_step('nextgen_menu');
            $wizard->set_step_text('nextgen_menu', __('Click on "Gallery".', 'nextgen-gallery-pro'));
            $wizard->set_step_target('nextgen_menu', '#toplevel_page_nextgen-gallery a.menu-top', 'right center', 'left center');
            $wizard->set_step_view('nextgen_menu', '#toplevel_page_nextgen-gallery a.menu-top');
            $wizard->set_step_lazy('nextgen_menu', true);
            
            $wizard->add_step('nextgen_other_options_menu');
            $wizard->set_step_text('nextgen_other_options_menu', __('Click "Other Options" to access NextGEN Options.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('nextgen_other_options_menu', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=ngg_other_options"]', 'right center', 'left center');
            $wizard->set_step_view('nextgen_other_options_menu', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=ngg_other_options"]');
            
            $wizard->add_step('image_resize');
            $wizard->set_step_text('image_resize', __('Ensure Image resize on upload is Enabled.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('image_resize', '.image_options #automatic_resize', 'right center', 'left center');
            $wizard->set_step_view('image_resize', '.image_options #automatic_resize');
            $wizard->set_step_optional('image_resize', true);

            $wizard->add_step('image_backup');
            $wizard->set_step_text('image_backup', __('Ensure Image backups on upload is Enabled.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('image_backup', '.image_options #backup_images_yes', 'right center', 'left center');
            $wizard->set_step_view('image_backup', '.image_options #backup_images_yes');
            $wizard->set_step_optional('image_backup', true);
            
            $wizard->add_step('lightbox_effects_tab');
            $wizard->set_step_text('lightbox_effects_tab', __('Click the "Lightbox Effects" tab.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('lightbox_effects_tab', '.ngg_page_content_menu a[data-id="lightbox_effects"]', 'right center', 'left center');
            $wizard->set_step_view('lightbox_effects_tab', '.ngg_page_content_menu a[data-id="lightbox_effects"]');
            
            $wizard->add_step('input_lightbox_effect');
            $wizard->set_step_text('input_lightbox_effect', __('Select "NextGEN Pro Lightbox" as your lightbox. This lightbox contains the interface for adding items to cart.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('input_lightbox_effect', '#lightbox_library', 'right center', 'left center');
            $wizard->set_step_view('input_lightbox_effect', '#lightbox_library');
            $wizard->set_step_optional('input_lightbox_effect', true);

            $wizard->add_step('save_other_options');
            $wizard->set_step_text('save_other_options', __('Click the "Save Options" button to save your changes.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('save_other_options', 'button.ngg_save_settings_button', 'bottom center', 'top right');
            $wizard->set_step_view('save_other_options', 'button.ngg_save_settings_button');
            
            /* Create a gallery if none exist */
            if ($number_galleries == 0) {
                $wizard->add_step('add_gallery_page');
                $wizard->set_step_text('add_gallery_page', __('Now click the "Add Gallery" tab to add a new gallery.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('add_gallery_page', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=ngg_addgallery"]', 'right center', 'left center');
                $wizard->set_step_view('add_gallery_page', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=ngg_addgallery"]');
                $wizard->set_step_lazy('add_gallery_page', true);
                $wizard->set_step_condition('add_gallery_page', 'nextgen_event', 'plupload_init', null, 10000);
                $wizard->add_step('input_gallery_name');
                $wizard->set_step_text('input_gallery_name', __('Select a name for your gallery.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('input_gallery_name', 'input#gallery_name:visible', 'bottom center', 'top center');
                $wizard->set_step_view('input_gallery_name', 'input#gallery_name');
                $wizard->set_step_lazy('input_gallery_name', true);
                $wizard->add_step('select_images');
                $wizard->set_step_text('select_images', __('Now click the "Add Files" button and select some images to add to the gallery.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('select_images', 'a#uploader_browse', 'bottom center', 'top center');
                $wizard->set_step_view('select_images', 'a#uploader_browse');
                $wizard->set_step_lazy('select_images', true);
                $wizard->add_step('upload_images');
                $wizard->set_step_text('upload_images', __('Now click the "Start Upload" button. LET UPLOAD COMPLETE before proceeding to next step.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('upload_images', 'a#uploader_upload', 'bottom center', 'top right');
                $wizard->set_step_view('upload_images', 'a#uploader_upload');
                $wizard->set_step_lazy('upload_images', true);
            }

            /* Associate pricelist with gallery */

            $wizard->add_step('manage_galleries');
            $wizard->set_step_text('manage_galleries', $manage_galleries_text);
            $wizard->set_step_target('manage_galleries', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=nggallery-manage-gallery"]', 'right center', 'left center');
            $wizard->set_step_view('manage_galleries', '#toplevel_page_nextgen-gallery .wp-submenu a[href*="admin.php?page=nggallery-manage-gallery"]');

            $wizard->add_step('select_gallery');
            $wizard->set_step_text('select_gallery', __('Click on a gallery to open it.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('select_gallery', '#the-list .title a:first-of-type', 'right center', 'left center');
            $wizard->set_step_view('select_gallery', '#the-list .title a:first-of-type');

            $wizard->add_step('gallery_settings');
            $wizard->set_step_text('gallery_settings', __('Click to open Gallery Settings.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('gallery_settings', '#gallerydiv h3', 'right center', 'left center');
            $wizard->set_step_view('gallery_settings', '#gallerydiv h3');

            $wizard->add_step('set_pricelist');
            $wizard->set_step_text('set_pricelist', __('Select your new pricelist.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('set_pricelist', '#gallery_pricelist', 'top center', 'bottom center');
            $wizard->set_step_view('set_pricelist', '#gallery_pricelist');

            $wizard->add_step('save_gallery_changes');
            $wizard->set_step_text('save_gallery_changes', __('Click to save your changes.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('save_gallery_changes', 'input.ngg_save_gallery_changes[type*="submit"]', 'top center', 'bottom left');
            $wizard->set_step_view('save_gallery_changes', 'input.ngg_save_gallery_changes[type*="submit"]');
            
            /* Add Gallery to page */

            $wizard->add_step('pages_menu');
            $wizard->set_step_text('pages_menu', __('Click on "Pages" to access your WordPress pages.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('pages_menu', '#menu-pages a.menu-top', 'right center', 'left center');
            $wizard->set_step_view('pages_menu', '#menu-pages a.menu-top');
            $wizard->set_step_condition('pages_menu', 'wait', '2000');

            $wizard->add_step('add_page_menu');
            $wizard->set_step_text('add_page_menu', __('Click "Add New" to create a new page.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('add_page_menu', '#menu-pages a[href*="post-new.php?post_type=page"]', 'right center', 'left center');
            $wizard->set_step_view('add_page_menu', '#menu-pages a[href*="post-new.php?post_type=page"]');
            
            if ($is_block_editor) {
                $wizard->add_step('input_block_page_title');
                $wizard->set_step_text('input_block_page_title', __('Type in a title for your page.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('input_block_page_title', '.editor-post-title__input', 'bottom center', 'top center');
                $wizard->set_step_view('input_block_page_title', '.editor-post-title__input');
                
                $wizard->add_step('add_block');
                $wizard->set_step_text('add_block', __('Now click the button to insert a block.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('add_block', 'button.block-editor-inserter__toggle', 'right center', 'left center');
                $wizard->set_step_view('add_block', 'button.block-editor-inserter__toggle');
                $wizard->set_step_lazy('add_block', true);

                $wizard->add_step('search_nextgen');
                $wizard->set_step_text('search_nextgen', __('Type "nextgen" to search for the NextGEN block.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('search_nextgen', 'input.block-editor-inserter__search', 'right center', 'left center');
                $wizard->set_step_view('search_nextgen', 'input.block-editor-inserter__search');
                $wizard->set_step_lazy('search_nextgen', true);
                $wizard->set_step_condition('search_nextgen', 'wait', '1500');

                $wizard->add_step('add_ngg_block');
                $wizard->set_step_text('add_ngg_block', __('Click on the NextGEN block to add it.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('add_ngg_block', 'button.editor-block-list-item-imagely-nextgen-gallery', 'right center', 'left center');
                $wizard->set_step_view('add_ngg_block', 'button.editor-block-list-item-imagely-nextgen-gallery');
                $wizard->set_step_lazy('add_ngg_block', true);
                $wizard->set_step_condition('add_ngg_block', 'wait', '1500');

                $wizard->add_step('add-ngg-gallery');
                $wizard->set_step_text('add-ngg-gallery', __('Now click the "Add NextGEN Gallery" button.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('add-ngg-gallery', '.add-ngg-gallery', 'bottom center', 'top center');
                $wizard->set_step_view('add-ngg-gallery', '.add-ngg-gallery');
                $wizard->set_step_lazy('add-ngg-gallery', true);			
            }
            else {
                $wizard->add_step('input_page_title');
                $wizard->set_step_text('input_page_title', __('Type in a title for your page.', 'nextgen-gallery-pro'));
                $wizard->set_step_target('input_page_title', 'input#title', 'bottom center', 'top center');
                $wizard->set_step_view('input_page_title', 'input#title');
                
                $wizard->add_step('add_gallery_button');
                $wizard->set_step_text('add_gallery_button', __('Click the "Add Gallery" button to open NextGEN\'s Insert Gallery Window (IGW).', 'nextgen-gallery-pro'));
                $wizard->set_step_target('add_gallery_button', 'a#ngg-media-button', 'right center', 'left center');
                $wizard->set_step_view('add_gallery_button', 'a#ngg-media-button');
            }
            
            $wizard->add_step('source_select');
            $wizard->set_step_text('source_select', __('Click inside the "Galleries" field and select your gallery.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('source_select', '#source_configuration .galleries_column .select2-container input', 'right center', 'left center');
            $wizard->set_step_view('source_select', '#source_configuration .galleries_column select');
            $wizard->set_step_context('source_select', 'iframe[src*="' . NGG_ATTACH_TO_POST_SLUG . '"]');
            $wizard->set_step_lazy('source_select', true);

            $wizard->add_step('customize_tab');
            $wizard->set_step_text('customize_tab', __('Click the "Customize" tab.', 'nextgen-gallery-pro'));
            $wizard->set_step_target('customize_tab', 'a[data-id="display_settings_tab"]', 'bottom center', 'top center');
            $wizard->set_step_view('customize_tab', 'a[data-id="display_settings_tab"]');
            $wizard->set_step_context('customize_tab', 'iframe[src*="' . NGG_ATTACH_TO_POST_SLUG . '"]');
            $wizard->set_step_lazy('customize_tab', true);
            
            $wizard->add_step('enable_ecommerce');
            $wizard->set_step_text('enable_ecommerce', __('Scroll down and set "Enable Ecommerce" to Yes. Then click "Next Step".', 'nextgen-gallery-pro'));
            // $wizard->set_step_target('enable_ecommerce', '#photocrati-nextgen_basic_thumbnails_is_ecommerce_enabled', 'right center', 'left center');
            $wizard->set_step_view('enable_ecommerce', '#photocrati-nextgen_basic_thumbnails_is_ecommerce_enabled');
            $wizard->set_step_context('enable_ecommerce', 'iframe[src*="' . NGG_ATTACH_TO_POST_SLUG . '"]');
            // $wizard->set_step_lazy('enable_ecommerce', true);
            $wizard->set_step_optional('enable_ecommerce', true);

            $wizard->add_step('insert_gallery');
            $wizard->set_step_text('insert_gallery', __('Click the "Insert Gallery" button. Then click "Next Step".', 'nextgen-gallery-pro'));
            $wizard->set_step_target('insert_gallery', '#displayed_tab #save_displayed_gallery', 'top center', 'bottom left');
            $wizard->set_step_view('insert_gallery', '#displayed_tab #save_displayed_gallery');
            $wizard->set_step_context('insert_gallery', 'iframe[src*="' . NGG_ATTACH_TO_POST_SLUG . '"]');
            $wizard->set_step_lazy('insert_gallery', true);
            $wizard->set_step_condition('insert_gallery', 'wait', '1000');
            
            $wizard->add_step('finish');
            $wizard->set_step_text('finish', __('Congratulations! You\'re done. You\'ve set up ecommerce and added an eccommerce gallery. You can now click "Publish" or "Preview" to view your gallery.', 'nextgen-gallery-pro'));
        }
	}

	function duplicate_pricelist()
	{
		if (isset($_REQUEST['ngg_duplicate']) && current_user_can('NextGEN Change options')) {
			$pricelist_mapper = C_Pricelist_Mapper::get_instance();
			if (($pricelist = $pricelist_mapper->find($_REQUEST['id'], TRUE))) {

				// Get items for the pricelist
				$items = $pricelist->get_items();

				// Find the unique post title
				$results = $pricelist_mapper->select()->where(array('post_title LIKE %s', $pricelist->post_title . '%'))->run_query();
				$i=0;
				foreach ($results as $p) {
					$number = intval(trim(str_replace($pricelist->post_title, '', $p->post_title)));
					if ($number > $i) $i = $number;
				}
				$i++;

				// Create new pricelist
				$pricelist->ID = NULL;
				$pricelist->post_date = $pricelist->post_date_gmt = $pricelist->post_modified = $pricelist->post_modified_gmt = NULL;
				$pricelist->post_title = $pricelist->title = "{$pricelist->title} {$i}";
				$pricelist_mapper->save($pricelist);

				// Duplicate pricelist items
				$item_mapper = C_Pricelist_Item_Mapper::get_instance();
				foreach ($items as $item) {
					$item->ID = NULL;
					$item->pricelist_id = $pricelist->ID;
					$item_mapper->save($item);
				}

				wp_redirect(admin_url("/edit.php?post_type={$_REQUEST['post_type']}"));
			}
		}
	}

    // TODO - Cache this
	static function is_valid_license($return_status=FALSE)
	{
		$registry       = C_Component_Registry::get_instance();
        $auto_update    = $registry->get_module('photocrati-auto_update');
        $license        = $auto_update->get_license('photocrati-nextgen-pro');

        $transient_key  = __METHOD__.$license;
        $cache          = defined('NGG_PRO_CACHE_LICENSE_CHECKS') ? NGG_PRO_CACHE_LICENSE_CHECKS : TRUE;
        $ttl            = defined('NGG_PRO_CACHE_LICENSE_TTL')    ? NGG_PRO_CACHE_LICENSE_TTL    : 3600; // hour

        if (self::$_license_check_cache)
            $status = self::$_license_check_cache;
        else
            $status = $cache ? get_transient($transient_key) : FALSE;

        if (!$status)
        {
            try {
                $status = $auto_update->api_request(
                    $auto_update->_get_api_url(),
                    'cklic',
                    array(
                        'product-list'  => array(
                            'photocrati-nextgen-pro' => $registry->get_product('photocrati-nextgen-pro')->module_version
                        ),
                    )
                );
            }
            catch (Exception $ex) {
                $status = 'ERROR'; 
            }

            // Prevent multiple transient reads, or if caching is disabled, from making potentially hundreds
            // of license checks for a single page request
            self::$_license_check_cache = $status;

            if ($cache)
                set_transient($transient_key, $status, $ttl);
        }

        // NOTE: because above we make sure we only request a license check for the photocrati-nextgen-pro product, a response of
        // PARTIAL makes no sense and really just indicates that the license is completely invalid, i.e. probably a Nil license
        // so in this specific case it is safe to consider PARTIAL the same as INVALID
		if ($return_status)
		    return $status;
		else
		    return !in_array(
                $status,
                array(
                    'ERROR',
                    'EXPIRED',
                    'INVALID',
                    'PARTIAL'
                )
            );
	}

    function add_parent_menu()
    {
        $controller = $this->get_registry()->get_utility('I_NextGen_Admin_Page', NGG_PRO_ECOMMERCE_OPTIONS_PAGE);

        add_menu_page(
            __('Ecommerce', 'nextgen-gallery-pro'),
            __('Ecommerce', 'nextgen-gallery-pro'),
            'NextGEN Change options',
            NGG_PRO_ECOMMERCE_OPTIONS_PAGE,
            array(&$controller, 'index_action'),
            path_join(NGGALLERY_URLPATH, 'admin/images/imagely_icon.png'),
            11
        );
    }

    function set_bulk_actions($actions)
    {
        unset($actions['edit']);
	    unset($actions['delete']);
        return $actions;
    }

    function set_pricelist_bulk_actions($actions)
    {
        $actions['ngg_delete_pricelist'] = __('Delete Permanently');
        unset($actions['edit']);
        unset($actions['delete']);
        unset($actions['trash']);
        return $actions;
    }

    public function handle_pricelist_bulk_actions($redirect_to, $action, $post_ids)
    {
        if ($action === 'ngg_delete_pricelist')
        {
            $mapper = C_Pricelist_Mapper::get_instance();
            foreach ($post_ids as $post_id) {
                $mapper->destroy($post_id);
            }
        }

        wp_redirect($redirect_to);
    }

    function remove_post_status_views($views)
    {
        unset($views['draft']);
        unset($views['publish']);
        if (count($views) == 1) $views = array();
        return $views;
    }

    function restore_search_filter()
    {
        return $_REQUEST['s'];
    }

    function order_columns($columns)
    {
        unset($columns['title']);

        $columns['order_hash']      = __('ID', 'nextgen-gallery-pro');
        $columns['order_customer']  = __('Customer', 'nextgen-gallery-pro');
        $columns['order_status']    = __('Order Status', 'nextgen-gallery-pro');
        $columns['order_gateway']   = __('Payment Gateway', 'nextgen-gallery-pro');
        $columns['order_coupon']    = __('Coupon', 'nextgen-gallery-pro');
        $columns['order_total']     = __('Total', 'nextgen-gallery-pro');

        return $columns;
    }

    function output_order_column($column_name, $post_id)
    {
        global $post;
        $order_mapper = C_Order_Mapper::get_instance();
        $entity = $order_mapper->unserialize($post->post_content);
        switch ($column_name) {
            case 'order_coupon':
                if (isset($entity['cart']) && isset($entity['cart']['coupon'])) {
                    $coupon = $entity['cart']['coupon'];
                    if ((C_Coupon_Mapper::get_instance()->find($coupon['id'])))
                        echo "<a href='edit.php?post_type=ngg_coupon&ngg_edit=1&id={$coupon['id']}' target='_blank'>{$coupon['code']}</a>";
                    else
                        echo $coupon['code'];
                }
                else {
                    echo __("No coupon.", 'nextgen-gallery-pro');
                }
                break;
            // TODO: Fix separation of concerns problem here
            // The ecommerce module is iterating over all known gateways. This isn't scalable
            // as if we ever add/remove a gateway, we need to adjust this code as well.
            case 'order_gateway':
                $str = '';
                switch ($entity['payment_gateway']) {
                    case 'free':
                        $str = __('Free', 'nextgen-gallery-pro');
                        break;
                    case 'cheque':
                    case 'check':
                        $str = __('Check', 'nextgen-gallery-pro');
                        break;
                    case 'paypal_standard':
                    case 'paypal_express_checkout':
                    case 'paypal_express':
                    case 'paypal_checkout':
                        $str = __('PayPal', 'nextgen-gallery-pro');
                        break;
                    case 'stripe_checkout':
                        $str = __('Stripe', 'nextgen-gallery-pro');
                        break;
                    case 'test_gateway':
                        $str = __('Test', 'nextgen-gallery-pro');
                        break;
                    default:
                        break;
                }
                echo $str;
                break;
            case 'order_total':
                $cart = new C_NextGen_Pro_Cart($entity['cart'], array('saved' => TRUE));
                echo $this->get_formatted_price($cart->get_total(), $cart->get_currency());
                if (isset($entity['aws_order_id'])) {
                	echo sprintf(
                		'<span class="ngg-lab-order-cost"> (%s $<span class="ngg-lab-order-cost-amount">0.00</span>)</span>',
		                esc_attr(__('Print Cost: ', 'nextgen-gallery-pro'))
	                );
                }
                break;
            case 'order_status':
            	$tmpl = '
            	    <span class="ngg-order-status">%s</span>%s
            	';
            	$lab_status_el = '';

            	// If a print lab order, then also display the print lab order status
                if (isset($entity['aws_order_id'])) {
	                $label = __('Resubmit to lab', 'nextgen-gallery-pro');
	                $nonce = wp_create_nonce('resubmit_lab_order');
	                $order = $entity['hash'];

					$lab_status_tmpl = '
						<span
							class="lab-order-status"
							data-order="%s"
							data-aws-order="%s"
							data-error-label="%s"
							data-error-msg="%s"
							data-cancel-label="%s"
                            data-success-label="%s"
                            data-not-done-label="%s"
                            data-confirm-payment-label="%s">
							(<span class="lab-order-status-label">%s</span> <i class="lab-order-status-icon fa fa-exclamation-circle"></i>)
						</span>
						<a
							href="#"
							data-success-label="%s"
							data-label="%s"
							data-alt-label="%s"
							data-nonce="%s"
							data-order="%s"
							class="resubmit-lab-order">
							%s
						</a>
					';

					$lab_status_el = sprintf(
						$lab_status_tmpl,
						esc_attr($order),
						esc_attr($entity['aws_order_id']),
						esc_attr(__('Error'), 'nextgen-gallery-pro'),
						esc_attr(__('There was a problem looking up your order status. Please try again later.', 'nextgen-gallery-pro')),
						esc_attr(__('Cancelled', 'nextgen-gallery-pro')),
                        esc_attr(__('Submitted', 'nextgen-gallery-pro')),
                        esc_attr(__('Processing', 'nextgen-gallery-pro')),
                        esc_attr(__('Click to confirm', 'nextgen-gallery-pro')),
						esc_attr(__('Checking...', 'nextgen-gallery-pro')),
						esc_attr(__('Resubmitted', 'nextgen-gallery-pro')),
						esc_attr($label),
						esc_attr(__('Resubmitting...', 'nextgen-gallery-pro')),
						esc_attr($nonce),
						esc_attr($order),
						esc_html($label)
					);
                }

                // Render elements
            	echo sprintf($tmpl, esc_attr(self::get_order_status_label($entity['status'])), $lab_status_el);
                break;
            case 'order_hash':
                echo sprintf('<a href="%s">%s</a>', get_edit_post_link($post), esc_html($post_id));
                break;
            case 'order_customer':
                $checkout = C_NextGen_Pro_Checkout::get_instance();
                $url = esc_attr($checkout->get_thank_you_page_url($entity['hash']));
                $name = esc_html($entity['customer_name']);
                echo "<a href='{$url}' target='_blank'>{$name}</a>";
                echo "<br/>";
                $link = strpos($entity['email'], '@') === FALSE ? FALSE : TRUE;
                if ($link)
                    echo "<a href='mailto:" . esc_attr($entity['email']) . "'>";
                echo esc_html($entity['email']);
                if ($link)
                    echo "</a>";
                break;
        }
    }

    static function get_order_status_label($status)
    {
        $labels = self::get_order_statuses();
        return isset($labels[$status])
            ? $labels[$status]
            : $status;
    }

    static function get_order_statuses()
    {
        return apply_filters('ngg_ecommerce_order_statuses', array(
            'all'               => __('All order statuses', 'nextgen-gallery-pro'),
            'paid'              => __('Paid', 'nextgen-gallery-pro'),
            'unpaid'            => __('Unpaid', 'nextgen-gallery-pro'),
            'awaiting_payment'  => __('Awaiting Payment', 'nextgen-gallery-pro'),
            'fraud'             => __('Fraud', 'nextgen-gallery-pro'),
            'failed'            => __('Failed', 'nextgen-gallery-pro')
        ));
    }

    function filter_orders_restrict_manage_posts($post_type = FALSE)
    {
        if ($post_type !== 'ngg_order') return;

        // List of possible order statuses
        $options = array();
        $statuses = self::get_order_statuses();

        // Sanitize
        foreach ($statuses as $key => $value) {
            $options[esc_attr($key)] = esc_html($value);
        }
        $statuses = $options;

        // Create options
        foreach ($statuses as $key => $value)
            $options[] = "<option value='{$key}'>{$value}</option>";
        $options = implode("\n", $options);

        echo "<select name='order_status'>{$options}</select>";
    }

    function filter_orders_pre_get_posts($query)
    {
        // TODO:
        // Need to alias verified as paid, and unverified as 'awaiting_payment'
        $meta_query = array();

        // Filter by order status
        if ($_REQUEST['order_status'] != 'all')
        {
            $meta_query[] = array(
                'key'   => 'status',
                'value' => urldecode($_REQUEST['order_status'])
            );
        }

        if (isset($_REQUEST['s']))
        {
            $query->set('s', NULL);
            $meta_query[] = array(
                'key'     => 'customer_name',
                'value'   => urldecode($_REQUEST['s']),
                'compare' => 'LIKE'
            );
        }

        if ($meta_query)
            $query->set('meta_query', $meta_query);
    }

    function register_forms()
    {
        // Add forms
        $forms = C_Form_Manager::get_instance();
	    $forms->add_form(NGG_PRO_ECOMMERCE_INSTRUCTIONS_PAGE, NGG_PRO_ECOMMERCE_INSTRUCTIONS_FORM);

	    $forms->add_form(NGG_PRO_ECOMMERCE_OPTIONS_PAGE, NGG_PRO_ECOMMERCE_OPTIONS_FORM);
	    $forms->add_form(NGG_PRO_ECOMMERCE_OPTIONS_PAGE, NGG_PRO_MAIL_FORM);
	    $forms->add_form(NGG_PRO_ECOMMERCE_OPTIONS_PAGE, NGG_PRO_PAYMENT_GATEWAY_FORM);
	    $forms->add_form(NGG_PRO_ECOMMERCE_OPTIONS_PAGE, NGG_PRO_ECOMMERCE_PRINTLAB_FORM);

	    $forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_MANUAL_PRICELIST_SOURCE);
	    $forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_ECOMMERCE_CATEGORY_PRINTS);
	    $forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_ECOMMERCE_CATEGORY_CANVAS);
	    $forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_ECOMMERCE_CATEGORY_MOUNTED_PRINTS);
	    //$forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_ECOMMERCE_CATEGORY_METAL_PRINTS);
	    $forms->add_form(NGG_PRO_PRICELIST_CATEGORY_PAGE, NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS);
    }

    /**
     * Hides the quick edit button to avoid users changing the post_status of a pricelist
     * @param $actions
     * @return mixed
     */
    function hide_quick_edit_link($actions, $post)
    {
        $retval = array();

	    if ($post->post_type != 'ngg_order') {
		    if (!empty($actions['edit']))
                $retval['edit'] = $actions['edit'];
                
            if (!empty($actions['trash']))
            $retval['trash'] = $actions['trash'];
		    if (!empty($actions['untrash']))
			    $retval['untrash'] = $actions['untrash'];

		    if ($post->post_type == 'ngg_pricelist')
		    {
			    $url = esc_attr(admin_url('/edit.php?post_type=ngg_pricelist&ngg_duplicate=1&id='.$post->ID));
                $retval['ngg_duplicate_pricelist'] = "<a href='{$url}'>".__('Duplicate', 'nextgen-gallery-pro')."</a>";
                
                unset($retval['trash']);
		    }

		    
	    }

        return $retval;
    }

    function enqueue_flush_cart()
    {
        self::enqueue_cart_resources();

        $router = C_Router::get_instance();
        wp_enqueue_script(
            'ngg_ecommerce_clear_cart',
            $router->get_static_url('photocrati-nextgen_pro_ecommerce#clear_cart.js'),
            array('photocrati_ajax', 'ngg_pro_cart'),
            NGG_PRO_ECOMMERCE_MODULE_VERSION,
            TRUE
        );
    }

    function serve_ecommerce_pages($posts, $query)
    {
        if ($query->is_main_query()) {

            if (isset($_REQUEST['ngg_pro_digital_downloads_page'])) {
                $post = new stdClass;
                $post->name = 'ngg_pro_digital_downloads_page';
                $post->post_title = __('Digital Downloads', 'nextgen-gallery-pro');
                $post->post_parent = 0;
                $post->post_content = "[ngg_pro_digital_downloads]";
                $post->post_type = 'page';
                $posts = array($post);
                $query->is_singular = TRUE;
                $query->is_page = TRUE;
                $query->is_home = FALSE;
            }
            elseif (isset($_REQUEST['ngg_pro_checkout_page'])) {
                $post = new stdClass;
                $post->name = 'ngg_pro_checkout_page';
                $post->post_title = __('Checkout', 'nextgen-gallery-pro');
                $post->post_parent = 0;
                $post->post_content = "[ngg_pro_checkout]";
                $post->post_type = 'page';
                $post->comment_status = 'closed';
                $posts = array($post);
                $query->is_singular = TRUE;
                $query->is_page = TRUE;
                $query->is_home = FALSE;
            }
            elseif (isset($_REQUEST['ngg_pro_return_page'])) {
                $post = new stdClass;
                $post->name = 'ngg_pro_return_page';
                $post->post_title = __('Order Details', 'nextgen-gallery-pro');
                $post->post_parent = 0;
                $post->post_content = "[ngg_pro_order_details]";
                $post->post_type = 'page';
                $post->comment_status = 'closed';
                $posts = array($post);
                $query->is_singular = TRUE;
                $query->is_page = TRUE;
                $query->is_home = FALSE;
            }
            elseif (isset($_REQUEST['ngg_pro_cancel_page'])) {
                $post = new stdClass;
                $post->name = 'ngg_pro_return_page';
                $post->post_title = __('Order Cancelled', 'nextgen-gallery-pro');
                $post->post_parent = 0;
                $post->post_content = __('Your order was cancelled', 'nextgen-gallery-pro');
                $post->post_type = 'page';
                $post->comment_status = 'closed';
                $posts = array($post);
                $query->is_singular = TRUE;
                $query->is_page = TRUE;
                $query->is_home = FALSE;
            }
            elseif (isset($_REQUEST['ngg_pro_verify_page'])) {
                $post = new stdClass;
                $post->name = 'ngg_pro_verifying_order_page';
                $post->post_title = __('Verifying order', 'nextgen-gallery-pro');
                $post->post_parent = 0;
                $post->post_content = '[ngg_pro_verify_order]';
                $post->post_type = 'page';
                $post->comment_status = 'closed';
                $posts = array($post);
                $query->is_singular = TRUE;
                $query->is_page = TRUE;
                $query->is_home = FALSE;
            }

            remove_filter('posts_results', array($this, 'serve_ecommerce_pages'), 10, 2);
        }

        return $posts;
    }

    function render_cart_count()
    {
        self::enqueue_cart_resources();
        return "<script type=text/javascript'>document.write(Ngg_Pro_Cart.get_instance().length);</script>";
    }

    function render_checkout_form()
    {
        $checkout = C_NextGen_Pro_Checkout::get_instance();
        return $checkout->checkout_form();
    }

    function render_digital_downloads()
    {
        $controller = C_Digital_Downloads::get_instance();
        return $controller->index_action();
    }

    function render_order_verification()
    {
        $controller = C_NextGen_Pro_Order_Verification::get_instance();
        return $controller->render($_REQUEST['order']);
    }

    function render_order_details($attrs=array(), $inner_content='')
    {
        $retval = __('Oops! This page usually displays details for image purchases, but you have not ordered any images yet. Please feel free to continue browsing. Thanks for visiting.', 'nextgen-gallery-pro');

        // Get the order to display
        $order_id   = FALSE;
        $method     = FALSE;
        if     (isset($attrs['order'])) {
            $order_id = $attrs['order'];
            $method = 'find_by_hash';
        }
        elseif (isset($_REQUEST['order'])) {
            $order_id = $_REQUEST['order'];
            $method = 'find_by_hash';
        }
        elseif (isset($attrs['order_id'])) {
            $order_id = $attrs['order_id'];
            $method = 'find';
        }

        // If we have an order, continue...
        if ($method && (($order = C_Order_Mapper::get_instance()->$method($order_id, TRUE)))) {

            // If no inner connect has been added, then use our own
            if (!$inner_content) $inner_content = __("Thank you for your order, [customer_name]. You ordered the following items:
                [items]

                <h3>Order Details</h3>
                <p>
                Subtotal: [subtotal_amount]<br/>
                [if_used_coupon]Discount: [discount_amount]<br/>[/if_used_coupon]
                [if_ordered_shippable_items]Shipping: [shipping_amount]<br/>[/if_ordered_shippable_items]
                [if_has_tax]Tax: [tax_amount]<br/>[/if_has_tax]
                Total: [total_amount]<br/>
                </p>

                [if_ordered_shippable_items]
                <p>
                We will be shipping your items to:<br/>
                [shipping_street_address]<br/>
                [if_shipping_has_address_line]
                    [shipping_address_line]<br/>
                [/if_shipping_has_address_line]

                [shipping_city], [shipping_state] [shipping_zip]<br/>
                [shipping_country]
                </p>
                [/if_ordered_shippable_items]

                [if_ordered_digital_downloads]
                <h3>Digital Downloads</h3>
                <p>You may download your digital products <a href='[digital_downloads_page_url]'>here.</a></p>
                [/if_ordered_digital_downloads]
            ", 'nextgen-gallery-pro');

            $retval = apply_filters('ngg_order_details_content', $inner_content);

            // Add some other values to the order object
            $other_values = array(
                'subtotal'                      =>  $order->get_cart()->get_subtotal(),
                'subtotal_amount'               =>  $order->get_cart()->get_subtotal(),
                'shipping'                      =>  $order->get_cart()->get_shipping(),
                'shipping_amount'               =>  $order->get_cart()->get_shipping(),
                'digital_downloads_page_url'    =>  $this->get_digital_downloads_url($order->hash),
                'total'                         =>  $order->get_cart()->get_total(),
                'total_amount'                  =>  $order->get_cart()->get_total(),
                'discount_amount'               =>  M_NextGen_Pro_Coupons::get_order_discount_amount($order),
                'tax'                           =>  $order->get_cart()->get_tax(),
                'tax_amount'                    =>  $order->get_cart()->get_tax(),
            );

            $printlab_order = null;
            // Check ifPrintlab fullfilled item
            if (isset($order->aws_order_id) && is_admin())
            {
            	$printlab_order = self::get_lab_order_status($order->hash);
                $printlab_message = '';
                if ($printlab_order['status_message'])
                	$printlab_message = ' (' . $printlab_order['status_message'] . ')';

                $other_values['cost_of_goods'] = $printlab_order['total_cost'];
                $other_values['printlab_status_full'] = $printlab_order['status'] . $printlab_message;
                $other_values['printlab_pdf_invoice_link'] = $printlab_order['invoice_pdf_url'];
            }

            foreach ($other_values as $key => $value) $order->$key = $value;

            // Substitute placeholders for each variable of the order
            foreach (get_object_vars($order->get_entity()) as $key => $value) {
                $escape = TRUE;
                switch ($key) {
                    case 'ID':
                        $key = 'order_id';
                        break;
                    case 'post_date':
                        $key = 'order_date';
                        break;
                    case 'post_author':
                    case 'post_title':
                    case 'post_excerpt':
                    case 'post_status':
                    case 'comment_status':
                    case 'ping_status':
                    case 'post_name':
                    case 'to_ping':
                    case 'pinged':
                    case 'post_content_filtered':
                    case 'post_content':
                    case 'menu_order':
                    case 'post_type':
                        break;
                    case 'meta_value':
                        $key = 'order_hash';
                        break;
                    case 'total_amount':
                    case 'shipping':
                    case 'shipping_amount':
                    case 'subtotal':
                    case 'subtotal_amount':
                    case 'tax':
                    case 'tax_amount':
	                case 'cost_of_goods':
                        $value = self::get_formatted_price($value, $order->get_cart()->get_currency());
                        $escape = FALSE;
                        break;
                }
                if (!is_array($value)) $retval = str_replace("[{$key}]", ($escape ? esc_html($value): $value), $retval);
            };

            // Parse [if_ordered_shippable_items]
            $open_tag   = preg_quote("[if_ordered_shippable_items]", '#');
            $close_tag  = preg_quote("[/if_ordered_shippable_items]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $order->get_cart()->has_shippable_items() ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }
            
            // Parse [if_shipping_has_phone]
            $open_tag   = preg_quote("[if_shipping_has_phone]", '#');
            $close_tag  = preg_quote("[/if_shipping_has_phone]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $order->shipping_phone ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }
            
            // Parse [if_shipping_has_address_line]
            $open_tag   = preg_quote("[if_shipping_has_address_line]", '#');
            $close_tag  = preg_quote("[/if_shipping_has_address_line]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $order->shipping_address_line ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_used_coupon]
            $open_tag    = preg_quote("[if_used_coupon]", '#');
            $close_tag   = preg_quote("[/if_used_coupon]", '#');
            $regex       = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_coupon = FALSE;
            if (!empty($order->cart['coupon']) && is_array($order->cart['coupon']))
                $show_coupon = TRUE;
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_coupon ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_ordered_digital_downloads]
            $open_tag   = preg_quote("[if_ordered_digital_downloads]", '#');
            $close_tag  = preg_quote("[/if_ordered_digital_downloads]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_downloads = FALSE;
            if ($order->get_cart()->has_digital_downloads() && $order->status == 'paid')
                $show_downloads = TRUE;
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_downloads ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_ordered_printlab_items]
            $open_tag   = preg_quote("[if_ordered_printlab_items]", '#');
            $close_tag  = preg_quote("[/if_ordered_printlab_items]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_printlab = $printlab_order != null;
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_printlab ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_printlab_order_success]
            $open_tag   = preg_quote("[if_printlab_order_success]", '#');
            $close_tag  = preg_quote("[/if_printlab_order_success]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_printlab = $printlab_order != null && strtolower($printlab_order['status']) != 'error';
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_printlab ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_printlab_pdf_invoice_link]
            $open_tag   = preg_quote("[if_printlab_pdf_invoice_link]", '#');
            $close_tag  = preg_quote("[/if_printlab_pdf_invoice_link]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_pdf_link = empty($order->printlab_pdf_invoice_link) ? FALSE : TRUE;
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_pdf_link ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Parse [if_has_tax]
            $open_tag   = preg_quote("[if_has_tax]", '#');
            $close_tag  = preg_quote("[/if_has_tax]", '#');
            $regex      = "#{$open_tag}(.*?){$close_tag}#ms";
            $show_tax = FALSE;
            if ($order->get_cart()->get_tax())
                $show_tax = TRUE;
            if (preg_match_all($regex, $retval, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $replacement = $show_tax ? $match[1] : '';
                    $retval = str_replace($match[0], $replacement, $retval);
                }
            }

            // Render cart
            if (strpos($retval, '[items]') !== FALSE) {
                $retval = str_replace(
                    '[items]',
                    C_NextGen_Pro_Order_Controller::get_instance()->render($order->get_cart()),
                    $retval
                );
            }

            $retval = apply_filters('ngg_order_details', $retval, $order);

            // Unset any variables on the order we may have set
            foreach ($other_values as $key => $value) unset($order->$key);
        }

        return $retval;
    }

    /**
     * @param string $aws_order_id
     * @return array|null
     */
    static function get_lab_order_status($order_hash)
    {
	    if ($order_hash != null)
	    {
		    $res = wp_remote_post('https://850t23mohg.execute-api.us-east-1.amazonaws.com/latest/', array(
			    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			    'body'        => json_encode(array('order' => $order_hash)),
			    'method'      => 'POST',
			    'data_format' => 'body',
		    ));

		    if (!($res instanceof WP_Error)) {
			    $body = $res['body'];

			    if (is_string($body))
				    $body = json_decode($body, true);

			    $currency = C_NextGen_Pro_Currencies::$currencies[840]; // USD;

			    if (!isset($body['statusCode']))
				    $body['statusCode'] = 'Error';

			    if (!isset($body['errorMessage']) && isset($body['message']))
				    $body['errorMessage'] = $body['message'];

			    if (isset($body['errorMessage']) && !isset($body['message'])) {
			    	$body['errorMessage'] = __("Could not determine print lab order status at this time. Please check again later.", "nextgen-gallery-pro");
			    }

			    return array(
				    'status' => ($body['statusCode'] == 'Submitted' ? 'Submitted' : 'Error'),
				    'status_code' => $body['statusCode'],
				    'status_important' => !empty($body['errorMessage']),
				    'status_message' => !empty($body['errorMessage']) ? $body['errorMessage'] : null,
				    'total_cost' => !empty($body['costOfGoods']) ? floatval($body['costOfGoods']) : null,
				    'invoice_pdf_url' => !empty($body['invoicePdf']) ? $body['invoicePdf'] : '',
			    );
		    }
		    else {
			    return array(
				    'status' => 'Error',
				    'status_code' => 'E_HTTP_REQUEST',
				    'status_important' => true,
				    'status_message' => $res->get_error_message(),
				    'total_cost' => 'N/A',
				    'invoice_pdf_url' => '',
			    );
		    }
	    }

	    return null;
    }

    static function get_formatted_price($value, $country=FALSE, $use_fontawesome=TRUE)
    {
        return sprintf(self::get_price_format_string($country, $use_fontawesome), $value);
    }

	static function get_formatted_price_without_symbol($value, $currency=FALSE)
	{
		return sprintf(self::get_price_format_string_without_symbol($currency), $value);
	}

	static function get_price_format_string_without_symbol($currency=FALSE)
	{
		$settings = C_NextGen_Settings::get_instance();

		if (empty($currency))
			$currency = $settings->ecommerce_currency;

		$currency = C_NextGen_Pro_Currencies::$currencies[$currency];

		return "%.{$currency['exponent']}f";
    }
    
    static function is_stripe_test_mode_enabled()
    {
        return defined('NGG_PRO_ECOMMERCE_STRIPE_TESTING') && NGG_PRO_ECOMMERCE_STRIPE_TESTING;
    }

	static function get_price_format_string($currency = FALSE, $use_fontawesome=TRUE, $use_string_placeholder=FALSE)
	{
		$settings = C_NextGen_Settings::get_instance();

		if (empty($currency))
			$currency = $settings->ecommerce_currency;

		$currency = C_NextGen_Pro_Currencies::$currencies[$currency];

		if (!empty($currency['fontawesome']) AND $use_fontawesome)
		{
			$symbol = $currency['fontawesome'];
			$symbol = "<i class='fa {$symbol}'></i>";
		}
		else {
			// decode so we don't send &#8364;2.01 when we want €2.01 and we send mail as text (not html)
			$symbol = html_entity_decode($currency['symbol']);
		}

		$retval = $use_string_placeholder ? "%s" : "%.{$currency['exponent']}f";


		$locale = localeconv();
		if (defined('WPLANG')) {
			$original_locale = setlocale(LC_MONETARY, '0');
			setlocale(LC_MONETARY, WPLANG);
			$locale = localeconv();
			setlocale(LC_MONETARY, $original_locale);
		}

		$space = '';
//        if ($locale['p_sep_by_space'])
//            $space = ' ';

		if (array_key_exists('p_cs_precedes', $locale) && $locale['p_cs_precedes'])
			$retval = $symbol . $space . $retval;
		else
			$retval = $retval . $space . $symbol;


		return $retval;
	}

	/**
	 * Determines if an image meets the minimum requirements WHCC and for the pricelist item
	 *
	 * @param stdClass $item
	 * @param string|C_Image $image_or_id
	 * @return bool
	 */
    static function does_item_meet_minimum_requirements($item, $image_or_id)
    {
        $storage = C_Gallery_Storage::get_instance();

        if (is_object($image_or_id))
            /** @var stdClass $image */
            $image = $image_or_id;
        else
            $image = C_Image_Mapper::get_instance()->find($image_or_id);

        $source_data = $item->source_data;

        $extension = M_I18n::mb_pathinfo($image->filename, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['jpeg', 'jpg', 'jpeg_backup', 'jpg_backup']))
            return FALSE;

        if (isset($source_data['lab_properties']['minimum_resolution']))
        {
            $backup_dims      = $storage->get_backup_dimensions($image);
            $min_resolution   = $source_data['lab_properties']['minimum_resolution'];
            $backup_largest   = $backup_dims['width'] > $backup_dims['height'] ? $backup_dims['width'] : $backup_dims['height'];
            $backup_smallest  = $backup_dims['width'] < $backup_dims['height'] ? $backup_dims['width'] : $backup_dims['height'];
            $minimum_largest  = $min_resolution['W'] > $min_resolution['H'] ? $min_resolution['W'] : $min_resolution['H'];
            $minimum_smallest = $min_resolution['W'] < $min_resolution['H'] ? $min_resolution['W'] : $min_resolution['H'];

            if ($backup_largest < $minimum_largest || $backup_smallest < $minimum_smallest)
                return FALSE;
        }

        return TRUE;
    }

    static function enqueue_cart_resources()
    {
        $router = C_Router::get_instance();

        if (!wp_script_is('sprintf'))
            wp_register_script('sprintf', $router->get_static_url('photocrati-nextgen_pro_ecommerce#sprintf.js'));

        $cart_dependencies = [
            'photocrati_ajax', 'backbone', 'underscore', 'sprintf', 'jquery'
        ];

        $use_cookies = (bool) C_NextGen_Settings::get_instance()->get('ecommerce_cookies_enable', TRUE);
        if (!$use_cookies)
        {
            wp_register_script('ngg_basil_storage', $router->get_static_url('photocrati-nextgen_pro_ecommerce#basil.min.js'));
            $cart_dependencies[] = 'ngg_basil_storage';
        }

        wp_enqueue_script('js-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@2.2.0/src/js.cookie.min.js', array(), '2.2.0');        

        wp_register_script(
            'ngg_pro_cart',
            $router->get_static_url('photocrati-nextgen_pro_ecommerce#cart.js'),
            $cart_dependencies,
            NGG_PRO_ECOMMERCE_MODULE_VERSION,
            TRUE // all NGG lightboxes are enqueued in the footer
        );

        $sources = array();
        $source_manager = C_Pricelist_Source_Manager::get_instance();
        foreach ($source_manager->get_ids() as $source_id) {
            $sources[$source_id] = $source_manager->get($source_id, 'lab_fulfilled');
        }

        wp_enqueue_script('ngg_pro_cart');
        wp_localize_script('ngg_pro_cart', 'Ngg_Pro_Cart_Settings', array(
            'currency_format'    =>   M_NextGen_Pro_Ecommerce::get_price_format_string(),
            'checkout_url'       =>   M_NextGen_Pro_Ecommerce::get_checkout_page_url(),
	        // Because wp_localize_script() doesn't much care for booleans or the string '0'
            'use_cookies'        => ($use_cookies ? 'true' : 'false'),
            'country_list_json_url' => $router->get_static_url('photocrati-nextgen_pro_ecommerce#country_list.json'),
            'i18n'               => $i18n = C_NextGen_Pro_Checkout::get_instance()->get_i18n_strings(),
            'sources'            =>   $sources
        ));
    }

    static function is_windows_hosting()
    {
        return isset($_SERVER['OS']) && preg_match("/windows/i", $_SERVER['OS']);
    }

    static function get_country_list_json_url()
    {
        $router = C_Router::get_instance();

        return self::is_windows_hosting()
            ?   $router->get_static_url('photocrati-nextgen_pro_ecommerce#country_list.txt')
            :   $router->get_static_url('photocrati-nextgen_pro_ecommerce#country_list.json');
    }

    function enqueue_backend_resources()
    {
        $router = C_Router::get_instance();

        // When the Other Options page is opened, we're going to use JS to change the label
        // of the "Change options" capatibility in the Roles tab
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'ngg_other_options') {
            wp_enqueue_script('ngg_ecommerce_roles', $router->get_static_url('photocrati-nextgen_pro_ecommerce#roles.js'));
            wp_localize_script(
                'ngg_ecommerce_roles',
                'ngg_change_options_note',
                array(__('(includes Ecommerce Options)', 'nextgen-gallery-pro'))
            );
        }
        else if (isset($_REQUEST['post']))
        {
            $order = C_Order_Mapper::get_instance()->find($_REQUEST['post'], TRUE);

            if ($order != null)
            {
                wp_enqueue_style('ngg_order_printing', $router->get_static_url('photocrati-nextgen_pro_ecommerce#order_printing.css'));
            }
        }

	    // TODO: We should have a means of determining whether a post type or page is provided by NextGEN Pro or NextGEN Plus,
	    // and if so, to enqueue a common admin.css file
	    // Load ecommerce admin css file for orders admin pages
	    if ( isset($_REQUEST['post_type']) && ($_REQUEST['post_type'] == 'ngg_order') ) {
		    wp_enqueue_style( 'ngg_order_admin', $router->get_static_url('photocrati-nextgen_pro_ecommerce#admin.css'), array(), $this->module_version);
            wp_enqueue_script('ngg_order_admin', $router->get_static_url('photocrati-nextgen_pro_ecommerce#manage_orders.js'), array('photocrati_ajax'), $this->module_version);
            wp_localize_script('ngg_order_admin', 'ngg_order_i18n', array(
                'mark_as_paid_prompt' => __('A print lab order will be submitted for any orders that include any print lab items and you will be billed for the cost of goods. Once submitted, the print lab order cannot be cancelled. Continue to Mark as Paid?', 'nextgen-gallery-pro')
            ));
	    }
    }

    function enqueue_resources()
    {
        $router = null;

        if (!wp_script_is('sprintf')) {
            $router = $router ? $router : C_Router::get_instance();
            wp_register_script('sprintf', $router->get_static_url('photocrati-nextgen_pro_ecommerce#sprintf.js'));
        }

        // When the ngg_order_complete parameter is present in the querystring, then
        // we'll enqueue ngg_order_complete and then use the_posts filter to append a <script> tag
        // to the post content, which will delete the cart
        if (isset($_REQUEST['ngg_order_complete'])) {
            wp_enqueue_script('photocrati_ajax');
        }

        // When the pro lightbox is selected as the desired lightbox effect, we will
        // enqueue some JS to extend the Pro Lightbox with the add to cart functionality
        $settings = C_NextGen_Settings::get_instance();
        if (is_null($settings->thumbEffectContext)) $settings->thumbEffectContext = '';
        if ($settings->thumbEffect == 'NGG_PRO_LIGHTBOX' && $settings->thumbEffectContext != 'nextgen_images') {
            $router = $router ? $router : C_Router::get_instance();
            wp_enqueue_script('ngg_nplmodal_ecommerce', $router->get_static_url('photocrati-nextgen_pro_ecommerce#nplmodal_overrides.js'));
        }

        $style = $settings->ecommerce_cart_menu_item;

        // make sure to enqueue fontawesome to get the icon when needed
        if ($style && $style != 'none')
        {
            $this->enqueue_fontawesome();
            self::enqueue_cart_resources();
        }
    }

    public function enqueue_fontawesome()
    {
        // Enqueue fontawesome
        if (method_exists('M_Gallery_Display', 'enqueue_fontawesome'))
            M_Gallery_Display::enqueue_fontawesome();
        wp_enqueue_style('fontawesome');
    }

    // Used for the Manage Orders and Manage Pricelists page; disables WordPress from trying to autosave these
    // two custom post types when managing them
    public function dequeue_autosave()
    {
        wp_dequeue_script('autosave');
    }

    function nav_menu_objects($menu_objects, $args)
    {
        $settings = C_NextGen_Settings::get_instance();
        $style = $settings->ecommerce_cart_menu_item;

        if ($style && $style != 'none') {
            foreach ($menu_objects as &$menu_item) {
                if ($menu_item->type == 'post_type' && $menu_item->object == 'page' && $menu_item->object_id == $settings->ecommerce_page_checkout) {
                    $css_classes = array('nextgen-menu-item-cart');
                    $class = 'nextgen-menu-item-cart';
                    $add_total = false;

                    switch ($style) {
                        case 'icon':
                        case 'icon_with_items':
                            $menu_item->title = '<i class="fa fa-shopping-cart nextgen-menu-cart-icon nextgen-menu-cart-icon-'.$style.'" style="display:none"></i>';
                            break;
                        case 'icon_and_total':
                        case 'icon_and_total_with_items':
                            $menu_item->title = '<i class="fa fa-shopping-cart nextgen-menu-cart-icon nextgen-menu-cart-icon-'.$style.'" style="display:none"></i> (#)';
                            $add_total = true;
                            break;
                    }

                    if ($add_total) {
                        $placeholder = '<span class="nextgen-menu-cart-placeholder"></span>';
                        $menu_title = $menu_item->title;
                        $menu_item->title = preg_replace('/\\(\\s*\\#\\s*\\)/i', $placeholder, $menu_title);

                        if ($menu_item->title == $menu_title)
                            $menu_item->title = rtrim($menu_title) . ' ' . $placeholder;
                    }

                    $menu_item->classes = array_unique(array_merge($menu_item->classes, $css_classes));
                }
            }
        }

        return $menu_objects;
    }

    function register_post_types()
    {
        register_post_type('ngg_pricelist', array(
            'show_ui'               =>  TRUE,
            'labels'                =>  array(
                'name'              =>  __('Pricelists', 'nextgen-gallery-pro'),
                'singular_name'     =>  __('Pricelist', 'nextgen-gallery-pro'),
                'menu_name'         =>  __('Pricelist', 'nextgen-gallery-pro'),
                'add_new_item'      =>  __('Add New Pricelist', 'nextgen-gallery-pro'),
                'edit_item'         =>  __('Edit Pricelist', 'nextgen-gallery-pro'),
                'new_item'          =>  __('New Pricelist', 'nextgen-gallery-pro'),
                'view_item'         =>  __('View Pricelist', 'nextgen-gallery-pro'),
                'search_items'      =>  __('Search Pricelists', 'nextgen-gallery-pro'),
                'not_found'         =>  __('No pricelists found', 'nextgen-gallery-pro'),
            ),
            'publicly_queryable'    =>  FALSE,
            'exclude_from_search'   =>  TRUE,
            'supports'              =>  array('title'),
            'show_in_menu'          =>  FALSE
        ));

        register_post_type('ngg_pricelist_item', array(
            'label'                 =>  __('Pricelist Item', 'nextgen-gallery-pro'),
            'publicly_queryable'    =>  FALSE,
            'exclude_from_search'   =>  TRUE
        ));

        register_post_type('ngg_order', array(
            'show_ui'               =>  TRUE,
            'labels'                =>  array(
                'name'              =>  __('Orders', 'nextgen-gallery-pro'),
                'singular_name'     =>  __('Order', 'nextgen-gallery-pro'),
                'menu_name'         =>  __('Orders', 'nextgen-gallery-pro'),
                'add_new_item'      =>  __('Add New Order', 'nextgen-gallery-pro'),
                'edit_item'         =>  __('View Order', 'nextgen-gallery-pro'),
                'new_item'          =>  __('New Order', 'nextgen-gallery-pro'),
                'view_item'         =>  __('View Order', 'nextgen-gallery-pro'),
                'search_items'      =>  __('Search Orders', 'nextgen-gallery-pro'),
                'not_found'         =>  __('No orders found', 'nextgen-gallery-pro'),
            ),
            'supports' => FALSE,
            'register_meta_box_cb' => array($this, 'order_meta_boxes'),
            'publicly_queryable'    =>  FALSE,
            'exclude_from_search'   =>  TRUE,
            'show_in_menu'          =>  FALSE,
            'map_meta_cap'          =>  TRUE,
            'capabilities'          =>  array(
                'create_posts'      =>  FALSE,
                'edit_post'         =>  'edit_post',
                'edit_posts'        =>  'edit_posts'
            )
        ));
    }

    function order_meta_boxes($order_post)
    {
        add_meta_box('ngg_order_details', __('Order Details', 'nextgen-gallery-pro'), array($this, 'render_meta_box_order_details'));
    }

    function render_meta_box_order_details($first)
    {
        $inner_content = __("The customer [customer_name] ordered the following items:
            [items]

            <h3>Order Details</h3>
            <p>
            Subtotal: [subtotal_amount]<br/>
            [if_used_coupon]Discount: [discount_amount]<br/>[/if_used_coupon]
            [if_ordered_shippable_items]Shipping: [shipping_amount]<br/>[/if_ordered_shippable_items]
            [if_has_tax]Tax: [tax_amount]<br/>[/if_has_tax]
            Total: [total_amount]<br/>
            [if_ordered_printlab_items]
                Printlab Status: [printlab_status_full]</br>
            [/if_ordered_printlab_items]
            [if_printlab_order_success]
                Cost of Goods: [cost_of_goods]<br/>
                [if_printlab_pdf_invoice_link]
                    Invoice: <a target='_blank' href='[printlab_pdf_invoice_link]'>View PDF</a></br>
                [/if_printlab_pdf_invoice_link]
            [/if_printlab_order_success]
            </p>

            [if_ordered_shippable_items]
            <p>
            We will be shipping your items to:<br/>
            [shipping_street_address]<br/>
            [if_shipping_has_address_line]
            [shipping_address_line]<br/>
            [/if_shipping_has_address_line]
            [shipping_city], [shipping_state] [shipping_zip]<br/>
            [shipping_country]<br/>
            [if_shipping_has_phone]
            Ph: [shipping_phone]
            [/if_shipping_has_phone]
            </p>
            [/if_ordered_shippable_items]

            [if_ordered_digital_downloads]
            <h3>Digital Downloads</h3>
            <p>Download link for digital products: <a href='[digital_downloads_page_url]'>here.</a></p>
            [/if_ordered_digital_downloads]
        ", 'nextgen-gallery-pro');

        echo $this->render_order_details(array('order_id' => $first->ID), $inner_content);
    }

    /**
     * Provides new settings to every display type
     */
    function register_display_type_settings()
    {
        foreach (C_Display_type_Mapper::get_instance()->find_all() as $display_type) {
            $this->get_registry()->add_adapter('I_Form', 'A_Display_Type_Ecommerce_Form', $display_type->name);
        }
    }

    static function get_checkout_page_url($scheme=NULL)
    {
        $retval = site_url('/?ngg_pro_checkout_page=1', $scheme);
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->ecommerce_page_checkout) {
            $retval = get_page_link($settings->ecommerce_page_checkout);
        }
        return $retval;
    }

    function get_digital_downloads_url($order_hash)
    {
        $retval = site_url('?ngg_pro_digital_downloads_page=1');
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->ecommerce_page_digital_downloads) {
            $retval = get_page_link($settings->ecommerce_page_digital_downloads);
        }

        $retval = add_query_arg('order', $order_hash, $retval);

        return $retval;
    }

    /**
     * We want to display our form for adding pricelists on the same page that lists all pricelists. We choose
     * to do that when the 'ngg_edit' parameter is present in the querystring. Because WordPress exposes no hooks
     * to override the contents of the page, we use what hooks are available to start a buffer, flush the
     * original contents, and then output our own contents
     *
     * We start the buffer using the admin_all_notices hook
     */
    function buffer_for_manage_pricelist_page()
    {
        ob_start();
    }

	/**
	 * See the inline doc for buffer_for_manage_pricelist_page() for more details. This method is used
	 * to flush the buffer and output our own content for the Manage Pricelists page
	 */
	function render_manage_pricelist_page()
	{
		// WP uses a parameter called 'action', so we have to temporary call it 'ngg_action'
		if (isset($_REQUEST['ngg_action']))
			$_POST['action'] = $_REQUEST['action'] = $_REQUEST['ngg_action'];

		ob_end_clean();

		$page = C_Pricelist_Category_Page::get_instance();
		$page->index_action();

        echo '<div class="clear"></div></div><!-- wpbody-content -->
<div class="clear"></div></div><!-- wpbody -->
<div class="clear"></div></div><!-- wpcontent -->

<div id="wpfooter">';
    }

    function redirect_to_manage_pricelist_page()
    {
        if (strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/post-new.php') !== FALSE && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'ngg_pricelist') {
            wp_redirect(add_query_arg($_GET, admin_url('/edit.php?post_type=ngg_pricelist&ngg_edit=1')));
        }
    }

    function custom_edit_link($url)
    {
        global $post;

        // we can't always assume $post exists
        if (empty($post))
            return $url;

        if ($post->post_type == 'ngg_pricelist') {
            $url = admin_url('/edit.php?post_type=ngg_pricelist&ngg_edit=1&id='.$post->ID);
        }
        elseif ($post->post_type == 'ngg_order') {
            // $mapper = C_Order_Mapper::get_instance();
            // if (($order = $mapper->find($post->ID))) {
            //     $checkout = C_NextGen_Pro_Checkout::get_instance();
            //     $url = $checkout->get_thank_you_page_url($order->hash);
            // }
        }

        return $url;
    }

    /**
     * Adds a pricelist field for galleries
     * @param $fields
     * @param $gallery
     * @return mixed
     */
    function add_gallery_pricelist_field($fields, $gallery)
    {
        $fields['right']['pricelist'] = array(
            'label'         =>  __('Pricelist', 'nextgen-gallery-pro'),
            'id'            =>  'gallery_pricelist',
            'callback'      =>  array(&$this, 'render_gallery_pricelist_field')
        );
        return $fields;
    }

    /**
     * Adds another column on the Manage Galleries (edit mode) page
     * @param $columns
     * @return mixed
     */
    function add_ecommerce_column($columns)
    {
        $columns += 1;

        add_filter(
            "ngg_manage_images_column_{$columns}_header",
            array(&$this, 'render_ecommerce_column_header'),
            20,
            2
        );

        add_filter(
            "ngg_manage_images_column_{$columns}_content",
            array(&$this, 'render_ecommerce_column'),
            20,
            2
        );

        return $columns;
    }

    function render_ecommerce_column_header()
    {
        return __('Ecommerce', 'nextgen-gallery-pro');
    }

    function render_ecommerce_column($output, $picture)
    {
        $image_id               = $picture->{$picture->id_field};
        $mapper                 = C_Pricelist_Mapper::get_instance();
        $gallery_default_label  = esc_html__("Use gallery's pricelist", 'nextgen-gallery-pro');
        $selected_pricelist_id  = isset($picture->pricelist_id) ? $picture->pricelist_id : 0;
        $selected               = selected($selected_pricelist_id, 0, FALSE);
        $none_selected          = selected($selected_pricelist_id, -1, FALSE);
        $no_pricelist_label     = esc_html__("None (not for sale)", 'nextgen-gallery-pro');

        $out = array();
        $out[] = "<select id='image_{$image_id}_pricelist' name='images[{$image_id}][pricelist_id]'>";
        $out[] = "<option {$selected} value='0'>{$gallery_default_label}</option>";
        $out[] = "<option {$none_selected} value='-1'>{$no_pricelist_label}</option>";

        foreach ($mapper->find_all() as $pricelist) {
            $pricelist_id       = esc_attr($pricelist->{$pricelist->id_field});
            $pricelist_title    = esc_html($pricelist->title);
            $selected           = selected($selected_pricelist_id, $pricelist_id, FALSE);
            $out[] = "<option {$selected} value='{$pricelist_id}'>{$pricelist_title}</option>";
        }

        $out[] = "</select>";

        return $output.implode("\n", $out);
    }

    /**
     * Renders the gallery pricelist field
     */
    function render_gallery_pricelist_field($gallery)
    {
        $mapper = C_Pricelist_Mapper::get_instance();
        $selected_pricelist_id = 0;
        if (($selected_pricelist = $mapper->find_for_gallery($gallery))) {
            $selected_pricelist_id = $selected_pricelist->{$selected_pricelist->id_field};
        }

        echo "<select name='pricelist_id' id='gallery_pricelist'>";
        $selected = selected($selected_pricelist_id, 0, FALSE);
        echo "<option value='0' {$selected}>" . __('None', 'nextgen-gallery-pro') . "</option>";

        foreach ($mapper->find_all() as $pricelist) {
            $pricelist_id       = $pricelist->{$pricelist->id_field};
            $pricelist_title    = esc_html($pricelist->title);
            $selected           = selected($selected_pricelist_id, $pricelist_id, FALSE);
            echo "<option {$selected} value='{$pricelist_id}'>{$pricelist_title}</option>";
        }

        echo "</select>";
    }

    public function return_own_installer($installers)
    {
        $installers[] = 'C_NextGen_Pro_Ecommerce_Installer';
        return $installers;
    }

    function add_pro_lightbox_cart_sidebar_data($retval)
    {
        /** @var A_Ecommerce_Ajax $controller */
        $controller = C_Ajax_Controller::get_instance();
        $retval = $controller->get_image_items_action($retval);
        $retval = $controller->get_digital_download_settings_action($retval);
        return $retval;
    }

    /**
     * Adds additional bulk actions to the orders list
     *
     * @param array|null $actions
     * @return array
     */
    function order_bulk_actions($actions)
    {
        if (is_null($actions))
            $actions = array();

        $actions['mark_as_paid']             = __('Mark as Paid',             'nextgen-gallery-pro');
        $actions['mark_as_unpaid']           = __('Mark as Unpaid',           'nextgen-gallery-pro');
        $actions['mark_as_awaiting_payment'] = __('Mark as Awaiting Payment', 'nextgen-gallery-pro');

        return $actions;
    }

    /**
     * Processes "mark as [action] actions"
     */
    function process_order_bulk_actions()
    {
        global $typenow;
        if ($typenow !== 'ngg_order')
            return;

        if (empty($_REQUEST['post']))
            return;

        $wp_list_table = _get_list_table('WP_Posts_List_Table');
        $action = $wp_list_table->current_action();

        $ids = array_map('intval', $_REQUEST['post']);
        if (empty($ids))
            return;

        $url = remove_query_arg(array('mark_as_paid'), wp_get_referer());
        $url = remove_query_arg(array('mark_as_unpaid'), wp_get_referer());
        $url = remove_query_arg(array('mark_as_awaiting_payment'), wp_get_referer());
        if (!$url)
            $url = admin_url('edit.php?post_type=ngg_order');
        $url = add_query_arg('paged', $wp_list_table->get_pagenum(), $url);

        switch ($action) {
            case 'mark_as_paid':
            case 'mark_as_unpaid':
            case 'mark_as_awaiting_payment':
                $checkout = new C_NextGen_Pro_Checkout();
                $new_status = str_replace('mark_as_', '', $action);
                $changed = 0;
                foreach ($ids as $post_id) {
                    $order = C_Order_Mapper::get_instance()->find($post_id, TRUE);
                    if ($order->status == $new_status) continue;
                    $checkout->$action($order);
                    $changed++;
                }
                setcookie('ngg_pro_order_status_updates', (int)$changed, time() + 18000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
                wp_redirect($url);
                throw new E_Clean_Exit;
            default:
                return;
        }
    }

    /**
     * Prints notification that orders have been updated
     */
    function order_bulk_action_notices()
    {
        global $post_type;

        if (empty($_COOKIE['ngg_pro_order_status_updates']))
            return;

        if ($post_type == 'ngg_order')
        {
            setcookie('ngg_pro_order_status_updates', 0, time() - 3600, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
            $message = sprintf(
                _n(
                    'Order status updated',
                    '%s orders updated',
                    (int)$_COOKIE['ngg_pro_order_status_updates'],
                    'nextgen-gallery-pro'
                ),
                number_format_i18n((int)$_COOKIE['ngg_pro_order_status_updates'])
            );
            echo "<div class='updated'><p>{$message}</p></div>";
        }
    }

    static function get_studio_email_address()
    {
        $settings = C_NextGen_Settings::get_instance();

        // Get studio e-mail
        $studio_email = $settings->get('ecommerce_studio_email', FALSE);
        if (!$studio_email)
            $studio_email = $settings->get('ecommerce_email_notification_recipient', FALSE);
        if (!$studio_email)
            $studio_email = get_bloginfo('admin_email');

        return $studio_email;            
    }

    function get_type_list()
    {
        return array(
            'A_Digital_Downloads_Form'            => 'adapter.digital_downloads_form.php',
            'A_Display_Type_Ecommerce_Form'       => 'adapter.display_type_ecommerce_form.php',
            'A_Ecommerce_Ajax'                    => 'adapter.ecommerce_ajax.php',
            'A_Ecommerce_Display_Type_Mapper'     => 'adapter.ecommerce_display_type_mapper.php',
            'A_Ecommerce_Factory'                 => 'adapter.ecommerce_factory.php',
            'A_Ecommerce_Gallery'                 => 'adapter.ecommerce_gallery.php',
            'A_Ecommerce_Image'                   => 'adapter.ecommerce_image.php',
            'A_Ecommerce_Instructions_Controller' => 'adapter.ecommerce_instructions_controller.php',
            'A_Ecommerce_Instructions_Form'       => 'adapter.ecommerce_instructions_form.php',
            'A_Ecommerce_Options_Controller'      => 'adapter.ecommerce_options_controller.php',
            'A_Ecommerce_Options_Form'            => 'adapter.ecommerce_options_form.php',
            'A_Ecommerce_Pages'                   => 'adapter.ecommerce_pages.php',
            'A_Ecommerce_Printlab_Form'           => 'adapter.ecommerce_printlab_form.php',
            'A_Ecommerce_Pro_Lightbox_Form'       => 'adapter.ecommerce_pro_lightbox_form.php',
            'A_Manual_Pricelist_Settings_Form'    => 'adapter.manual_pricelist_settings_form.php',
            'A_NextGen_Pro_Lightbox_Mail_Form'    => 'adapter.nextgen_pro_lightbox_mail_form.php',
            'A_NplModal_Ecommerce_Overrides'      => 'adapter.nplmodal_ecommerce_overrides.php',
            'A_Payment_Gateway_Form'              => 'adapter.payment_gateway_form.php',
            'A_Pricelist_Datamapper_Column'       => 'adapter.pricelist_datamapper_column.php',
            'A_Pricelist_Factory'                 => 'adapter.pricelist_factory.php',
            'A_Print_Category_Form'               => 'adapter.print_category_form.php',
            'C_Currency_Conversion_Notice'        => 'class.currency_conversion_notice.php',
            'C_Digital_Downloads'                 => 'class.digital_downloads.php',
            'C_Ecommerce_Pro_Lightbox_Installer'  => 'class.ecommerce_pro_lightbox_installer.php',
            'C_Invalid_License_Notice'            => 'class.invalid_license_notice.php',
            'C_NextGEN_Printlab_Catalog_Data'     => 'class.nextgen_printlab_catalog_data.php',
            'C_NextGEN_Printlab_Manager'          => 'class.nextgen_printlab_manager.php',
            'C_NextGen_Pro_Add_To_Cart'           => 'class.nextgen_pro_add_to_cart.php',
            'C_NextGen_Pro_Cart'                  => 'class.nextgen_pro_cart.php',
            'C_NextGen_Pro_Checkout'              => 'class.nextgen_pro_checkout.php',
            'C_NextGen_Pro_Currencies'            => 'class.nextgen_pro_currencies.php',
            'C_NextGen_Pro_Ecommerce_Trigger'     => 'class.nextgen_pro_ecommerce_trigger.php',
            'C_NextGen_Pro_Order'                 => 'class.nextgen_pro_order.php',
            'C_NextGen_Pro_Order_Controller'      => 'class.nextgen_pro_order_controller.php',
            'C_NextGen_Pro_Order_Verification'    => 'class.nextgen_pro_order_verification.php',
            'C_Nextgen_Mail_Manager'              => 'class.nextgen_mail_manager.php',
            'C_Non_HTTPS_Notice'                  => 'class.non_https_notice.php',
            'C_Order_Mapper'                      => 'class.order_mapper.php',
            'C_Pricelist'                         => 'class.pricelist.php',
            'C_Pricelist_Category_Manager'        => 'class.pricelist_category_manager.php',
            'C_Pricelist_Category_Page'           => 'class.pricelist_category_page.php',
            'C_Pricelist_Item'                    => 'class.pricelist_item.php',
            'C_Pricelist_Item_Mapper'             => 'class.pricelist_item_mapper.php',
            'C_Pricelist_Mapper'                  => 'class.pricelist_mapper.php',
            'C_Pricelist_Shipping_Method_Manager' => 'class.pricelist_shipping_method_manager.php',
            'C_Pricelist_Source'                  => 'class.pricelist_source.php',
            'C_Pricelist_Source_Download'         => 'class.pricelist_source_download.php',
            'C_Pricelist_Source_Manager'          => 'class.pricelist_source_manager.php',
            'C_Pricelist_Source_Manual'           => 'class.pricelist_source_manual.php',
            'C_Pricelist_Source_Print'            => 'class.pricelist_source_print.php',
            'C_Pricelist_Source_WHCC'             => 'class.pricelist_source_whcc.php'
        );
    }
}

class C_NextGen_Pro_Ecommerce_Installer extends AC_NextGen_Pro_Settings_Installer
{
    function __construct()
    {
        $this->set_defaults(array(
            'ecommerce_currency'                        => 840, // 'USD'
	        'ecommerce_studio_name'                     => '',
            'ecommerce_studio_email'                    => '',
            'ecommerce_home_country'                    => 840, // 'United States',
	        'ecommerce_studio_street_address'           => '',
            'ecommerce_studio_address_line'             => '',
            'ecommerce_studio_city'                     => '',
            'ecommerce_home_state'                      => '',
            'ecommerce_home_zip'                        => '',
            'ecommerce_page_checkout'                   => '',
            'ecommerce_page_thanks'                     => '',
            'ecommerce_page_cancel'                     => '',
            'ecommerce_page_digital_downloads'          => '',
            'ecommerce_enable_email_notification'       => TRUE,
            'ecommerce_email_notification_subject'      => __('New Purchase!', 'nextgen-gallery-pro'),
            'ecommerce_email_notification_recipient'    => get_bloginfo('admin_email'),
            'ecommerce_enable_email_receipt'            => TRUE,
            'ecommerce_email_receipt_subject'           => __("Thank you for your purchase!", 'nextgen-gallery-pro'),
            'ecommerce_email_receipt_body'              => __("Thank you for your order, %%customer_name%%.\n\nYou ordered %%item_count%% items, and have been billed a total of %%total_amount%%.\n\nTo review your order, please go to %%order_details_page%%.\n\nThanks for shopping at %%site_url%%!", 'nextgen-gallery-pro'),
            'ecommerce_email_notification_body'         => __("You received a payment of %%total_amount%% from %%customer_name%% (%%email%%). For more details, visit: %%order_details_page%%\n\n%%gateway_admin_note%%\n\nHere is a comma separated list of the image file names. You can copy and\npaste this in your favorite image management software to quickly search for\nand find all selected images.\n\nFiles: %%file_list%%", 'nextgen-gallery-pro'),
            'ecommerce_not_for_sale_msg'                => __("Sorry, this image is not currently for sale.", 'nextgen-gallery-pro'),
            'ecommerce_tax_enable'                      => FALSE,
            'ecommerce_tax_rate'                        => '8.5',
            'ecommerce_tax_include_shipping'            => FALSE,
            'ecommerce_cookies_enable'                  => TRUE,
	        'ecommerce_intl_shipping'                   => 'disabled',
            'ecommerce_intl_shipping_rate'              => 40,
            'ecommerce_domestic_shipping'               => 'flat',
            'ecommerce_domestic_shipping_rate'          => 5,
	        'ecommerce_whcc_intl_shipping'              => FALSE,
	        'ecommerce_whcc_intl_shipping_rate'         => 40,
	        'stripe_card_info'                          => '',
	        'stripe_cus_id'                             => ''
        ));

        $this->set_groups(array('ecommerce'));
    }

    function install()
    {
        parent::install();

        delete_transient('ngg_catalog_versions');
        delete_transient('ngg_whcc_catalog');
        delete_transient('ngg_whcc_catalog_version');
        delete_transient('ngg_whcc_catalog_standard');
        delete_transient('ngg_whcc_catalog_standard_version');

        $settings = C_NextGen_Settings::get_instance();
        $ngg_pro_lightbox = $settings->get('ngg_pro_lightbox');
        if (empty($ngg_pro_lightbox['display_cart']))
        {
            $ngg_pro_lightbox['display_cart'] = 0;
            $settings->set('ngg_pro_lightbox', $ngg_pro_lightbox);
        }
    }
}

new M_NextGen_Pro_Ecommerce;
