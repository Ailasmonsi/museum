<?php
class A_Coupon_Admin_Form extends Mixin
{
    function get_title()
    {
        return __('Coupon details', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('coupon_id_code', 'coupon_discount_type', 'coupon_discount_amount', 'coupon_date_start', 'coupon_date_end');
    }
    function save_action()
    {
        return $this->get_model()->is_valid();
    }
    function _render_coupon_id_code_field($coupon)
    {
        $coupon->name = 'coupon';
        return $this->_render_text_field($coupon, 'id_code', __('Code', 'nextgen-gallery-pro'), $coupon->id_code, __('This field must be unique from every other coupon code', 'nextgen-gallery-pro'));
    }
    function _render_coupon_discount_type_field($coupon)
    {
        $coupon->name = 'coupon';
        return $this->_render_select_field($coupon, 'discount_type', __('Discount type', 'nextgen-gallery-pro'), array('percent' => __('Percent', 'nextgen-gallery-pro'), 'flat' => __('Flat', 'nextgen-gallery-pro')), $coupon->discount_type);
    }
    function _render_coupon_discount_amount_field($coupon)
    {
        $coupon->name = 'coupon';
        return $this->_render_number_field($coupon, 'discount_amount', __('Discount amount', 'nextgen-gallery-pro'), $coupon->discount_amount, '', FALSE, '', 0);
    }
    function _render_coupon_date_start_field($coupon)
    {
        $coupon->name = 'coupon';
        return $this->_render_text_field($coupon, 'date_start', __('Starting date', 'nextgen-gallery-pro'), $coupon->date_start);
    }
    function _render_coupon_date_end_field($coupon)
    {
        $coupon->name = 'coupon';
        return $this->_render_text_field($coupon, 'date_end', __('Ending date', 'nextgen-gallery-pro'), $coupon->date_end);
    }
    function enqueue_static_resources()
    {
        wp_register_script('ngg_flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr');
        wp_enqueue_script('ngg-coupon-admin-js', $this->get_static_url('photocrati-coupons#admin.js'), ['ngg_flatpickr', 'jquery-ui-tooltip'], FALSE, TRUE);
        wp_enqueue_style('ngg_flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_style('ngg-coupon-admin-css', $this->get_static_url('photocrati-coupons#admin.css'));
    }
}
class A_Coupon_Admin_Settings_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'nextgen_pro_coupons_enable';
        return $fields;
    }
    function _render_nextgen_pro_coupons_enable_field($expectednull = null)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->_render_radio_field($model, 'coupons_disabled', __('Hide coupons on checkout', 'nextgen-gallery-pro'), $settings->ecommerce_coupons_disabled ? TRUE : FALSE);
    }
}
class A_Coupon_Factory extends Mixin
{
    /**
     * @param array $properties
     * @param C_Coupon_Mapper|false $mapper
     * @param mixed $context
     * @return C_Coupon
     */
    function ngg_coupon($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return new C_Coupon($properties, $mapper, $context);
    }
}
class C_Coupon extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Coupon_Mapper';
    static $date_format = 'Y-m-d';
    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->implement('I_Coupon');
    }
    function initialize($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        if (!$mapper) {
            $mapper = $this->get_registry()->get_utility($this->_mapper_interface);
        }
        parent::initialize($mapper, $properties);
    }
    function validate()
    {
        $this->validates_presence_of('title', array(), __('Title must not be blank', 'nextgen-gallery-pro'));
        $this->validates_presence_of('id_code', array(), __('Coupon code must not be blank', 'nextgen-gallery-pro'));
        $this->object->validates_uniqueness_of('id_code', array(), __('Coupon code must be unique', 'nextgen-gallery-pro'));
        $this->validates_presence_of('discount_type', array(), __('Discount type must not be blank', 'nextgen-gallery-pro'));
        $this->validates_inclusion_of('discount_type', array('flat', 'percent'), __('Discount type must be "Flat" or "Percent"', 'nextgen-gallery-pro'));
        $this->validates_presence_of('discount_amount', array(), __('Discount amount must not be blank', 'nextgen-gallery-pro'));
        $this->validates_numericality_of('discount_amount', 0.0, '>=', FALSE, __('Discount amount must be a number 0 or larger', 'nextgen-gallery-pro'));
        $this->validates_presence_of('date_start', array(), __('Starting date must not be blank', 'nextgen-gallery-pro'));
        $this->validates_presence_of('date_end', array(), __('Ending date must not be blank', 'nextgen-gallery-pro'));
        // because validates_format_of doesn't work as as of NGG 2.1.43
        $this->validates_date('date_start', self::$date_format, __('Starting date must be in the form (Year)-(Month)-(Day)', 'nextgen-gallery-pro'));
        $this->validates_date('date_end', self::$date_format, __('Ending date must be in the form (Year)-(Month)-(Day)', 'nextgen-gallery-pro'));
        $this->validates_date_precedes_date('date_start', 'date_end', self::$date_format, __('Starting date must come before the ending date', 'nextgen-gallery-pro'));
    }
    function validates_date($property, $format = 'Y-m-d', $msg = NULL)
    {
        // This method only validates date format, validates_presence_of() will handle empty $property
        if (empty($this->object->{$property})) {
            return;
        }
        $date = new DateTime(date($format, strtotime($this->object->{$property})));
        $valid = $date && $date->format($format) == $this->object->{$property};
        if (!$valid) {
            if (!$msg) {
                $msg = sprintf('%s is an invalid date', $this->object->humanize_string($property));
            }
            $this->add_error($msg, $property);
        }
    }
    function validates_date_precedes_date($property_one, $property_two, $format = 'Y-m-d', $msg = NULL)
    {
        $date_one = new DateTime(date($format, strtotime($this->object->{$property_one})));
        $date_two = new DateTime(date($format, strtotime($this->object->{$property_two})));
        $one_valid = $date_one && $date_one->format($format) == $this->object->{$property_one};
        $two_valid = $date_two && $date_two->format($format) == $this->object->{$property_two};
        // This method only validates that the date preceeds the other; validates_date() handles date format
        if (!$one_valid || !$two_valid) {
            return;
        }
        if (strtotime($this->object->{$property_one}) > strtotime($this->object->{$property_two})) {
            if (!$msg) {
                $msg = sprintf('%s must precede %s', $this->object->humanize_string($property_one), $this->object->humanize_string($property_two));
            }
            $this->add_error($msg, $property_one);
        }
    }
    /**
     * @return bool
     * @throws Exception
     */
    function validate_current_availability()
    {
        try {
            // For single-day coupons we must set the ending time to the final stroke of the day
            $date_end = $this->get_datetime(date(self::$date_format, strtotime($this->date_end)));
            $date_end->setTime(23, 59, 59);
            // $date_start begins at 00:00:00 naturally, no need to call setTime()
            $date_start = $this->get_datetime(date(self::$date_format, strtotime($this->date_start)));
            // time() always returns UTC; we must offset to account for timezones
            $today = $this->get_datetime(date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 60 * 60));
            $valid = $date_end && $date_end->format(self::$date_format) == $this->date_end;
            if (!$valid) {
                return false;
            }
            if ($date_end >= $today && $date_start <= $today) {
                return true;
            }
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }
    /**
     * Returns a DateTime object whose timezone has been set to follow the WordPress setting
     *
     * @param string $time
     * @return DateTime
     * @throws Exception
     */
    public function get_datetime($time)
    {
        // IF the wordpress timezone is set to one of the direct UTC offset values then DateTimeZone needs
        // to have the offset number converted to a string it can parse. Almost every WP site will have a
        // usable timezone_string setting, but just in case we have a fallback for UTC offsets as well.
        try {
            return $this->get_datetime_from_string($time);
        } catch (Exception $exception) {
        }
        try {
            return $this->get_datetime_from_gmt($time);
        } catch (Exception $exception) {
            // Fallback: create a new DateTime without any timezone information
            return new DateTime($time);
        }
    }
    /**
     * @param string $time
     * @return DateTime
     * @throws Exception
     */
    public function get_datetime_from_gmt($time)
    {
        $offset = get_option('gmt_offset');
        $offset_sting = $offset > 0 ? "-{$offset}" : '+' . absint($offset);
        return new DateTime($time, new DateTimeZone("Etc/GMT{$offset_sting}"));
    }
    /**
     * @param string $time
     * @return DateTime
     * @throws Exception
     */
    public function get_datetime_from_string($time)
    {
        // WordPress usually has this option available for our use
        $timezone_string = get_option('timezone_string');
        if (!empty($timezone_string)) {
            return new DateTime($time, new DateTimeZone($timezone_string));
        } else {
            throw new Exception("timezone_string option is empty");
        }
    }
    function get_limited_entity()
    {
        return array('code' => $this->id_code, 'id' => $this->ID, 'discount_amount' => $this->discount_amount, 'discount_type' => $this->discount_type);
    }
    function get_discount_amount($total, $exponent, $rounding = PHP_ROUND_HALF_DOWN)
    {
        if (!M_NextGen_Pro_Coupons::are_coupons_enabled()) {
            return $total;
        }
        if ($this->discount_type == 'percent') {
            $total = bcmul(bcdiv($this->discount_amount, 100, intval($exponent) * 2), $total, intval($exponent) * 2);
            $total = round($total, intval($exponent), $rounding);
        } else {
            if ($this->discount_type == 'flat') {
                $total = bcsub($this->discount_amount, 0.0, $exponent);
            }
        }
        if ($total < 0.0) {
            $total = 0;
        }
        return $total;
    }
}
class C_Coupon_Admin_Page extends C_NextGen_Admin_Page_Controller
{
    static $_instance = NULL;
    /**
     * @param bool|string $context
     * @return C_Coupon_Admin_Page
     */
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
    function define($context = FALSE)
    {
        parent::define('ngg_pro_coupons');
    }
    function get_required_permission()
    {
        return 'NextGEN Change options';
    }
    function get_page_heading()
    {
        if (strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/edit.php') !== FALSE && !empty($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'ngg_coupon' && empty($_REQUEST['id'])) {
            return __('New Coupon', 'nextgen-gallery-pro');
        } else {
            return __('Edit Coupon', 'nextgen-gallery-pro');
        }
    }
    /**
     * Use the correct template when C_NextGen_Admin_Page_Controller calls index_action()
     * @return string
     */
    function index_template()
    {
        /* Use redesigned template if user has redesign version of NGG */
        if (defined('NGG_PLUGIN_VERSION')) {
            if (version_compare(NGG_PLUGIN_VERSION, '2.2.99') >= 0) {
                return 'photocrati-coupons#edit_redesign';
            }
        }
        return 'photocrati-coupons#edit';
    }
    function is_coupon_edit_page()
    {
        return (M_Attach_To_Post::is_atp_url() || is_admin()) && strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/edit.php') !== FALSE && !empty($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'ngg_coupon';
    }
    function register_hooks()
    {
        add_action('init', array($this, 'init'));
        add_filter('get_edit_post_link', array($this, 'custom_edit_link'));
        add_action('admin_init', array($this, 'redirect_to_manage_coupon_page'));
        add_filter('manage_ngg_coupon_posts_columns', array($this, 'set_columns'));
        add_filter('manage_ngg_coupon_posts_custom_column', array($this, 'order_count_column'), 10, 2);
        // We're very specific about only running on the manage ngg_coupons page
        if ($this->is_coupon_edit_page()) {
            add_filter('post_row_actions', array($this, 'hide_quick_edit_link'), 10, 2);
            // WP uses a parameter called 'action', so we have to temporary call it 'ngg_action'
            if (isset($_REQUEST['action'])) {
                $_REQUEST['ngg_action'] = $_REQUEST['action'];
            }
            unset($_REQUEST['action']);
            unset($_POST['action']);
            if (isset($_REQUEST['ngg_edit'])) {
                add_action('all_admin_notices', array(&$this, 'buffer_for_manage_coupon_page'), PHP_INT_MAX - 1);
                add_action('in_admin_footer', array(&$this, 'render_manage_coupon_page'));
            }
        }
    }
    function buffer_for_manage_coupon_page()
    {
        ob_start();
    }
    /*
     * This injects the coupon admin form into the area where the custom-post-type editor would normally be generated
     */
    function render_manage_coupon_page()
    {
        if (isset($_REQUEST['ngg_action'])) {
            $_POST['action'] = $_REQUEST['action'] = $_REQUEST['ngg_action'];
        }
        ob_end_clean();
        $page = C_Coupon_Admin_Page::get_instance();
        $page->index_action();
        echo '<div class="clear"></div></div><!-- wpbody-content -->
              <div class="clear"></div></div><!-- wpbody -->
              <div class="clear"></div></div><!-- wpcontent -->
              <div id="wpfooter">';
    }
    function set_columns($columns)
    {
        return array('cb' => $columns['cb'], 'title' => $columns['title'], 'order_count' => __('Times Used', 'nextgen-gallery-pro'), 'date' => $columns['date']);
    }
    function order_count_column($column, $post_id)
    {
        $count = C_Coupon_Mapper::get_instance()->get_use_count($post_id);
        echo number_format_i18n($count, 0);
    }
    /**
     * Registers our custom post type with WP
     */
    function init()
    {
        // TODO: check for post-type=ngg_coupon
        if ($this->is_coupon_edit_page()) {
            wp_deregister_script('postbox');
        }
        register_post_type('ngg_coupon', array('labels' => array('name' => __('Coupons', 'nextgen-gallery-pro'), 'singular_name' => __('Coupon', 'nextgen-gallery-pro'), 'add_new_item' => __('Add New Coupon', 'nextgen-gallery-pro'), 'edit_item' => __('Edit Coupon', 'nextgen-gallery-pro'), 'new_item' => __('New Coupon', 'nextgen-gallery-pro'), 'view_item' => __('View Coupon', 'nextgen-gallery-pro'), 'search_items' => __('Search Coupon', 'nextgen-gallery-pro')), 'public' => FALSE, 'has_archive' => FALSE, 'hierarchical' => FALSE, 'show_ui' => TRUE, 'supports' => array('title'), 'show_in_menu' => FALSE, 'map_meta_cap' => TRUE));
    }
    /**
     * Determined by the 'action_proxy' attribute in the template and sorted by the _get_action() method in this class
     * 
     * @return bool
     */
    function save_action()
    {
        $retval = FALSE;
        $coupon = $this->get_model();
        if ($coupon->save($_REQUEST['coupon'])) {
            $this->coupon = $coupon;
            wp_redirect(admin_url("edit.php?post_type=ngg_coupon&ngg_edit=1&id=" . $coupon->id() . '&message=saved'));
        }
        return $retval;
    }
    /**
     * Ensure WP links to the new-coupon & edit-coupon pages with ngg_edit=1 in the URL
     *
     * @param string $url
     * @return string
     */
    function custom_edit_link($url)
    {
        global $post;
        if (empty($post)) {
            return $url;
        }
        if ($post->post_type == 'ngg_coupon') {
            $url = admin_url('/edit.php?post_type=ngg_coupon&ngg_edit=1&id=' . $post->ID);
        }
        return $url;
    }
    /**
     * Hides the quick edit button to avoid users changing the post_status of a pricelist
     *
     * @param $actions
     * @return mixed
     */
    function hide_quick_edit_link($actions, $post)
    {
        $retval = array();
        if ($post->post_type == 'ngg_coupon') {
            if (!empty($actions['edit'])) {
                $retval['edit'] = $actions['edit'];
            }
            if (!empty($actions['trash'])) {
                $retval['trash'] = $actions['trash'];
            }
            if (!empty($actions['untrash'])) {
                $retval['untrash'] = $actions['untrash'];
            }
        }
        return $retval;
    }
    /**
     * Redirect users from the new-coupon page to new-coupon with ngg_edit=1 in the URL
     */
    function redirect_to_manage_coupon_page()
    {
        if (strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/post-new.php') !== FALSE && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'ngg_coupon') {
            wp_redirect(admin_url('/edit.php?post_type=ngg_coupon&ngg_edit=1'));
        }
    }
    /**
     * Determine whether the coupon should be saved or deleted
     *
     * @return string
     */
    function _get_action()
    {
        $action = $this->object->param('action');
        if (empty($action) && !empty($_REQUEST['action_proxy'])) {
            $action = strtolower($_REQUEST['action_proxy']);
        }
        $retval = preg_quote($action, '/');
        $retval = strtolower(preg_replace("/[^\\w]/", '_', $retval));
        return preg_replace("/_{2,}/", "_", $retval) . '_action';
    }
    /**
     * Returns the coupon to edit for the current page
     *
     * @return C_Coupon
     */
    function get_model()
    {
        if (!isset($this->coupon)) {
            $coupon_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $mapper = C_Coupon_Mapper::get_instance();
            if (!($this->coupon = $mapper->find($coupon_id, TRUE))) {
                $this->coupon = $mapper->create();
            }
        }
        return $this->coupon;
    }
}
/**
 * @mixin Mixin_Coupon_Mapper
 */
class C_Coupon_Mapper extends C_CustomPost_DataMapper_Driver
{
    public static $_instances = array();
    /**
     * @param bool|string $context
     * @return C_Coupon_Mapper
     */
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
    function define($context = FALSE, $not_used = FALSE)
    {
        $object_name = 'ngg_coupon';
        if (!is_array($context)) {
            $context = array($context);
        }
        array_push($context, $object_name);
        parent::define($object_name, $context);
        $this->add_mixin('Mixin_Coupon_Mapper');
        $this->set_model_factory_method($object_name);
        // Define columns
        $this->define_column('ID', 'BIGINT');
        $this->define_column('post_author', 'BIGINT');
        $this->define_column('title', 'VARCHAR(255)');
        $this->define_column('id_code', 'VARCHAR(255)');
        $this->define_column('discount_type', 'VARCHAR(255)');
        $this->define_column('discount_amount', 'DECIMAL');
        $this->define_column('date_start', 'VARCHAR(255)');
        $this->define_column('date_end', 'VARCHAR(255');
    }
    function initialize($context = FALSE)
    {
        parent::initialize('ngg_coupon');
    }
}
/**
 * @property C_CustomPost_DataMapper_Driver $object
 */
class Mixin_Coupon_Mapper extends Mixin
{
    function set_defaults($entity)
    {
        // These should be set for all coupon entries
        $entity->post_status = 'publish';
        $entity->post_title = $entity->title;
    }
    /**
     * @param string $code
     * @param bool $model
     * @return null|C_Coupon
     */
    function find_by_code($code, $model = FALSE)
    {
        $retval = NULL;
        $code = strtoupper($code);
        $this->object->select();
        $this->object->where(array('id_code = %s', $code));
        $results = $this->object->run_query(FALSE, $model);
        if ($results) {
            $retval = $results[0];
        }
        return $retval;
    }
    /**
     * @param string $coupon_id
     * @return int
     */
    function get_use_count($coupon_id)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT COUNT(`post_id`) AS `order_count`\n                                 FROM `{$wpdb->postmeta}`\n                                 WHERE `meta_key` = 'ngg_coupon_id'\n                                 AND `meta_value` = %s", $coupon_id);
        return $wpdb->get_var($query);
    }
}