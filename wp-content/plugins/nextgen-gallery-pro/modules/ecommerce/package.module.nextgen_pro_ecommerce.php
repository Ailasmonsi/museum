<?php
class A_Digital_Downloads_Form extends Mixin
{
    function get_title()
    {
        return __('Digital Downloads', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('digital_downloads');
    }
    function save_action()
    {
        return $this->get_model()->is_valid();
    }
    function enqueue_static_resources()
    {
        wp_enqueue_script('nextgen_pro_lightbox_digital_downloads_form_settings', $this->get_static_url('photocrati-nextgen_pro_ecommerce#source_settings_downloads.js'));
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->show_licensing_link = __('Display link to license terms?', 'nextgen-gallery-pro');
        $i18n->licensing_page = __('Licensing page:', 'nextgen-gallery-pro');
        $i18n->name_header = __('Name:', 'nextgen-gallery-pro');
        $i18n->price_header = __('Price:', 'nextgen-gallery-pro');
        $i18n->resolution_header = __('Longest Image Dimension:', 'nextgen-gallery-pro');
        $i18n->resolution_tooltip = __('A setting of 0px will deliver full-resolution images', 'nextgen-gallery-pro');
        $i18n->resolution_placeholder = __('Enter 0 for maximum', 'nextgen-gallery-pro');
        $i18n->item_title_placeholder = __('Enter title of the item', 'nextgen-gallery-pro');
        $i18n->delete = __('Delete', 'nextgen-gallery-pro');
        $i18n->add_another_item = __('Add another item', 'nextgen-gallery-pro');
        $i18n->no_items = __('No items available for this source.', 'nextgen-gallery-pro');
        $i18n->free_items_label = __('Allow free downloads to download directly from the cart sidebar', 'nextgen-gallery-pro');
        return $i18n;
    }
    function get_image_resolutions()
    {
        $retval = array('100' => 'Full');
        for ($i = 90; $i > 0; $i -= 10) {
            $retval[$i] = "{$i}%";
        }
        return $retval;
    }
    function get_pages()
    {
        return get_pages(array('number' => 100));
    }
    function _render_digital_downloads_field()
    {
        $items = $this->get_model()->get_digital_downloads();
        $settings = $this->get_model()->digital_download_settings;
        // This attribute was added after initial release; this just prevents a PHP warning for existing pricelists
        if (!isset($settings['skip_checkout'])) {
            $settings['skip_checkout'] = '0';
        }
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#accordion_download_items', array('items' => $items, 'settings' => $settings, 'i18n' => $this->object->get_i18n_strings(), 'image_resolutions' => $this->object->get_image_resolutions(), 'pages' => $this->object->get_pages(), 'item_category' => NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS), TRUE);
    }
}
class A_Display_Type_Ecommerce_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        // Add an option to enable e-commerce only if there are pricelists created
        if (C_Pricelist_Mapper::get_instance()->count() > 0) {
            if (is_array($fields)) {
                $fields[] = 'is_ecommerce_enabled';
            }
        }
        return $fields;
    }
    function _render_is_ecommerce_enabled_field($display_type)
    {
        $value = isset($display_type->settings['is_ecommerce_enabled']) ? $display_type->settings['is_ecommerce_enabled'] : FALSE;
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#field_ecommerce_enabled', array('display_type_name' => $display_type->name, 'instructions_label' => esc_attr(__('see instructions', 'nextgen-gallery-pro')), 'non_https_warning' => __('<strong>Warning</strong>: HTTPS was not detected! Without it NextGen Gallery Pro cannot process payments.', 'nextgen-gallery-pro'), 'name' => 'is_ecommerce_enabled', 'label' => __('Enable ecommerce?', 'nextgen-gallery-pro'), 'value' => $value, 'text' => '', 'hidden' => FALSE, 'href' => esc_attr(admin_url('/admin.php?page=ngg-ecommerce-instructions-page')), 'is_ssl' => is_ssl()), TRUE);
    }
}
class A_Ecommerce_Ajax extends Mixin
{
    /**
     * Read an image file into memory and display it
     *
     * This is necessary for htaccess or server-side protection that blocks access to filenames ending with "_backup"
     * At the moment it only supports the backup or full size image.
     */
    function get_image_file_action()
    {
        $image_id = $this->param('image_id', FALSE);
        $order_id = $this->param('order_id', FALSE);
        $item_id = $this->param('item_id', FALSE);
        $invalid_request = FALSE;
        // Image id must be present along with either an order id or pricelist item id
        if (!$image_id || !$order_id && !$item_id || $order_id && $item_id) {
            $invalid_request = TRUE;
        }
        if (!$invalid_request && $order_id) {
            $order = C_Order_Mapper::get_instance()->find_by_hash($order_id);
            if (!$order || !is_object($order) || $order->status !== 'paid' || !in_array($image_id, $order->cart['image_ids'])) {
                $invalid_request = TRUE;
            }
        } else {
            if (!$invalid_request && $item_id) {
                $mapper = C_Pricelist_Item_Mapper::get_instance();
                $item = $mapper->find($item_id);
                if (!$item || !is_object($item) || $item->price > 0) {
                    $invalid_request = TRUE;
                }
            }
        }
        if ($invalid_request) {
            header('HTTP/1.1 404 Not found');
            exit;
        }
        $storage = C_Gallery_Storage::get_instance();
        // By default this method serves the backup image as that is the only size requested by
        // C_Digital_Downloads->render_download_list()
        $abspath = $storage->get_image_abspath($image_id, 'backup');
        // Just in case the image has been removed from NextGen but the file was not removed (this can be triggered via
        // the Gallery > Other Options > Image Options > "Delete Image File?" setting
        if (empty($abspath)) {
            if (!isset($order)) {
                $order = C_Order_Mapper::get_instance()->find_by_hash($order_id);
            }
            $image = new stdClass();
            $original = $order->cart['images'][$image_id];
            foreach ($original as $key => $value) {
                $image->{$key} = $value;
            }
            $storage = C_Gallery_Storage::get_instance();
            $abspath = $storage->get_image_abspath($image, 'backup');
            $image_id = $image;
        }
        if ($item_id && $item->resolution != 0) {
            $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
            $params = array('width' => $item->resolution, 'height' => $item->resolution, 'crop' => FALSE, 'watermark' => FALSE, 'quality' => 100);
            $named_size = $dynthumbs->get_size_name($params);
            $abspath = $storage->get_image_abspath($image_id, $named_size, TRUE);
            if (!$abspath) {
                $thumbnail = $storage->generate_image_size($image_id, $named_size);
                if ($thumbnail) {
                    $thumbnail->destruct();
                    $abspath = $storage->get_image_abspath($image_id, $named_size, TRUE);
                }
            }
        }
        $mimetype = 'application/octet';
        if (function_exists('finfo_buffer')) {
            $finfo = new finfo(FILEINFO_MIME);
            $mimetype = @$finfo->file($abspath);
        } elseif (function_exists('mime_content_type')) {
            $mimetype = @mime_content_type($abspath);
        }
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . basename($storage->get_image_abspath($image_id, 'full')));
        header("Content-type: " . $mimetype);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . @filesize($abspath));
        readfile($abspath);
        exit;
    }
    function get_digital_download_settings_action($original_retval = array())
    {
        $ids = explode(',', $this->object->param('id'));
        foreach ($ids as $id) {
            if ($pricelist = C_Pricelist_Mapper::get_instance()->find_for_image($id)) {
                $retval = $pricelist->digital_download_settings;
                $retval['header'] = esc_html(__('Digital Downloads', 'nextgen-gallery-pro'));
                if (intval($retval['show_licensing_link']) > 0) {
                    $retval['licensing_link'] = get_page_link($retval['licensing_page_id']);
                    $view_licensing_terms = __('View license terms', 'nextgen-gallery-pro');
                    $retval['header'] .= " <a target='_blank' href='{$retval['licensing_link']}'>({$view_licensing_terms})</a>";
                }
                $original_retval[$id]['digital_download_settings'] = $retval;
            }
        }
        return $original_retval;
    }
    function get_cart_items_action()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $settings = $this->param('settings');
        if (!$settings) {
            $settings = array();
        }
        if (!isset($settings['shipping_method'])) {
            $settings['shipping_method'] = FALSE;
        }
        $cart = new C_NextGen_Pro_Cart($this->param('cart'), $settings);
        return $cart->to_array();
    }
    function get_shipping_amount_action()
    {
        $cart = new C_NextGen_Pro_Cart($this->param('cart'), $this->param('settings'));
        return array('shipping' => $cart->get_shipping());
    }
    function is_print_lab_ready($cache = FALSE)
    {
        static $_cache = NULL;
        if ($_cache && $cache) {
            return $_cache;
        }
        $retval = M_NextGen_Pro_Ecommerce::check_ecommerce_requirements();
        $_cache = $retval['printlab_ecommerce_ready'];
        return $_cache;
    }
    /**
     * @param bool $cache
     * @param C_Pricelist_Item $item
     * @return bool|null
     */
    function is_ecommerce_ready($cache = FALSE, $item)
    {
        static $_cache = array();
        if (!empty($_cache[$item->ID]) && $cache) {
            return $_cache[$item->ID];
        }
        $retval = M_NextGen_Pro_Ecommerce::check_ecommerce_requirements();
        $key = 'manual_ecommerce_ready';
        if ($item->source === NGG_PRO_DIGITAL_DOWNLOADS_SOURCE) {
            $key = 'download_ecommerce_ready';
        }
        $_cache[$item->ID] = $retval[$key];
        return $_cache[$item->ID];
    }
    function get_image_items_action($original_retval = array())
    {
        $ids = explode(',', $this->object->param('id'));
        foreach ($ids as $image_id) {
            $retval = array('items' => array(), 'valid_license' => M_NextGen_Pro_Ecommerce::is_valid_license());
            $cart = $this->param('cart');
            $cart = new C_NextGen_Pro_Cart($cart);
            if ($pricelist = C_Pricelist_Mapper::get_instance()->find_for_image($image_id, TRUE)) {
                $retval['pricelist'] = $pricelist->ID;
                $retval['has_pricelist'] = TRUE;
                $is_print_lab_ready = $retval['is_print_lab_ready'] = $this->is_print_lab_ready(TRUE);
                $items = $pricelist->get_items($image_id, TRUE);
                if ($pricelist->are_catalogs_out_of_date()) {
                    $items = $pricelist->get_latest_pricing($items);
                }
                foreach (array_values($items) as $item) {
                    $ecommerce_ready = $retval[$item->ID . '_ecommerce_ready'] = $this->is_ecommerce_ready(TRUE, $item);
                    $is_labfulfilled = $retval[$item->ID . '_is_labfulfilled'] = $item->is_lab_fulfilled();
                    if ($ecommerce_ready && (!$is_labfulfilled || $is_labfulfilled && $is_print_lab_ready)) {
                        // Determine if the item is in the cart. If so, set the item's quantity
                        foreach ($cart->get_items($item->source) as $cart_item) {
                            if ($cart_item->ID == $item->ID && $cart_item->image->pid == $image_id) {
                                $item->quantity = $cart_item->quantity;
                                $item->crop_offset = $cart_item->crop_offset;
                                break;
                            }
                        }
                        $retval['items'][] = $item->get_entity();
                    }
                }
            } else {
                $retval['has_pricelist'] = FALSE;
            }
            $original_retval[$image_id]['image_items'] = $retval;
        }
        return $original_retval;
    }
    function is_order_paid_action()
    {
        $retval = array('paid' => FALSE);
        if ($order = C_Order_Mapper::get_instance()->find_by_hash($this->param('order'))) {
            if ($order->status == 'paid') {
                $retval['paid'] = TRUE;
                $checkout = C_NextGen_Pro_Checkout::get_instance();
                $retval['thank_you_page_url'] = $checkout->get_thank_you_page_url($order->hash, TRUE);
            }
        } else {
            $retval['error'] = __("We're sorry, but we couldn't find your order.", 'nextgen-gallery-pro');
        }
        return $retval;
    }
    function check_ecommerce_requirements_action()
    {
        header('Accept: application/json');
        header('Content-Type: application/json');
        $retval = array('success' => FALSE);
        if (wp_verify_nonce($_POST['nonce'], 'check_ecommerce_requirements')) {
            try {
                $retval['status'] = M_NextGen_Pro_Ecommerce::check_ecommerce_requirements();
                $retval['success'] = TRUE;
            } catch (Exception $ex) {
                $retval['error'] = $ex->toString();
            }
        } else {
            $retval['error'] = "Cheatin', eh?";
        }
        $retval = array_merge($retval, $_POST);
        return $retval;
    }
    function get_print_lab_order_action()
    {
        header('Accept: application/json');
        header('Content-Type: application/json');
        $retval = array('success' => FALSE);
        // Accepts JSON document as body
        $input = file_get_contents("php://input");
        if ($json = json_decode($input)) {
            if (isset($json->nonce) && WPSimpleNonce::checkNonce($json->nonce->name, $json->nonce->value)) {
                if (isset($json->order) && ($order = C_Order_Mapper::get_instance()->find_by_hash($json->order, TRUE))) {
                    // To always ensure that the order has the latest representation of the cart
                    $order->cart = $order->get_cart()->to_array();
                    $order = $order->get_entity();
                    unset($order->cart['images']);
                    // Set print lab service parameters
                    $order->licenseKey = isset($order->license_key) ? $order->license_key : M_NextGen_Pro_Ecommerce::get_license('photocrati-nextgen-pro');
                    $order->stripeCustId = C_NextGen_Settings::get_instance()->get('stripe_cus_id', NULL);
                    $order->stripeMode = M_NextGen_Pro_Ecommerce::is_stripe_test_mode_enabled() ? 'testing' : 'live';
                    $retval['success'] = TRUE;
                    $retval['order'] = $order;
                }
            }
        }
        return $retval;
    }
    function resubmit_lab_order_action()
    {
        $retval = array('success' => FALSE);
        if (isset($_POST['order']) and $order = C_Order_Mapper::get_instance()->find_by_hash($_POST['order'], TRUE)) {
            if (isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'resubmit_lab_order')) {
                if (!C_NextGen_Pro_Checkout::get_instance()->submit_lab_order($order, TRUE) instanceof WP_Error) {
                    $retval['success'] = TRUE;
                }
            }
        }
        return $retval;
    }
    function delete_credit_card_info_action()
    {
        $retval = array('success' => FALSE, 'next_nonce' => base64_encode(json_encode(WPSimpleNonce::createNonce('deleteCreditCardInfo'))));
        if (isset($_POST['nonce']) && ($nonce = json_decode($this->param('nonce'))) && WPSimpleNonce::checkNonce($nonce->name, $nonce->value)) {
            $settings = C_NextGen_Settings::get_instance();
            if ($settings->stripe_cus_id) {
                // TODO: Write an endpoint which removes the customer record in Stripe
                $settings->delete('stripe_cus_id');
                $settings->delete('stripe_card_info');
                $retval['success'] = $settings->save();
            } else {
                $retval['success'] = TRUE;
            }
        }
        return $retval;
    }
    function update_credit_card_info_action()
    {
        $retval = array('success' => FALSE, 'next_nonce' => base64_encode(json_encode(WPSimpleNonce::createNonce('saveCreditCardInfo'))));
        if (isset($_POST['nonce']) && ($nonce = json_decode($this->param('nonce'))) && WPSimpleNonce::checkNonce($nonce->name, $nonce->value)) {
            if (isset($_POST['payment_method'])) {
                $token = $this->param('payment_method');
                if ($token) {
                    $settings = C_NextGen_Settings::get_instance();
                    $body = json_encode(array('payment_method' => $token, 'studio_name' => $settings->get('ecommerce_studio_name'), 'studio_email' => M_NextGen_Pro_Ecommerce::get_studio_email_address(), 'studio_street_address' => $settings->get('ecommerce_studio_street_address'), 'studio_address_line' => $settings->get('ecommerce_studio_address_line'), 'studio_city' => $settings->get('ecommerce_studio_city'), 'studio_zip' => $settings->get('ecommerce_home_zip'), 'studio_country' => $settings->get('ecommerce_home_country'), 'studio_state' => $settings->get('ecommerce_home_state'), 'license' => M_NextGen_Pro_Ecommerce::get_license('photocrati-nextgen-pro'), 'testing' => M_NextGen_Pro_Ecommerce::is_stripe_test_mode_enabled()));
                    $response = wp_remote_post('https://4osfgn6rvj.execute-api.us-east-1.amazonaws.com/latest/saveCustomer', array('body' => $body, 'headers' => array('Content-Type' => 'application/json'), 'timeout' => 30));
                    if (!is_wp_error($response)) {
                        $retval['backend_response'] = $response['body'] = json_decode($response['body']);
                        if (property_exists($response['body'], 'id')) {
                            $settings->set('stripe_cus_id', $response['body']->id);
                            $retval['success'] = TRUE;
                        }
                        if (property_exists($response['body'], 'payment_method')) {
                            $payment_method = $response['body']->payment_method;
                            $settings->set('stripe_card_info', get_object_vars($payment_method));
                        }
                        $settings->save();
                    } else {
                        $retval['backend_error'] = $response->get_error_message();
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * @return array
     */
    function save_pricelist_action()
    {
        $pricelist_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $mapper = C_Pricelist_Mapper::get_instance();
        $pricelist = $mapper->find($pricelist_id, TRUE);
        if (!$pricelist) {
            $pricelist = $mapper->create();
        }
        // disable caching or the changes we're about to save() won't be displayed
        $mapper = C_Pricelist_Mapper::get_instance();
        $mapper->_use_cache = FALSE;
        // A prior bug caused titles to have quotation marks escaped every time the pricelist was saved.
        // For this reason we now strip backslashes entirely from pricelist & item titles
        $pricelist_param = $this->object->param('pricelist');
        $pricelist_param['title'] = str_replace('\\', '', $pricelist_param['title']);
        if ($pricelist->save($pricelist_param)) {
            // Reset the pricelist object
            $this->pricelist = $pricelist;
            $pricelist_item = $this->object->param('pricelist_item');
            if (is_null($pricelist_item)) {
                $pricelist_item = array();
            } elseif (is_string($pricelist_item)) {
                $pricelist_item = json_decode($pricelist_item, true);
            }
            // Create price list items
            $item_mapper = C_Pricelist_Item_Mapper::get_instance();
            foreach ($pricelist_item as $id => $updates) {
                // Set the pricelist associated to each item
                $updates['pricelist_id'] = $pricelist->id();
                $updates['title'] = str_replace('\\', '', $updates['title']);
                if (!isset($updates['source_data'])) {
                    $updates['source_data'] = '';
                } else {
                    // These fields are omitted from the form sent to the browser as they're both extraneous (the user
                    // can't edit them and the data is retrieved from the catalog anyway) and costly in size to transmit
                    // simply parsing the DOM is grab all of the printlab source_data attributes is slow in the browser.
                    $sd = $updates['source_data'];
                    $manager = C_NextGEN_Printlab_Manager::get_instance();
                    $catalog = $manager->get_catalog($sd['catalog_id']);
                    $product = $catalog->find_product($sd['product_id']);
                    // TODO: Remove the next line in some way
                    // The cost must always be stored in the original amount provided by the printlab, but the
                    // manage-pricelist page submits the cost value after it's been converted to the site currency.
                    $updates['cost'] = $product->get_cost();
                    // $sd['catalog_id']      = $updates['source_data]['catalog_id'];
                    $sd['category_id'] = $product->category_original;
                    $sd['lab_attributes'] = $product->get_property('lab_attributes');
                    $sd['lab_id'] = $product->lab_id;
                    $sd['lab_properties'] = $product->get_property('lab_properties');
                    $sd['parent_category_id'] = $product->category;
                    $sd['product_id'] = strval($product->id);
                    $updates['source_data'] = $sd;
                }
                if (strpos($id, 'new-') !== FALSE) {
                    // Form validation & retrieving errors from pricelist items is not currently
                    // working / complete but we can skip creating new blank entries
                    if (empty($updates['title'])) {
                        continue;
                    }
                    $item = $item_mapper->create($updates);
                    $item->save();
                } else {
                    $item = $item_mapper->find($id, TRUE);
                    $item->save($updates);
                }
            }
            if (isset($_REQUEST['deleted_items'])) {
                $pricelist->destroy_items($_REQUEST['deleted_items']);
            }
            return array('redirect_url' => admin_url("edit.php?post_type=ngg_pricelist&ngg_edit=1&id=" . $pricelist->id() . '&message=saved'));
        }
        return array('error' => __('An error occurred saving this pricelist', 'nextgen-gallery-pro'));
    }
}
/**
 * Sets default values for added ecommerce settings
 *
 * @mixin C_Display_Type_Mapper
 * @adapts I_Display_Type_Mapper
 */
class A_Ecommerce_Display_Type_Mapper extends Mixin
{
    function set_defaults($entity)
    {
        $this->call_parent('set_defaults', $entity);
        $this->object->_set_default_value($entity, 'settings', 'is_ecommerce_enabled', FALSE);
    }
}
class A_Ecommerce_Factory extends Mixin
{
    function ngg_order($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return new C_NextGen_Pro_Order($properties, $mapper, $context);
    }
    function order($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return $this->ngg_order($properties, $mapper, $context);
    }
}
class A_Ecommerce_Gallery extends Mixin
{
    function define_columns()
    {
        $this->object->define_column('pricelist_id', 'BIGINT', 0, TRUE);
    }
}
class A_Ecommerce_Image extends Mixin
{
    function define_columns()
    {
        $this->object->define_column('pricelist_id', 'BIGINT', 0, TRUE);
    }
}
class A_Ecommerce_Instructions_Controller extends C_NextGen_Admin_Page_Controller
{
    function get_page_title()
    {
        return __('Ecommerce Set Up', 'nextgen-gallery-pro');
    }
    function get_page_heading()
    {
        return __('Ecommerce Set Up', 'nextgen-gallery-pro');
    }
    function get_required_permission()
    {
        return 'NextGEN Change options';
    }
}
class A_Ecommerce_Instructions_Form extends Mixin
{
    function get_title()
    {
        return $this->get_page_heading();
    }
    function get_page_heading()
    {
        return __('Getting Started', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('ecommerce_instructions');
    }
    function _render_ecommerce_instructions_field()
    {
        $i18n = $this->get_i18n_strings();
        $ecommerce_steps = array('ecommerce_pages' => $i18n->ecommerce_pages, 'studio_address' => $i18n->studio_address, 'payment_gateway' => $i18n->payment_gateway, 'image_settings' => $i18n->image_settings, 'pro_lightbox' => $i18n->pro_lightbox, 'pricelist_created' => $i18n->pricelist_created, 'pricelist_associated' => $i18n->assocated_pricelist, 'has_ssl' => $i18n->has_ssl, 'enable_ecommerce' => $i18n->enabled_ecommerce);
        $printlab_steps = array('active_license' => $i18n->has_active_license, 'card_on_file' => $i18n->card_on_file, 'has_printlab_items' => $i18n->has_items, 'image_resizing' => $i18n->image_resizing);
        return $this->render_partial('photocrati-nextgen_pro_ecommerce#instructions', array('can_pro_wizard_run' => M_NextGen_Pro_Ecommerce::can_pro_wizard_run(), 'nonce' => wp_create_nonce('check_ecommerce_requirements'), 'status' => M_NextGen_Pro_Ecommerce::check_ecommerce_requirements(), 'i18n' => $i18n, 'ecommerce_steps' => $ecommerce_steps, 'printlab_steps' => $printlab_steps, 'render_status' => array($this, 'render_status')), TRUE);
    }
    function render_status($status, $step_id, $default)
    {
        $i18n = $this->get_i18n_strings();
        $classname = $default;
        if (array_key_exists($step_id, $status)) {
            if ($status[$step_id] === 'optional') {
                $classname = 'optional';
            } else {
                if ($status[$step_id]) {
                    $classname = 'done';
                } else {
                    $classname = 'required';
                }
            }
        }
        echo sprintf("<span class='ngg-status %s'>%s</span>", $classname, $i18n->{$classname});
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        $router = C_Router::get_instance();
        wp_enqueue_style('photocrati-nextgen_pro_ecommerce_instructions', $this->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_instructions.css'));
        wp_enqueue_script('photocrati-nextgen_pro_ecommerce_instructions-js', $this->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_instructions.js'), array('jquery', 'photocrati_ajax'));
        wp_localize_script('photocrati-nextgen_pro_ecommerce_instructions-js', 'ecommerce_instructions_i18n', get_object_vars($this->object->get_i18n_strings()));
    }
    function get_i18n_strings()
    {
        $retval = new stdClass();
        $retval->intro = __('Want help? Watch our short ecommerce overview video!', 'nextgen-gallery-pro');
        $retval->ecom_requirements = __('The following are required for ALL ECOMMERCE including manual and automated print fulfillment.', 'nextgen-gallery-pro');
        $retval->ecom_colors = __('Status will show green if done, red if still needed.', 'nextgen-gallery-pro');
        $retval->print_requirements = __('The following are required only for AUTOMATED PRINT FULFILLMENT.', 'nextgen-gallery-pro');
        $retval->checking = __('Checking...', 'nextgen-gallery-pro');
        $retval->check_now = __('Check Now', 'nextgen-gallery-pro');
        $retval->check_again = __('Check Again', 'nextgen-gallery-pro');
        $retval->ecomm_header = __("How to create a gallery with ecommerce", 'nextgen-gallery-pro');
        $retval->unknown = __('Unknown', 'nextgen-gallery-pro');
        $retval->done = __('Done', 'nextgen-gallery-pro');
        $retval->required = __('Required', 'nextgen-gallery-pro');
        $retval->optional = __('Optional', 'nextgen-gallery-pro');
        $retval->ecommerce_pages = sprintf(__('Choose pages to use for Checkout, Order Confirmation, Cancel, and Digital Downloads. %s', 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_ecommerce_options'), __('Ecommerce Settings', 'nextgen-gallery-pro')));
        $retval->studio_address = sprintf(__("Add studio name, email, and address. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_ecommerce_options'), __('Ecommerce Settings', 'nextgen-gallery-pro')));
        $retval->payment_gateway = sprintf(__("Set up a payment gateway. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_ecommerce_options'), __('Ecommerce Settings.', 'nextgen-gallery-pro')));
        $retval->image_settings = sprintf(__("Enable image backups. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_other_options'), __('Other Options', 'nextgen-gallery-pro')));
        $retval->image_resizing = sprintf(__("Enabled image resizing on upload. %s", "nextgen-gallery-pro"), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_other_options'), __('Other Options', 'nextgen-gallery-pro')));
        $retval->pro_lightbox = sprintf(__("Select Pro Lighbox as your lightbox. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_other_options'), __('Other Options ', 'nextgen-gallery-pro')));
        $retval->pricelist_created = sprintf(__("Create a Pricelist. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('edit.php?post_type=ngg_pricelist'), __('Manage Pricelists', 'nextgen-gallery-pro')));
        $retval->assocated_pricelist = sprintf(__("Associate your pricelist with a gallery. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=nggallery-manage-gallery'), __('Manage Galleries', 'nextgen-gallery-pro')));
        $retval->enabled_ecommerce = __("When inserting your gallery on a page, be sure to enable ecommerce.", 'nextgen-gallery-pro');
        $retval->has_active_license = sprintf(__("Have active NextGEN Pro License. %s", 'nextgen-gallery-pro'), sprintf('<a href="https://www.imagely.com/account" target="_blank">%s</a>', __('Login to Imagely to renew', 'nextgen-gallery-pro')));
        $retval->card_on_file = sprintf(__("Have credit card on file (it will charged to cover wholesale cost from print lab). %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('admin.php?page=ngg_ecommerce_options'), __('Ecommerce Options', 'nextgen-gallery-pro')));
        $retval->has_items = sprintf(__("Add Print Lab items to a Pricelist. %s", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', admin_url('edit.php?post_type=ngg_pricelist'), __('Manage Pricelists', 'nextgen-gallery-pro')));
        $retval->has_ssl = sprintf(__("Add SSL certificate. Without HTTPS NextGen Gallery Pro cannot process payments.", 'nextgen-gallery-pro'), sprintf('<a href="%s" target="_blank">%s</a>', 'https://searchengineland.com/google-starts-giving-ranking-boost-secure-httpsssl-sites-199446', __('SEO and search results', 'nextgen-gallery-pro')), sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.searchenginejournal.com/chrome-browser-https/', __('insecure', 'nextgen-gallery-pro')));
        $retval->additional_documentation = sprintf(__("Additional Documentation on %s", 'nextgen-gallery-pro'), sprintf("<a target='_blank' href='%s'>%s</a>", 'https://www.imagely.com/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ecommerce', __('imagely.com', 'nextgen-gallery-pro')));
        $retval->documentation_links = array('https://www.imagely.com/docs/ecommerce-overview/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ecommerce' => __('Ecommerce Overview', 'nextgen-gallery-pro'), 'https://www.imagely.com/docs/ecommerce-settings/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ecommerce' => __('How to Configure Ecommerce Options', 'nextgen-gallery-pro'), 'https://www.imagely.com/docs/create-pricelist/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ecommerce' => __('How to Create and Assign a Pricelist', 'nextgen-gallery-pro'), 'https://www.imagely.com/docs/add-ecommerce/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ecommerce' => __('How to Add Ecommerce to a Gallery', 'nextgen-gallery-pro'));
        $retval->proofing_header = __('How to create a proofing gallery', 'nextgen-gallery-pro');
        $retval->proofing_step_1 = sprintf(__("Configure your %s.", 'nextgen-gallery-pro'), sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=ngg_ecommerce_options'), __('proofing settings', 'nextgen-gallery-pro')));
        $retval->proofing_step_2 = sprintf(__("Select %s as your desired lightbox effect.", 'nextgen-gallery-pro'), sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=ngg_other_options'), __('NextGen Pro Lightbox', 'nextgen-gallery-pro')));
        $retval->proofing_step_3 = __("When adding a gallery via the NextGen Insert Gallery Window, click the option to enable proofing.", 'nextgen-gallery-pro');
        return $retval;
    }
}
/**
 * @property C_NextGen_Admin_Page_Controller object
 */
class A_Ecommerce_Options_Controller extends Mixin
{
    /**
     * @return string
     */
    function get_page_title()
    {
        return __('Ecommerce Options', 'nextgen-gallery-pro');
    }
    /**
     * @return string
     */
    function get_page_heading()
    {
        return $this->get_page_title();
    }
    /**
     * @return stdClass
     */
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        return $i18n;
    }
    /**
     * @return string
     */
    function get_required_permission()
    {
        return 'NextGEN Change options';
    }
    function create_new_page($title, $content)
    {
        global $user_ID;
        $page = array('post_type' => 'page', 'post_status' => 'publish', 'post_content' => $content, 'post_author' => $user_ID, 'post_title' => $title, 'comment_status' => 'closed');
        return wp_insert_post($page);
    }
    function save_action()
    {
        if ($ecommerce = $this->object->param('ecommerce')) {
            $settings = C_NextGen_Settings::get_instance();
            // If we change currencies, we have to update the price of all pricelist items to cost + default markup
            $update_pricelist_item_price = $settings->ecommerce_currency != $ecommerce['currency'];
            foreach ($ecommerce as $key => $value) {
                $key = "ecommerce_{$key}";
                $settings->{$key} = $value;
            }
            if ($ecommerce['page_checkout'] == '') {
                $settings->ecommerce_page_checkout = $this->create_new_page(__('Shopping Cart', 'nextgen-gallery-pro'), '[ngg_pro_checkout]');
            } else {
                $this->add_shortcode_to_post($settings->ecommerce_page_checkout = $ecommerce['page_checkout'], '[ngg_pro_checkout]');
            }
            if ($ecommerce['page_thanks'] == '') {
                $settings->ecommerce_page_thanks = $this->create_new_page(__('Thanks', 'nextgen-gallery-pro'), '[ngg_pro_order_details]');
            } else {
                $this->add_shortcode_to_post($settings->ecommerce_page_thanks = $ecommerce['page_thanks'], '[ngg_pro_order_details]');
            }
            if ($ecommerce['page_cancel'] == '') {
                $settings->ecommerce_page_cancel = $this->create_new_page(__('Order Cancelled', 'nextgen-gallery-pro'), __('You order was cancelled.', 'nextgen-gallery-pro'));
            } else {
                $this->add_shortcode_to_post($settings->ecommerce_page_cancel = $ecommerce['page_cancel'], __('Your order was cancelled', 'nextgen-gallery-pro'), TRUE);
            }
            if ($ecommerce['page_digital_downloads'] == '') {
                $settings->ecommerce_page_digital_downloads = $this->create_new_page(__('Digital Downloads', 'nextgen-gallery-pro'), __('[ngg_pro_digital_downloads]'));
            } else {
                $this->add_shortcode_to_post($settings->ecommerce_page_digital_downloads = $ecommerce['page_digital_downloads'], '[ngg_pro_digital_downloads]');
            }
            if (isset($ecommerce['cart_menu_item']) && $ecommerce['cart_menu_item'] != 'none') {
                $this->add_checkout_page_to_menu();
            } else {
                $this->remove_checkout_page_from_menu();
            }
            if (!M_NextGen_Pro_Ecommerce::is_valid_license()) {
                $settings->ecommerce_tax_enable = 0;
            }
            if ($settings->save() && $update_pricelist_item_price) {
                $manager = C_NextGEN_Printlab_Manager::get_instance();
                foreach ($manager->get_catalog_ids() as $id) {
                    $catalog = $manager->get_catalog($id);
                    C_NextGen_Pro_Currencies::get_conversion_rate($catalog->currency, $settings->ecommerce_currency);
                }
                $this->update_pricelist_item_prices();
            }
        }
    }
    function add_checkout_page_to_menu()
    {
        $checkout_page_id = intval(C_NextGen_Settings::get_instance()->ecommerce_page_checkout);
        foreach (get_nav_menu_locations() as $location => $menu_id) {
            $items = wp_get_nav_menu_items($menu_id);
            $has_checkout_page = FALSE;
            if (is_array($items) and !empty($items)) {
                foreach ($items as $item) {
                    if ($item instanceof WP_Post && intval($item->object_id) == $checkout_page_id) {
                        $has_checkout_page = TRUE;
                    }
                }
            }
            if (!$has_checkout_page) {
                $checkout_page = WP_Post::get_instance($checkout_page_id);
                wp_update_nav_menu_item($menu_id, 0, $args = array('menu-item-object-id' => intval($checkout_page_id), 'menu-item-object' => $checkout_page->post_type, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-classes' => 'nextgen-menu-cart-icon-auto'));
            }
            break;
            // only add to the first navigation menu location
        }
    }
    function remove_checkout_page_from_menu()
    {
        $checkout_page_id = C_NextGen_Settings::get_instance()->ecommerce_page_checkout;
        foreach (get_nav_menu_locations() as $location => $menu_id) {
            $items = wp_get_nav_menu_items($menu_id);
            if (!is_array($items) || empty($items)) {
                continue;
            }
            foreach ($items as $item) {
                if ($item instanceof WP_Post && intval($item->object_id) == $checkout_page_id && in_array('nextgen-menu-cart-icon-auto', $item->classes)) {
                    _wp_delete_post_menu_item($item->db_id);
                }
            }
        }
    }
    function update_pricelist_item_prices()
    {
        global $wpdb;
        $mapper = C_Pricelist_Item_Mapper::get_instance();
        $post_ids = array();
        $post_content_when_clauses = array();
        $meta_value_when_clauses = array();
        foreach ($mapper->find_all() as $item) {
            if (isset($item->cost)) {
                // Mark this post as one to be updated
                $post_ids[] = $item->ID;
                $item->price = $mapper->get_price($item, TRUE, TRUE, FALSE, TRUE);
                $post = $mapper->_convert_entity_to_post($item);
                $post_content_when_clauses[] = "WHEN {$post->ID} THEN \"{$post->post_content_filtered}\"";
                $meta_value_when_clauses[] = "WHEN (post_id = {$post->ID} AND meta_key = 'price') THEN {$item->price}";
            }
        }
        // Are there posts to update?
        if ($post_ids) {
            $post_ids = implode(",", $post_ids);
            $post_content_when_clauses = implode("\n", $post_content_when_clauses);
            $meta_value_when_clauses = implode("\n", $meta_value_when_clauses);
            // Update posts table
            $sql = trim("\n                                UPDATE {$wpdb->posts}\n                                SET\n                                    post_content = (\n                                        CASE ID\n                                            {$post_content_when_clauses}\n                                        END\n                                    ),\n                                    post_content_filtered = (\n                                        CASE ID\n                                            {$post_content_when_clauses}\n                                        END\n                                    )\n                                \n                                WHERE ID IN ({$post_ids}); \n                            ");
            $wpdb->query($sql);
            // Update postmeta table
            $sql = trim("\n                    UPDATE ({$wpdb->postmeta})\n                    SET meta_value = (\n                        CASE\n                            {$meta_value_when_clauses}\n                        END\n                    )\n                    WHERE post_id IN ({$post_ids}) AND meta_key = 'price';\n                ");
            $wpdb->query($sql);
            return TRUE;
        }
        return FALSE;
    }
    function add_shortcode_to_post($post_id, $shortcode, $only_if_empty = FALSE)
    {
        if ($post = get_post($post_id)) {
            if ($only_if_empty) {
                if (strlen($post->post_content) == 0) {
                    $post->post_content .= "\n" . $shortcode;
                    wp_update_post($post);
                }
            } elseif (strpos($post->post_content, $shortcode) === FALSE) {
                $post->post_content .= "\n" . $shortcode;
                wp_update_post($post);
            }
        }
    }
}
/** @property C_Form $object */
class A_Ecommerce_Options_Form extends Mixin
{
    function get_title()
    {
        return $this->get_page_heading();
    }
    function get_page_heading()
    {
        return __('General Options', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array(
            'nextgen_pro_ecommerce_currency',
            'nextgen_pro_ecommerce_page_checkout',
            'nextgen_pro_ecommerce_page_thanks',
            'nextgen_pro_ecommerce_page_cancel',
            'nextgen_pro_ecommerce_page_digital_downloads',
            'nextgen_pro_ecommerce_cart_menu_item',
            'nextgen_pro_ecommerce_not_for_sale_msg',
            'nextgen_pro_ecommerce_studio_name',
            'nextgen_pro_ecommerce_studio_street_address',
            'nextgen_pro_ecommerce_studio_address_line',
            'nextgen_pro_ecommerce_studio_city',
            'nextgen_pro_ecommerce_home_country',
            'nextgen_pro_ecommerce_home_state',
            'nextgen_pro_ecommerce_home_zip',
            'nextgen_pro_ecommerce_studio_email',
            'nextgen_pro_ecommerce_domestic_shipping_rate',
            'nextgen_pro_ecommerce_intl_shipping_rate',
            // 'nextgen_pro_ecommerce_whcc_intl_shipping_rate',
            'nextgen_pro_ecommerce_tax_enable',
            'nextgen_pro_ecommerce_cookies_enable',
        );
    }
    function get_i18n_strings()
    {
        $i18n = NULL;
        try {
            $i18n = $this->call_parent('get_i18n_strings');
        } catch (Exception $ex) {
        }
        if (!$i18n) {
            $i18n = new stdClass();
        }
        $i18n->calculating = __('Calculating...', 'nextgen-gallery-pro');
        $i18n->currency_changed = sprintf(__("If you change your currency, any print lab items in your pricelist will have their price updated using the last bulk markup value applied to that pricelist.\n\nIf no previous bulk markup value is found, a default markup of %d%% will be applied.\n\nPlease select OK to continue or CANCEL to revert to the previous currency selected.", "nextgen-gallery-pro"), NGG_PRO_ECOMMERCE_DEFAULT_MARKUP);
        $i18n->error_empty = __('%s cannot be empty.', 'nextgen-gallery-pro');
        $i18n->error_invalid = __('%s is in an invalid format.', 'nextgen-gallery-pro');
        $i18n->error_minimum = __('%s needs to be at least %s characters.', 'nextgen-gallery-pro');
        $i18n->form_invalid = __('Form contains errors, please fix these errors before saving.', 'nextgen-gallery-pro');
        $i18n->invalid_zip = __('Invalid zip or postal code.', 'nextgen-gallery-pro');
        $i18n->license_expired = __('Your NextGEN Pro license has expired. Sales tax has been disabled.', 'nextgen-gallery-pro');
        $i18n->select_country = __('Select Country', 'nextgen-gallery-pro');
        $i18n->select_region = __('Select Region', 'nextgen-gallery-pro');
        return $i18n;
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        $router = C_Router::get_instance();
        if (!wp_script_is('sprintf')) {
            wp_register_script('sprintf', $router->get_static_url('photocrati-nextgen_pro_ecommerce#sprintf.js'));
        }
        if (!wp_script_is('stripe-v3')) {
            wp_register_script('stripe-v3', 'https://js.stripe.com/v3/');
        }
        // Enqueue fontawesome
        if (method_exists('M_Gallery_Display', 'enqueue_fontawesome')) {
            M_Gallery_Display::enqueue_fontawesome();
        }
        wp_enqueue_style('fontawesome');
        wp_enqueue_style('photocrati-nextgen_pro_ecommerce_options', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_options.css'));
        wp_enqueue_script('photocrati-nextgen_pro_ecommerce_options-settings-js', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_options_form_settings.js'), array('jquery', 'jquery-ui-tooltip', 'jquery.nextgen_radio_toggle', 'sprintf', 'stripe-v3'), NGG_PRO_ECOMMERCE_MODULE_VERSION);
        $settings = C_NextGen_Settings::get_instance();
        wp_localize_script('photocrati-nextgen_pro_ecommerce_options-settings-js', 'NGG_Pro_EComm_Settings', array('iso_4217_countries' => C_NextGen_Pro_Currencies::$countries, 'i18n' => $this->object->get_i18n_strings(), 'country_list_json_url' => M_NextGen_Pro_Ecommerce::get_country_list_json_url(), 'selected_country' => $settings->ecommerce_home_country, 'selected_state' => $settings->ecommerce_home_state, 'field_selectors' => array('root' => '#ngg_page_content form', 'name' => '#ecommerce_studio_name', 'street_address' => '#ecommerce_studio_street_address', 'address_line' => '#ecommerce_studio_address_line', 'city' => '#ecommerce_studio_city', 'country' => '#ecommerce_home_country', 'state' => '#ecommerce_home_state', 'zip' => '#ecommerce_home_zip', 'email' => '#ecommerce_studio_email', 'paypal_email' => '#ecommerce_paypal_email', 'paypal_user' => '#ecommerce_paypal_username', 'paypal_pass' => '#ecommerce_paypal_password', 'paypal_sig' => '#ecommerce_paypal_signature', 'paypal_std_email' => '#ecommerce_paypal_std_email', 'stripe_key_pub' => '#ecommerce_stripe_key_public', 'stripe_key_priv' => '#ecommerce_stripe_key_private')));
    }
    function _render_nextgen_pro_ecommerce_not_for_sale_msg_field()
    {
        $settings = C_NextGen_Settings::get_instance();
        // _render_select_field only needs $model->name
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_textarea_field($model, 'not_for_sale_msg', __("\"Not for sale\" Message", 'nextgen-gallery-pro'), $settings->ecommerce_not_for_sale_msg);
    }
    function _render_nextgen_pro_ecommerce_home_country_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        // _render_select_field only needs $model->name
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_select_field($model, 'home_country', __('<strong>Studio Country</strong>', 'nextgen-gallery-pro'), array(), $settings->ecommerce_home_country);
    }
    function _render_nextgen_pro_ecommerce_home_state_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'home_state', __('<strong>Studio State</strong>', 'nextgen-gallery-pro'), '', FALSE);
    }
    function _render_nextgen_pro_ecommerce_home_zip_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'home_zip', __('<strong>Studio Postal Code</strong> (required for taxes)', 'nextgen-gallery-pro'), $settings->get('ecommerce_home_zip'), FALSE);
    }
    function _render_nextgen_pro_ecommerce_studio_name_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'studio_name', __('<strong>Studio Name</strong>', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_name'), FALSE);
    }
    function _render_nextgen_pro_ecommerce_studio_street_address_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'studio_street_address', __('<strong>Studio Street Address</strong>', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_street_address'), FALSE);
    }
    function _render_nextgen_pro_ecommerce_studio_address_line_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'studio_address_line', __('Studio Address Line #2', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_address_line'), FALSE);
    }
    function _render_nextgen_pro_ecommerce_studio_city_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_text_field($model, 'studio_city', __('<strong>Studio City</strong>', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_city'), FALSE);
    }
    function _render_nextgen_pro_ecommerce_studio_email_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        $input = $this->object->_render_text_field($model, 'studio_email', __('<strong>Studio Email</strong>', 'nextgen-gallery-pro'), $settings->get('ecommerce_studio_email'), FALSE);
        $input = str_replace("/>", "readonly onfocus=\"if (this.hasAttribute('readonly')) {this.removeAttribute('readonly'); this.blur(); this.trigger('focus');}\"/>", $input);
        return $input;
    }
    function _retrieve_page_list()
    {
        $pages = apply_filters('ngg_ecommerce_page_list', get_pages());
        $options = array('' => __('Create new', 'nextgen-gallery-pro'));
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }
        return $options;
    }
    function _render_nextgen_pro_ecommerce_currency_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        $currencies = array();
        foreach (C_NextGen_Pro_Currencies::$currencies as $id => $currency) {
            $currencies[$id] = $currency['name'];
        }
        return $this->object->_render_select_field($model, 'currency', __('Currency', 'nextgen-gallery-pro'), $currencies, C_NextGen_Settings::get_instance()->ecommerce_currency);
    }
    function _render_nextgen_pro_ecommerce_page_checkout_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        $pages = $this->_retrieve_page_list();
        return $this->object->_render_select_field($model, 'page_checkout', __('Checkout page', 'nextgen-gallery-pro'), $pages, C_NextGen_Settings::get_instance()->ecommerce_page_checkout, __("This page requires the [ngg_pro_checkout] shortcode, which will be automatically added if not already present. Selecting \"Create new\" will create a new page that will appear in your Primary Menu unless you've customized your menu settings: http://codex.wordpress.org/Appearance_Menus_SubPanel", 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_page_thanks_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        $pages = $this->_retrieve_page_list();
        return $this->object->_render_select_field($model, 'page_thanks', __('Thank-you page', 'nextgen-gallery-pro'), $pages, C_NextGen_Settings::get_instance()->ecommerce_page_thanks, __("This page should have the [ngg_pro_order_details] shortcode, which will be automatically added if not already present. Selecting \"Create new\" will create a new page that will appear in your Primary Menu unless you've customized your menu settings: http://codex.wordpress.org/Appearance_Menus_SubPanel", 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_page_cancel_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        $pages = $this->_retrieve_page_list();
        return $this->object->_render_select_field($model, 'page_cancel', __('Cancel page', 'nextgen-gallery-pro'), $pages, C_NextGen_Settings::get_instance()->ecommerce_page_cancel, __("Selecting \"Create new\" will create a new page that will appear in your Primary Menu unless you've customized your menu settings: http://codex.wordpress.org/Appearance_Menus_SubPanel", 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_page_digital_downloads_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        $pages = $this->_retrieve_page_list();
        return $this->object->_render_select_field($model, 'page_digital_downloads', __('Digital downloads page', 'nextgen-gallery-pro'), $pages, C_NextGen_Settings::get_instance()->ecommerce_page_digital_downloads, __("This page requires the [ngg_pro_digital_downloads] shortcode, which will be automatically added if not already present. Selecting \"Create new\" will create a new page that will appear in your Primary Menu unless you've customized your menu settings: http://codex.wordpress.org/Appearance_Menus_SubPanel", 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_tax_enable_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $value = $settings->ecommerce_tax_enable;
        $license = M_NextGen_Pro_Ecommerce::is_valid_license();
        if (!$license) {
            $value = 0;
        }
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#field_tax_enable', array('display_type_name' => 'ecommerce', 'name' => 'tax_enable', 'label' => __('Enable sales tax', 'nextgen-gallery-pro'), 'value' => $value, 'text' => __('A valid NextGen Pro license is required to calculate sales tax.', 'nextgen-gallery-pro'), 'tax1' => __('SALES TAX NOTE: Sales tax is complex. ', 'nextgen-gallery-pro'), 'tax2' => __('CLICK HERE', 'nextgen-gallery-pro'), 'tax3' => __(' to learn more about sales tax and how NextGEN Pro calculates it. Because we use a third party service (TaxJar), an active Pro license is required to enable sales tax.', 'nextgen-gallery-pro'), 'hidden' => FALSE, 'isValid' => $license), TRUE);
    }
    function _render_nextgen_pro_ecommerce_domestic_shipping_rate_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#shipping_field', array('display_type_name' => 'ecommerce', 'name' => 'domestic_shipping', 'name_amount' => 'domestic_shipping_rate', 'label' => __('Domestic shipping for manual-fulfilled items', 'nextgen-gallery-pro'), 'options' => array('flat_rate' => __('Flat Rate', 'nextgen-gallery-pro'), 'percent_rate' => __('Percentage Rate', 'nextgen-gallery-pro')), 'options_pieces' => array('flat_rate' => $currency['code'], 'percent_rate' => __('%', 'nextgen-gallery-pro')), 'value' => $settings->ecommerce_domestic_shipping, 'value_amount' => $settings->ecommerce_domestic_shipping_rate), true);
    }
    function _render_nextgen_pro_ecommerce_intl_shipping_rate_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#shipping_field', array('display_type_name' => 'ecommerce', 'name' => 'intl_shipping', 'name_amount' => 'intl_shipping_rate', 'label' => __('Allow International shipping for manual-fulfilled items', 'nextgen-gallery-pro'), 'options' => array('disabled' => __('Disabled', 'nextgen-gallery-pro'), 'flat_rate' => __('Flat Rate', 'nextgen-gallery-pro'), 'percent_rate' => __('Percentage Rate', 'nextgen-gallery-pro')), 'options_pieces' => array('flat_rate' => $currency['code'], 'percent_rate' => __('%', 'nextgen-gallery-pro')), 'value' => $settings->ecommerce_intl_shipping, 'value_amount' => $settings->ecommerce_intl_shipping_rate), true);
    }
    function _render_nextgen_pro_ecommerce_whcc_intl_shipping_rate_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#intl_shipping_field', array('tooltip' => __('WHCC will fulfill your automated print lab orders. WHCC is based in the US and will provide a shipping cost estimate at the time of checkout. If you want to allow shipments outside US/Canada, you will need to turn this option on and configure the settings below. They\'ll be used to charge for shipping when users order prints. WHCC will then charge you (through us) separately for the cost of shipping.', 'nextgen-gallery-pro'), 'display_type_name' => 'ecommerce', 'name' => 'whcc_intl_shipping', 'name_amount' => 'whcc_intl_shipping_rate', 'label' => _('Allow Automated Print Lab Shipments Outside US and Canada'), 'options' => array('disabled' => __('Disabled', 'nextgen-gallery-pro'), 'flat_rate' => __('Flat Rate', 'nextgen-gallery-pro'), 'percent_rate' => __('Percentage Rate', 'nextgen-gallery-pro')), 'options_pieces' => array('flat_rate' => $currency['code'], 'percent_rate' => __('%', 'nextgen-gallery-pro')), 'value' => $settings->ecommerce_whcc_intl_shipping, 'value_amount' => $settings->ecommerce_whcc_intl_shipping_rate), true);
        return $output;
    }
    function _render_nextgen_pro_ecommerce_cookies_enable_field($model)
    {
        $settings = C_NextGen_Settings::get_instance();
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_radio_field($model, 'cookies_enable', __('Use cookies for cart storage', 'nextgen-gallery-pro'), $settings->ecommerce_cookies_enable, __("Cookies are adequate for most customers but can only hold a limited number (around 30) of products due to browser limitations. When disabled the browser localStorage API will be used which does not have this problem but cart contents will be different on example.com vs www.example.com as well as across HTTP/HTTPS", 'nextgen-gallery-pro'));
    }
    function _render_nextgen_pro_ecommerce_cart_menu_item_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_select_field($model, 'cart_menu_item', __('Cart menu icon', 'nextgen-gallery-pro'), array('none' => __('None', 'nextgen-gallery-pro'), 'icon' => __('Icon Only', 'nextgen-gallery-pro'), 'icon_with_items' => __('Icon Only (When Cart Has Items)', 'nextgen-gallery-pro'), 'icon_and_total' => __('Icon & Total', 'nextgen-gallery-pro'), 'icon_and_total_with_items' => __('Icon & Total (When Cart Has Items)', 'nextgen-gallery-pro')), C_NextGen_Settings::get_instance()->ecommerce_cart_menu_item, __('Determines the appearance of the Checkout page selected above when shown as an entry inside a menu. When a setting other than None is selected, the checkout page will be added to the first navigation menu registered by your theme.', 'nextgen-gallery-pro'));
    }
}
class A_Ecommerce_Pages extends Mixin
{
    function setup()
    {
        $this->object->add(NGG_PRO_ECOMMERCE_OPTIONS_PAGE, array('adapter' => 'A_Ecommerce_Options_Controller', 'parent' => 'ngg_ecommerce_options', 'add_menu' => TRUE));
        $this->object->add('ngg_manage_pricelists', array('url' => '/edit.php?post_type=ngg_pricelist', 'menu_title' => __('Manage Pricelists', 'nextgen-gallery-pro'), 'permission' => 'NextGEN Change options', 'parent' => 'ngg_ecommerce_options'));
        $this->object->add('ngg_manage_coupons', array('url' => '/edit.php?post_type=ngg_coupon', 'menu_title' => __('Manage Coupons', 'nextgen-gallery-pro'), 'permission' => 'NextGEN Change options', 'parent' => 'ngg_ecommerce_options'));
        $this->object->add('ngg_manage_orders', array('url' => '/edit.php?post_type=ngg_order', 'menu_title' => __('View Orders', 'nextgen-gallery-pro'), 'permission' => 'NextGEN Change options', 'parent' => 'ngg_ecommerce_options'));
        $this->object->add('ngg_manage_proofs', array('url' => '/edit.php?post_type=nextgen_proof', 'menu_title' => __('View Proofs', 'nextgen-gallery-pro'), 'permission' => 'NextGEN Change options', 'parent' => 'ngg_ecommerce_options'));
        $this->object->add(NGG_PRO_ECOMMERCE_INSTRUCTIONS_PAGE, array('adapter' => 'A_Ecommerce_Instructions_Controller', 'parent' => 'ngg_ecommerce_options'));
        return $this->call_parent('setup');
    }
}
/**
 * Class A_Ecommerce_Printlab_Form
 * @mixin C_Form
 */
class A_Ecommerce_Printlab_Form extends Mixin
{
    function get_title()
    {
        return $this->get_page_heading();
    }
    function get_page_heading()
    {
        return __('Print Lab Integration', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('ecommerce_stripe_connect');
    }
    function enqueue_static_resources()
    {
        parent::call_parent('enqueue_static_resources');
        wp_localize_script('photocrati-nextgen_pro_ecommerce_options-settings-js', 'print_lab_i18n', get_object_vars($this->get_i18n_strings()));
    }
    function get_i18n_strings()
    {
        $i18n = $this->page->get_i18n_strings();
        $i18n->faq1 = __("<strong>DO I NEED THIS?</strong> A credit card is needed only if you want to use automated print fulfillment.", 'nextgen-gallery-pro');
        $i18n->faq2 = __("<strong>IS THIS SECURE?</strong> Assuming you've enabled SSL on your website, then yes. This form sends your card information directly to Stripe, one of the world's leading payment processors. It is stored securely at Stripe, not locally by WordPress or NextGEN Gallery. Note: Without SSL, this form is not 100% secure. You should also enable SSL before receiving payments from your own visitors.", 'nextgen-gallery-pro');
        $i18n->faq3 = __("<strong>WILL YOU CHARGE ME?</strong> You will not be charged now. Your card will only be charged if someone submits a print lab order on your site. At that point, you will be billed for print and shipping costs from the print lab. You would pay those costs yourself if you worked directly with the lab. We're just automating the process for you.", 'nextgen-gallery-pro');
        $i18n->agreement = __("<strong>AGREEMENT: By submitting your card here, you authorise Imagely to bill your card for the cost of print lab orders.</strong>", 'nextgen-gallery-pro');
        $i18n->stripe_connect = __("Update credit card", 'nextgen-gallery-pro');
        $i18n->valid_card = __('Done! You have a valid credit card on file (last four digits %s).', 'nextgen-gallery-pro');
        $i18n->invalid_card = __('The credit card you submitted (last four digits %s) has expired.', 'nextgen-gallery-pro');
        $i18n->no_card = __('No card on file.', 'nextgen-gallery-pro');
        $i18n->connected = __('Your card is now connected and ready for automatic print lab fulfillment.', 'nextgen-gallery-pro');
        $i18n->not_connected = __("We're sorry, but we were unable to save your credit card information. Please check your card information and try again.", 'nextgen-gallery-pro');
        $i18n->remove_card = __('Remove card', 'nextgen-gallery-pro');
        $i18n->remove_card_err = __('There was a problem trying to remove your card', 'nextgen-gallery-pro');
        $i18n->card_removed = __('Your card has been removed', 'nextgen-gallery-pro');
        $i18n->non_https_warning = __('<strong>IMPORTANT: Your site is not using SSL/HTTPS. Please add SSL/HTTPS and return to this tab to add your credit card.</strong>', 'nextgen-gallery-pro');
        $i18n->btn_disabled = __("You are unable to save a credit card until SSL/HTTPS has been enabled for your site", 'nextgen-gallery-pro');
        return $i18n;
    }
    function get_last_4_digits()
    {
        $retval = FALSE;
        $card = C_NextGen_Settings::get_instance()->get('stripe_card_info');
        if ($card) {
            $retval = $card['last4'];
        }
        return $retval;
    }
    function has_card_expired()
    {
        $retval = TRUE;
        $card = C_NextGen_Settings::get_instance()->get('stripe_card_info');
        if ($card) {
            $expiry = new DateTime();
            $expiry->setDate(intval($card['exp_year']), intval($card['exp_month']), 1);
            $now = new DateTime();
            $retval = $now >= $expiry;
        }
        return $retval;
    }
    function get_stripe_customer_id()
    {
        return C_NextGen_Settings::get_instance()->get('stripe_cus_id', FALSE);
    }
    function _render_ecommerce_stripe_connect_field()
    {
        wp_localize_script('photocrati-nextgen_pro_ecommerce_options-settings-js', 'nggpro_stripe_data', array('server_url' => 'https://4osfgn6rvj.execute-api.us-east-1.amazonaws.com/latest/getSetupIntentSecret', 'return_url' => site_url("stripe_intents_rtn=1"), 'testing' => M_NextGen_Pro_Ecommerce::is_stripe_test_mode_enabled(), 'isSetupDone' => $this->get_stripe_customer_id() ? TRUE : FALSE, 'update_nonce' => base64_encode(json_encode(WPSimpleNonce::createNonce('saveCreditCardInfo'))), 'site_url' => site_url()));
        $delete_nonce = base64_encode(json_encode(WPSimpleNonce::createNonce('deleteCreditCardInfo')));
        return $this->render_partial("photocrati-nextgen_pro_ecommerce#stripe-connect", array('i18n' => $this->object->get_i18n_strings(), 'expired' => $this->has_card_expired(), 'last_4_digits' => $this->get_last_4_digits(), 'delete_nonce' => $delete_nonce, 'is_ssl' => is_ssl()), TRUE);
    }
}
class A_Ecommerce_Pro_Lightbox_Form extends A_NextGen_Pro_Lightbox_Form
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'ecommerce_pro_lightbox_ecommerce_header';
        $fields[] = 'ecommerce_pro_lightbox_display_cart';
        return $fields;
    }
    function enqueue_static_resources()
    {
        wp_enqueue_script('ngg_pro_ecommerce_lightbox_form', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_pro_lightbox_form.js'));
        return $this->call_parent('enqueue_static_resources');
    }
    function _render_ecommerce_pro_lightbox_ecommerce_header_field($lightbox)
    {
        return $this->_render_header_field($lightbox, 'ecommerce', __('ECommerce', 'nextgen-gallery-pro'));
    }
    function _render_ecommerce_pro_lightbox_display_cart_field($lightbox)
    {
        $value = NULL;
        if (is_array($lightbox->values) && isset($lightbox->values['nplModalSettings'])) {
            if (isset($lightbox->values['nplModalSettings']['display_cart'])) {
                $value = $lightbox->values['nplModalSettings']['display_cart'];
            }
        } elseif (isset($lightbox->display_settings['display_cart'])) {
            $value = $lightbox->display_settings['display_cart'];
        }
        return $this->_render_radio_field($lightbox, 'display_cart', __('Display cart initially', 'nextgen-gallery-pro'), $value, __('When on the cart sidebar will be opened at startup. If the "Display Comments" option is also on the comments panel will open instead.', 'nextgen-gallery-pro'));
    }
}
class A_Manual_Pricelist_Settings_Form extends Mixin
{
    function get_title()
    {
        return __('Manual Fulfillment Settings', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('manual_pricelist_settings');
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->domestic_shipping = __('Domestic shipping rate:', 'nextgen-gallery-pro');
        $i18n->global_shipping = __('International shipping rate:', 'nextgen-gallery-pro');
        $i18n->allow_global_shipping = __('Enable international shipping?', 'nextgen-gallery-pro');
        return $i18n;
    }
    function save_action()
    {
        return $this->get_model()->is_valid();
    }
    function _render_manual_pricelist_settings_field()
    {
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#accordion_pricelist_settings', array('settings' => $this->get_model()->settings, 'i18n' => $this->get_i18n_strings(), 'shipping_methods' => $this->object->get_shipping_methods()), TRUE);
    }
    function get_shipping_methods()
    {
        return array('flat' => __('Flat Rate', 'nextgen-gallery-pro'), 'percentage' => __('Percentage', 'nextgen-gallery-pro'));
    }
    function enqueue_static_resources()
    {
        wp_enqueue_script('nggpro_manual_pricelist_css', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#source_settings_manual.js'));
    }
}
class A_NextGen_Pro_Lightbox_Mail_Form extends Mixin
{
    function get_title()
    {
        return __('E-mail', 'nextgen-gallery-pro');
    }
    function get_page_heading()
    {
        return __('E-mail Settings', 'nextgen-gallery-pro');
    }
    function _get_field_names()
    {
        return array('ngg_pro_ecommerce_email_notification_subject', 'ngg_pro_ecommerce_email_notification_body', 'ngg_pro_ecommerce_enable_email_receipt', 'ngg_pro_ecommerce_email_receipt_subject', 'ngg_pro_ecommerce_email_receipt_body');
    }
    function get_proxy_model()
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $model;
    }
    function get_model()
    {
        return $settings = C_Settings_Model::get_instance();
    }
    function _render_ngg_pro_ecommerce_email_notification_subject_field()
    {
        return $this->_render_text_field($this->get_proxy_model(), 'email_notification_subject', __('Order notification e-mail subject:', 'nextgen-gallery-pro'), $this->get_model()->ecommerce_email_notification_subject, NULL, NULL, __('Subject', 'nextgen-gallery-pro'));
    }
    function _render_ngg_pro_ecommerce_email_notification_recipient_field()
    {
        return $this->_render_text_field($this->get_proxy_model(), 'email_notification_recipient', __('Order notification e-mail recipient:', 'nextgen-gallery-pro'), $this->get_model()->ecommerce_email_notification_recipient, NULL, NULL, __('john@example.com', 'nextgen-gallery-pro'));
    }
    function _render_ngg_pro_ecommerce_email_notification_body_field()
    {
        return $this->_render_textarea_field($this->get_proxy_model(), 'email_notification_body', __('Order notification e-mail content:', 'nextgen-gallery-pro'), $this->get_model()->ecommerce_email_notification_body, __("Wrap placeholders in %%param%%. Accepted placeholders: customer_name, email, total_amount, item_count, shipping_street_address, shipping_city, shipping_state, shipping_zip, shipping_country, order_id, hash, order_details_page, admin_email, blog_name, blog_description, blog_url, site_url, home_url, and file_list", 'nextgen-gallery-pro'), NULL);
    }
    function _render_ngg_pro_ecommerce_enable_email_receipt_field()
    {
        $model = $this->get_model();
        return $this->_render_radio_field($this->get_proxy_model(), 'enable_email_receipt', __('Send e-mail receipt to customer?', 'nextgen-gallery-pro'), $model->ecommerce_enable_email_receipt, __('If enabled a receipt will be sent to the customer after successful checkout', 'nextgen-gallery-pro'));
    }
    function _render_ngg_pro_ecommerce_email_receipt_subject_field()
    {
        $model = $this->get_model();
        return $this->_render_text_field($this->get_proxy_model(), 'email_receipt_subject', __('E-mail subject:', 'nextgen-gallery-pro'), $this->get_model()->ecommerce_email_receipt_subject, NULL, $model->ecommerce_enable_email_receipt ? FALSE : TRUE, __('Subject', 'nextgen-gallery-pro'));
    }
    function _render_ngg_pro_ecommerce_email_receipt_body_field()
    {
        $model = $this->get_model();
        return $this->_render_textarea_field($this->get_proxy_model(), 'email_receipt_body', __('E-mail content:', 'nextgen-gallery-pro'), $this->get_model()->ecommerce_email_receipt_body, __("Wrap placeholders in %%param%%. Accepted placeholders: customer_name, email, total_amount, item_count, shipping_street_address, shipping_city, shipping_state, shipping_zip, shipping_country, order_id, hash, order_details_page, admin_email, blog_name, blog_description, blog_url, site_url, and home_url", 'nextgen-gallery-pro'), $model->ecommerce_enable_email_receipt ? FALSE : TRUE);
    }
}
class A_NplModal_Ecommerce_Overrides extends Mixin
{
    function enqueue_lightbox_resources($displayed_gallery)
    {
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->thumbEffect == NGG_PRO_LIGHTBOX) {
            wp_enqueue_script('ngg_nplmodal_ecommerce', $this->get_static_url('photocrati-nextgen_pro_ecommerce#nplmodal_overrides.js'), ['photocrati-nextgen_pro_lightbox-1']);
        }
        $this->call_parent('enqueue_lightbox_resources', $displayed_gallery);
    }
}
/** @property C_NextGen_Admin_Page_Controller object */
class A_Payment_Gateway_Form extends Mixin
{
    /**
     * @return string
     */
    function get_title()
    {
        return $this->object->get_page_heading();
    }
    /**
     * @return string
     */
    function get_page_heading()
    {
        return __('Payment Gateway', 'nextgen-gallery-pro');
    }
    /**
     * @return array
     */
    function _get_field_names()
    {
        return array();
    }
    function save_action()
    {
        $ecommerce = $this->object->param('ecommerce');
        if (empty($ecommerce)) {
            return;
        }
    }
    function enqueue_static_resources()
    {
        wp_enqueue_script('photocrati-nextgen_pro_ecommerce_payment_gateway-settings-js', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#ecommerce_payment_gateway_form_settings.js'), array('jquery.nextgen_radio_toggle'));
    }
}
class A_Pricelist_Datamapper_Column extends Mixin
{
    function define_columns()
    {
        $this->object->define_column('pricelist_id', 'BIGINT', 0, TRUE);
    }
}
class A_Pricelist_Factory extends Mixin
{
    function ngg_pricelist($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return new C_Pricelist($properties, $mapper, $context);
    }
    function pricelist($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return $this->ngg_pricelist($properties, $mapper, $context);
    }
    function ngg_pricelist_item($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return new C_Pricelist_Item($properties, $mapper, $context);
    }
    function pricelist_item($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        return $this->ngg_pricelist_item($properties, $mapper, $context);
    }
}
/** @property C_Form object */
class A_Print_Category_Form extends Mixin
{
    function get_title()
    {
        $title = __('Print Pricelist', 'nextgen-gallery-pro');
        switch ($this->object->context) {
            case NGG_PRO_ECOMMERCE_CATEGORY_CANVAS:
                $title = __('Canvas', 'nextgen-gallery-pro');
                break;
            case NGG_PRO_ECOMMERCE_CATEGORY_METAL_PRINTS:
                $title = __('Metal Prints', 'nextgen-gallery-pro');
                break;
            case NGG_PRO_ECOMMERCE_CATEGORY_MOUNTED_PRINTS:
                $title = __('Mounted Prints', 'nextgen-gallery-pro');
                break;
            case NGG_PRO_ECOMMERCE_CATEGORY_PRINTS:
                $title = __('Prints', 'nextgen-gallery-pro');
                break;
        }
        return $title;
    }
    /**
     * @param array $attrs
     * @param string|false $currency Example '840' for USD, '978' for EUR
     * @return string
     */
    static function render_price_field($attrs = array(), $currency = FALSE)
    {
        // Wish I could use a map and anonymous function here, but we're stuck with
        // PHP 5.2 and create_function() is NOT an alternative
        $attr_list = array();
        foreach ($attrs as $k => $v) {
            $attr_list[] = "{$k}=\"" . esc_attr($v) . "\"";
        }
        $attr_list = implode(" ", $attr_list);
        return sprintf(M_NextGen_Pro_Ecommerce::get_price_format_string($currency, TRUE, TRUE), "<input autocomplete='off' {$attr_list}/>");
    }
    function _get_field_names()
    {
        return array('print_pricelist_items');
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->add_another_item = __('Add another item', 'nextgen-gallery-pro');
        $i18n->cost_header = __('Cost', 'nextgen-gallery-pro');
        $i18n->cost_header_alt = __('Cost (%s)', 'nextgen-gallery-pro');
        $i18n->delete = __('Delete', 'nextgen-gallery-pro');
        $i18n->item_title_placeholder = __('Enter title of the item', 'nextgen-gallery-pro');
        $i18n->name_header = __('Name', 'nextgen-gallery-pro');
        $i18n->no_items = __('No items available for this source.', 'nextgen-gallery-pro');
        $i18n->price_header = __('Price', 'nextgen-gallery-pro');
        $i18n->price_header_alt = __('Price (%s)', 'nextgen-gallery-pro');
        $i18n->price_header_tooltip = __('All amounts shown are in %s', 'nextgen-gallery-pro');
        $i18n->cost_header_tooltip = __('All amounts shown are in %s. Due to fluctuations in currency conversion rates prices may not be exact.', 'nextgen-gallery-pro');
        return $i18n;
    }
    function save_action()
    {
        /** @var C_Pricelist $pricelist */
        $pricelist = $this->object->get_model();
        return $pricelist->is_valid();
    }
    function _render_print_pricelist_items_field()
    {
        $manager = C_NextGEN_Printlab_Manager::get_instance();
        $settings = C_NextGen_Settings::get_instance();
        /** @var C_Pricelist $pricelist */
        $pricelist = $this->object->get_model();
        $items = $pricelist->get_category_items($this->object->context);
        $site_currency_id = $cost_currency_id = $price_currency_id = $settings->ecommerce_currency;
        // If we cannot get a conversion ratio we use the catalog's currency instead of the site setting
        // TODO - this doesn't look generic and will break when we add more print labs
        $catalog = $manager->get_catalog('whcc');
        if ($catalog->currency !== $site_currency_id) {
            $currency_conversion_error = get_transient(C_NextGen_Pro_Currencies::get_conversion_error_transient_name($catalog->currency, $site_currency_id));
            if ($currency_conversion_error === FALSE) {
                $cost_currency_id = $site_currency_id;
            } else {
                $cost_currency_id = $catalog->currency;
            }
        }
        $cost_currency = C_NextGen_Pro_Currencies::$currencies[$cost_currency_id];
        $price_currency = C_NextGen_Pro_Currencies::$currencies[$price_currency_id];
        $cost_step = 1.0 / pow(10, $cost_currency['exponent']);
        $price_step = 1.0 / pow(10, $price_currency['exponent']);
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#accordion_print_items', array(
            'cost_currency' => $cost_currency,
            'cost_currency_id' => $cost_currency_id,
            'cost_step' => $cost_step,
            'i18n' => $this->get_i18n_strings(),
            'item_category' => $this->object->context,
            'items' => $items,
            'price_currency' => $price_currency,
            'price_currency_id' => $price_currency_id,
            'price_step' => $price_step,
            'printlab_manager' => C_NextGEN_Printlab_Manager::get_instance(),
            'show_alt_headers' => $site_currency_id == '840' ? FALSE : TRUE,
            // Enable for all currencies but USD
            'item_mapper' => C_Pricelist_Item_Mapper::get_instance(),
        ), TRUE);
    }
    function enqueue_static_resources()
    {
        wp_enqueue_script('jquery.serialize-json-js', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#jquery.serializejson.js'), array('jquery'));
    }
}
class C_Currency_Conversion_Notice
{
    /** @var C_Currency_Conversion_Notice $_instance */
    protected static $_instance = NULL;
    /** @var string $_message */
    protected static $_message = '';
    /**
     * @return C_Currency_Conversion_Notice
     */
    public static function get_instance()
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
    /**
     * @return string
     */
    public function get_css_class()
    {
        return 'error';
    }
    /**
     * @param string $message
     */
    protected static function add_message($message)
    {
        if ($message === FALSE) {
            return;
        }
        if (!empty(self::$_message)) {
            self::$_message .= "<br/>";
        }
        self::$_message = $message;
    }
    /**
     * @return bool
     */
    public function is_renderable()
    {
        if (!C_NextGen_Admin_Page_Manager::is_requested()) {
            return FALSE;
        }
        // TODO: Remove this specific if() when a non-USD accepting printlab has been added
        // At the moment NextGen Pro's only printlab is WHCC which only accepts USD; for performance we
        // just skip this notification entirely if the site currency is USD as there's no conversion to do anyway.
        $settings = C_NextGen_Settings::get_instance();
        $currency = $settings->ecommerce_currency;
        if ($currency === '840') {
            return FALSE;
        }
        $manager = C_NextGEN_Printlab_Manager::get_instance();
        // TODO: Fix this
        // This is a clumsy hack. The problem is that this notice gets executed during the POST submission
        // when updating ecommerce settings and most importantly this notice is run BEFORE THE ECOMMERCE OPTIONS CONTROLLER
        // which means C_NextGen_Settings in no way yet represents what it's about to become.
        //
        // To ensure that this notification is presented correctly on the page loaded immediately after changing the
        // ecommerce options and uses the *new* currency we check if we're dealing with the ecommerce options page
        // and that a new currency has been provided before changing the currency we use in this notification
        if (!empty($_GET['page']) && $_GET['page'] === 'ngg_ecommerce_options' && !empty($_POST['ecommerce']) && !empty($_POST['ecommerce']['currency'])) {
            $new_currency = $_POST['ecommerce']['currency'];
            if (!empty(C_NextGen_Pro_Currencies::$currencies[$new_currency])) {
                $currency = $new_currency;
            }
        }
        foreach ($manager->get_catalog_ids() as $id) {
            $catalog = $manager->get_catalog($id);
            if ($catalog->currency !== $currency) {
                $transient_name = C_NextGen_Pro_Currencies::get_conversion_transient_name($catalog->currency, $currency);
                $transient_error_name = C_NextGen_Pro_Currencies::get_conversion_error_transient_name($catalog->currency, $currency);
                $error = get_transient($transient_error_name);
                if ($error !== FALSE) {
                    self::add_message($error);
                } else {
                    // lookup transient
                    $rate = get_transient($transient_name);
                    // doesn't exist: refresh our memory..
                    if ($rate === FALSE) {
                        $rate = C_NextGen_Pro_Currencies::get_conversion_rate($catalog->currency, $currency);
                    }
                    // no conversion: again look for the error message
                    if ($rate === 0) {
                        self::add_message(get_transient($transient_error_name));
                    }
                }
            }
        }
        if (!empty(self::$_message)) {
            return TRUE;
        }
        return FALSE;
    }
    /**
     * @return string
     */
    public function render()
    {
        return self::$_message;
    }
}
/**
 * NextGEN Gallery 2.0.66 didn't have proper implementations of handling backup images
 */
class Mixin_Pro_Storage extends Mixin
{
    /**
     * Use the 'backup' image as the 'original' so that generated images use the backup image as their source
     *
     * @param $image
     * @param bool $check_existance
     *
     * @return mixed
     */
    function get_original_abspath($image, $check_existance = FALSE)
    {
        return $this->object->get_image_abspath($image, 'backup', $check_existance);
    }
    /**
     * Gets the absolute path where the image is stored
     * Can optionally return the path for a particular sized image
     */
    function get_image_abspath($image, $size = 'full', $check_existance = FALSE)
    {
        $retval = NULL;
        $fs = C_Fs::get_instance();
        // Ensure that we have a size
        if (!$size) {
            $size = 'full';
        }
        // If we have the id, get the actual image entity
        if (is_numeric($image)) {
            $image = $this->object->_image_mapper->find($image);
        }
        // Ensure we have the image entity - user could have passed in an
        // incorrect id
        if (is_object($image)) {
            if ($gallery_path = $this->object->get_gallery_abspath($image->galleryid)) {
                $folder = $prefix = $size;
                switch ($size) {
                    # Images are stored in the associated gallery folder
                    case 'full':
                    case 'original':
                    case 'image':
                        $retval = $fs->join_paths($gallery_path, $image->filename);
                        break;
                    case 'backup':
                        $retval = $fs->join_paths($gallery_path, $image->filename . '_backup');
                        if (!@file_exists($retval)) {
                            $retval = $fs->join_paths($gallery_path, $image->filename);
                        }
                        break;
                    case 'thumbnails':
                    case 'thumbnail':
                    case 'thumb':
                    case 'thumbs':
                        $size = 'thumbnail';
                        $folder = 'thumbs';
                        $prefix = 'thumbs';
                    // deliberately no break here
                    // We assume any other size of image is stored in the a
                    //subdirectory of the same name within the gallery folder
                    // gallery folder, but with the size appended to the filename
                    default:
                        $image_path = $fs->join_paths($gallery_path, $folder);
                        // NGG 2.0 stores relative filenames in the meta data of
                        // an image. It does this because it uses filenames
                        // that follow conventional WordPress naming scheme.
                        if (isset($image->meta_data) && isset($image->meta_data[$size]) && isset($image->meta_data[$size]['filename'])) {
                            $image_path = $fs->join_paths($image_path, $image->meta_data[$size]['filename']);
                        } else {
                            $image_path = $fs->join_paths($image_path, "{$prefix}_{$image->filename}");
                        }
                        $retval = $image_path;
                        break;
                }
            }
        }
        // Check the existance of the file
        if ($retval && $check_existance) {
            if (!file_exists($retval)) {
                $retval = NULL;
            }
        }
        return $retval ? rtrim($retval, "/\\") : $retval;
    }
    /**
     * Backs up an image file
     * @param int|object $image
     */
    function backup_image($image)
    {
        $retval = FALSE;
        if ($image_path = $this->object->get_image_abspath($image)) {
            $retval = copy($image_path, $this->object->get_backup_abspath($image));
            // Store the dimensions of the image
            if (function_exists('getimagesize')) {
                if (!is_object($image)) {
                    $image = C_Image_Mapper::get_instance()->find($image);
                }
                if ($image) {
                    $dimensions = getimagesize($retval);
                    $image->meta_data['backup'] = array('filename' => basename($retval), 'width' => $dimensions[0], 'height' => $dimensions[1], 'generated' => microtime());
                }
            }
        }
        return $retval;
    }
    /**
     * Gets the absolute path of the backup of an original image
     * @param string $image
     */
    function get_backup_abspath($image)
    {
        return $this->object->get_image_abspath($image, 'backup');
    }
    function get_backup_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'backup');
    }
    function get_backup_url($image)
    {
        return $this->object->get_image_url($image, 'backup');
    }
}
/**
 * Class Mixin_Pro_Ecomm_Storage
 *
 * NextGen Gallery's get_original_abspath() points to the fullsize image which we don't want
 */
class Mixin_Pro_Ecomm_Storage extends Mixin
{
    /**
     * Use the 'backup' image as the 'original' so that generated images use the backup image as their source
     *
     * @param $image
     * @param bool $check_existance
     *
     * @return mixed
     */
    function get_original_abspath($image, $check_existance = FALSE)
    {
        return $this->object->get_image_abspath($image, 'backup', $check_existance);
    }
    /**
     * At some point NGG's get_image_abspath() changed so that 'original' returned the main image and not the _backup
     * version of it. This causes digital downloads to render from that main image which is not desired.
     *
     * @param int|stdClass|C_Image $image
     * @param string $size
     * @param bool $check_existance
     * @return null|string
     */
    function get_image_abspath($image, $size = 'full', $check_existance = FALSE)
    {
        if ($size === 'original') {
            $size = 'backup';
        }
        return $this->call_parent('get_image_abspath', $image, $size, $check_existance);
    }
}
class C_Digital_Downloads extends C_MVC_Controller
{
    static $instance = NULL;
    /**
     * @return C_Digital_Downloads
     */
    static function get_instance()
    {
        if (!self::$instance) {
            $klass = get_class();
            self::$instance = new $klass();
        }
        return self::$instance;
    }
    function get_i18n_strings($order)
    {
        $retval = new stdClass();
        $retval->image_header = __('Image', 'nextgen-gallery-pro');
        $retval->resolution_header = __('Resolution', 'nextgen-gallery-pro');
        $retval->item_description_header = __('Item', 'nextgen-gallery-pro');
        $retval->download_header = __('Download', 'nextgen-gallery-pro');
        $retval->order_info = sprintf(__('Digital Downloads for Order #%s', 'nextgen-gallery-pro'), $order->ID);
        return $retval;
    }
    function index_action()
    {
        wp_enqueue_style('ngg-digital-downloads-page', $this->get_static_url('photocrati-nextgen_pro_ecommerce#digital_downloads_page.css'));
        $retval = __('Oops! This page usually displays details for image purchases, but you have not ordered any images yet. Please feel free to continue browsing. Thanks for visiting.', 'nextgen-gallery-pro');
        if ($order = C_Order_Mapper::get_instance()->find_by_hash($this->param('order'), TRUE)) {
            // Display digital downloads for verified transactions
            if ($order->status == 'paid') {
                $retval = $this->render_download_list($order);
            } else {
                $retval = $this->render_partial('photocrati-nextgen_pro_ecommerce#waiting_for_confirmation', array('msg' => __("We haven't received payment confirmation yet. This may take a few minutes. Please wait...", 'nextgen-gallery-pro')), TRUE);
            }
        }
        return $retval;
    }
    function get_gallery_storage()
    {
        $storage = C_Gallery_Storage::get_instance();
        if (version_compare(NGG_PLUGIN_VERSION, '2.0.66.99') <= 0) {
            $storage->get_wrapped_instance()->add_mixin('Mixin_Pro_Storage');
        } else {
            $storage->get_wrapped_instance()->add_mixin('Mixin_Pro_Ecomm_Storage');
        }
        return $storage;
    }
    function render_download_list($order)
    {
        $cart = $order->get_cart()->to_array();
        $storage = $this->get_gallery_storage();
        $images = array();
        $settings = C_NextGen_Settings::get_instance();
        foreach ($cart['images'] as $image_obj) {
            foreach ($image_obj->items as $item) {
                $image = new stdClass();
                foreach (get_object_vars($image_obj) as $key => $val) {
                    $image->{$key} = $val;
                }
                if ($item->source == NGG_PRO_DIGITAL_DOWNLOADS_SOURCE) {
                    $named_size = 'backup';
                    // Use the full resolution image
                    if ($item->resolution != 0) {
                        $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
                        $params = array('width' => $item->resolution, 'height' => $item->resolution, 'crop' => FALSE, 'watermark' => FALSE, 'quality' => 100);
                        $named_size = $dynthumbs->get_size_name($params);
                        $new_path = $storage->get_image_abspath($image, $named_size, TRUE);
                        if (!$new_path) {
                            $thumbnail = $storage->generate_image_size($image, $named_size);
                            if ($thumbnail) {
                                $thumbnail->destruct();
                            }
                        }
                    }
                    if ($named_size == 'backup') {
                        // in case the backup files are protected by server side rules we serve fullsize images from
                        // an ajax endpoint.
                        //
                        // we don't need to honor permalink styles as this is mostly hidden just determine the most
                        // reliable path to the photocrati_ajax controller
                        $url = $settings->get('ajax_url');
                        $pos = strpos($url, '?');
                        if ($pos === FALSE) {
                            $url .= '?';
                        } else {
                            $url .= '&';
                        }
                        $url .= 'action=get_image_file&order_id=' . $order->hash . '&image_id=' . $image_obj->{$image_obj->id_field};
                        $image->download_url = $url;
                    } else {
                        $image->download_url = $storage->get_image_url($image, $named_size);
                    }
                    // Set other properties
                    $dimensions = $storage->get_image_dimensions($image, $named_size);
                    $image->dimensions = $dimensions;
                    $image->resolution = $dimensions['width'] . 'x' . $dimensions['height'];
                    $image->item_description = $item->title;
                    $image->thumbnail_url = $storage->get_thumbnail_url($image);
                    array_push($images, $image);
                }
            }
        }
        return $this->render_partial('photocrati-nextgen_pro_ecommerce#digital_downloads_list', array('images' => $images, 'order' => $order, 'i18n' => $this->get_i18n_strings($order)), TRUE);
    }
}
class C_Invalid_License_Notice
{
    /** @var C_Invalid_License_Notice */
    static $_instance = NULL;
    /**
     * @return string
     */
    function get_css_class()
    {
        return 'error';
    }
    /**
     * @return bool
     */
    function is_renderable()
    {
        return C_NextGen_Admin_Page_Manager::is_requested() && !M_NextGen_Pro_Ecommerce::is_valid_license();
    }
    /**
     * @return string
     */
    function render()
    {
        return __("<strong>Your NextGEN Pro license has expired.</strong> Automated print lab fulfillment and automated sales tax require active membership. To use these services, please renew your license at <a target='_blank' href='http://www.imagely.com'>imagely.com</a>.", 'nextgen-gallery-pro');
    }
    /**
     * @return C_Invalid_License_Notice
     */
    static function get_instance()
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
}
class E_NextGen_Mail_Missing_Details extends RuntimeException
{
    function __construct($message_or_previous = FALSE, $code = 0, $previous = NULL)
    {
        // We don't know if we have been passed a message yet or not
        $message = FALSE;
        // Determine if the first parameter is a string or exception
        if ($message_or_previous) {
            if (is_string($message_or_previous)) {
                $message = $message_or_previous;
            } else {
                $previous = $message_or_previous;
            }
        }
        // If no message was provided, create a default message
        if (!$message) {
            $message = __('To send an e-mail, recipient, subject, and content is required', 'nextgen-gallery-pro');
        }
        parent::__construct($message, $code);
    }
}
class C_Nextgen_Mail_Content
{
    var $_list;
    var $_private;
    var $_template;
    function __construct()
    {
        $this->_list = array();
        $this->_private = array();
    }
    function is_property($name)
    {
        return isset($this->_list[$name]);
    }
    function is_property_private($name)
    {
        return isset($this->_private[$name]) && $this->_private[$name];
    }
    function get_property($name)
    {
        if (isset($this->_list[$name])) {
            return $this->_list[$name];
        }
        return null;
    }
    function set_property($name, $value)
    {
        $this->_list[$name] = $value;
        $this->_private[$name] = false;
    }
    function set_property_private($name, $value)
    {
        $this->_list[$name] = $value;
        $this->_private[$name] = true;
    }
    function get_subject()
    {
        return $this->get_property('subject');
    }
    function set_subject($subject)
    {
        $this->set_property_private('subject', $subject);
    }
    function get_sender()
    {
        return $this->get_property('sender');
    }
    function set_sender($sender)
    {
        $this->set_property_private('sender', $sender);
    }
    function load_template($template_text)
    {
        $this->_template = $template_text;
    }
    function evaluate_template($template_text = null)
    {
        if ($template_text == null) {
            $template_text = $this->_template;
        }
        $template_text = str_replace(array("\r\n", "\n"), "\n", $template_text);
        $matches = null;
        if (preg_match_all('/%%(\\w+)%%/', $template_text, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $var = $match[1];
                $parts = explode('_', $var);
                $root = array_shift($parts);
                $name = implode('_', $parts);
                $replace = null;
                $var_value = !$this->is_property_private($var) ? $this->get_property($var) : null;
                if ($var_value == null) {
                    $var_meta = !$this->is_property_private($root) ? $this->get_property($root) : null;
                    if ($var_meta != null && isset($var_meta[$name])) {
                        $var_value = $var_meta[$name];
                    }
                }
                if ($var_value == null) {
                    // This is a place to have certain defaults set, or values which are not easily settable in a property list. It could also be extended in the future with custom callbacks etc.
                    switch ($root) {
                        case 'time':
                            switch ($name) {
                                case 'now_utc':
                                    // for clarification in case it's not obvious, this will replace the meta variable %%time_now_utc%% in the mail template
                                    $var_value = date(DATE_RFC850);
                                    break;
                            }
                            break;
                    }
                }
                if ($var_value != null) {
                    $replace = $var_value;
                }
                if (is_array($replace)) {
                    $replace = implode(', ', $replace);
                }
                $template_text = str_replace($match[0], $replace, $template_text);
            }
        }
        return $template_text;
    }
}
/*
* How you would send an e-mail

	$mailman = $registry->get_utility('I_Nextgen_Mail_Manager');
	$content = $mailman->create_content();
	$content->set_subject('test');
	$content->set_property('user', 'Test');
	$content->load_template('Hi %%user%%, test');

	$mailman->send_mail($content, 'some@email.com');
*/
class Mixin_Nextgen_Mail_Manager extends Mixin
{
    private $_from_name = NULL;
    private $_from_email = NULL;
    function create_content($type = null)
    {
        if ($type == null) {
            $type = 'C_Nextgen_Mail_Content';
        }
        return new $type();
    }
    function _override_from_name_hook($_)
    {
        return $this->_from_name;
    }
    function _override_from_email_hook($_)
    {
        return $this->_from_email;
    }
    function send($to_email, $subject = FALSE, $content = FALSE, $from_name = FALSE, $from_email = FALSE, $mail_headers = array())
    {
        $settings = C_NextGen_Settings::get_instance();
        // Ensure that we have a sender name
        if (!$from_name) {
            $from_name = $settings->get('ecommerce_studio_name');
        }
        if (!$from_name) {
            $from_name = $settings->get('ecommerce_studio_name');
        }
        if (!$from_name) {
            $from_name = get_bloginfo('name');
        }
        // Ensure that we have a sender e-mail
        if (!$from_email) {
            $from_email = M_NextGen_Pro_Ecommerce::get_studio_email_address();
        }
        $this->_from_name = $from_name;
        $this->_from_email = $from_email;
        // Get mail content
        $mail_body = null;
        if (is_string($content)) {
            $mail_body = $content;
        } else {
            if ($content instanceof C_Nextgen_Mail_Content) {
                if ($subject == null) {
                    $subject = $content->get_subject();
                }
                $mail_body = $content->evaluate_template();
            }
        }
        // Do we have everything required?
        if (!$to_email || !$subject || !$content) {
            throw new E_NextGen_Mail_Missing_Details();
        }
        // Override sender
        add_filter('wp_mail_from', array($this, '_override_from_email_hook'), PHP_INT_MAX - 1);
        add_filter('wp_mail_from_name', array($this, '_override_from_name_hook'), PHP_INT_MAX - 1);
        $retval = wp_mail($to_email, $subject, $mail_body, array_merge($mail_headers, array("From:\"{$from_name}\" <{$from_email}>")));
        remove_filter('wp_mail_from', array($this, '_override_from_email_hook'), PHP_INT_MAX - 1);
        remove_filter('wp_mail_from_name', array($this, '_override_from_name_hook'), PHP_INT_MAX - 1);
        return $retval;
    }
    /**
     * Please use send() instead
     * @deprecated
     * @see send()
     */
    function send_mail($content, $receiver, $subject = null, $sender = null, $mail_headers = array())
    {
        $mail_body = null;
        if (is_string($content)) {
            $mail_body = $content;
        } else {
            if ($content instanceof C_Nextgen_Mail_Content) {
                if ($subject == null) {
                    $subject = $content->get_subject();
                }
                if ($sender == null) {
                    $sender = $content->get_sender();
                }
                $mail_body = $content->evaluate_template();
            }
        }
        if ($mail_body != null) {
            if ($sender != null) {
                $mail_headers['From'] = $sender;
            }
            wp_mail($receiver, $subject, $mail_body, $mail_headers);
        }
    }
}
/**
 * @implements I_NextGen_Mail_Manager
 * @mixin Mixin_Nextgen_Mail_Manager
 */
class C_Nextgen_Mail_Manager extends C_Component
{
    static $_instances = array();
    function define($context = FALSE)
    {
        parent::define($context);
        $this->implement('I_Nextgen_Mail_Manager');
        $this->add_mixin('Mixin_Nextgen_Mail_Manager');
    }
    /**
     * @param bool|string $context
     * @return C_Nextgen_Mail_Manager
     */
    static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context])) {
            self::$_instances[$context] = new C_Nextgen_Mail_Manager($context);
        }
        return self::$_instances[$context];
    }
}
class C_NextGEN_Printlab_Catalog_Data
{
    static function get_whcc_currency()
    {
        return 'USD';
    }
    static function get_whcc_data($force = FALSE)
    {
        $timeout = NGG_PRO_WHCC_CATALOG_TTL;
        if (!($catalog = get_transient('ngg_whcc_catalog_standard', FALSE))) {
            $req = new WP_Http();
            $res = $req->get("https://s3.amazonaws.com/imagely-catalogs/catalog_standard.json", array('timeout' => 120));
            $catalog = !$res instanceof WP_Error && isset($res['body']) && ($json = json_decode($res['body'], TRUE)) ? $json : FALSE;
            if ($json) {
                set_transient('ngg_whcc_catalog_standard', $json, $timeout);
                set_transient('ngg_whcc_catalog_standard_version', is_object($json) ? $json->version : $json['version'], $timeout);
            }
        }
        return $catalog;
    }
}
class C_NextGEN_Printlab_Item
{
    var $_id = null;
    var $_catalog = null;
    var $_properties = array();
    var $_cache = array();
    function __construct(&$catalog, $properties)
    {
        $this->_catalog =& $catalog;
        $this->_properties = $properties;
    }
    function get_property($name)
    {
        if (isset($this->_properties[$name])) {
            return $this->_properties[$name];
        }
        return null;
    }
    function __get($name)
    {
        $retval = NULL;
        switch ($name) {
            case 'id':
                $retval = $this->get_property('hash');
                break;
            case 'lab_id':
                $retval = $this->get_property('id');
                break;
            case 'label':
                $options = $this->get_property('options');
                $retval = $this->get_property('label');
                if ($options) {
                    $retval .= ' (' . implode(', ', array_values($options)) . ')';
                }
                break;
            case 'catalog_id':
                $retval = $this->_catalog->id;
                break;
            case 'catalog':
                $retval = $this->_catalog;
                break;
            case 'cost':
                $retval = $this->get_property('price');
                break;
            default:
                $retval = $this->get_property($name);
        }
        return $retval;
    }
    function is_default()
    {
        return $this->is_common;
    }
    function get_cost()
    {
        if (!isset($this->_cache['cost'])) {
            $currency = C_NextGen_Pro_Currencies::$currencies[$this->_catalog->currency];
            $exponent = $currency['exponent'];
            $this->_cache['cost'] = bcadd($this->cost, 0.0, $exponent);
        }
        return $this->_cache['cost'];
    }
    function get_cost_display()
    {
        return M_NextGen_Pro_Ecommerce::get_formatted_price($this->get_cost(), $this->_catalog->currency);
    }
    /**
     * @param float|int $markup
     * @return float|int
     */
    function get_cost_estimate($markup = 0)
    {
        $item = new stdClass();
        $item->cost = $this->get_cost();
        return C_Pricelist_Item_Mapper::get_instance()->get_price($item, $markup, TRUE);
    }
    function get_cost_estimate_display($markup = 0)
    {
        return M_NextGen_Pro_Ecommerce::get_formatted_price($this->get_cost_estimate($markup));
    }
}
/**
 * @property string $id
 * @property string $currency
 */
class C_NextGEN_Printlab_Catalog
{
    var $_id = null;
    var $_data = null;
    var $_currency = null;
    var $_order_handler = null;
    var $_categories = array();
    var $_items = array();
    function __construct($id, $data, $currency)
    {
        ini_set('memory_limit', '1024M');
        $this->_id = $id;
        $this->_data = $data;
        $this->_currency = $currency;
        $this->_parse_data();
    }
    function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->_id;
            case 'currency':
                return $this->_currency;
        }
        return null;
    }
    function _parse_data()
    {
        foreach ($this->_data['categories'] as $category_id => $category) {
            $products = isset($category['products']) ? $category['products'] : null;
            $parent = isset($category['parent']) ? $category['parent'] : null;
            $root = $parent != null ? $parent : $category_id;
            if ($products != null) {
                foreach ($products as $product_id => $product) {
                    $product['category'] = $root;
                    $product['category_original'] = $category_id;
                    $this->_items[$root][] = new C_NextGEN_Printlab_Item($this, $product);
                }
            }
            unset($category['products']);
            $category['id'] = $category_id;
            $category['gid'] = $this->_id . '.' . $category_id;
            $this->_categories[$category_id] = $category;
        }
    }
    /**
     * @return array
     */
    function get_categories()
    {
        return array_keys($this->_categories);
    }
    /**
     * @return array
     */
    function get_root_categories()
    {
        return array_keys($this->_items);
    }
    function get_category_info($category)
    {
        return $this->_categories[$category];
    }
    /**
     * @param $category
     * @return C_NextGEN_Printlab_Item[]|null
     */
    function get_category_items($category)
    {
        if (isset($this->_items[$category])) {
            usort($this->_items[$category], array($this, '_sort_by_label'));
            return $this->_items[$category];
        }
        return null;
    }
    function _sort_by_label($a, $b)
    {
        return strnatcasecmp($a->label, $b->label);
    }
    /**
     * @param string $product_id
     * @return C_NextGEN_Printlab_Item|null
     */
    function find_product($product_id)
    {
        foreach ($this->_items as $category => $items) {
            foreach ($items as $item) {
                if ($item->id == $product_id) {
                    return $item;
                }
            }
        }
        return null;
    }
    /**
     * @param string $order_hash
     * @return array|null
     */
    function get_order_data($order_hash)
    {
        return M_NextGen_Pro_Ecommerce::get_lab_order_status($order_hash);
    }
}
class C_NextGEN_Printlab_Manager extends C_Component
{
    static $_instances = array();
    var $_catalogs = array();
    /**
     * Returns an instance of the printlab manager
     * @return C_NextGEN_Printlab_Manager
     */
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
    /**
     * Defines the instance
     * @param mixed $context
     */
    function define($context = FALSE)
    {
        parent::define($context);
        $this->implement('I_NextGEN_Printlab_Manager');
        $this->add_catalog('whcc', C_NextGEN_Printlab_Catalog_Data::get_whcc_data(), C_NextGEN_Printlab_Catalog_Data::get_whcc_currency(), array('C_NextGEN_Printlab_Catalog_Data'));
    }
    function add_catalog($catalog_id, $catalog_data, $currency)
    {
        $currency = C_NextGen_Pro_Currencies::find_currency_id($currency);
        $catalog = new C_NextGEN_Printlab_Catalog($catalog_id, $catalog_data, $currency);
        $this->_catalogs[$catalog_id] = $catalog;
        return $catalog;
    }
    function remove_catalog($catalog_id)
    {
        if (isset($this->_catalogs[$catalog_id])) {
            unset($this->_catalogs[$catalog_id]);
        }
    }
    /**
     * @param $catalog_id
     * @return C_NextGEN_Printlab_Catalog|null
     */
    function get_catalog($catalog_id)
    {
        if (isset($this->_catalogs[$catalog_id])) {
            return $this->_catalogs[$catalog_id];
        }
        return null;
    }
    /**
     * @return string[]
     */
    public function get_catalog_ids()
    {
        $retval = array();
        foreach ($this->_catalogs as $catalog_id => $catalog) {
            $retval[] = $catalog_id;
        }
        return $retval;
    }
    function get_printlab_item($item_id)
    {
    }
}
class C_NextGen_Pro_Add_To_Cart
{
    static $_template_rendered = FALSE;
    function enqueue_static_resources()
    {
        $router = C_Router::get_instance();
        // For some reason ajax.js isn't registered yet in 2.0.67.14 and above, so we have
        // to do it manually.
        if (method_exists('M_Ajax', 'register_scripts')) {
            M_Ajax::register_scripts();
        }
        $dependencies = ['photocrati-nextgen_pro_lightbox-1'];
        if (version_compare(NGG_PLUGIN_VERSION, '2.0.67') <= 0) {
            $dependencies[] = 'ngg-store-js';
        }
        wp_enqueue_script('ngg-pro-lightbox-ecommerce-overrides', $router->get_static_url('photocrati-nextgen_pro_ecommerce#lightbox_overrides.js'), $dependencies, NGG_PRO_ECOMMERCE_MODULE_VERSION, TRUE);
        wp_enqueue_style('ngg-pro-add-to-cart', $router->get_static_url('photocrati-nextgen_pro_ecommerce#add_to_cart.css'), array(), NGG_PRO_ECOMMERCE_MODULE_VERSION);
        M_NextGen_Pro_Ecommerce::enqueue_cart_resources();
        if (!self::$_template_rendered) {
            self::$_template_rendered = TRUE;
            $parameters = array('not_for_sale_msg' => C_NextGen_Settings::get_instance()->ecommerce_not_for_sale_msg, 'categories' => $this->get_categories(), 'i18n' => $this->get_i18n_strings());
            $add_to_cart_wrapper = new C_MVC_View('photocrati-nextgen_pro_ecommerce#add_to_cart/wrapper', $parameters);
            $add_to_cart_header = new C_MVC_View('photocrati-nextgen_pro_ecommerce#add_to_cart/header', $parameters);
            $add_to_cart_normal_item = new C_MVC_View('photocrati-nextgen_pro_ecommerce#add_to_cart/normal_item', $parameters);
            $add_to_cart_download_item = new C_MVC_View('photocrati-nextgen_pro_ecommerce#add_to_cart/download_item', $parameters);
            wp_localize_script('ngg-pro-lightbox-ecommerce-overrides', 'ngg_add_to_cart_templates', array('add_to_cart_wrapper' => $add_to_cart_wrapper->render(TRUE), 'add_to_cart_header' => $add_to_cart_header->render(TRUE), 'add_to_cart_normal_item' => $add_to_cart_normal_item->render(TRUE), 'add_to_cart_download_item' => $add_to_cart_download_item->render(TRUE)));
            wp_localize_script('ngg-pro-lightbox-ecommerce-overrides', 'ngg_cart_i18n', (array) $this->get_i18n_strings());
        }
    }
    function get_categories()
    {
        $result = array();
        $manager = C_Pricelist_Category_Manager::get_instance();
        foreach ($manager->get_ids() as $id) {
            $category = $manager->get($id);
            $result[$id] = $this->_render_category_header_template($id, $category['title']);
        }
        return $result;
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->add_to_cart = __('Add To Cart', 'nextgen-gallery-pro');
        $i18n->checkout = __('View Cart / Checkout', 'nextgen-gallery-pro');
        $i18n->coupon_error = __('Invalid coupon', 'nextgen-gallery-pro');
        $i18n->description = __('Description', 'nextgen-gallery-pro');
        $i18n->free_price = __('Free', 'nextgen-gallery-pro');
        $i18n->item_count = __('%d item(s)', 'nextgen-gallery-pro');
        $i18n->not_for_sale = __('This image is not for sale', 'nextgen-gallery-pro');
        $i18n->price = __('Price', 'nextgen-gallery-pro');
        $i18n->qty_add_desc = __('Change quantities to update your cart.', 'nextgen-gallery-pro');
        $i18n->quantity = __('Quantity', 'nextgen-gallery-pro');
        $i18n->total = __('Total', 'nextgen-gallery-pro');
        $i18n->update_cart = __('Update Cart', 'nextgen-gallery-pro');
        $i18n->nggpl_cart_updated = __('Your cart has been updated', 'nextgen-gallery-pro');
        $i18n->nggpl_toggle_sidebar = __('Toggle cart sidebar', 'nextgen-gallery-pro');
        $i18n->download_add = __('Add', 'nextgen-gallery-pro');
        $i18n->download_free = __('Download', 'nextgen-gallery-pro');
        $i18n->download_remove = __('Remove', 'nextgen-gallery-pro');
        return $i18n;
    }
    function _render_category_header_template($id, $title)
    {
        return "<h3><span id='{$id}_header'>{$title}</span></h3><div class='nggpl-category_contents' id='{$id}'></div>";
    }
}
class C_NextGen_Pro_Cart
{
    protected $_parsing = FALSE;
    protected $_state = array();
    protected $_subtotal = NULL;
    protected $_shipping = NULL;
    protected $_total = NULL;
    protected $_settings = array();
    protected $_saved = FALSE;
    protected $_discount = NULL;
    protected $_undiscounted_subtotal = NULL;
    protected $_shipments = NULL;
    protected $_shipping_methods = NULL;
    protected $_tax_info = NULL;
    protected $_error = NULL;
    /** @var null|C_Coupon */
    protected $_coupon = NULL;
    /**
     * C_NextGen_Pro_Cart constructor.
     * @param null|array $json
     * @param array $cart_settings
     * @param bool $force_saved Use ONLY when working with orders pulled from the database; this sets the saved status to TRUE to prevent errors when working with legacy (before the '_saved' attribute) orders.
     */
    function __construct($json = NULL, $cart_settings = array())
    {
        if ($cart_settings && is_array($cart_settings)) {
            $this->_settings = $cart_settings;
        }
        $this->_settings = $this->validate_setting($this->_settings);
        if ($json) {
            $this->_parse_state($json);
        }
        $this->recalculate();
    }
    function is_saved_cart()
    {
        return $this->_saved;
    }
    function to_array()
    {
        $this->recalculate();
        // will only occur on non-saved carts
        $retval = array('images' => array(), 'image_ids' => array(), 'subtotal' => $this->get_subtotal(), 'shipments' => $this->get_shipments(), 'shipping_methods' => array_values($this->get_shipping_methods()), 'shipping' => $this->get_shipping(), 'total' => $this->get_total(), 'tax_info' => $this->get_tax_info(), 'tax' => $this->get_tax(), 'tax_enable' => $this->is_tax_enabled(), 'tax_rate' => $this->get_default_tax_rate(), 'has_shippable_items' => $this->has_shippable_items(), 'undiscounted_subtotal' => $this->get_undiscounted_subtotal(), 'settings' => $this->_settings, 'error' => $this->_error, 'currency' => $this->get_currency());
        foreach ($this->_state as $image_id => $image) {
            $image = clone $image;
            $items = $image->items;
            $image->item_ids = array();
            $image->items = array();
            foreach ($items as $source => $items_array) {
                foreach ($items_array as $pricelist_id => $inner_items_array) {
                    foreach ($inner_items_array as $item_id => $item) {
                        $image->item_ids[] = $item_id;
                        $image->items[$item_id] = $item;
                    }
                }
            }
            $retval['images'][$image_id] = $image;
            $retval['image_ids'][] = $image_id;
        }
        if (isset($this->_coupon) && ($discount = $this->get_discount())) {
            $retval['coupon'] = $this->_coupon;
            $retval['coupon']['discount_given'] = $discount;
        }
        return $retval;
    }
    function recalculate($force = FALSE)
    {
        if ($this->has_items() && ($force || !$this->is_saved_cart() && !$this->_parsing)) {
            $this->_intl_shipping = C_NextGen_Settings::get_instance()->ecommerce_intl_shipping;
            $this->_tax_enable = C_NextGen_Settings::get_instance()->get('ecommerce_tax_enable', FALSE);
            $this->_tax_rate = C_NextGen_Settings::get_instance()->get('$settings->ecommerce_tax_rate');
            $this->_tax_info = $this->_calculate_tax();
            $this->_discount = $this->_calculate_discount();
            $this->_undiscounted_subtotal = $this->_calculate_undiscounted_subtotal();
            $this->_subtotal = $this->_calculate_subtotal();
            $this->_shipments = $this->_calculate_shipments();
            $this->_shipping_methods = $this->_calculate_shipping_methods();
            $this->_settings['shipping_method'] = $this->_calculate_selected_shipping_method();
            $this->_shipments = $this->_calculate_shipments();
            $this->_shipping = $this->_calculate_shipping();
            try {
                // Prevent multiple tax calculations being done
                if (NULL === $this->_tax_info || !is_object($this->_tax_info)) {
                    $this->_tax_info = $this->_calculate_tax();
                }
            } catch (RuntimeException $exception) {
                $this->_error = $exception->getMessage();
                $this->_tax_info = new stdClass();
                $this->_tax_info->amount_to_collect = 0.0;
            }
            // _calculate_total() depends on and thus must follow _calculate_tax()
            $this->_total = $this->_calculate_total();
        }
    }
    function get_settings()
    {
        return $this->_settings;
    }
    function get_setting($key, $default = NULL)
    {
        return array_key_exists($key, $this->_settings) ? $this->_settings[$key] : $default;
    }
    /**
     * @param array $settings
     * @param array $overrides (optional)
     * @return array
     */
    function validate_setting($settings, $overrides = array())
    {
        $ngg_settings = C_NextGen_Settings::get_instance();
        if (!is_array($settings)) {
            $settings = array();
        }
        // Override fields
        if ($overrides) {
            foreach ($overrides as $key => $value) {
                if ($value) {
                    $settings[$key] = $value;
                }
            }
        }
        // Set default values for all shipping address fields
        foreach (array('shipping_address', 'studio_address') as $address_key) {
            if (!isset($settings[$address_key]) || !is_array($settings[$address_key])) {
                $settings[$address_key] = array();
            }
            foreach (array('name', 'street_address', 'address_line', 'city', 'state', 'zip', 'country') as $field) {
                // Ensure there's no whitespace or padding
                if (!empty($settings[$address_key][$field])) {
                    $settings[$address_key][$field] = trim(strip_tags($settings[$address_key][$field]));
                }
                if (empty($settings[$address_key][$field])) {
                    if ($address_key == 'studio_address') {
                        switch ($field) {
                            case 'name':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_studio_name');
                                break;
                            case 'street_address':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_studio_street_address');
                                break;
                            case 'address_line':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_studio_address_line');
                                break;
                            case 'city':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_studio_city');
                                break;
                            case 'state':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_home_state');
                                break;
                            case 'zip':
                                $settings[$address_key][$field] = $ngg_settings->get('ecommerce_home_zip');
                                break;
                            case 'email':
                                $settings[$address_key][$field] = M_NextGen_Pro_Ecommerce::get_studio_email_address();
                                break;
                            case 'country':
                                // Get country
                                if ($country_code = $ngg_settings->get('ecommerce_home_country')) {
                                    $settings[$address_key][$field] = $country_code;
                                } else {
                                    $settings[$address_key][$field] = '';
                                }
                                break;
                        }
                    } else {
                        if ($field == 'country' && ($country_code = $ngg_settings->get('ecommerce_home_country'))) {
                            $settings[$address_key][$field] = $country_code;
                        } else {
                            $settings[$address_key][$field] = '';
                        }
                    }
                }
            }
            if (!isset($settings['studio_address']['email'])) {
                $settings['studio_address']['email'] = M_NextGen_Pro_Ecommerce::get_studio_email_address();
            }
            if (isset($settings['saved'])) {
                $this->_saved = TRUE;
            }
        }
        return $settings;
    }
    /**
     * Simplified state to represent the cart:
     * array(
     *  'images'                        =>  array(
     *          1 (image_id)            =>  array(
     *              'items'             =>  array(
     *                  1 (item_id)     =>  array(
     *                      'quantity'  =>  2
     *                  )
     *              ),
     *              'item_ids'          =>  array(
     *                  1 (item_id)
     *              )
     *          )
     *  ),
     *  'image_ids'                     =>  array(
     *          1 (image_id)
     *  )
     * )
     * @var array $client_state
     * @var bool $force_saved When TRUE this will force $this->_saved to TRUE
     */
    function _parse_state($client_state)
    {
        $this->_parsing = TRUE;
        // Restore cached values so that we don't have calculate this stuff over and over
        foreach (array('undiscounted_subtotal', 'subtotal', 'shipping', 'total', 'settings', 'saved', 'discount', 'coupon', 'tax_info', 'tax_rate', 'intl_shipping', 'currency', 'error', 'shipments') as $param) {
            if (isset($client_state[$param])) {
                $key = "_{$param}";
                $this->{$key} = $client_state[$param];
            }
        }
        // Backwards compatbility - before print lab, we only had tax, not tax info
        if (isset($client_state['tax']) && !isset($client_state['tax_info'])) {
            $this->_tax_info = new stdClass();
            $this->_tax_info->amount_to_collect = floatval($client_state['tax']);
        }
        // Ensure that tax info is an object
        if (is_array($this->_tax_info)) {
            $this->_tax_info = $this->_arr_to_object($this->_tax_info);
        }
        // Ensure that shipments are objects, not arrays
        if ($this->_shipments) {
            foreach ($this->_shipments as $source => $shipments) {
                $this->_shipments[$source] = array_map(array($this, '_arr_to_object'), $shipments);
            }
        }
        if (isset($client_state['images']) and is_array($client_state['images'])) {
            foreach ($client_state['images'] as $image_id => $image_props) {
                $this->add_image($image_id, $image_props);
            }
        }
        if (!$this->is_saved_cart()) {
            $code = NULL;
            if (!empty($client_state['coupon'])) {
                $code = is_array($client_state['coupon']) ? $client_state['coupon']['code'] : $client_state['coupon'];
            }
            $this->apply_coupon($code);
        }
        $this->_parsing = FALSE;
    }
    function apply_coupon($code = NULL)
    {
        if (M_NextGen_Pro_Coupons::are_coupons_enabled() && !empty($code) && ($coupon = C_Coupon_Mapper::get_instance()->find_by_code($code, TRUE))) {
            if ($coupon->validate_current_availability()) {
                $this->_coupon = $coupon->get_limited_entity();
            } else {
                $this->_coupon = NULL;
            }
        } else {
            $this->_coupon = NULL;
        }
    }
    function add_items($items = array())
    {
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $image_id => $image_items) {
            $this->add_image($image_id, array('items' => $image_items));
        }
    }
    function has_items()
    {
        return count($this->_state) ? TRUE : FALSE;
    }
    function get_exif_orientation($image, $size = 'full')
    {
        $storage = C_Gallery_Storage::get_instance();
        // This method is necessary
        if (!function_exists('exif_read_data')) {
            return;
        }
        // We only need to continue if the Orientation tag is set
        $exif = @exif_read_data($storage->get_image_abspath($image, $size), 'exif');
        if (empty($exif['Orientation']) || $exif['Orientation'] == 1) {
            return;
        }
        $degree = FALSE;
        if ($exif['Orientation'] == 3) {
            $degree = 180;
        }
        if ($exif['Orientation'] == 6) {
            $degree = 90;
        }
        if ($exif['Orientation'] == 8) {
            $degree = 270;
        }
        return $degree;
    }
    /**
     * Gets an image from the cart or DB
     */
    function get_image($image_id, $fallback = NULL)
    {
        $image = isset($this->_state[$image_id]) ? $this->_state[$image_id] : FALSE;
        if (!$image) {
            $mapper = C_Image_Mapper::get_instance();
            if ($image = $mapper->find($image_id)) {
                $storage = C_Gallery_Storage::get_instance();
                $rotation = $this->get_exif_orientation($image);
                $dynthumbs = C_Dynamic_Thumbnails_Manager::get_instance();
                $params = array('width' => 200, 'height' => 200, 'crop' => FALSE, 'watermark' => FALSE, 'rotation' => $rotation, 'quality' => 100);
                $thumb_size = $dynthumbs->get_size_name($params);
                $params = array('width' => 1024, 'height' => 1024, 'crop' => FALSE, 'rotation' => $rotation);
                $crop_size = $dynthumbs->get_size_name($params);
                $image->thumbnail_url = $storage->get_image_url($image, $thumb_size);
                $image->dimensions = $storage->get_image_dimensions($image, $thumb_size);
                $image->width = $image->dimensions['width'];
                $image->height = $image->dimensions['height'];
                $image->full_url = $storage->get_full_url($image);
                $image->full_dimensions = $storage->get_full_dimensions($image);
                $image->exif_orientation = $rotation;
                $image->crop_url = $storage->get_image_url($image, $crop_size);
                $image->crop_dimensions = $storage->get_image_dimensions($image, $crop_size);
                $image->ecommerce_size = isset($image->meta_data['backup']) ? 'backup' : 'full';
                $image->md5 = $this->_get_image_checksum($image, $image->ecommerce_size);
                $image->url = $storage->get_image_url($image, $image->ecommerce_size);
                $image->items = array();
            } else {
                $image = $fallback ? (object) $fallback : NULL;
            }
        }
        return $image;
    }
    function get_item($image_id, $item_id, $fallback = NULL)
    {
        $item = FALSE;
        if (!$this->has_image($image_id)) {
            $this->add_image($image_id, array());
        }
        if ($image = $this->get_image($image_id)) {
            // First try to retrieve the item from the cart
            if (isset($image->items)) {
                foreach ($image->items as $source_id => $pricelist_to_items) {
                    foreach ($pricelist_to_items as $pricelist_id => $items) {
                        if (isset($items[$item_id])) {
                            $item = $items[$item_id];
                            break;
                        }
                    }
                    if ($item) {
                        break;
                    }
                }
            }
            // If no item was found in the cart, then look it up using the mapper
            if (!$item) {
                $mapper = C_Pricelist_Item_Mapper::get_instance();
                if ($item = $mapper->find($item_id)) {
                    $source = C_Pricelist_Source_Manager::get_instance()->get_handler($item->source);
                    $item->quantity = 0;
                    $item->shippable_to = $source->get_shippable_countries();
                    $item->crop_offset = $this->_calculate_item_crop_offset($image_id, $item);
                    if (!$item->crop_offset) {
                        $crop_offset = '';
                    }
                } else {
                    $item = $fallback ? (object) $fallback : NULL;
                }
            }
        }
        return $item;
    }
    function has_image($image_id)
    {
        return isset($this->_state[$image_id]);
    }
    function add_image($image_id, $image_props)
    {
        if ($image = $this->get_image($image_id, $image_props)) {
            // Get items from image props.
            $image_props = is_object($image_props) ? get_object_vars($image_props) : $image_props;
            $items = isset($image_props['items']) ? $image_props['items'] : array();
            // Persist the image to the cart
            foreach ($image_props as $key => $val) {
                $image->{$key} = $val;
            }
            unset($image->items);
            $this->_state[$image_id] = $image;
            // Add items associated with the image
            foreach ($items as $item_id => $item_props) {
                if (is_numeric($item_id)) {
                    $this->add_item($image_id, $item_id, $item_props);
                }
            }
            $this->recalculate();
        }
    }
    function _calculate_item_crop_offset($image_id, $item)
    {
        if ($image = $this->get_image($image_id)) {
            // calculate default crop_offset
            $full_dimensions = isset($image->crop_dimensions) ? $image->crop_dimensions : NULL;
            if ($full_dimensions != null && isset($item->source_data['lab_properties'])) {
                $print_ratio = $item->source_data['lab_properties']['aspect']['ratio'];
                $image_ratio = $full_dimensions['width'] / $full_dimensions['height'];
                if ($print_ratio != 0 && $image_ratio != 0) {
                    if ($print_ratio > 1 && $image_ratio < 1 || $print_ratio < 1 && $image_ratio > 1) {
                        $print_ratio = 1 / $print_ratio;
                    }
                    $ratio_diff = $image_ratio - $print_ratio;
                    if ($ratio_diff < 0) {
                        $crop_width = $full_dimensions['width'];
                        $crop_height = $crop_width / $print_ratio;
                    } else {
                        $crop_height = $full_dimensions['height'];
                        $crop_width = $crop_height * $print_ratio;
                    }
                    $crop_x = ($full_dimensions['width'] - $crop_width) / 2;
                    $crop_y = ($full_dimensions['height'] - $crop_height) / 2;
                    return sprintf('%d,%d,%d,%d', $crop_x, $crop_y, $crop_x + $crop_width, $crop_y + $crop_height);
                }
            }
        }
        return NULL;
    }
    /**
     * Returns the checksum of the image
     * @param $image
     * @param string $size
     * @return null|string
     */
    function _get_image_checksum($image, $size = 'full')
    {
        $storage = C_Gallery_Storage::get_instance();
        $retval = NULL;
        if ($storage->has_method('get_image_checksum')) {
            $retval = $storage->get_image_checksum($image, $size);
        } else {
            if ($image_abspath = $storage->get_image_abspath($image, $size, TRUE)) {
                $retval = md5_file($image_abspath);
            }
        }
        return $retval;
    }
    function add_item($image_id, $item_id, $item_props = array())
    {
        $image = $this->get_image($image_id);
        if ($image && ($item = $this->get_item($image_id, $item_id, $item_props))) {
            // Treat an object as if it were an array
            if (is_object($item_props)) {
                $item_props = get_object_vars($item_props);
            }
            // Ensure that the items source key exists as an array
            if (!isset($image->items[$item->source])) {
                $image->items[$item->source] = array();
            }
            // Ensure that the item's pricelist id exists as a key in the array
            if (!isset($image->items[$item->source][$item->pricelist_id])) {
                $image->items[$item->source][$item->pricelist_id] = array();
            }
            // Append item props
            foreach ($item_props as $key => $val) {
                if ($key == 'quantity') {
                    $val = intval($val);
                }
                $item->{$key} = $val;
            }
            // Assure that the quantity has been provided
            if (!isset($item->quantity)) {
                $item->quantity = 1;
            }
            // Persist
            $image->items[$item->source][$item->pricelist_id][$item_id] = $item;
            $this->_state[$image_id] = $image;
            $this->recalculate();
            return TRUE;
        }
        return FALSE;
    }
    function has_international_shipping_rate()
    {
        $intl_shipping = isset($this->_intl_shipping) ? $this->_intl_shipping : C_NextGen_Settings::get_instance()->get('ecommerce_intl_shipping', '');
        return intl_shipping != '' && intl_shipping != 'disabled';
    }
    function get_images($with_items = FALSE)
    {
        $retval = array();
        foreach (array_values($this->_state) as $image) {
            $i = clone $image;
            if (!$with_items) {
                unset($i->items);
            }
            $retval[] = $i;
        }
        return $retval;
    }
    function get_items($source = NULL)
    {
        $retval = array();
        foreach (array_values($this->_state) as $image) {
            $items = is_array($image) ? $image['items'] : $image->items;
            foreach ($items as $source_id => $pricelists) {
                foreach ($pricelists as $pricelist_id => $items) {
                    foreach ($items as $item_id => $item) {
                        if (!$source || $item->source == $source) {
                            $i = clone $item;
                            $i->image = clone $image;
                            $retval[] = $i;
                        }
                    }
                }
            }
        }
        return $retval;
    }
    function is_tax_enabled()
    {
        return isset($this->_tax_enable) ? $this->_tax_enable : (bool) C_NextGen_Settings::get_instance()->get('ecommerce_tax_enable', FALSE);
    }
    function get_default_tax_rate()
    {
        return isset($this->_tax_rate) ? $this->_tax_rate : C_NextGen_Settings::get_instance()->get('$settings->ecommerce_tax_rate');
    }
    /**
     * Determines if the cart has digital downloads
     * @return bool
     */
    function has_digital_downloads()
    {
        $retval = FALSE;
        foreach ($this->_state as $image_id => $image) {
            foreach ($image->items as $source => $pricelists) {
                foreach ($pricelists as $pricelist_id => $items) {
                    foreach ($items as $item) {
                        if ($item->source == NGG_PRO_DIGITAL_DOWNLOADS_SOURCE) {
                            $retval = TRUE;
                            break;
                        }
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * @return int|float
     */
    function _calculate_discount()
    {
        $currency = C_NextGen_Pro_Currencies::$currencies[$this->get_currency()];
        if (!empty($this->_coupon)) {
            $coupon = NULL;
            $id = NULL;
            if (is_string($this->_coupon)) {
                $id = $this->_coupon;
            } else {
                if (is_array($this->_coupon) && isset($this->_coupon['code'])) {
                    $id = $this->_coupon['code'];
                }
            }
            $coupon = C_Coupon_Mapper::get_instance()->find_by_code($id, TRUE);
            if ($coupon) {
                if (!$coupon->validate_current_availability()) {
                    return 0;
                }
                return $coupon->get_discount_amount($this->get_undiscounted_subtotal(), $currency['exponent']);
            }
        }
        return 0;
    }
    function prepare_for_persistence()
    {
        $this->_saved = TRUE;
    }
    function get_discount()
    {
        if (!$this->_discount) {
            $this->_discount = $this->_calculate_discount();
        }
        return $this->_discount;
    }
    /**
     * @return C_Coupon|null
     */
    public function get_coupon()
    {
        if (!$this->_coupon) {
            return NULL;
        }
        return $this->_coupon;
    }
    function get_currency()
    {
        return isset($this->_currency) ? $this->_currency : C_NextGen_Settings::get_instance()->get('ecommerce_currency');
    }
    /**
     * Gets the subtotal of all items in the cart
     *
     * @return float
     */
    function _calculate_subtotal()
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$this->get_currency()];
        $total = bcsub($this->get_undiscounted_subtotal(), $this->get_discount(), $currency['exponent']);
        if ($total < 0.0) {
            $total = 0;
        }
        return $total;
    }
    function get_subtotal()
    {
        if ($this->_subtotal === NULL) {
            $this->_calculate_subtotal();
        }
        return $this->_subtotal;
    }
    function get_undiscounted_subtotal()
    {
        if ($this->_undiscounted_subtotal === NULL) {
            $this->_calculate_undiscounted_subtotal();
        }
        return $this->_undiscounted_subtotal;
    }
    function _calculate_undiscounted_subtotal()
    {
        $retval = 0;
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$this->get_currency()];
        foreach ($this->_state as $image_id => $image) {
            foreach ($image->items as $source => $pricelists) {
                foreach ($pricelists as $pricelist_id => $items) {
                    foreach ($items as $item_id => $item) {
                        $retval = bcadd($retval, round(bcmul($item->price, $item->quantity, intval($currency['exponent']) * 2), $currency['exponent'], PHP_ROUND_HALF_UP), $currency['exponent']);
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * Returns all items sorted by pricelist and then source
     */
    function get_sorted_items()
    {
        $retval = array();
        foreach (array_values($this->_state) as $image) {
            $items = is_array($image) ? $image['items'] : $image->items;
            foreach ($items as $source => $pricelists) {
                foreach ($pricelists as $pricelist_id => $items) {
                    foreach ($items as $item) {
                        if (!isset($retval[$pricelist_id])) {
                            $retval[$pricelist_id] = array();
                        }
                        if (!isset($retval[$pricelist_id][$source])) {
                            $retval[$pricelist_id][$source] = array();
                        }
                        $i = clone $item;
                        $i->image = clone $image;
                        $retval[$pricelist_id][$source][] = $i;
                    }
                }
            }
        }
        return $retval;
    }
    function _calculate_shipments()
    {
        $retval = array();
        $source_manager = C_Pricelist_Source_Manager::get_instance();
        foreach ($source_manager->get_ids() as $source_id) {
            $items = $this->get_items($source_id);
            $source = $source_manager->get_handler($source_id);
            if ($shipments = $source->get_shipments($items, $this->_settings, $this)) {
                $retval[$source_id] = array_map(array($this, '_arr_to_object'), $shipments);
            }
        }
        return $retval;
    }
    function get_shipments()
    {
        if ($this->_shipments === NULL) {
            $this->_shipments = $this->_calculate_shipments();
        }
        return $this->_shipments;
    }
    function _arr_to_object($arr)
    {
        return (object) $arr;
    }
    function _add_to_inner_array(&$arr, $key, $value)
    {
        if (!isset($arr[$key])) {
            $arr[$key] = array($value);
        } else {
            $arr[$key][] = $value;
        }
    }
    function _get_universal_shipping_methods()
    {
        $retval = array();
        $manager = C_Pricelist_Shipping_Method_Manager::get_instance();
        foreach ($this->get_shipments() as $source_id => $shipments) {
            foreach ($shipments as $shipment) {
                foreach ($shipment->shipping_methods as $shipping_method_id => $shipping_method) {
                    if ($manager->is_universal_method($shipping_method_id)) {
                        $this->_add_to_inner_array($retval, $shipping_method_id, $shipping_method);
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * Gets all shipping methods which are common to all shipments
     *
     * @return array
     */
    function _get_common_shipping_methods()
    {
        $manager = C_Pricelist_Shipping_Method_Manager::get_instance();
        // Find common shipping methods
        $common_methods = array();
        foreach ($manager->get_ids() as $shipping_method_id) {
            $common = TRUE;
            foreach ($this->get_shipments() as $source_id => $shipments) {
                // Iterate over all shipments for that source
                foreach ($shipments as $shipment) {
                    $found = FALSE;
                    foreach ($shipment->shipping_methods as $id => $underlying_shipping_method) {
                        if ($id == $shipping_method_id) {
                            $found = TRUE;
                            break;
                        } else {
                            if ($manager->is_universal_method($id)) {
                                $found = TRUE;
                            }
                        }
                    }
                    if (!$found) {
                        $common = FALSE;
                    }
                    if (!$common) {
                        break;
                    }
                }
                if (!$common) {
                    break;
                }
            }
            // If the method is common, we need to get the underlying shipping method
            // for each shipment
            if ($common) {
                $common_methods[$shipping_method_id] = array();
                // Get all shipments per source
                foreach ($this->get_shipments() as $source_id => $shipments) {
                    // Iterate over all shipments for that source
                    foreach ($shipments as $shipment) {
                        if (isset($shipment->shipping_methods[$shipping_method_id])) {
                            $common_methods[$shipping_method_id][] = $shipment->shipping_methods[$shipping_method_id];
                        }
                    }
                }
            }
            // Remove shipping methods which have no underlying methods
            foreach (array_keys($common_methods) as $common_method_id) {
                if (!$common_methods[$common_method_id]) {
                    unset($common_methods[$common_method_id]);
                }
            }
        }
        return $common_methods;
    }
    function _combine_shipping_methods($shipping_method_id, $title, $shipping_methods)
    {
        $amount = 0.0;
        $data = array();
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        foreach ($shipping_methods as $method_details) {
            // Combine data
            if (isset($method_details['data']) && $method_details['data']) {
                $data = array_merge($data, $method_details['data']);
            }
            // Combine price
            if (isset($method_details['price']) && $method_details['price']) {
                $amount = bcadd($amount, $method_details['price'], $currency['exponent']);
            }
            if (isset($method_details['amount']) && $method_details['amount']) {
                $amount = bcadd($amount, $method_details['amount'], $currency['exponent']);
            }
            // Combine surcharges
            if (isset($method_details['surcharge']) && $method_details['surcharge']) {
                $amount = bcadd($amount, $method_details['surcharge'], $currency['exponent']);
            }
            if (isset($method_details['other_surcharge']) && $method_details['other_surcharge']) {
                $amount = bcadd($amount, $method_details['other_surcharge'], $currency['exponent']);
            }
        }
        return array('name' => $shipping_method_id, 'title' => $title, 'amount' => $amount, 'underlying_methods' => $shipping_methods, 'data' => $data);
    }
    function has_shipments_with_no_methods()
    {
        $retval = FALSE;
        foreach ($this->get_shipments() as $source_id => $shipments) {
            foreach ($shipments as $shipment) {
                if (!count($shipment->shipping_methods)) {
                    $retval = TRUE;
                }
            }
        }
        return $retval;
    }
    function get_shipping_methods()
    {
        if ($this->_shipping_methods === NULL) {
            $this->_shipping_methods = $this->_calculate_shipping_methods();
        }
        return $this->_shipping_methods;
    }
    /**
     * Returns a list of all shipping methods compatible with the cart
     * @param array $settings
     * @return array
     */
    function _calculate_shipping_methods()
    {
        $manager = C_Pricelist_Shipping_Method_Manager::get_instance();
        $retval = array();
        /*
                {
           ngg_manual_shipping: [
               {
                   alias => 'Manual Domestic Shipping for Pricelist 5',
                   price => 5.00,
                   surcharge => 0.00,
                   other_surcharge => 0.00,
                   source => 'ngg_manual_pricelist'
               },
               {
                   alias => 'Manual Domestic Shipping for Pricelist 8',
                   price => 15.00,
                   surcharge => 0.00,
                   other_surcharge => 5.00,
                   source => 'ngg_manual_pricelist'
               },
           ],
        
           ngg_international_shipping: [
               {
                   alias => 'WHCC International Shipping',
                   price => 10.00,
                   surcharge => 0.00,
                   other_surcharge => 5.00,
                   source => 'ngg_whcc_pricelist'
               },
           ]
        		}
        */
        $common_methods = $this->_get_common_shipping_methods();
        // Segment universal shipping methods from common methods
        $universal_methods = $this->_get_universal_shipping_methods();
        foreach (array_keys($common_methods) as $shipping_method_id) {
            if ($manager->is_universal_method($shipping_method_id)) {
                unset($common_methods[$shipping_method_id]);
            }
        }
        // If there are only universal shipping methods, treat them as common methods
        if (!$common_methods && !$this->has_shipments_with_no_methods()) {
            $common_methods = array_merge($common_methods, $universal_methods);
            $universal_methods = array();
        }
        // Combine common methods
        foreach ($common_methods as $shipping_method_id => $underyling_methods) {
            $retval[$shipping_method_id] = $this->_combine_shipping_methods($shipping_method_id, $manager->get($shipping_method_id, 'title'), $underyling_methods);
        }
        // Add/combine universal methods to each common method
        if ($universal_methods) {
            $universal_methods = array_values($universal_methods);
            $universal_methods = array_pop($universal_methods);
            foreach (array_keys($common_methods) as $shipping_method_id) {
                $retval[$shipping_method_id] = $this->_combine_shipping_methods($shipping_method_id, $manager->get($shipping_method_id, 'title'), array_merge(array($retval[$shipping_method_id]), $universal_methods), TRUE);
            }
        }
        return $retval;
    }
    function _get_least_expensive_shipping_method($cart_settings = array(), $shipping_methods = array())
    {
        $cart_settings = $this->validate_setting($this->_settings, $cart_settings);
        if (!$shipping_methods) {
            $shipping_methods = $this->get_shipping_methods($cart_settings);
        }
        $amount = PHP_INT_MAX;
        $least_expensive = NULL;
        foreach ($shipping_methods as $shipping_method_name => $properties) {
            if ($properties['amount'] < $amount) {
                $least_expensive = $shipping_method_name;
                $amount = $properties['amount'];
            }
        }
        return $least_expensive;
    }
    function get_selected_shipping_method()
    {
        if (!array_key_exists('shipping_method', $this->_settings) or $this->_settings['shipping_method'] === NULL) {
            $this->_settings['shipping_method'] = $this->_calculate_selected_shipping_method();
        }
        return $this->_settings['shipping_method'];
    }
    function _calculate_selected_shipping_method()
    {
        $cart_settings = $this->_settings;
        $selected_method = isset($cart_settings['shipping_method']) ? $cart_settings['shipping_method'] : NULL;
        if ($this->_shipping_methods && !$selected_method || $this->_shipping_methods && !isset($this->_shipping_methods[$selected_method])) {
            $selected_method = $this->_get_least_expensive_shipping_method();
        }
        return $selected_method;
    }
    function get_shipping()
    {
        if ($this->_shipping === NULL) {
            $this->_shipping = $this->_calculate_shipping();
        }
        return $this->_shipping;
    }
    function _calculate_shipping()
    {
        $retval = 0.0;
        $shipping_methods = $this->get_shipping_methods();
        $selected_method = $this->get_selected_shipping_method();
        if ($selected_method && isset($shipping_methods[$selected_method])) {
            $shipping_method = $shipping_methods[$selected_method];
            $retval = $shipping_method['amount'];
        }
        return $retval;
    }
    function get_total()
    {
        if ($this->_total === NULL) {
            $this->_total = $this->_calculate_total();
        }
        return $this->_total;
    }
    public function get_total_array()
    {
        return $this->_calculate_total(TRUE);
    }
    function _calculate_total($return_array = FALSE)
    {
        $currency = C_NextGen_Pro_Currencies::$currencies[$this->get_currency()];
        $subtotal = $this->get_subtotal();
        // includes discount
        $total = $subtotal;
        $taxes = 0.0;
        if ($this->is_tax_enabled()) {
            $taxes = $this->get_tax();
            $total = bcadd($total, $taxes, $currency['exponent']);
        }
        $shipping = $this->get_shipping();
        $total = bcadd($shipping, $total, $currency['exponent']);
        if (!$return_array) {
            return $total;
        } else {
            return array('discount' => $this->get_discount(), 'shipping' => $shipping, 'subtotal' => $subtotal, 'taxes' => $taxes, 'total' => $total);
        }
    }
    function get_tax()
    {
        return $this->get_tax_info()->amount_to_collect;
    }
    function get_tax_info()
    {
        if ($this->_tax_info === NULL) {
            $this->_tax_info = $this->_calculate_tax();
        }
        return $this->_tax_info;
    }
    function _calculate_tax()
    {
        $retval = new stdClass();
        $retval->amount_to_collect = 0.0;
        $cart_settings = $this->_settings;
        if ($this->is_tax_enabled() && $this->has_shippable_items() && $cart_settings['shipping_address']['country'] && $cart_settings['shipping_address']['state']) {
            $ecommerce_status = M_NextGen_Pro_Ecommerce::check_ecommerce_requirements();
            $has_print_lab = $ecommerce_status['print_lab_ready'];
            if (!defined('NGG_PRO_USE_WHCC_NEXUS')) {
                define('NGG_PRO_USE_WHCC_NEXUS', $has_print_lab);
            }
            $default_nexus = defined('NGG_PRO_USE_WHCC_NEXUS') && constant('NGG_PRO_USE_WHCC_NEXUS') ? array(array('country' => 'US', 'state' => 'AL'), array('country' => 'US', 'state' => 'AZ'), array('country' => 'US', 'state' => 'CA'), array('country' => 'US', 'state' => 'CO'), array('country' => 'US', 'state' => 'CT'), array('country' => 'US', 'state' => 'FL'), array('country' => 'US', 'state' => 'GA'), array('country' => 'US', 'state' => 'ID'), array('country' => 'US', 'state' => 'IL'), array('country' => 'US', 'state' => 'IN'), array('country' => 'US', 'state' => 'IA'), array('country' => 'US', 'state' => 'KS'), array('country' => 'US', 'state' => 'KY'), array('country' => 'US', 'state' => 'LA'), array('country' => 'US', 'state' => 'MD'), array('country' => 'US', 'state' => 'MA'), array('country' => 'US', 'state' => 'MI'), array('country' => 'US', 'state' => 'MO'), array('country' => 'US', 'state' => 'MN'), array('country' => 'US', 'state' => 'NJ'), array('country' => 'US', 'state' => 'NY'), array('country' => 'US', 'state' => 'NC'), array('country' => 'US', 'state' => 'OH'), array('country' => 'US', 'state' => 'OK'), array('country' => 'US', 'state' => 'PA'), array('country' => 'US', 'state' => 'SC'), array('country' => 'US', 'state' => 'TN'), array('country' => 'US', 'state' => 'TX'), array('country' => 'US', 'state' => 'UT'), array('country' => 'US', 'state' => 'WA'), array('country' => 'US', 'state' => 'WI'), array('country' => 'US', 'state' => 'VA'), array('country' => 'CA', 'state' => 'AB'), array('country' => 'CA', 'state' => 'BC'), array('country' => 'CA', 'state' => 'MB'), array('country' => 'CA', 'state' => 'NB'), array('country' => 'CA', 'state' => 'NL'), array('country' => 'CA', 'state' => 'NS'), array('country' => 'CA', 'state' => 'NT'), array('country' => 'CA', 'state' => 'NU'), array('country' => 'CA', 'state' => 'ON'), array('country' => 'CA', 'state' => 'PE'), array('country' => 'CA', 'state' => 'QC'), array('country' => 'CA', 'state' => 'SK'), array('country' => 'CA', 'state' => 'YT')) : array();
            $nexus = apply_filters('ngg_pro_taxjar_nexus', $default_nexus);
            $taxjar_params = array('license_key' => M_NextGen_Pro_Ecommerce::get_license('photocrati-nextgen-pro'), 'from' => $cart_settings['studio_address'], 'to' => $cart_settings['shipping_address'], 'amount' => $this->get_subtotal($cart_settings), 'shipping' => $this->get_shipping($cart_settings));
            if ($nexus) {
                $taxjar_params['nexus'] = $nexus;
            }
            $body = json_encode(apply_filters('ngg_pro_taxjar_params', $taxjar_params));
            $response = wp_remote_post('https://xta4y4f2g4.execute-api.us-east-1.amazonaws.com/latest/getTaxes', array('body' => $body, 'headers' => array('Content-Type' => 'application/json')));
            if (!is_wp_error($response)) {
                $response['body'] = json_decode($response['body']);
                if (isset($response['body']) && property_exists($response['body'], 'tax')) {
                    $retval = $response['body']->tax;
                } else {
                    if (isset($response['body']) && property_exists($response['body'], 'detail')) {
                        $error = FALSE;
                        switch ($response['body']->detail) {
                            case 'invalid_zip':
                                $error = __('Please enter a valid zip or postal code', 'nextgen-gallery-pro');
                                break;
                            case 'invalid_license':
                                $error = __('Please inform the studio that there is a licensing problem and that taxes cannot be calculated', 'nextgen-gallery-pro');
                                break;
                        }
                        if ($error) {
                            throw new RuntimeException($error);
                        }
                    }
                }
            }
        }
        return $retval;
    }
    /**
     * Determines if the cart has shippable items
     * @return bool
     */
    function has_shippable_items()
    {
        return $this->has_lab_items() || $this->has_manual_items();
    }
    function has_lab_items()
    {
        $retval = FALSE;
        $sources = C_Pricelist_Source_Manager::get_instance();
        foreach ($sources->get_ids() as $source_id) {
            if (count($this->get_items($source_id))) {
                if ($sources->get($source_id, 'lab_fulfilled')) {
                    $retval = TRUE;
                    break;
                }
            }
        }
        return $retval;
    }
    function has_manual_items()
    {
        return count($this->get_items(NGG_PRO_MANUAL_PRICELIST_SOURCE)) ? TRUE : FALSE;
    }
    function has_whcc_items()
    {
        return count($this->get_items(NGG_PRO_WHCC_PRICELIST_SOURCE)) ? TRUE : FALSE;
    }
}
/**
 * Class C_NextGen_Pro_Checkout
 * @mixin Mixin_NextGen_Pro_Checkout
 * @mixin A_PayPal_Checkout_Form
 * @mixin A_Stripe_Checkout_Button
 * @mixin A_PayPal_Standard_Button
 * @mixin A_PayPal_Express_Checkout_Button
 */
class C_NextGen_Pro_Checkout extends C_MVC_Controller
{
    static $_instance = NULL;
    /**
     * @return C_NextGen_Pro_Checkout
     */
    static function get_instance()
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
    function define($context = FALSE)
    {
        parent::define();
        $this->implement('I_NextGen_Pro_Checkout');
        $this->add_mixin('Mixin_NextGen_Pro_Checkout');
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class Mixin_NextGen_Pro_Checkout extends Mixin
{
    /**
     * Adapters are expected to override to provide more payment gateway buttons
     * @return array
     */
    function get_checkout_buttons()
    {
        return array();
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->image_header = __('Image', 'nextgen-gallery-pro');
        $i18n->quantity_header = __('Quantity', 'nextgen-gallery-pro');
        $i18n->item_header = __('Description', 'nextgen-gallery-pro');
        $i18n->crop_button = __('Edit Crop', 'nextgen-gallery-pro');
        $i18n->crop_button_close = __('Save Crop', 'nextgen-gallery-pro');
        $i18n->price_header = __('Price', 'nextgen-gallery-pro');
        $i18n->total_header = __('Totals', 'nextgen-gallery-pro');
        $i18n->subtotal = __('Subtotal:', 'nextgen-gallery-pro');
        $i18n->shipping = __('Shipping:', 'nextgen-gallery-pro');
        $i18n->total = __('Total:', 'nextgen-gallery-pro');
        $i18n->no_items = __('There have been no items added to your cart.', 'nextgen-gallery-pro');
        $i18n->continue_shopping = __('Continue shopping', 'nextgen-gallery-pro');
        $i18n->empty_cart = __('Empty cart', 'nextgen-gallery-pro');
        $i18n->ship_to = __('Ship to:', 'nextgen-gallery-pro');
        $i18n->ship_via = __('Ship via:', 'nextgen-gallery-pro');
        $i18n->ship_elsewhere = __('International', 'nextgen-gallery-pro');
        $i18n->tax = __('Tax:', 'nextgen-gallery-pro');
        $i18n->update_shipping = __('Update shipping & taxes', 'nextgen-gallery-pro');
        $i18n->coupon_undiscounted_subtotal = __('Subtotal before discount:', 'nextgen-gallery-pro');
        $i18n->coupon_discount_amount = __('Discount:', 'nextgen-gallery-pro');
        $i18n->coupon_placeholder = __('Coupon code', 'nextgen-gallery-pro');
        $i18n->coupon_apply = __('Apply', 'nextgen-gallery-pro');
        $i18n->coupon_notice = __('Coupon has been applied', 'nextgen-gallery-pro');
        $i18n->shipping_name_label = __('Full Name', 'nextgen-gallery-pro');
        $i18n->shipping_name_tip = __('Full Name', 'nextgen-gallery-pro');
        $i18n->shipping_email_label = __('Email', 'nextgen-gallery-pro');
        $i18n->shipping_email_tip = __('Email', 'nextgen-gallery-pro');
        $i18n->shipping_street_address_label = __('Address Line 1', 'nextgen-gallery-pro');
        $i18n->shipping_street_address_tip = __('Address Line 1', 'nextgen-gallery-pro');
        $i18n->shipping_address_line_label = __('Address Line 2', 'nextgen-gallery-pro');
        $i18n->shipping_address_line_tip = __('Address Line 2', 'nextgen-gallery-pro');
        $i18n->shipping_city_label = __('City', 'nextgen-gallery-pro');
        $i18n->shipping_city_tip = __('City', 'nextgen-gallery-pro');
        $i18n->shipping_country_label = __('Country', 'nextgen-gallery-pro');
        $i18n->shipping_country_tip = __('Country', 'nextgen-gallery-pro');
        $i18n->shipping_state_label = __('State / Region', 'nextgen-gallery-pro');
        $i18n->shipping_state_tip = __('State / Region', 'nextgen-gallery-pro');
        $i18n->shipping_zip_label = __('Postal Code', 'nextgen-gallery-pro');
        $i18n->shipping_zip_tip = __('Zip / Postal Code', 'nextgen-gallery-pro');
        $i18n->shipping_phone_label = __('Phone', 'nextgen-gallery-pro');
        $i18n->shipping_phone_tip = __('Phone', 'nextgen-gallery-pro');
        $i18n->unshippable = __("We're sorry, but one or more items you've selected cannot be shipped to this country.", 'nextgen-gallery-pro');
        $i18n->tbd = __('Please Add Address', 'nextgen-gallery-pro');
        $i18n->select_country = __('Select Country', 'nextgen-gallery-pro');
        $i18n->select_region = __('Select Region', 'nextgen-gallery-pro');
        $i18n->error_empty = __('%s cannot be empty.', 'nextgen-gallery-pro');
        $i18n->error_minimum = __('%s needs to be at least %s characters.', 'nextgen-gallery-pro');
        $i18n->error_invalid = __('%s is in an invalid format.', 'nextgen-gallery-pro');
        $i18n->error_form_invalid = __('Form contains errors, please correct all errors before submitting the order.', 'nextgen-gallery-pro');
        $i18n->calculating = __('Calculating...', 'nextgen-gallery-pro');
        return $i18n;
    }
    function enqueue_static_resources()
    {
        M_NextGen_Pro_Ecommerce::enqueue_cart_resources();
        // Enqueue fontawesome
        if (method_exists('M_Gallery_Display', 'enqueue_fontawesome')) {
            M_Gallery_Display::enqueue_fontawesome();
        } else {
            C_Display_Type_Controller::get_instance()->enqueue_displayed_gallery_trigger_buttons_resources();
        }
        C_Lightbox_Library_Manager::get_instance()->enqueue();
        wp_enqueue_style('fontawesome');
        wp_enqueue_style('lightbox-featherlight', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#featherlight/featherlight.min.css'), false, '1.7.13');
        wp_enqueue_script('lightbox-featherlight', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#featherlight/featherlight.js'), array('jquery'), '1.7.13');
        wp_enqueue_style('croppie', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#croppie/croppie.css'), false, '2.6.4');
        wp_enqueue_script('croppie', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#croppie/croppie.js'), array('jquery'), '2.6.4');
        wp_enqueue_style('ngg-pro-checkout', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#checkout.css'));
        wp_enqueue_script('ngg-pro-checkout', $this->object->get_static_url('photocrati-nextgen_pro_ecommerce#checkout.js'), ['jquery', 'ngg_pro_cart']);
        foreach ($this->object->get_checkout_buttons() as $btn) {
            $method = "enqueue_{$btn}_resources";
            if ($this->object->has_method($method)) {
                $this->object->{$method}();
            }
        }
    }
    function get_continue_shopping_url()
    {
        return isset($_GET['referrer']) ? $_GET['referrer'] : '';
    }
    function checkout_form()
    {
        $this->enqueue_static_resources();
        if ($this->object->is_post_request()) {
            $this->processor();
        }
        // Get checkout buttons
        $buttons = array();
        foreach ($this->object->get_checkout_buttons() as $btn) {
            $method = "_render_{$btn}_button";
            $buttons[] = $this->object->{$method}();
        }
        $settings = C_NextGen_Settings::get_instance();
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#checkout_form', array('buttons' => $buttons, 'referrer_url' => $this->get_continue_shopping_url(), 'i18n' => $this->object->get_i18n_strings(), 'display_taxes' => $settings->ecommerce_tax_enable, 'display_coupon' => M_NextGen_Pro_Coupons::are_coupons_enabled()), TRUE);
    }
    function processor()
    {
        if ($gateway = $this->object->param('ngg_pro_checkout')) {
            $method = "process_{$gateway}_request";
            if ($this->object->has_method($method)) {
                $this->object->{$method}();
            }
        }
    }
    /**
     * @param C_NextGen_Pro_Cart $cart
     * @param string $customer_name
     * @param string $email
     * @param string $payment_gateway
     * @param string $status
     * @return C_NextGen_Pro_Order
     */
    function create_order($cart, $customer_name, $email, $payment_gateway, $status = 'awaiting_payment', $gateway_note = '')
    {
        $settings = $cart->get_settings();
        $order_mapper = C_Order_Mapper::get_instance();
        $properties = array('customer_name' => $customer_name, 'email' => $email, 'payment_gateway' => $payment_gateway, 'cart' => $cart->to_array(), 'status' => $status, 'post_status' => 'publish', 'subtotal' => $cart->get_subtotal(), 'tax' => $cart->get_tax(), 'total_amount' => $cart->get_total(), 'shipping' => $cart->get_shipping(), 'gateway_admin_note' => $gateway_note);
        if (isset($settings['shipping_address'])) {
            $shipping_address = $settings['shipping_address'];
            $properties = array_merge($properties, array('shipping_street_address' => $shipping_address['street_address'], 'shipping_street_address' => $shipping_address['street_address'], 'shipping_address_line' => $shipping_address['address_line'], 'shipping_city' => $shipping_address['city'], 'shipping_state' => $shipping_address['state'], 'shipping_zip' => $shipping_address['zip'], 'shipping_country' => $shipping_address['country'], 'shipping_phone' => $shipping_address['phone'], 'shipping_name' => $shipping_address['name']));
        }
        /** @var C_NextGen_Pro_Order $order */
        $order = $order_mapper->create($properties);
        return $order;
    }
    /**
     * @param array $settings
     * @param array $items
     * @param string $coupon
     * @param bool $inverse_price_validation Enable the free gateway to reverse the validation of cart totals
     * @return array
     * @throws Exception
     */
    public function prepare_order($settings = array(), $items = array(), $coupon = '', $inverse_price_validation = FALSE)
    {
        if (!is_array($items) || empty($items)) {
            throw new Exception(__('Your cart is empty', 'nextgen-gallery-pro'));
        }
        $cart = new C_NextGen_Pro_Cart(NULL, $settings);
        $shipping_address = $cart->get_setting('shipping_address');
        $customer = array_merge($shipping_address, array('name' => $shipping_address['name'], 'email' => $shipping_address['email']));
        $cart->add_items($items);
        $cart->apply_coupon($coupon);
        if (!$inverse_price_validation) {
            if ((float) $cart->get_total() <= 0) {
                throw new Exception(__('Invalid request', 'nextgen-gallery-pro'));
            }
        } else {
            $total = bcsub((float) $cart->get_total(), (float) $cart->get_discount());
            if ($total > 0 || $total < 0) {
                throw new Exception(__('Invalid request', 'nextgen-gallery-pro'));
            }
        }
        if (!$cart->has_items()) {
            throw new Exception(__('Your cart is empty', 'nextgen-gallery-pro'));
        }
        if ($cart->has_shippable_items()) {
            $found = FALSE;
            foreach (C_NextGen_Pro_Currencies::$countries as $id => $country) {
                if ($country['code'] === $customer['country']) {
                    $found = TRUE;
                    break;
                }
            }
            if (!$found) {
                throw new Exception(__('Invalid country selected, please try again.', 'nextgen-gallery-pro'));
            }
        }
        $retval = array();
        $retval['customer'] = $customer;
        $retval['cart'] = $cart;
        // We add this now because the discount amounts are only calculated by this method and we
        // need those values before we call create_order()
        $retval['cart_array'] = $cart->to_array();
        return $retval;
    }
    /**
     * @param array $settings
     * @param array $items
     * @param string $coupon
     * @param string $status
     * @param string $gateway
     * @param string $gateway_message
     * @param bool $inverse_price_validation
     * @param bool $send_order_notification
     * @return array
     * @throws Exception
     */
    public function save_order($settings = array(), $items = array(), $coupon = '', $status = 'awaiting_payment', $gateway, $gateway_message = '', $send_order_notification = TRUE, $inverse_price_validation = FALSE)
    {
        /*
         * Step One: basic validation, rules checking, preparation
         */
        $prepared_order = $this->object->prepare_order($settings, $items, $coupon, $inverse_price_validation);
        /*
         * Step two: create the C_Order object itself
         */
        $order = $this->object->create_order($prepared_order['cart'], $prepared_order['customer']['name'], $prepared_order['customer']['email'], $gateway, $status, $gateway_message);
        /*
         * Step three: save the C_Order object; retrieve and parse any errors into a readable format
         */
        $result = C_Order_Mapper::get_instance()->save($order);
        $errors = $order->get_errors();
        if (!$result || !empty($errors)) {
            $errmsg = __('Could not save order:', 'nextgen-gallery-pro');
            foreach ($errors as $field => $field_errors) {
                foreach ($field_errors as $error) {
                    $errmsg .= "\n" . $error;
                }
            }
            throw new Exception($errmsg);
        }
        if ($send_order_notification) {
            $this->send_order_notification($order);
        }
        switch ($status) {
            case 'verified':
            case 'paid':
                $this->mark_as_paid($order, TRUE, TRUE, $gateway_message);
                break;
            case 'unpaid':
                $this->mark_as_unpaid($order, TRUE, $gateway_message);
                break;
            case 'unverified':
            case 'awaiting_payment':
            case 'awaiting-payment':
            case 'waiting-payment':
            case 'waiting_payment':
                $this->mark_as_awaiting_payment($order, TRUE, $gateway_message);
                break;
            case 'failed':
                $this->mark_as_failed($order, TRUE, $gateway_message);
                break;
            case 'fraud':
                $this->mark_as_fraud($order, TRUE, $gateway_message);
                break;
        }
        /*
         * Step four: all is well, finish:
         */
        $retval = $prepared_order;
        // customer and cart keys are provided by prepare_order()
        $retval['order'] = $order->hash;
        $retval['redirect'] = $this->object->get_thank_you_page_url($order->hash, TRUE);
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order_object_or_hash Order object or hash ID
     * @param string $subject
     * @param string $body
     * @param null|string $to
     * @return bool
     */
    function _send_email($order_object_or_hash, $subject, $body, $to = NULL)
    {
        $retval = FALSE;
        $order = $this->_get_order($order_object_or_hash);
        // Ensure that we have a valid order
        if ($order) {
            // Use only the order entity
            if (get_class($order) != 'stdClass') {
                $order = $order->get_entity();
            }
            // Get the order total
            $cart = new C_NextGen_Pro_Cart($order->cart);
            $order->total_amount = $order->total_amount = $cart->get_total();
            // Get the destination url
            $order_details_page = $this->get_thank_you_page_url($order->hash, TRUE);
            // Get needed components
            $mail = C_Nextgen_Mail_Manager::get_instance();
            // Set additional order variables
            $order->order_details_page = $order_details_page;
            $order->total_amount_formatted = M_NextGen_Pro_Ecommerce::get_formatted_price($order->total_amount, $cart->get_currency(), FALSE);
            $order->order_total_formatted = M_NextGen_Pro_Ecommerce::get_formatted_price($order->total_amount, $cart->get_currency(), FALSE);
            $order->admin_email = M_NextGen_Pro_Ecommerce::get_studio_email_address();
            $order->blog_description = get_bloginfo('description');
            $order->blog_name = get_bloginfo('name');
            $order->blog_url = site_url();
            $order->site_url = site_url();
            $order->home_url = home_url();
            $order->order_id = $order->ID;
            // Determine image filenames
            $file_list = array();
            foreach ($cart->get_images() as $image) {
                $file_list[] = $image->filename;
            }
            $order->item_count = count($cart->get_items());
            $order->file_list = implode(", ", $file_list);
            // Send the e-mail
            $content = $mail->create_content();
            $content->set_subject($subject);
            $content->load_template($body);
            foreach (get_object_vars($order) as $key => $val) {
                $content->set_property($key, $val);
            }
            $mail->send($to ? $to : $order->email, $subject, $content);
            $retval = TRUE;
        }
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order_object_or_hash Order object or hash ID
     * @return bool
     */
    function send_email_receipt($order_object_or_hash)
    {
        $retval = FALSE;
        if ($order = $this->_get_order($order_object_or_hash)) {
            $settings = C_NextGen_Settings::get_instance();
            // Send e-mail receipt to customer
            if ((!isset($order->has_sent_email_receipt) || !$order->has_sent_email_receipt) && $settings->ecommerce_enable_email_receipt) {
                $retval = $this->_send_email($order_object_or_hash, $settings->ecommerce_email_receipt_subject, str_replace(array('%%order_total%%', '%%order_amount%%', '%%total_amount%%'), array('%%order_total_formatted%%', '%%order_amount_formatted%%', '%%total_amount_formatted%%'), $settings->ecommerce_email_receipt_body));
                if ($retval) {
                    $order->has_sent_email_receipt = TRUE;
                    $order->save();
                }
            }
        }
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order_object_or_hash Order object or hash ID
     * @return bool
     */
    function send_order_notification($order_object_or_hash)
    {
        $retval = FALSE;
        if ($order = $this->_get_order($order_object_or_hash)) {
            $settings = C_NextGen_Settings::get_instance();
            // Send admin notification
            if ((!isset($order->has_sent_email_notification) || !$order->has_sent_email_notification) && $settings->ecommerce_enable_email_notification) {
                $retval = $this->_send_email($order_object_or_hash, $settings->ecommerce_email_notification_subject, str_replace(array('%%order_total%%', '%%order_amount%%', '%%total_amount%%'), array('%%order_total_formatted%%', '%%order_amount_formatted%%', '%%total_amount_formatted%%'), $settings->ecommerce_email_notification_body), M_NextGen_Pro_Ecommerce::get_studio_email_address());
                if ($retval) {
                    $order->has_sent_email_notification = TRUE;
                    $order->save();
                }
            }
        }
        return $retval;
    }
    /**
     * Marks an order as paid
     *
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @param bool $send_emails
     * @param bool $lab_fulfill
     * @param string $note
     * @return C_NextGen_Pro_Order|FALSE
     */
    function mark_as_paid($order, $send_emails = TRUE, $lab_fulfill = TRUE, $note = '')
    {
        $retval = FALSE;
        // Get the order
        if ($order = $this->_get_order($order)) {
            $order->status = 'paid';
            $order->status_note = $note;
            $order->save();
            $order = apply_filters('ngg_order_marked_as_paid', $order);
            if (apply_filters('ngg_order_marked_as_paid_send_emails', $send_emails, $order)) {
                $this->send_email_receipt($order);
            }
            if (apply_filters('ngg_order_marked_as_paid_lab_fulfill', $lab_fulfill && $order->get_cart()->has_lab_items(), $order)) {
                self::submit_lab_order($order);
            }
            $retval = $order;
        }
        return $retval;
    }
    /**
     * Marks an order as unpaid
     *
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @param bool $send_emails
     * @param string $note
     * @return bool|C_NextGen_Pro_Order
     */
    function mark_as_unpaid($order, $send_emails = TRUE, $note = '')
    {
        $retval = FALSE;
        // Get the order
        if ($order = $this->_get_order($order)) {
            $order->status = 'unpaid';
            $order->status_note = $note;
            $order->save();
            if (apply_filters('ngg_order_marked_as_unpaid_send_emails', $send_emails, $order)) {
                // TODO: Allow customized e-mails to be sent
            }
            $retval = $order;
        }
        return $retval;
    }
    /**
     * Marks the order as awaiting payment
     *
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @param bool $send_emails
     * @param string $note
     * @return bool|C_NextGen_Pro_Order
     */
    function mark_as_awaiting_payment($order, $send_emails = TRUE, $note = '')
    {
        $retval = FALSE;
        // Get the order
        if ($order = $this->_get_order($order)) {
            $order->status = 'awaiting_payment';
            $order->status_note = $note;
            $order->save();
            if (apply_filters('ngg_order_marked_as_awaiting_payment_send_emails', $send_emails, $order)) {
                // TODO: Allow customized e-mails to be sent
            }
            $retval = $order;
        }
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @param bool $send_emails
     * @param string $note
     * @return bool|C_NextGen_Pro_Order
     */
    function mark_as_fraud($order, $send_emails = TRUE, $note = '')
    {
        $retval = FALSE;
        // Get the order
        if ($order = $this->_get_order($order)) {
            $order->status = 'fraud';
            $order->status_note = $note;
            $order->save();
            if (apply_filters('ngg_order_marked_as_fraud_send_emails', $send_emails, $order)) {
                // TODO: Allow customized e-mails to be sent
            }
            $retval = $order;
        }
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @param bool $send_emails
     * @param string $note
     * @return bool|C_NextGen_Pro_Order
     */
    function mark_as_failed($order, $send_emails = TRUE, $note = '')
    {
        $retval = FALSE;
        // Get the order
        if ($order = $this->_get_order($order)) {
            $order->status = 'failed';
            $order->status_note = $note;
            $order->save();
            if (apply_filters('ngg_order_marked_as_failed_send_emails', $send_emails, $order)) {
                // TODO: Allow customized e-mails to be sent
            }
            $retval = $order;
        }
        return $retval;
    }
    /**
     * Determines whether an order is paid
     *
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @return bool
     */
    function is_order_paid($order)
    {
        $retval = FALSE;
        if ($order = $this->_get_order($order)) {
            $retval = $order->is_paid();
        }
        return $retval;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     * @return C_NextGen_Pro_Order
     */
    function _get_order($order)
    {
        if (!is_object($order)) {
            $order = is_string($order) ? C_Order_Mapper::get_instance()->find_by_hash($order, TRUE) : C_Order_Mapper::get_instance()->find($order, TRUE);
        }
        return $order;
    }
    /**
     * @param C_NextGen_Pro_Order|string $order Order object or hash ID
     */
    function redirect_to_thank_you_page($order)
    {
        // Get the order
        if ($order = $this->_get_order($order)) {
            // Expose hook for third-parties
            do_action('ngg_pro_purchase_complete', $order);
            // Get the destination url
            $order_details_page = $this->get_thank_you_page_url($order->hash, TRUE);
            wp_redirect($order_details_page);
        } else {
            echo __("We couldn't find your order. We apologize for the inconvenience", 'nextgen-gallery-pro');
        }
        throw new E_Clean_Exit();
    }
    /**
     * @param C_NextGen_Pro_Order $order
     * @param bool $force
     * @return array|WP_Error|void
     */
    function submit_lab_order(C_NextGen_Pro_Order $order, $force = FALSE)
    {
        if ($force || !isset($order->aws_order_id)) {
            $settings = C_NextGen_Settings::get_instance();
            $params = array('url' => add_query_arg('action', 'get_print_lab_order', $settings->get('ajax_url')), 'order' => $order->hash, 'nonce' => WPSimpleNonce::createNonce('get_print_lab_order'), 'retrieved' => FALSE);
            // Use test mode?
            $test_mode = FALSE;
            if (defined('NGG_PRO_LAB_TEST_MODE')) {
                $test_mode = NGG_PRO_LAB_TEST_MODE;
            }
            $test_mode = apply_filters('ngg_pro_lab_test_mode', $test_mode, $order);
            $prod_url = 'https://jy12m1w2q2.execute-api.us-east-1.amazonaws.com/latest/';
            $test_url = 'https://pv7bfbnfge.execute-api.us-east-1.amazonaws.com/latest/';
            $api_url = apply_filters('ngg_pro_lab_api_url', $test_mode ? $test_url : $prod_url);
            if (!defined('NGG_PRO_LAB_API_URL')) {
                define('NGG_PRO_LAB_API_URL', $api_url);
            }
            $response = wp_remote_post(NGG_PRO_LAB_API_URL, array('body' => json_encode($params), 'headers' => array('Content-Type' => 'application/json'), 'timeout' => 30));
            if (!is_wp_error($response)) {
                $response['body'] = json_decode($response['body']);
                if (property_exists($response['body'], 'executionArn')) {
                    $order->aws_order_id = $response['body']->executionArn;
                    $order->save();
                }
            }
            return $response;
        }
    }
    function redirect_to_cancel_page()
    {
        wp_redirect($this->get_cancel_page_url());
        throw new E_Clean_Exit();
    }
    /**
     * @param $order_hash
     */
    function redirect_to_order_verification_page($order_hash)
    {
        wp_redirect($this->object->get_order_verification_page_url($order_hash));
        throw new E_Clean_Exit();
    }
    function get_thank_you_page_url($order_id, $order_complete = FALSE)
    {
        $params = array('order' => $order_id);
        if ($order_complete) {
            $params['ngg_order_complete'] = 1;
        }
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->ecommerce_page_thanks) {
            return $this->get_page_url(C_NextGen_Settings::get_instance()->ecommerce_page_thanks, $params);
        } else {
            return $this->_add_to_querystring(site_url('/?ngg_pro_return_page=1'), $params);
        }
    }
    function _add_to_querystring($url, $params = array())
    {
        if ($params) {
            $qs = array();
            foreach ($params as $key => $value) {
                $qs[] = urlencode($key) . '=' . urlencode($value);
            }
            $url .= (strpos($url, '?') === FALSE ? '?' : '&') . implode('&', $qs);
        }
        return $url;
    }
    /**
     * @param string $order_hash
     * @return string|void
     */
    function get_order_verification_page_url($order_hash)
    {
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->get('ecommerce_page_order_verification', FALSE)) {
            return $this->_add_to_querystring($this->get_page_url($settings->get('ecommerce_page_order_verification')), array('order' => $order_hash));
        } else {
            return site_url('/?ngg_pro_verify_page=1&order=' . $order_hash);
        }
    }
    function get_cancel_page_url()
    {
        $settings = C_NextGen_Settings::get_instance();
        if ($settings->ecommerce_page_cancel) {
            return $this->get_page_url($settings->ecommerce_page_cancel);
        } else {
            return $this->_add_to_querystring(site_url('/?ngg_pro_cancel_page=1'));
        }
    }
    function get_page_url($page_id, $params = array())
    {
        $link = get_page_link($page_id);
        if ($params) {
            $link = $this->_add_to_querystring($link, $params);
        }
        return $link;
    }
    function redirect_to_page($page_id, $params = array())
    {
        wp_redirect($this->get_page_url($page_id, $params));
    }
}
class C_NextGen_Pro_Currencies
{
    /** @var int $recheck_rate */
    public static $recheck_rate = 86400;
    // Once per day (seconds)
    /** @var array $currency_rates */
    public static $currency_rates = array();
    /**
     * Nations by ISO 3166 listing with currency (ISO 4217) mapping
     *
     * @link http://en.wikipedia.org/wiki/Iso_3166
     * @var array Countries
     */
    public static $countries = array(4 => array(
        'name' => 'Afghanistan',
        // Us-English Name
        'code' => 'AF',
        // ISO-3166-1 Alpha-2
        'id' => 4,
        // ISO-3166-1 Numeric
        'currency_code' => '971',
    ), 248 => array('name' => 'Åland Islands', 'code' => 'AX', 'id' => 248, 'currency_code' => '978'), 8 => array('name' => 'Albania', 'code' => 'AL', 'id' => 8, 'currency_code' => '008'), 12 => array('name' => 'Algeria', 'code' => 'DZ', 'id' => 12, 'currency_code' => '012'), 16 => array('name' => 'American Samoa', 'code' => 'AS', 'id' => 16, 'currency_code' => '840'), 20 => array('name' => 'Andorra', 'code' => 'AD', 'id' => 20, 'currency_code' => '978'), 24 => array('name' => 'Angola', 'code' => 'AO', 'id' => 24, 'currency_code' => '973'), 660 => array('name' => 'Anguilla', 'code' => 'AI', 'id' => 660, 'currency_code' => '951'), 28 => array('name' => 'Antigua and Barbuda', 'code' => 'AG', 'id' => 28, 'currency_code' => '951'), 32 => array('name' => 'Argentina', 'code' => 'AR', 'id' => 32, 'currency_code' => '032'), 51 => array('name' => 'Armenia', 'code' => 'AM', 'id' => 51, 'currency_code' => '051'), 533 => array('name' => 'Aruba', 'code' => 'AW', 'id' => 533, 'currency_code' => '533'), 36 => array('name' => 'Australia', 'code' => 'AU', 'id' => 36, 'currency_code' => '036'), 40 => array('name' => 'Austria', 'code' => 'AT', 'id' => 40, 'currency_code' => '978'), 31 => array('name' => 'Azerbaijan', 'code' => 'AZ', 'id' => 31, 'currency_code' => '944'), 44 => array('name' => 'Bahamas', 'code' => 'BS', 'id' => 44, 'currency_code' => '044'), 48 => array('name' => 'Bahrain', 'code' => 'BH', 'id' => 48, 'currency_code' => '048'), 50 => array('name' => 'Bangladesh', 'code' => 'BD', 'id' => 50, 'currency_code' => '050'), 52 => array('name' => 'Barbados', 'code' => 'BB', 'id' => 52, 'currency_code' => '052'), 112 => array('name' => 'Belarus', 'code' => 'BY', 'id' => 112, 'currency_code' => '974'), 56 => array('name' => 'Belgium', 'code' => 'BE', 'id' => 56, 'currency_code' => '978'), 84 => array('name' => 'Belize', 'code' => 'BZ', 'id' => 84, 'currency_code' => '084'), 204 => array('name' => 'Benin', 'code' => 'BJ', 'id' => 204, 'currency_code' => '952'), 60 => array('name' => 'Bermuda', 'code' => 'BM', 'id' => 60, 'currency_code' => '060'), 64 => array('name' => 'Bhutan', 'code' => 'BT', 'id' => 64, 'currency_code' => '356'), 68 => array('name' => 'Bolivia, Plurinational State of', 'code' => 'BO', 'id' => 68, 'currency_code' => '068'), 535 => array('name' => 'Bonaire, Sint Eustatius and Saba', 'code' => 'BQ', 'id' => 535, 'currency_code' => '840'), 70 => array('name' => 'Bosnia and Herzegovina', 'code' => 'BA', 'id' => 70, 'currency_code' => '977'), 72 => array('name' => 'Botswana', 'code' => 'BW', 'id' => 72, 'currency_code' => '072'), 74 => array('name' => 'Bouvet Island', 'code' => 'BV', 'id' => 74, 'currency_code' => '578'), 76 => array('name' => 'Brazil', 'code' => 'BR', 'id' => 76, 'currency_code' => '986'), 86 => array('name' => 'British Indian Ocean Territory', 'code' => 'IO', 'id' => 86, 'currency_code' => '840'), 96 => array('name' => 'Brunei Darussalam', 'code' => 'BN', 'id' => 96, 'currency_code' => '096'), 100 => array('name' => 'Bulgaria', 'code' => 'BG', 'id' => 100, 'currency_code' => '975'), 854 => array('name' => 'Burkina Faso', 'code' => 'BF', 'id' => 854, 'currency_code' => '952'), 108 => array('name' => 'Burundi', 'code' => 'BI', 'id' => 108, 'currency_code' => '108'), 116 => array('name' => 'Cambodia', 'code' => 'KH', 'id' => 116, 'currency_code' => '116'), 120 => array('name' => 'Cameroon', 'code' => 'CM', 'id' => 120, 'currency_code' => '950'), 124 => array('name' => 'Canada', 'code' => 'CA', 'id' => 124, 'currency_code' => '124'), 132 => array('name' => 'Cape Verde', 'code' => 'CV', 'id' => 132, 'currency_code' => '132'), 136 => array('name' => 'Cayman Islands', 'code' => 'KY', 'id' => 136, 'currency_code' => '136'), 140 => array('name' => 'Central African Republic', 'code' => 'CF', 'id' => 140, 'currency_code' => '950'), 148 => array('name' => 'Chad', 'code' => 'TD', 'id' => 148, 'currency_code' => '950'), 152 => array('name' => 'Chile', 'code' => 'CL', 'id' => 152, 'currency_code' => '152'), 156 => array('name' => 'China', 'code' => 'CN', 'id' => 156, 'currency_code' => '156'), 162 => array('name' => 'Christmas Island', 'code' => 'CX', 'id' => 162, 'currency_code' => '036'), 166 => array('name' => 'Cocos (Keeling) Islands', 'code' => 'CC', 'id' => 166, 'currency_code' => '036'), 170 => array('name' => 'Colombia', 'code' => 'CO', 'id' => 170, 'currency_code' => '170'), 174 => array('name' => 'Comoros', 'code' => 'KM', 'id' => 174, 'currency_code' => '174'), 178 => array('name' => 'Congo', 'code' => 'CG', 'id' => 178, 'currency_code' => '950'), 180 => array('name' => 'Congo, the Democratic Republic of the', 'code' => 'CD', 'id' => 180, 'currency_code' => '976'), 184 => array('name' => 'Cook Islands', 'code' => 'CK', 'id' => 184, 'currency_code' => '554'), 188 => array('name' => 'Costa Rica', 'code' => 'CR', 'id' => 188, 'currency_code' => '188'), 191 => array('name' => 'Croatia', 'code' => 'HR', 'id' => 191, 'currency_code' => '191'), 192 => array('name' => 'Cuba', 'code' => 'CU', 'id' => 192, 'currency_code' => '192'), 531 => array('name' => 'Curaçao', 'code' => 'CW', 'id' => 531, 'currency_code' => '532'), 196 => array('name' => 'Cyprus', 'code' => 'CY', 'id' => 196, 'currency_code' => '978'), 203 => array('name' => 'Czech Republic', 'code' => 'CZ', 'id' => 203, 'currency_code' => '203'), 384 => array('name' => 'Côte d\'Ivoire', 'code' => 'CI', 'id' => 384, 'currency_code' => '952'), 208 => array('name' => 'Denmark', 'code' => 'DK', 'id' => 208, 'currency_code' => '208'), 262 => array('name' => 'Djibouti', 'code' => 'DJ', 'id' => 262, 'currency_code' => '262'), 212 => array('name' => 'Dominica', 'code' => 'DM', 'id' => 212, 'currency_code' => '951'), 214 => array('name' => 'Dominican Republic', 'code' => 'DO', 'id' => 214, 'currency_code' => '214'), 218 => array('name' => 'Ecuador', 'code' => 'EC', 'id' => 218, 'currency_code' => '840'), 818 => array('name' => 'Egypt', 'code' => 'EG', 'id' => 818, 'currency_code' => '818'), 222 => array('name' => 'El Salvador', 'code' => 'SV', 'id' => 222, 'currency_code' => '840'), 226 => array('name' => 'Equatorial Guinea', 'code' => 'GQ', 'id' => 226, 'currency_code' => '950'), 232 => array('name' => 'Eritrea', 'code' => 'ER', 'id' => 232, 'currency_code' => '232'), 233 => array('name' => 'Estonia', 'code' => 'EE', 'id' => 233, 'currency_code' => '978'), 231 => array('name' => 'Ethiopia', 'code' => 'ET', 'id' => 231, 'currency_code' => '230'), 238 => array('name' => 'Falkland Islands (Malvinas)', 'code' => 'FK', 'id' => 238, 'currency_code' => '238'), 234 => array('name' => 'Faroe Islands', 'code' => 'FO', 'id' => 234, 'currency_code' => '208'), 242 => array('name' => 'Fiji', 'code' => 'FJ', 'id' => 242, 'currency_code' => '242'), 246 => array('name' => 'Finland', 'code' => 'FI', 'id' => 246, 'currency_code' => '978'), 250 => array('name' => 'France', 'code' => 'FR', 'id' => 250, 'currency_code' => '978'), 254 => array('name' => 'French Guiana', 'code' => 'GF', 'id' => 254, 'currency_code' => '978'), 258 => array('name' => 'French Polynesia', 'code' => 'PF', 'id' => 258, 'currency_code' => '953'), 260 => array('name' => 'French Southern Territories', 'code' => 'TF', 'id' => 260, 'currency_code' => '978'), 266 => array('name' => 'Gabon', 'code' => 'GA', 'id' => 266, 'currency_code' => '950'), 270 => array('name' => 'Gambia', 'code' => 'GM', 'id' => 270, 'currency_code' => '270'), 268 => array('name' => 'Georgia', 'code' => 'GE', 'id' => 268, 'currency_code' => '981'), 276 => array('name' => 'Germany', 'code' => 'DE', 'id' => 276, 'currency_code' => '978'), 288 => array('name' => 'Ghana', 'code' => 'GH', 'id' => 288, 'currency_code' => '936'), 292 => array('name' => 'Gibraltar', 'code' => 'GI', 'id' => 292, 'currency_code' => '292'), 300 => array('name' => 'Greece', 'code' => 'GR', 'id' => 300, 'currency_code' => '978'), 304 => array('name' => 'Greenland', 'code' => 'GL', 'id' => 304, 'currency_code' => '208'), 308 => array('name' => 'Grenada', 'code' => 'GD', 'id' => 308, 'currency_code' => '951'), 312 => array('name' => 'Guadeloupe', 'code' => 'GP', 'id' => 312, 'currency_code' => '978'), 316 => array('name' => 'Guam', 'code' => 'GU', 'id' => 316, 'currency_code' => '840'), 320 => array('name' => 'Guatemala', 'code' => 'GT', 'id' => 320, 'currency_code' => '320'), 831 => array('name' => 'Guernsey', 'code' => 'GG', 'id' => 831, 'currency_code' => '826'), 324 => array('name' => 'Guinea', 'code' => 'GN', 'id' => 324, 'currency_code' => '324'), 624 => array('name' => 'Guinea-Bissau', 'code' => 'GW', 'id' => 624, 'currency_code' => '952'), 328 => array('name' => 'Guyana', 'code' => 'GY', 'id' => 328, 'currency_code' => '328'), 332 => array('name' => 'Haiti', 'code' => 'HT', 'id' => 332, 'currency_code' => '840'), 334 => array('name' => 'Heard Island and McDonald Mcdonald Islands', 'code' => 'HM', 'id' => 334, 'currency_code' => '036'), 336 => array('name' => 'Holy See (Vatican City State)', 'code' => 'VA', 'id' => 336, 'currency_code' => '978'), 340 => array('name' => 'Honduras', 'code' => 'HN', 'id' => 340, 'currency_code' => '340'), 344 => array('name' => 'Hong Kong', 'code' => 'HK', 'id' => 344, 'currency_code' => '344'), 348 => array('name' => 'Hungary', 'code' => 'HU', 'id' => 348, 'currency_code' => '348'), 352 => array('name' => 'Iceland', 'code' => 'IS', 'id' => 352, 'currency_code' => '352'), 356 => array('name' => 'India', 'code' => 'IN', 'id' => 356, 'currency_code' => '356'), 360 => array('name' => 'Indonesia', 'code' => 'ID', 'id' => 360, 'currency_code' => '360'), 364 => array('name' => 'Iran, Islamic Republic of', 'code' => 'IR', 'id' => 364, 'currency_code' => '364'), 368 => array('name' => 'Iraq', 'code' => 'IQ', 'id' => 368, 'currency_code' => '368'), 372 => array('name' => 'Ireland', 'code' => 'IE', 'id' => 372, 'currency_code' => '978'), 833 => array('name' => 'Isle of Man', 'code' => 'IM', 'id' => 833, 'currency_code' => '826'), 376 => array('name' => 'Israel', 'code' => 'IL', 'id' => 376, 'currency_code' => '376'), 380 => array('name' => 'Italy', 'code' => 'IT', 'id' => 380, 'currency_code' => '978'), 388 => array('name' => 'Jamaica', 'code' => 'JM', 'id' => 388, 'currency_code' => '388'), 392 => array('name' => 'Japan', 'code' => 'JP', 'id' => 392, 'currency_code' => '392'), 832 => array('name' => 'Jersey', 'code' => 'JE', 'id' => 832, 'currency_code' => '826'), 400 => array('name' => 'Jordan', 'code' => 'JO', 'id' => 400, 'currency_code' => '400'), 398 => array('name' => 'Kazakhstan', 'code' => 'KZ', 'id' => 398, 'currency_code' => '398'), 404 => array('name' => 'Kenya', 'code' => 'KE', 'id' => 404, 'currency_code' => '404'), 296 => array('name' => 'Kiribati', 'code' => 'KI', 'id' => 296, 'currency_code' => '036'), 408 => array('name' => 'Korea, Democratic People\'s Republic of', 'code' => 'KP', 'id' => 408, 'currency_code' => '408'), 410 => array('name' => 'Korea, Republic of', 'code' => 'KR', 'id' => 410, 'currency_code' => '410'), 414 => array('name' => 'Kuwait', 'code' => 'KW', 'id' => 414, 'currency_code' => '414'), 417 => array('name' => 'Kyrgyzstan', 'code' => 'KG', 'id' => 417, 'currency_code' => '417'), 418 => array('name' => 'Lao People\'s Democratic Republic', 'code' => 'LA', 'id' => 418, 'currency_code' => '418'), 428 => array('name' => 'Latvia', 'code' => 'LV', 'id' => 428, 'currency_code' => '428'), 422 => array('name' => 'Lebanon', 'code' => 'LB', 'id' => 422, 'currency_code' => '422'), 426 => array('name' => 'Lesotho', 'code' => 'LS', 'id' => 426, 'currency_code' => '710'), 430 => array('name' => 'Liberia', 'code' => 'LR', 'id' => 430, 'currency_code' => '430'), 434 => array('name' => 'Libya', 'code' => 'LY', 'id' => 434, 'currency_code' => '434'), 438 => array('name' => 'Liechtenstein', 'code' => 'LI', 'id' => 438, 'currency_code' => '756'), 440 => array('name' => 'Lithuania', 'code' => 'LT', 'id' => 440, 'currency_code' => '440'), 442 => array('name' => 'Luxembourg', 'code' => 'LU', 'id' => 442, 'currency_code' => '978'), 446 => array('name' => 'Macao', 'code' => 'MO', 'id' => 446, 'currency_code' => '446'), 807 => array('name' => 'Macedonia, the Former Yugoslav Republic of', 'code' => 'MK', 'id' => 807, 'currency_code' => '807'), 450 => array('name' => 'Madagascar', 'code' => 'MG', 'id' => 450, 'currency_code' => '969'), 454 => array('name' => 'Malawi', 'code' => 'MW', 'id' => 454, 'currency_code' => '454'), 458 => array('name' => 'Malaysia', 'code' => 'MY', 'id' => 458, 'currency_code' => '458'), 462 => array('name' => 'Maldives', 'code' => 'MV', 'id' => 462, 'currency_code' => '462'), 466 => array('name' => 'Mali', 'code' => 'ML', 'id' => 466, 'currency_code' => '952'), 470 => array('name' => 'Malta', 'code' => 'MT', 'id' => 470, 'currency_code' => '978'), 584 => array('name' => 'Marshall Islands', 'code' => 'MH', 'id' => 584, 'currency_code' => '840'), 474 => array('name' => 'Martinique', 'code' => 'MQ', 'id' => 474, 'currency_code' => '978'), 478 => array('name' => 'Mauritania', 'code' => 'MR', 'id' => 478, 'currency_code' => '478'), 480 => array('name' => 'Mauritius', 'code' => 'MU', 'id' => 480, 'currency_code' => '480'), 175 => array('name' => 'Mayotte', 'code' => 'YT', 'id' => 175, 'currency_code' => '978'), 484 => array('name' => 'Mexico', 'code' => 'MX', 'id' => 484, 'currency_code' => '484'), 583 => array('name' => 'Micronesia, Federated States of', 'code' => 'FM', 'id' => 583, 'currency_code' => '840'), 498 => array('name' => 'Moldova, Republic of', 'code' => 'MD', 'id' => 498, 'currency_code' => '498'), 492 => array('name' => 'Monaco', 'code' => 'MC', 'id' => 492, 'currency_code' => '978'), 496 => array('name' => 'Mongolia', 'code' => 'MN', 'id' => 496, 'currency_code' => '496'), 499 => array('name' => 'Montenegro', 'code' => 'ME', 'id' => 499, 'currency_code' => '978'), 500 => array('name' => 'Montserrat', 'code' => 'MS', 'id' => 500, 'currency_code' => '951'), 504 => array('name' => 'Morocco', 'code' => 'MA', 'id' => 504, 'currency_code' => '504'), 508 => array('name' => 'Mozambique', 'code' => 'MZ', 'id' => 508, 'currency_code' => '943'), 104 => array('name' => 'Myanmar', 'code' => 'MM', 'id' => 104, 'currency_code' => '104'), 516 => array('name' => 'Namibia', 'code' => 'NA', 'id' => 516, 'currency_code' => '710'), 520 => array('name' => 'Nauru', 'code' => 'NR', 'id' => 520, 'currency_code' => '036'), 524 => array('name' => 'Nepal', 'code' => 'NP', 'id' => 524, 'currency_code' => '524'), 528 => array('name' => 'Netherlands', 'code' => 'NL', 'id' => 528, 'currency_code' => '978'), 540 => array('name' => 'New Caledonia', 'code' => 'NC', 'id' => 540, 'currency_code' => '953'), 554 => array('name' => 'New Zealand', 'code' => 'NZ', 'id' => 554, 'currency_code' => '554'), 558 => array('name' => 'Nicaragua', 'code' => 'NI', 'id' => 558, 'currency_code' => '558'), 562 => array('name' => 'Niger', 'code' => 'NE', 'id' => 562, 'currency_code' => '952'), 566 => array('name' => 'Nigeria', 'code' => 'NG', 'id' => 566, 'currency_code' => '566'), 570 => array('name' => 'Niue', 'code' => 'NU', 'id' => 570, 'currency_code' => '554'), 574 => array('name' => 'Norfolk Island', 'code' => 'NF', 'id' => 574, 'currency_code' => '036'), 580 => array('name' => 'Northern Mariana Islands', 'code' => 'MP', 'id' => 580, 'currency_code' => '840'), 578 => array('name' => 'Norway', 'code' => 'NO', 'id' => 578, 'currency_code' => '578'), 512 => array('name' => 'Oman', 'code' => 'OM', 'id' => 512, 'currency_code' => '512'), 586 => array('name' => 'Pakistan', 'code' => 'PK', 'id' => 586, 'currency_code' => '586'), 585 => array('name' => 'Palau', 'code' => 'PW', 'id' => 585, 'currency_code' => '840'), 591 => array('name' => 'Panama', 'code' => 'PA', 'id' => 591, 'currency_code' => '840'), 598 => array('name' => 'Papua New Guinea', 'code' => 'PG', 'id' => 598, 'currency_code' => '598'), 600 => array('name' => 'Paraguay', 'code' => 'PY', 'id' => 600, 'currency_code' => '600'), 604 => array('name' => 'Peru', 'code' => 'PE', 'id' => 604, 'currency_code' => '604'), 608 => array('name' => 'Philippines', 'code' => 'PH', 'id' => 608, 'currency_code' => '608'), 612 => array('name' => 'Pitcairn', 'code' => 'PN', 'id' => 612, 'currency_code' => '554'), 616 => array('name' => 'Poland', 'code' => 'PL', 'id' => 616, 'currency_code' => '985'), 620 => array('name' => 'Portugal', 'code' => 'PT', 'id' => 620, 'currency_code' => '978'), 630 => array('name' => 'Puerto Rico', 'code' => 'PR', 'id' => 630, 'currency_code' => '840'), 634 => array('name' => 'Qatar', 'code' => 'QA', 'id' => 634, 'currency_code' => '634'), 642 => array('name' => 'Romania', 'code' => 'RO', 'id' => 642, 'currency_code' => '946'), 643 => array('name' => 'Russian Federation', 'code' => 'RU', 'id' => 643, 'currency_code' => '643'), 646 => array('name' => 'Rwanda', 'code' => 'RW', 'id' => 646, 'currency_code' => '646'), 638 => array('name' => 'Réunion', 'code' => 'RE', 'id' => 638, 'currency_code' => '978'), 652 => array('name' => 'Saint Barthélemy', 'code' => 'BL', 'id' => 652, 'currency_code' => '978'), 654 => array('name' => 'Saint Helena, Ascension and Tristan da Cunha', 'code' => 'SH', 'id' => 654, 'currency_code' => '654'), 659 => array('name' => 'Saint Kitts and Nevis', 'code' => 'KN', 'id' => 659, 'currency_code' => '951'), 662 => array('name' => 'Saint Lucia', 'code' => 'LC', 'id' => 662, 'currency_code' => '951'), 663 => array('name' => 'Saint Martin (French part)', 'code' => 'MF', 'id' => 663, 'currency_code' => '978'), 666 => array('name' => 'Saint Pierre and Miquelon', 'code' => 'PM', 'id' => 666, 'currency_code' => '978'), 670 => array('name' => 'Saint Vincent and the Grenadines', 'code' => 'VC', 'id' => 670, 'currency_code' => '951'), 882 => array('name' => 'Samoa', 'code' => 'WS', 'id' => 882, 'currency_code' => '882'), 674 => array('name' => 'San Marino', 'code' => 'SM', 'id' => 674, 'currency_code' => '978'), 678 => array('name' => 'Sao Tome and Principe', 'code' => 'ST', 'id' => 678, 'currency_code' => '678'), 682 => array('name' => 'Saudi Arabia', 'code' => 'SA', 'id' => 682, 'currency_code' => '682'), 686 => array('name' => 'Senegal', 'code' => 'SN', 'id' => 686, 'currency_code' => '952'), 688 => array('name' => 'Serbia', 'code' => 'RS', 'id' => 688, 'currency_code' => '941'), 690 => array('name' => 'Seychelles', 'code' => 'SC', 'id' => 690, 'currency_code' => '690'), 694 => array('name' => 'Sierra Leone', 'code' => 'SL', 'id' => 694, 'currency_code' => '694'), 702 => array('name' => 'Singapore', 'code' => 'SG', 'id' => 702, 'currency_code' => '702'), 534 => array('name' => 'Sint Maarten (Dutch part)', 'code' => 'SX', 'id' => 534, 'currency_code' => '532'), 703 => array('name' => 'Slovakia', 'code' => 'SK', 'id' => 703, 'currency_code' => '978'), 705 => array('name' => 'Slovenia', 'code' => 'SI', 'id' => 705, 'currency_code' => '978'), 90 => array('name' => 'Solomon Islands', 'code' => 'SB', 'id' => 90, 'currency_code' => '090'), 706 => array('name' => 'Somalia', 'code' => 'SO', 'id' => 706, 'currency_code' => '706'), 710 => array('name' => 'South Africa', 'code' => 'ZA', 'id' => 710, 'currency_code' => '710'), 728 => array('name' => 'South Sudan', 'code' => 'SS', 'id' => 728, 'currency_code' => '728'), 724 => array('name' => 'Spain', 'code' => 'ES', 'id' => 724, 'currency_code' => '978'), 144 => array('name' => 'Sri Lanka', 'code' => 'LK', 'id' => 144, 'currency_code' => '144'), 729 => array('name' => 'Sudan', 'code' => 'SD', 'id' => 729, 'currency_code' => '938'), 740 => array('name' => 'Suriname', 'code' => 'SR', 'id' => 740, 'currency_code' => '968'), 744 => array('name' => 'Svalbard and Jan Mayen', 'code' => 'SJ', 'id' => 744, 'currency_code' => '578'), 748 => array('name' => 'Swaziland', 'code' => 'SZ', 'id' => 748, 'currency_code' => '748'), 752 => array('name' => 'Sweden', 'code' => 'SE', 'id' => 752, 'currency_code' => '752'), 756 => array('name' => 'Switzerland', 'code' => 'CH', 'id' => 756, 'currency_code' => '756'), 760 => array('name' => 'Syrian Arab Republic', 'code' => 'SY', 'id' => 760, 'currency_code' => '760'), 158 => array('name' => 'Taiwan, Province of China', 'code' => 'TW', 'id' => 158, 'currency_code' => '901'), 762 => array('name' => 'Tajikistan', 'code' => 'TJ', 'id' => 762, 'currency_code' => '972'), 834 => array('name' => 'Tanzania, United Republic of', 'code' => 'TZ', 'id' => 834, 'currency_code' => '834'), 764 => array('name' => 'Thailand', 'code' => 'TH', 'id' => 764, 'currency_code' => '764'), 626 => array('name' => 'Timor-Leste', 'code' => 'TL', 'id' => 626, 'currency_code' => '840'), 768 => array('name' => 'Togo', 'code' => 'TG', 'id' => 768, 'currency_code' => '952'), 772 => array('name' => 'Tokelau', 'code' => 'TK', 'id' => 772, 'currency_code' => '554'), 776 => array('name' => 'Tonga', 'code' => 'TO', 'id' => 776, 'currency_code' => '776'), 780 => array('name' => 'Trinidad and Tobago', 'code' => 'TT', 'id' => 780, 'currency_code' => '780'), 788 => array('name' => 'Tunisia', 'code' => 'TN', 'id' => 788, 'currency_code' => '788'), 792 => array('name' => 'Turkey', 'code' => 'TR', 'id' => 792, 'currency_code' => '949'), 795 => array('name' => 'Turkmenistan', 'code' => 'TM', 'id' => 795, 'currency_code' => '934'), 796 => array('name' => 'Turks and Caicos Islands', 'code' => 'TC', 'id' => 796, 'currency_code' => '840'), 798 => array('name' => 'Tuvalu', 'code' => 'TV', 'id' => 798, 'currency_code' => '036'), 800 => array('name' => 'Uganda', 'code' => 'UG', 'id' => 800, 'currency_code' => '800'), 804 => array('name' => 'Ukraine', 'code' => 'UA', 'id' => 804, 'currency_code' => '980'), 784 => array('name' => 'United Arab Emirates', 'code' => 'AE', 'id' => 784, 'currency_code' => '784'), 826 => array('name' => 'United Kingdom', 'code' => 'GB', 'id' => 826, 'currency_code' => '826'), 840 => array('name' => 'United States', 'code' => 'US', 'id' => 840, 'currency_code' => '840'), 581 => array('name' => 'United States Minor Outlying Islands', 'code' => 'UM', 'id' => 581, 'currency_code' => '840'), 858 => array('name' => 'Uruguay', 'code' => 'UY', 'id' => 858, 'currency_code' => '858'), 860 => array('name' => 'Uzbekistan', 'code' => 'UZ', 'id' => 860, 'currency_code' => '860'), 548 => array('name' => 'Vanuatu', 'code' => 'VU', 'id' => 548, 'currency_code' => '548'), 862 => array('name' => 'Venezuela, Bolivarian Republic of', 'code' => 'VE', 'id' => 862, 'currency_code' => '937'), 704 => array('name' => 'Viet Nam', 'code' => 'VN', 'id' => 704, 'currency_code' => '704'), 92 => array('name' => 'Virgin Islands, British', 'code' => 'VG', 'id' => 92, 'currency_code' => '840'), 850 => array('name' => 'Virgin Islands, U.S.', 'code' => 'VI', 'id' => 850, 'currency_code' => '840'), 876 => array('name' => 'Wallis and Futuna', 'code' => 'WF', 'id' => 876, 'currency_code' => '953'), 732 => array('name' => 'Western Sahara', 'code' => 'EH', 'id' => 732, 'currency_code' => '504'), 887 => array('name' => 'Yemen', 'code' => 'YE', 'id' => 887, 'currency_code' => '886'), 894 => array('name' => 'Zambia', 'code' => 'ZM', 'id' => 894, 'currency_code' => '967'), 716 => array('name' => 'Zimbabwe', 'code' => 'ZW', 'id' => 716, 'currency_code' => '932'));
    /**
     * Currencies of the world by ISO 4217
     *
     * @link http://en.wikipedia.org/wiki/ISO_4217
     * @var array
     */
    public static $currencies = array('971' => array(
        // Numeric code. *IMPORTANT* that this be quoted; PHP will not treat 008 the same as '008'
        'code' => 'AFN',
        // Alphabetical code, three digits
        'name' => 'Afghani',
        // US-English name of the currency
        'exponent' => '2',
        // Minor-units-how many decimals come after the major unit. USD has 2, while the Yen has 0
        'symbol' => '&#1547;',
    ), '008' => array('code' => 'ALL', 'name' => 'Lek', 'exponent' => '2', 'symbol' => 'L'), '012' => array('code' => 'DZD', 'name' => 'Algerian Dinar', 'exponent' => '2', 'symbol' => 'DA'), '840' => array('code' => 'USD', 'name' => 'US Dollar', 'exponent' => '2', 'symbol' => '$', 'fontawesome' => 'fa-usd'), '978' => array('code' => 'EUR', 'name' => 'Euro', 'exponent' => '2', 'symbol' => '&#8364;', 'fontawesome' => 'fa-eur'), '973' => array('code' => 'AOA', 'name' => 'Angolan Kwanza', 'exponent' => '2', 'symbol' => 'Kz'), '951' => array('code' => 'XCD', 'name' => 'East Caribbean Dollar', 'exponent' => '2', 'symbol' => '$'), '032' => array('code' => 'ARS', 'name' => 'Argentine Peso', 'exponent' => '2', 'symbol' => '$'), '051' => array('code' => 'AMD', 'name' => 'Armenian Dram', 'exponent' => '2', 'symbol' => '&#1423;'), '533' => array('code' => 'AWG', 'name' => 'Aruban Florin', 'exponent' => '2', 'symbol' => '&#402;'), '036' => array('code' => 'AUD', 'name' => 'Australian Dollar', 'exponent' => '2', 'symbol' => '$'), '944' => array('code' => 'AZN', 'name' => 'Azerbaijanian Manat', 'exponent' => '2', 'symbol' => '&#8380'), '044' => array('code' => 'BSD', 'name' => 'Bahamian Dollar', 'exponent' => '2', 'symbol' => '$'), '048' => array('code' => 'BHD', 'name' => 'Bahraini Dinar', 'exponent' => '3', 'symbol' => 'BD'), '050' => array('code' => 'BDT', 'name' => 'Bangladeshi Taka', 'exponent' => '2', 'symbol' => 'Tk;'), '052' => array('code' => 'BBD', 'name' => 'Barbados Dollar', 'exponent' => '2', 'symbol' => '$'), '974' => array('code' => 'BYR', 'name' => 'Belarussian Ruble', 'exponent' => '0', 'symbol' => 'Br'), '084' => array('code' => 'BZD', 'name' => 'Belize Dollar', 'exponent' => '2', 'symbol' => 'BZ$'), '952' => array('code' => 'XOF', 'name' => 'CFA Franc BCEAO', 'exponent' => '0', 'symbol' => 'CFA'), '060' => array('code' => 'BMD', 'name' => 'Bermudian Dollar', 'exponent' => '2', 'symbol' => '$'), '356' => array('code' => 'INR', 'name' => 'Indian Rupee', 'exponent' => '2', 'symbol' => '&#8377;', 'fontawesome' => 'fa-inr'), '068' => array('code' => 'BOB', 'name' => 'Boliviano', 'exponent' => '2', 'symbol' => '$b'), '977' => array('code' => 'BAM', 'name' => 'Convertible Mark', 'exponent' => '2', 'symbol' => 'KM'), '072' => array('code' => 'BWP', 'name' => 'Pula', 'exponent' => '2', 'symbol' => 'P'), '578' => array('code' => 'NOK', 'name' => 'Norwegian Krone', 'exponent' => '2', 'symbol' => 'kr'), '986' => array('code' => 'BRL', 'name' => 'Brazilian Real', 'exponent' => '2', 'symbol' => 'R$'), '096' => array('code' => 'BND', 'name' => 'Brunei Dollar', 'exponent' => '2', 'symbol' => '$'), '975' => array('code' => 'BGN', 'name' => 'Bulgarian Lev', 'exponent' => '2', 'symbol' => '&#1083;&#1074;'), '108' => array('code' => 'BIF', 'name' => 'Burundi Franc', 'exponent' => '0', 'symbol' => 'FBu'), '116' => array('code' => 'KHR', 'name' => 'Cambodian Riel', 'exponent' => '2', 'symbol' => '&#6107;'), '950' => array('code' => 'XAF', 'name' => 'CFA Franc BEAC', 'exponent' => '0', 'symbol' => 'FCFA'), '124' => array('code' => 'CAD', 'name' => 'Canadian Dollar', 'exponent' => '2', 'symbol' => '$'), '132' => array('code' => 'CVE', 'name' => 'Cape Verde Escudo', 'exponent' => '2', 'symbol' => '$'), '136' => array('code' => 'KYD', 'name' => 'Cayman Islands Dollar', 'exponent' => '2', 'symbol' => '$'), '152' => array('code' => 'CLP', 'name' => 'Chilean Peso', 'exponent' => '0', 'symbol' => '$'), '156' => array('code' => 'CNY', 'name' => 'Yuan Renminbi', 'exponent' => '2', 'symbol' => '&#165;', 'fontawesome' => 'fa-cny'), '170' => array('code' => 'COP', 'name' => 'Colombian Peso', 'exponent' => '2', 'symbol' => '$'), '174' => array('code' => 'KMF', 'name' => 'Comoro Franc', 'exponent' => '0', 'symbol' => 'Fr'), '976' => array('code' => 'CDF', 'name' => 'Congolese Franc', 'exponent' => '2', 'symbol' => 'Fr'), '554' => array('code' => 'NZD', 'name' => 'New Zealand Dollar', 'exponent' => '2', 'symbol' => '$'), '188' => array('code' => 'CRC', 'name' => 'Costa Rican Colon', 'exponent' => '2', 'symbol' => '&#8353;'), '191' => array('code' => 'HRK', 'name' => 'Croatian Kuna', 'exponent' => '2', 'symbol' => 'kn'), '192' => array('code' => 'CUP', 'name' => 'Cuban Peso', 'exponent' => '2', 'symbol' => '$MN', 'fontawesome' => 'fa-rouble'), '532' => array('code' => 'ANG', 'name' => 'Netherlands Antillean Guilder', 'exponent' => '2', 'symbol' => 'NA&#402;'), '203' => array('code' => 'CZK', 'name' => 'Czech Koruna', 'exponent' => '2', 'symbol' => 'K&#269;'), '208' => array('code' => 'DKK', 'name' => 'Danish Krone', 'exponent' => '2', 'symbol' => 'kr'), '262' => array('code' => 'DJF', 'name' => 'Djibouti Franc', 'exponent' => '0', 'symbol' => 'fr'), '214' => array('code' => 'DOP', 'name' => 'Dominican Peso', 'exponent' => '2', 'symbol' => 'RD$'), '818' => array('code' => 'EGP', 'name' => 'Egyptian Pound', 'exponent' => '2', 'symbol' => '&#163;'), '232' => array('code' => 'ERN', 'name' => 'Nakfa', 'exponent' => '2', 'symbol' => 'Nfk'), '230' => array('code' => 'ETB', 'name' => 'Ethiopian Birr', 'exponent' => '2', 'symbol' => 'Br'), '238' => array('code' => 'FKP', 'name' => 'Falkland Islands Pound', 'exponent' => '2', 'symbol' => '&#163;'), '242' => array('code' => 'FJD', 'name' => 'Fiji Dollar', 'exponent' => '2', 'symbol' => '$'), '953' => array('code' => 'XPF', 'name' => 'CFP Franc', 'exponent' => '0', 'symbol' => 'F'), '270' => array('code' => 'GMD', 'name' => 'Dalasi', 'exponent' => '2', 'symbol' => 'D'), '981' => array('code' => 'GEL', 'name' => 'Lari', 'exponent' => '2', 'symbol' => '&#4314;'), '936' => array('code' => 'GHS', 'name' => 'Ghana Cedi', 'exponent' => '2', 'symbol' => 'GH&#8373;'), '292' => array('code' => 'GIP', 'name' => 'Gibraltar Pound', 'exponent' => '2', 'symbol' => '&#163;'), '320' => array('code' => 'GTQ', 'name' => 'Quetzal', 'exponent' => '2', 'symbol' => 'Q'), '826' => array('code' => 'GBP', 'name' => 'Pound Sterling', 'exponent' => '2', 'symbol' => '&#163;', 'fontawesome' => 'fa-gbp'), '324' => array('code' => 'GNF', 'name' => 'Guinea Franc', 'exponent' => '0', 'symbol' => 'Fr'), '328' => array('code' => 'GYD', 'name' => 'Guyana Dollar', 'exponent' => '2', 'symbol' => 'G$'), '340' => array('code' => 'HNL', 'name' => 'Lempira', 'exponent' => '2', 'symbol' => 'L'), '344' => array('code' => 'HKD', 'name' => 'Hong Kong Dollar', 'exponent' => '2', 'symbol' => 'HK$'), '348' => array('code' => 'HUF', 'name' => 'Forint', 'exponent' => '2', 'symbol' => 'Ft'), '352' => array('code' => 'ISK', 'name' => 'Iceland Krona', 'exponent' => '0', 'symbol' => 'kr'), '360' => array('code' => 'IDR', 'name' => 'Rupiah', 'exponent' => '2', 'symbol' => 'Rp'), '364' => array('code' => 'IRR', 'name' => 'Iranian Rial', 'exponent' => '2', 'symbol' => '&#65020;'), '368' => array('code' => 'IQD', 'name' => 'Iraqi Dinar', 'exponent' => '3', 'symbol' => '&#1583;&#46;&#1593;'), '376' => array('code' => 'ILS', 'name' => 'New Israeli Sheqel', 'exponent' => '2', 'symbol' => '&#8362;'), '388' => array('code' => 'JMD', 'name' => 'Jamaican Dollar', 'exponent' => '2', 'symbol' => 'J$'), '392' => array('code' => 'JPY', 'name' => 'Yen', 'exponent' => '0', 'symbol' => '&#165;', 'fontawesome' => 'fa-jpy'), '400' => array('code' => 'JOD', 'name' => 'Jordanian Dinar', 'exponent' => '3', 'symbol' => 'JD'), '398' => array('code' => 'KZT', 'name' => 'Tenge', 'exponent' => '2', 'symbol' => '&#8376;'), '404' => array('code' => 'KES', 'name' => 'Kenyan Shilling', 'exponent' => '2', 'symbol' => 'Ksh'), '410' => array('code' => 'KRW', 'name' => 'Won', 'exponent' => '0', 'symbol' => '&#8361;', 'fontawesome' => 'fa-krw'), '414' => array('code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'exponent' => '3', 'symbol' => '&#1603;'), '417' => array('code' => 'KGS', 'name' => 'Som', 'exponent' => '2', 'symbol' => '&#1083;&#1074;'), '418' => array('code' => 'LAK', 'name' => 'Kip', 'exponent' => '2', 'symbol' => '&#8365;'), '428' => array('code' => 'LVL', 'name' => 'Latvian Lats', 'exponent' => '2', 'symbol' => 'Ls'), '422' => array('code' => 'LBP', 'name' => 'Lebanese Pound', 'exponent' => '2', 'symbol' => '&#163;'), '710' => array('code' => 'ZAR', 'name' => 'Rand', 'exponent' => '2', 'symbol' => 'R'), '430' => array('code' => 'LRD', 'name' => 'Liberian Dollar', 'exponent' => '2', 'symbol' => '$'), '434' => array('code' => 'LYD', 'name' => 'Libyan Dinar', 'exponent' => '3', 'symbol' => 'LD'), '756' => array('code' => 'CHF', 'name' => 'Swiss Franc', 'exponent' => '2', 'symbol' => 'SFr'), '440' => array('code' => 'LTL', 'name' => 'Lithuanian Litas', 'exponent' => '2', 'symbol' => 'Lt'), '446' => array('code' => 'MOP', 'name' => 'Pataca', 'exponent' => '2', 'symbol' => 'MOP$'), '807' => array('code' => 'MKD', 'name' => 'Denar', 'exponent' => '2', 'symbol' => '&#1076;&#1077;&#1085;'), '969' => array('code' => 'MGA', 'name' => 'Malagasy Ariary', 'exponent' => '2', 'symbol' => 'Ar'), '454' => array('code' => 'MWK', 'name' => 'Kwacha', 'exponent' => '2', 'symbol' => 'MK'), '458' => array('code' => 'MYR', 'name' => 'Malaysian Ringgit', 'exponent' => '2', 'symbol' => 'RM'), '462' => array('code' => 'MVR', 'name' => 'Rufiyaa', 'exponent' => '2', 'symbol' => 'Rf.'), '478' => array('code' => 'MRO', 'name' => 'Ouguiya', 'exponent' => '2', 'symbol' => 'UM'), '480' => array('code' => 'MUR', 'name' => 'Mauritius Rupee', 'exponent' => '2', 'symbol' => 'Rs'), '484' => array('code' => 'MXN', 'name' => 'Mexican Peso', 'exponent' => '2', 'symbol' => '$'), '498' => array('code' => 'MDL', 'name' => 'Moldovan Leu', 'exponent' => '2', 'symbol' => 'L'), '496' => array('code' => 'MNT', 'name' => 'Tugrik', 'exponent' => '2', 'symbol' => '&#8366;'), '504' => array('code' => 'MAD', 'name' => 'Moroccan Dirham', 'exponent' => '2', 'symbol' => 'MAD'), '943' => array('code' => 'MZN', 'name' => 'Mozambique Metical', 'exponent' => '2', 'symbol' => 'MT'), '104' => array('code' => 'MMK', 'name' => 'Kyat', 'exponent' => '2', 'symbol' => 'K'), '524' => array('code' => 'NPR', 'name' => 'Nepalese Rupee', 'exponent' => '2', 'symbol' => 'Rs'), '558' => array('code' => 'NIO', 'name' => 'Cordoba Oro', 'exponent' => '2', 'symbol' => 'C$'), '566' => array('code' => 'NGN', 'name' => 'Naira', 'exponent' => '2', 'symbol' => '&#8358;'), '512' => array('code' => 'OMR', 'name' => 'Rial Omani', 'exponent' => '3', 'symbol' => '&#65020;'), '586' => array('code' => 'PKR', 'name' => 'Pakistan Rupee', 'exponent' => '2', 'symbol' => 'PKR'), '598' => array('code' => 'PGK', 'name' => 'Kina', 'exponent' => '2', 'symbol' => 'K'), '600' => array('code' => 'PYG', 'name' => 'Guarani', 'exponent' => '0', 'symbol' => 'Gs'), '604' => array('code' => 'PEN', 'name' => 'Nuevo Sol', 'exponent' => '2', 'symbol' => 'S/.'), '608' => array('code' => 'PHP', 'name' => 'Philippine Peso', 'exponent' => '2', 'symbol' => '&#8369;'), '985' => array('code' => 'PLN', 'name' => 'Zloty', 'exponent' => '2', 'symbol' => '&#122;&#322;'), '634' => array('code' => 'QAR', 'name' => 'Qatari Rial', 'exponent' => '2', 'symbol' => '&#65020;'), '946' => array('code' => 'RON', 'name' => 'New Romanian Leu', 'exponent' => '2', 'symbol' => 'lei'), '643' => array('code' => 'RUB', 'name' => 'Russian Ruble', 'exponent' => '2', 'symbol' => '&#8381;', 'fontawesome' => 'fa-rub'), '646' => array('code' => 'RWF', 'name' => 'Rwanda Franc', 'exponent' => '0', 'symbol' => 'FRw'), '654' => array('code' => 'SHP', 'name' => 'Saint Helena Pound', 'exponent' => '2', 'symbol' => '&#163;'), '882' => array('code' => 'WST', 'name' => 'Tala', 'exponent' => '2', 'symbol' => 'WS$'), '678' => array('code' => 'STD', 'name' => 'Dobra', 'exponent' => '2', 'symbol' => 'Db'), '682' => array('code' => 'SAR', 'name' => 'Saudi Riyal', 'exponent' => '2', 'symbol' => '&#65020;'), '941' => array('code' => 'RSD', 'name' => 'Serbian Dinar', 'exponent' => '2', 'symbol' => '&#1056;&#1057;&#1044;'), '690' => array('code' => 'SCR', 'name' => 'Seychelles Rupee', 'exponent' => '2', 'symbol' => 'Rs'), '694' => array('code' => 'SLL', 'name' => 'Leone', 'exponent' => '2', 'symbol' => 'Le'), '702' => array('code' => 'SGD', 'name' => 'Singapore Dollar', 'exponent' => '2', 'symbol' => 'S$'), '090' => array('code' => 'SBD', 'name' => 'Solomon Islands Dollar', 'exponent' => '2', 'symbol' => 'SI$'), '706' => array('code' => 'SOS', 'name' => 'Somali Shilling', 'exponent' => '2', 'symbol' => 'S'), '728' => array('code' => 'SSP', 'name' => 'South Sudanese Pound', 'exponent' => '2', 'symbol' => '&#163;'), '144' => array('code' => 'LKR', 'name' => 'Sri Lanka Rupee', 'exponent' => '2', 'symbol' => 'Rs'), '938' => array('code' => 'SDG', 'name' => 'Sudanese Pound', 'exponent' => '2', 'symbol' => '&#163;'), '968' => array('code' => 'SRD', 'name' => 'Surinam Dollar', 'exponent' => '2', 'symbol' => 'SRD$'), '748' => array('code' => 'SZL', 'name' => 'Lilangeni', 'exponent' => '2', 'symbol' => 'E'), '752' => array('code' => 'SEK', 'name' => 'Swedish Krona', 'exponent' => '2', 'symbol' => 'kr'), '760' => array('code' => 'SYP', 'name' => 'Syrian Pound', 'exponent' => '2', 'symbol' => '&#163;'), '901' => array('code' => 'TWD', 'name' => 'New Taiwan Dollar', 'exponent' => '2', 'symbol' => '&#20803'), '972' => array('code' => 'TJS', 'name' => 'Somoni', 'exponent' => '2', 'symbol' => '$'), '764' => array('code' => 'THB', 'name' => 'Baht', 'exponent' => '2', 'symbol' => '&#3647;'), '776' => array('code' => 'TOP', 'name' => 'Pa’anga', 'exponent' => '2', 'symbol' => 'T$'), '780' => array('code' => 'TTD', 'name' => 'Trinidad and Tobago Dollar', 'exponent' => '2', 'symbol' => 'TT$'), '788' => array('code' => 'TND', 'name' => 'Tunisian Dinar', 'exponent' => '3', 'symbol' => '$'), '949' => array('code' => 'TRY', 'name' => 'Turkish Lira', 'exponent' => '2', 'symbol' => '&#8378;', 'fontawesome' => 'fa-try'), '934' => array('code' => 'TMT', 'name' => 'Turkmenistan New Manat', 'exponent' => '2', 'symbol' => 'T'), '800' => array('code' => 'UGX', 'name' => 'Uganda Shilling', 'exponent' => '0', 'symbol' => 'USh'), '980' => array('code' => 'UAH', 'name' => 'Hryvnia', 'exponent' => '2', 'symbol' => '&#8372;'), '784' => array('code' => 'AED', 'name' => 'UAE Dirham', 'exponent' => '2', 'symbol' => '&#1583;&#46;&#1573;'), '858' => array('code' => 'UYU', 'name' => 'Peso Uruguayo', 'exponent' => '2', 'symbol' => '$U'), '548' => array('code' => 'VUV', 'name' => 'Vatu', 'exponent' => '0', 'symbol' => 'VT'), '937' => array('code' => 'VEF', 'name' => 'Bolivar', 'exponent' => '2', 'symbol' => 'Bs.'), '704' => array('code' => 'VND', 'name' => 'Dong', 'exponent' => '0', 'symbol' => '&#8363;'), '886' => array('code' => 'YER', 'name' => 'Yemeni Rial', 'exponent' => '2', 'symbol' => '&#164;'), '967' => array('code' => 'ZMW', 'name' => 'Zambian Kwacha', 'exponent' => '2', 'symbol' => 'ZK'), '932' => array('code' => 'ZWL', 'name' => 'Zimbabwe Dollar', 'exponent' => '2', 'symbol' => 'Z$'));
    public static function find_currency_id($currency_code)
    {
        foreach (self::$currencies as $id => $currency) {
            if ($currency['code'] == $currency_code) {
                return $id;
            }
        }
        return NULL;
    }
    public static function get_conversion_transient_name($from, $to)
    {
        return 'ngg_currency_rate_from_' . $from . '_to_' . $to;
    }
    public static function get_conversion_error_transient_name($from, $to)
    {
        return 'ngg_currency_rate_error_from_' . $from . '_to_' . $to;
    }
    /**
     * @param int $from_currency Example: 840 (USD)
     * @param int $to_currency Example: 978 (EUR)
     * @return float
     */
    public static function get_conversion_rate($from_currency, $to_currency)
    {
        if (!isset(self::$currency_rates[$from_currency]) || !isset(self::$currency_rates[$from_currency][$to_currency])) {
            $recheck_rate = self::$recheck_rate;
            // Ensure that we at least return a float
            self::$currency_rates[$from_currency][$to_currency] = 0;
            $transient_name = self::get_conversion_transient_name($from_currency, $to_currency);
            $transient_error = self::get_conversion_error_transient_name($from_currency, $to_currency);
            $rate = get_transient($transient_name);
            $error = get_transient($transient_error);
            if ($rate !== FALSE) {
                self::$currency_rates[$from_currency][$to_currency] = $rate;
            } elseif ($rate === FALSE && $error === FALSE) {
                $from_code = isset(self::$currencies[$from_currency]) ? self::$currencies[$from_currency]['code'] : NULL;
                $to_code = isset(self::$currencies[$to_currency]) ? self::$currencies[$to_currency]['code'] : NULL;
                try {
                    if (!$from_code || !$to_code) {
                        throw new Exception(sprintf(__("'%s' or '%s' are invalid ISO 4217 numeric codes.", 'nextgen-gallery-pro'), $from_currency, $to_currency));
                    }
                    $appid = 'b544b1dd920346f6af4a9d094a085ea1';
                    $response = wp_remote_get('https://openexchangerates.org/api/convert/1/' . $from_code . '/' . $to_code . '?app_id=' . $appid);
                    if (!is_array($response) || empty($response['body'])) {
                        throw new Exception(__('Could not connect to api.exchangeratesapi.io', 'nextgen-gallery-pro'));
                    }
                    $json = json_decode($response['body'], TRUE);
                    if (!empty($json['error']) && $json['error']) {
                        throw new Exception("Error encountered during currency conversion rate lookup: " . $json['description']);
                    }
                    if (!empty($json['response'])) {
                        $rate = $json['response'];
                        self::$currency_rates[$from_currency][$to_currency] = $rate;
                        set_transient($transient_name, $rate, $recheck_rate);
                        delete_transient($transient_error);
                    } else {
                        throw new Exception(__('Unknown error encountered during currency conversion rate lookup.', 'nextgen-gallery-pro'));
                    }
                } catch (Exception $exception) {
                    $timezone = get_option('timezone_string');
                    if ($timezone) {
                        date_default_timezone_set($timezone);
                    }
                    $nextDate = date(get_option('date_format') . ' H:i', time() + $recheck_rate);
                    $message = $exception->getMessage();
                    $message .= "<br/>";
                    $message .= sprintf(__('The next attempt to contact exchangeratesapi.io will happen at %s', 'nextgen-gallery-pro'), $nextDate);
                    set_transient($transient_error, $message, $recheck_rate);
                }
            }
        }
        return self::$currency_rates[$from_currency][$to_currency];
    }
}
class C_NextGen_Pro_Ecommerce_Trigger extends C_NextGen_Pro_Lightbox_Trigger
{
    static function is_renderable($name, $displayed_gallery)
    {
        $retval = FALSE;
        if (self::is_pro_lightbox_enabled() && self::are_triggers_enabled($displayed_gallery)) {
            if (self::does_source_return_images($displayed_gallery)) {
                if (isset($displayed_gallery->display_settings['is_ecommerce_enabled'])) {
                    $retval = intval($displayed_gallery->display_settings['is_ecommerce_enabled']) ? TRUE : FALSE;
                }
                if (isset($displayed_gallery->display_settings['original_settings']) && isset($displayed_gallery->display_settings['original_settings']['is_ecommerce_enabled'])) {
                    $retval = intval($displayed_gallery->display_settings['original_settings']['is_ecommerce_enabled']) ? TRUE : FALSE;
                }
            }
        }
        return $retval;
    }
    function get_attributes()
    {
        $attrs = parent::get_attributes();
        $attrs['data-nplmodal-show-cart'] = 1;
        $attrs['data-nplmodal-gallery-id'] = $this->displayed_gallery->id();
        if ($this->view->get_id() == 'nextgen_gallery.image') {
            $image = $this->view->get_object();
            $attrs['data-image-id'] = $image->{$image->id_field};
        }
        return $attrs;
    }
    function get_css_class()
    {
        return 'fa ngg-trigger nextgen_pro_lightbox fa-shopping-cart';
    }
    function render()
    {
        $retval = '';
        $context = $this->view->get_context('object');
        // For Galleria & slideshow displays: show the gallery trigger if a single
        // image is available for sale
        if ($context && get_class($context) == 'C_MVC_View' && !empty($context->_params['images'])) {
            $mapper = C_Pricelist_Mapper::get_instance();
            foreach ($context->_params['images'] as $image) {
                if ($mapper->find_for_image($image)) {
                    $retval = parent::render();
                    break;
                }
            }
        } else {
            // Display the trigger if the image is for sale
            $mapper = C_Pricelist_Mapper::get_instance();
            if ($mapper->find_for_image($context)) {
                $retval = parent::render();
            }
        }
        return $retval;
    }
}
/**
 * @implements I_Order
 * @property C_DataMapper_Model $object
 */
class C_NextGen_Pro_Order extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Order_Mapper';
    /** @var null|C_NextGen_Pro_Cart */
    var $_cart = NULL;
    /**
     * @param array $properties
     * @param C_Order_Mapper|FALSE $mapper
     * @param mixed $context
     */
    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->implement('I_Order');
    }
    /**
     * @param array $properties
     * @param C_Order_Mapper|FALSE $mapper
     * @param mixed $context
     */
    function initialize($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        // If no mapper was specified, then get the mapper
        if (!$mapper) {
            $mapper = $this->get_registry()->get_utility($this->_mapper_interface);
        }
        // Construct the model
        parent::initialize($mapper, $properties);
        if (is_object($properties)) {
            $properties = get_object_vars($properties);
        }
        if (!isset($properties['cart'])) {
            $properties['cart'] = array();
        }
        $properties['cart']['saved'] = isset($properties['hash']);
        $this->_cart = new C_NextGen_Pro_Cart($properties['cart']);
    }
    /**
     * @param string $property
     * @return mixed
     */
    function get_property($property)
    {
        if (isset($this->{$property})) {
            return $this->{$property};
        } else {
            if (isset($this->get_cart()->{$property})) {
                return $this->{$this}->get_cart()->{$property};
            } else {
                if (isset($this->get_cart()->settings[$property])) {
                    return $this->get_cart()->settings[$property];
                }
            }
        }
    }
    /**
     * @return C_NextGen_Pro_Cart
     */
    function get_cart()
    {
        return $this->_cart;
    }
    /**
     * @return bool
     */
    function is_paid()
    {
        return in_array($this->status, array('verified', 'paid'));
    }
    /**
     * @return bool
     */
    public function validation()
    {
        // First validate attributes that all orders must posess
        $this->object->validates_presence_of('customer_name', array(), __('You must provide a name for the shipping information.', 'nextgen-gallery-pro'));
        $this->object->validates_presence_of('email', array(), __('You must provide an email address in case of any problems fulfilling your order.', 'nextgen-gallery-pro'));
        $this->object->validates_presence_of('payment_gateway', array(), __('An error has occurred, please try again later.', 'nextgen-gallery-pro'));
        $this->object->validates_presence_of('status', array(), __('An error has occurred, please try again later.', 'nextgen-gallery-pro'));
        $this->object->validates_numericality_of('subtotal', FALSE, FALSE, FALSE, __('An error has occurred, please try again later.', 'nextgen-gallery-pro'));
        $this->object->validates_numericality_of('tax', FALSE, FALSE, FALSE, __('An error has occurred, please try again later.', 'nextgen-gallery-pro'));
        $this->object->validates_numericality_of('total_amount', FALSE, FALSE, FALSE, __('An error has occurred, please try again later.', 'nextgen-gallery-pro'));
        // We only need to validate shipping information if there are items to be shipped
        if ($this->object->get_cart()->has_shippable_items()) {
            $this->object->validates_presence_of('shipping_city', array(), __('You must provide a city for the shipping information.', 'nextgen-gallery-pro'));
            $this->object->validates_presence_of('shipping_country', array(), __('You must provide a country for the shipping information.', 'nextgen-gallery-pro'));
            $this->object->validates_presence_of('shipping_name', array(), __('You must provide a name for the shipping information.', 'nextgen-gallery-pro'));
            $this->object->validates_presence_of('shipping_state', array(), __('You must provide a state for the shipping information.', 'nextgen-gallery-pro'));
            $this->object->validates_presence_of('shipping_street_address', array(), __('You must provide a street address for the shipping information.', 'nextgen-gallery-pro'));
            // TODO: the countries regions and postal code regex should really be moved into C_NextGen_Pro_Currencies::$countries
            // NextGen's validates_format_of() is currently broken; see: https://imagely.myjetbrains.com/youtrack/issue/NGG-678
            $countries = json_decode(file_get_contents(C_Fs::get_instance()->get_abspath('photocrati-nextgen_pro_ecommerce#country_list.json')));
            foreach ($countries as $country) {
                if ($this->object->shipping_country == $country[1]) {
                    if (empty($country[3])) {
                        break;
                    }
                    if (!preg_match('/' . $country[3] . '/i', $this->object->shipping_zip)) {
                        $this->object->add_error(__('You must provide a valid postal code for the shipping information.', 'nextgen-gallery-pro'), 'shipping_zip');
                    }
                    break;
                }
            }
            unset($countries);
            // release this from memory ASAP
        }
        return $this->object->is_valid();
    }
}
class C_NextGen_Pro_Order_Controller extends C_MVC_Controller
{
    static $_instance = NULL;
    /**
     * @return C_NextGen_Pro_Order_Controller
     */
    static function get_instance()
    {
        if (is_null(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->image = __('Image', 'nextgen-gallery-pro');
        $i18n->quantity = __('Quantity', 'nextgen-gallery-pro');
        $i18n->description = __('Description', 'nextgen-gallery-pro');
        $i18n->price = __('Price', 'nextgen-gallery-pro');
        $i18n->total = __('Total', 'nextgen-gallery-pro');
        return $i18n;
    }
    function enqueue_static_resources()
    {
        M_Gallery_Display::enqueue_fontawesome();
        wp_enqueue_style('ngg-pro-order-info', $this->get_static_url('photocrati-nextgen_pro_ecommerce#order_info.css'));
    }
    function render($cart)
    {
        $this->enqueue_static_resources();
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#order', array('currency' => $cart->get_currency(), 'images' => $cart->get_images(TRUE), 'i18n' => $this->get_i18n_strings()), TRUE);
    }
}
class C_NextGen_Pro_Order_Verification extends C_MVC_Controller
{
    static $_instance = NULL;
    /**
     * @return C_NextGen_Pro_Order_Verification
     */
    static function get_instance()
    {
        if (!isset(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->please_wait_msg = __("Please wait - we appreciate your patience.", 'nextgen-gallery-pro');
        $i18n->verifying_order_msg = __("We're verifying your order. This might take a few minutes.", 'nextgen-gallery-pro');
        $i18n->redirect_msg = __('This page will redirect automatically.', 'nextgen-gallery-pro');
        return $i18n;
    }
    function render($order_hash)
    {
        wp_enqueue_script('photocrati_ajax');
        return $this->render_partial('photocrati-nextgen_pro_ecommerce#order_verification', array('order_hash' => $order_hash, 'i18n' => $this->get_i18n_strings()), TRUE);
    }
}
class C_Non_HTTPS_Notice
{
    /** @var C_Non_HTTPS_Notice $_instance */
    static $_instance = NULL;
    /**
     * @return string
     */
    function get_css_class()
    {
        return 'notice notice-warning';
    }
    /**
     * @return bool
     */
    function is_renderable()
    {
        if (!C_NextGen_Admin_Page_Manager::is_requested()) {
            return FALSE;
        }
        // This notification should only display on ecommerce pages
        // TODO: consolidate this into a cleaner method than manual listing every page
        if ((empty($_GET['page']) || !in_array($_GET['page'], array('ngg_ecommerce_options', 'ngg-ecommerce-instructions-page'))) && (empty($_GET['post_type']) || !in_array($_GET['post_type'], array('ngg_pricelist', 'ngg_order', 'ngg_coupon', 'nextgen_proof')))) {
            return FALSE;
        }
        if (is_ssl()) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    /**
     * @return string
     */
    function render()
    {
        return __('NextGen has detected that your site does not have HTTPS enabled. While your site will function without HTTPS it is not recommended. HTTPS will improve your cart security, SEO, search results, and will remove the "insecure" marker that Google Chrome displays for sites without HTTPS.', 'nextgen-gallery-pro');
    }
    /**
     * @return C_Non_HTTPS_Notice
     */
    static function get_instance()
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }
}
/**
 * @mixin Mixin_Order_Mapper
 */
class C_Order_Mapper extends C_CustomPost_DataMapper_Driver
{
    public static $_instances = array();
    /**
     * @param bool|string $context
     * @return C_Order_Mapper
     */
    public static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
    function define($context = FALSE, $object_name = 'ngg_order')
    {
        // Add the object name to the context of the object as well
        // This allows us to adapt the driver itself, if required
        if (!is_array($context)) {
            $context = array($context);
        }
        array_push($context, $object_name);
        parent::define($object_name, $context);
        $this->add_mixin('Mixin_Order_Mapper');
        $this->set_model_factory_method($object_name);
        // Define columns/properties
        $this->define_column('ID', 'BIGINT', 0);
        $this->define_column('email', 'VARCHAR(255)');
        $this->define_column('customer_name', 'VARCHAR(255');
        $this->define_column('phone', 'VARCHAR(255)');
        $this->define_column('total_amount', 'DECIMAL', 0.0);
        $this->define_column('payment_gateway', 'VARCHAR(255)');
        $this->define_column('shipping_street_address', 'VARCHAR(255)');
        $this->define_column('shipping_address_line', 'VARCHAR(255)');
        $this->define_column('shipping_city', 'VARCHAR(255)');
        $this->define_column('shipping_state', 'VARCHAR(255)');
        $this->define_column('shipping_zip', 'VARCHAR(255)');
        $this->define_column('shipping_country', 'VARCHAR(255)');
        $this->define_column('shipping_phone', 'VARCHAR(255)');
        $this->define_column('cart', 'TEXT');
        $this->define_column('hash', 'VARCHAR(255)');
        $this->define_column('gateway_admin_note', 'VARCHAR(255)');
        $this->define_column('has_sent_email_receipt', 'BOOLEAN', FALSE);
        $this->define_column('has_sent_email_notification', 'BOOLEAN', FALSE);
        $this->define_column('aws_order_id', 'VARCHAR(255)');
        $this->add_serialized_column('cart');
    }
    function initialize($context = FALSE)
    {
        parent::initialize('ngg_order');
    }
    function find_by_hash($hash, $model = FALSE)
    {
        $results = $this->select()->where(array("hash = %s", $hash))->run_query(NULL, $model);
        return array_pop($results);
    }
}
class Mixin_Order_Mapper extends Mixin
{
    function _save_entity($entity)
    {
        if (is_string($this->cart)) {
            $this->cart = json_decode($this->cart);
        }
        if (is_object($this->cart)) {
            $this->cart->prepare_for_persistence();
        }
        // Create a unique hash
        if (!property_exists($entity, 'hash') or !$entity->hash) {
            $entity->hash = md5(time() . $entity->email . json_encode($this->cart));
        }
        $retval = $this->call_parent('_save_entity', $entity);
        do_action('ngg_order_saved', $retval, $entity);
        return $retval;
    }
    /**
     * Uses the title attribute as the post title
     * @param stdClass $entity
     * @return string
     */
    function get_post_title($entity)
    {
        return $entity->customer_name;
    }
    function set_defaults($entity)
    {
        // Pricelists should be published by default
        $entity->post_status = 'publish';
        // TODO: This should be part of the datamapper actually
        $entity->post_title = $this->get_post_title($entity);
    }
}
/**
 * @property C_Pricelist $object
 */
class C_Pricelist extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Pricelist_Mapper';
    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->implement('I_Pricelist');
    }
    /**
     * Initializes a display type with properties
     *
     * @param FALSE|C_Display_Type_Mapper $mapper
     * @param array|stdClass|C_Display_Type $properties
     * @param FALSE|string|array $context
     */
    function initialize($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        // If no mapper was specified, then get the mapper
        if (!$mapper) {
            $mapper = $this->get_registry()->get_utility($this->_mapper_interface);
        }
        // Construct the model
        parent::initialize($mapper, $properties);
    }
    function get_current_catalog_versions()
    {
        if (!is_array($this->ngg_catalog_versions)) {
            $this->ngg_catalog_versions = array();
        }
        return $this->ngg_catalog_versions;
    }
    function get_latest_catalog_versions()
    {
        if (!defined('NGG_FORCE_CHECK_CATALOG_VERSIONS') or !NGG_FORCE_CHECK_CATALOG_VERSIONS) {
            if ($versions = get_transient('ngg_catalog_versions')) {
                return $versions;
            }
        }
        $response = wp_remote_get("https://mu6ydhsp0h.execute-api.us-east-1.amazonaws.com/latest/catalogs");
        if (!$response instanceof WP_Error) {
            $versions = @json_decode($response['body'], TRUE);
            if (is_array($versions)) {
                set_transient('ngg_catalog_versions', $versions, NGG_PRO_WHCC_CATALOG_TTL);
                // 24 hours
                return $versions;
            }
        }
        return array();
    }
    function are_catalogs_out_of_date()
    {
        $latest = $this->object->get_latest_catalog_versions();
        $current = $this->object->get_current_catalog_versions();
        if (!$latest || !$current) {
            return TRUE;
        }
        foreach ($latest as $catalog => $version) {
            if (isset($current[$catalog]) && $current[$catalog] < $version) {
                return TRUE;
            }
        }
        return FALSE;
    }
    function get_latest_pricing($items = array())
    {
        if (!$items) {
            $items = $this->get_items();
        }
        if ($items) {
            $payload = json_encode($items);
            $response = wp_remote_post("https://mu6ydhsp0h.execute-api.us-east-1.amazonaws.com/latest/items", array('body' => $payload, 'headers' => array('Content-Type' => 'application/json'), 'timeout' => 30));
            if (!$response instanceof WP_Error && $response['response']['code'] === 200) {
                $updated_items = @json_decode($response['body']);
                $mapper = C_Pricelist_Item_Mapper::get_instance();
                foreach ($updated_items as $idx => $item) {
                    foreach (get_object_vars($item) as $key => $value) {
                        if ($key == 'source_data') {
                            $value = (array) $value;
                        }
                        if ($key == 'price') {
                            $value = $mapper->round($item, $value);
                        }
                        $items[$idx]->{$key} = $value;
                        $mapper->save($items[$idx]);
                    }
                }
                $this->ngg_catalog_versions = $this->object->get_latest_catalog_versions();
                $this->save();
                C_NextGEN_Printlab_Catalog_Data::get_whcc_data(TRUE);
            }
        }
        return $items;
    }
    /**
     * Gets all items from all sources for the pricelist, optionally filtered by an image
     *
     * @param null $image
     * @return array
     */
    function get_items($image = NULL, $model = FALSE)
    {
        $retval = array();
        $manager = C_Pricelist_Category_Manager::get_instance();
        foreach ($manager->get_ids() as $id) {
            $category_items = $this->get_category_items($id, $model);
            $retval = array_merge($retval, $this->object->_filter_pricelist_items($image, $category_items));
        }
        return $retval;
    }
    /**
     * Filter the list of pricelist items for the particular item
     */
    function _filter_pricelist_items($image, $items)
    {
        $retval = array();
        $source_manager = C_Pricelist_Source_Manager::get_instance();
        foreach ($items as $item_id => $item) {
            // If it is a lab item ensure that we have a license and that the image meets the minimum size requirements
            if ($source_manager->get($item->source, 'lab_fulfilled')) {
                if (M_NextGen_Pro_Ecommerce::is_valid_license() && (!$image || M_NextGen_Pro_Ecommerce::does_item_meet_minimum_requirements($item, $image))) {
                    $retval[$item_id] = $item;
                }
            } else {
                if (in_array($item->source, array(NGG_PRO_MANUAL_PRICELIST_SOURCE, NGG_PRO_DIGITAL_DOWNLOADS_SOURCE))) {
                    $retval[$item_id] = $item;
                }
            }
        }
        return apply_filters('ngg_pro_pricelist_items', $retval, $image);
    }
    function delete_items($ids = array())
    {
        $this->get_mapper()->destroy_items($this->id(), $ids);
    }
    function destroy_items($ids = array())
    {
        return $this->delete_items($ids);
    }
    function get_ngg_manual_pricelist_items($image)
    {
        return $this->get_manual_items($image);
    }
    function get_ngg_digital_downloads_items($image)
    {
        return $this->get_digital_downloads($image);
    }
    /**
     * Gets all manual items of the pricelist
     * @param null $image
     * @return mixed
     */
    function get_manual_items($image = NULL)
    {
        $mapper = C_Pricelist_Item_Mapper::get_instance();
        $conditions = array(array("pricelist_id = %d", $this->object->id()), array("source IN %s", array(NGG_PRO_MANUAL_PRICELIST_SOURCE)));
        // Omit placeholder items that were incorrectly saved
        $retval = array();
        $items = $mapper->select()->where($conditions)->order_by('ID', 'ASC')->run_query();
        foreach ($items as $item) {
            if (empty($item->title) && empty($item->price)) {
                continue;
            }
            $retval[] = $item;
        }
        return $retval;
    }
    function get_category_items($category, $model = FALSE)
    {
        $mapper = C_Pricelist_Item_Mapper::get_instance();
        $items = $mapper->get_category_items($this->object->id(), $category, $model);
        return $items;
    }
    /**
     * Gets all digital downloads for the pricelist
     * @param null $image_id
     * @return mixed
     */
    function get_digital_downloads($image_id = NULL)
    {
        // Find digital download items
        $mapper = C_Pricelist_Item_Mapper::get_instance();
        $items = $mapper->get_category_items($this->object->id(), NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS);
        // Filter by image resolutions
        if ($image_id) {
            $modified = $this->add_digital_downloads_resolutions($image_id, $items);
            if (!empty($modified)) {
                $items = $modified;
            }
        }
        return $items;
    }
    function add_digital_downloads_resolutions($image_id, $items)
    {
        $retval = array();
        $image = is_object($image_id) ? $image_id : C_Image_Mapper::get_instance()->find($image_id);
        if ($image) {
            $storage = C_Gallery_Storage::get_instance();
            foreach ($items as $item) {
                $source_width = $image->meta_data['width'];
                $source_height = $image->meta_data['height'];
                // the downloads themselves come from the backup as source so if possible only filter images
                // whose backup file doesn't have sufficient dimensions
                $backup_abspath = $storage->get_backup_abspath($image);
                if (@file_exists($backup_abspath)) {
                    $dimensions = @getimagesize($backup_abspath);
                    $source_width = $dimensions[0];
                    $source_height = $dimensions[1];
                }
                if (isset($item->resolution) && $item->resolution >= 0 && ($source_height >= $item->resolution or $source_width >= $item->resolution)) {
                    $retval[] = $item;
                }
            }
        }
        return $retval;
    }
    function destroy()
    {
        if (parent::destroy()) {
            return $this->destroy_items();
        }
        return FALSE;
    }
    function validate()
    {
        $this->validates_presence_of('title');
    }
    function get_default_markup()
    {
        $this->get_mapper()->get_default_markup_for_pricelist($this->ID);
    }
}
class C_Pricelist_Category_Manager
{
    public static $_instance = NULL;
    protected $_registered = array();
    /**
     * @return C_Pricelist_Category_Manager
     */
    static function get_instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new C_Pricelist_Category_Manager();
        }
        return self::$_instance;
    }
    /**
     * Registers a pricelist category with the system
     *
     * @param $id
     * @param array $properties
     */
    function register($id, $properties = array())
    {
        $this->_registered[$id] = $properties;
    }
    /**
     * Deregisters a pricelist category with the system
     *
     * @param $id
     */
    function deregister($id)
    {
        unset($this->_registered[$id]);
    }
    /**
     * Updates a category properties
     *
     * @param $id
     * @param array $properties
     */
    function update($id, $properties = array())
    {
        $retval = FALSE;
        if (isset($this->_registered[$id])) {
            foreach ($properties as $k => $v) {
                $this->_registered[$id][$k] = $v;
            }
            $retval = TRUE;
        }
        return $retval;
    }
    /**
     * Gets all or a specific property of a pricelist category
     *
     * @param $id
     * @param bool $property
     * @return null
     */
    function get($id, $property = FALSE)
    {
        $retval = NULL;
        if (isset($this->_registered[$id])) {
            if ($property && isset($this->_registered[$id][$property])) {
                $retval = $this->_registered[$id][$property];
            } else {
                if (!$property) {
                    $retval = $this->_registered[$id];
                }
            }
        }
        return $retval;
    }
    /**
     * Gets ids of all registered categories
     *
     * @return array
     */
    function get_ids()
    {
        return array_keys($this->_registered);
    }
    /**
     * Gets all categories registered to a source
     *
     * @param string $source Source ID
     * @return array Categories
     */
    function get_by_source($source_id)
    {
        $retval = array();
        foreach ($this->_registered as $category_id => $category) {
            if (in_array($source_id, $category['source'])) {
                $retval[$category_id] = $category;
            }
        }
        return $retval;
    }
}
/** @property C_NextGen_Admin_Page_Controller $object */
class C_Pricelist_Category_Page extends C_NextGen_Admin_Page_Controller
{
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            self::$_instances[$context] = new C_Pricelist_Category_Page();
        }
        return self::$_instances[$context];
    }
    function define($context = FALSE)
    {
        parent::define(NGG_PRO_PRICELIST_CATEGORY_PAGE);
    }
    function get_required_permission()
    {
        return 'NextGEN Change options';
    }
    function get_page_heading()
    {
        return __('Manage Pricelist', 'nextgen-gallery-pro');
    }
    function enqueue_backend_resources()
    {
        parent::enqueue_backend_resources();
        $router = C_Router::get_instance();
        if (!wp_script_is('sprintf')) {
            wp_register_script('sprintf', $router->get_static_url('photocrati-nextgen_pro_ecommerce#sprintf.js'));
        }
        wp_enqueue_script('sprintf');
        wp_enqueue_script('jquery.number');
        // Enqueue fontawesome
        if (method_exists('M_Gallery_Display', 'enqueue_fontawesome')) {
            M_Gallery_Display::enqueue_fontawesome();
        } else {
            C_Display_Type_Controller::get_instance()->enqueue_displayed_gallery_trigger_buttons_resources();
        }
        wp_enqueue_style('fontawesome');
        wp_enqueue_script('nggpro_manage_pricelist_page_js', $router->get_static_url('photocrati-nextgen_pro_ecommerce#manage_pricelist_page.js'), array('jquery', 'thickbox', 'jquery-ui-tooltip', 'underscore', 'jquery-ui-sortable'), NGG_PRO_ECOMMERCE_MODULE_VERSION);
        $sources = array();
        $source_manager = C_Pricelist_Source_Manager::get_instance();
        foreach ($source_manager->get_ids() as $source_id) {
            $sources[$source_id] = $source_manager->get($source_id, 'lab_fulfilled');
        }
        wp_localize_script('nggpro_manage_pricelist_page_js', 'ngg_pro_pricelist_sources', $sources);
        wp_localize_script('nggpro_manage_pricelist_page_js', 'manage_pricelist_page_i18n', get_object_vars($this->get_i18n_strings()));
        wp_enqueue_style('nggpro_manage_pricelist_page_css', $router->get_static_url('photocrati-nextgen_pro_ecommerce#manage_pricelist_page.css'));
        wp_enqueue_style('thickbox');
    }
    // Without this index_action() any direct invocation of this method results in __call() execing it over and over
    // and over and over until the PHP VM gives up and moves on. Do not remove this.
    function index_action()
    {
        return parent::index_action();
    }
    /**
     * Adds additional parameters to the manage_pricelist.php template
     *
     * @return array
     */
    function get_index_params()
    {
        $manager = C_Pricelist_Source_Manager::get_instance();
        $sources = array();
        $router = C_Router::get_instance();
        foreach ($manager->get_ids() as $source) {
            $info = $manager->get($source);
            $source_obj = new $info['classname']();
            $info['add_new_template'] = $source_obj->add_new_item_template();
            $sources[$source] = $info;
        }
        return array('pricelist_sources' => $sources, 'settings' => $this->get_model()->settings, 'wrap_css_class' => version_compare('2.2.99', NGG_PLUGIN_VERSION) > 0 ? 'not-redesign' : 'redesign', 'logo' => $router->get_static_url('photocrati-nextgen_pro_ecommerce#imagely_icon.png'));
    }
    function index_template()
    {
        return 'photocrati-nextgen_pro_ecommerce#manage_pricelist';
    }
    function get_model()
    {
        if (!isset($this->pricelist)) {
            $pricelist_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $mapper = C_Pricelist_Mapper::get_instance();
            $this->pricelist = $mapper->find($pricelist_id, TRUE);
            if ($this->pricelist) {
                if ($this->pricelist->are_catalogs_out_of_date()) {
                    $this->pricelist->get_latest_pricing($this->pricelist->get_items());
                }
            } else {
                $this->pricelist = $mapper->create();
            }
        }
        return $this->pricelist;
    }
    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->saved = __('Saved pricelist successfully', 'nextgen-gallery-pro');
        $i18n->deleted = __('Deleted pricelist', 'nextgen-gallery-pro');
        $i18n->gallery_wrap_notice = __('You are adding a Canvas to a pricelist. Please be aware that when printing a Canvas, the edges of the photo (between 1 to 3 inches) will wrap around the side of the product.', 'nextgen-gallery-pro');
        return $i18n;
    }
    /**
     * Gets the action to be executed
     * @return string
     */
    function _get_action()
    {
        $action = $this->object->param('action');
        if (!$action && isset($_REQUEST['action_proxy'])) {
            $action = $_REQUEST['action_proxy'];
        }
        $retval = preg_quote($action, '/');
        $retval = strtolower(preg_replace("/[^\\w]/", '_', $retval));
        $retval = preg_replace("/_{2,}/", "_", $retval) . '_action';
        return $retval;
    }
    function get_success_message()
    {
        $retval = $this->param('message');
        if (!$retval) {
            if ($this->_get_action() == 'delete_action') {
                $retval = 'deleted';
            } else {
                $retval = 'saved';
            }
        }
        return $this->get_i18n_strings()->{$retval};
    }
    function delete_action()
    {
        if ($this->get_model()->destroy()) {
            return wp_redirect(admin_url('edit.php?post_type=ngg_pricelist&ids=' . $this->get_model()->id()));
        } else {
            return FALSE;
        }
    }
}
class C_Pricelist_Item extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Pricelist_Item_Mapper';
    function define($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->implement('I_Pricelist_Item');
    }
    /**
     * Initializes a display type with properties
     *
     * @param false|C_Display_Type_Mapper $mapper
     * @param array|object|C_Display_Type $properties
     * @param false|string|array $context
     */
    function initialize($properties = array(), $mapper = FALSE, $context = FALSE)
    {
        // If no mapper was specified, then get the mapper
        if (!$mapper) {
            $mapper = $this->get_registry()->get_utility($this->_mapper_interface);
        }
        // Construct the model
        parent::initialize($mapper, $properties);
    }
    function validation()
    {
        $this->validates_presence_of('title');
        $this->validates_presence_of('price');
        $this->validates_presence_of('source');
        $this->validates_presence_of('category');
        $this->validates_presence_of('pricelist_id');
        $this->validates_presence_of('sortorder');
        $this->validates_numericality_of('sortorder');
        $this->validates_numericality_of('price', 0.0, '>=');
    }
    function get_price($apply_markup = TRUE, $apply_conversion = FALSE, $force_conversion = FALSE, $round = FALSE)
    {
        return $this->get_mapper()->get_price($this->get_entity(), $apply_markup, $apply_conversion, $force_conversion, $round);
    }
    function get_formatted_price($apply_markup = TRUE, $apply_conversion = FALSE, $force_conversion = FALSE, $round = FALSE)
    {
        return M_NextGen_Pro_Ecommerce::get_formatted_price($this->get_price($apply_markup, $apply_conversion, $force_conversion, $round));
    }
    function is_lab_fulfilled()
    {
        $source_manager = C_Pricelist_Source_Manager::get_instance();
        return $source_manager->get($this->source, 'lab_fulfilled');
    }
}
/**
 * @mixin Mixin_Pricelist_Item_Mapper
 */
class C_Pricelist_Item_Mapper extends C_CustomPost_DataMapper_Driver
{
    public static $_instances = array();
    /**
     * @param mixed $context
     * @return C_Pricelist_Item_Mapper
     */
    public static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            self::$_instances[$context] = new C_Pricelist_Item_Mapper();
        }
        return self::$_instances[$context];
    }
    function define($context = FALSE, $object_name = 'ngg_pricelist_item')
    {
        // Add the object name to the context of the object as well
        // This allows us to adapt the driver itself, if required
        if (!is_array($context)) {
            $context = array($context);
        }
        array_push($context, $object_name);
        parent::define($object_name, $context);
        $this->add_mixin('Mixin_Pricelist_Item_Mapper');
        $this->set_model_factory_method($object_name);
        // Define columns
        $this->define_column('ID', 'BIGINT', 0);
        $this->define_column('pricelist_id', 'BIGINT', 0);
        $this->define_column('price', 'DECIMAL', 0.0);
        $this->define_column('source', 'VARCHAR(255)');
        $this->define_column('source_data', 'TEXT');
        $this->define_column('category', 'VARCHAR(255)');
        $this->define_column('resolution', 'DECIMAL');
        $this->define_column('sortorder', 'BIGINT', 0);
        $this->define_column('is_shippable', 'BOOLEAN', FALSE);
        $this->add_serialized_column('source_data');
    }
    function initialize($context = FALSE)
    {
        parent::initialize('ngg_pricelist_item');
    }
}
/**
 * @property C_Pricelist_Item_Mapper $object
 */
class Mixin_Pricelist_Item_Mapper extends Mixin
{
    /**
     * Uses the title attribute as the post title
     *
     * @param stdClass $entity
     * @return string
     */
    function get_post_title($entity)
    {
        return $entity->title;
    }
    function get_category_items($pricelist_id, $category, $model = FALSE)
    {
        // The Nextgen datamapper can't retrieve posts based on a lack of a WP meta attribute
        // and when only some entries have that attribute it will always return those; for this
        // one instance we create a new WP_Query
        $source_part = array();
        $source_part['key'] = 'source';
        $source_part['value'] = NGG_PRO_DIGITAL_DOWNLOADS_SOURCE;
        $source_part['compare'] = $category == NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS ? '=' : '!=';
        $pricelist_id_part = array('key' => 'pricelist_id', 'value' => $pricelist_id, 'compare' => '=');
        $category_part = array('key' => 'category', 'value' => $category, 'compare' => '=');
        // A plain query: check for items in this pricelist with an appropriate source
        $meta_query = array($pricelist_id_part, $source_part, $category_part);
        // "Prints" are the fallback category; any pricelist item without a digital-downloads source and without
        // a category is treated as a Print
        if (in_array($category, array(NGG_PRO_ECOMMERCE_CATEGORY_PRINTS, NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS))) {
            $meta_query = array($pricelist_id_part, $source_part, array('relation' => 'OR', $category_part, array('key' => 'category', 'compare' => 'NOT EXISTS')));
        }
        $args = array('orderby' => 'ID', 'order' => 'ASC', 'post_type' => 'ngg_pricelist_item', 'post_status' => 'draft', 'posts_per_page' => -1, 'meta_query' => $meta_query);
        $query = new WP_Query($args);
        $results = $query->get_posts();
        $mapper = C_Pricelist_Item_Mapper::get_instance();
        foreach ($results as $ndx => $item) {
            $results[$ndx] = $mapper->find($item, $model);
        }
        foreach ($results as $ndx => $item) {
            // Again we must correct the missing category attribute for pricelist items created before categories
            if (empty($item->category) && !empty($item->source)) {
                if ($item->source == NGG_PRO_DIGITAL_DOWNLOADS_SOURCE) {
                    $item->category = NGG_PRO_ECOMMERCE_CATEGORY_DIGITAL_DOWNLOADS;
                } else {
                    $item->category = NGG_PRO_ECOMMERCE_CATEGORY_PRINTS;
                }
                $results[$ndx] = $item;
            }
        }
        // WP_Query doesn't well handle ordering entries by the sortorder meta value by their numeric value. It also
        // doesn't well handle mixed cases of items having a sortorder and some items having no sortorder attribute.
        // So we sort them out manually here: by sortorder first, then ID
        usort($results, array($this, 'compare_sort_order'));
        unset($query);
        return $results;
    }
    /**
     * Used in above comparison of pricelist items to build a sortorder
     *
     * @param $item_one
     * @param $item_two
     * @return int
     */
    function compare_sort_order($item_one, $item_two)
    {
        if ($item_one->ID == $item_two->ID) {
            return 0;
        }
        if (empty($item_one->sortorder) && empty($item_two->sortorder)) {
            return $item_one->ID < $item_two->ID ? -1 : 1;
        }
        return $item_one->sortorder < $item_two->sortorder ? -1 : 1;
    }
    function round($item, $amount)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = $settings->ecommerce_currency;
        $currency_info = C_NextGen_Pro_Currencies::$currencies[$currency];
        $exponent = intval($currency_info['exponent']);
        $pricelist_mapper = C_Pricelist_Mapper::get_instance();
        $markup = isset($item->pricelist_id) ? $pricelist_mapper->get_default_markup_for_pricelist($item->pricelist_id) : array('percentage' => NGG_PRO_ECOMMERCE_DEFAULT_MARKUP, 'rounding' => "none");
        $exponent_2 = 4;
        if ($markup['rounding'] != 'none') {
            $amount = round($amount, $exponent_2, PHP_ROUND_HALF_UP);
            if ($markup['rounding'] == 'cent') {
                $prev_amount = $amount;
                $amount = floor($amount);
                if ($prev_amount - $amount > 0) {
                    $amount = bcadd($amount, 0.99, $exponent_2);
                } else {
                    $amount = bcsub($amount, 0.01, $exponent_2);
                }
            } else {
                if ($markup['rounding'] == 'zero') {
                    $amount = ceil($amount);
                }
            }
        }
        return bcadd($amount, 0.0, $exponent);
    }
    /**
     * @param C_Pricelist_Item|stdClass $item
     * @param bool $apply_markup
     * @param bool $apply_conversion_rate
     * @param bool $force_conversion
     * @return float|int
     */
    function get_price($item, $apply_markup = TRUE, $apply_conversion_rate = FALSE, $force_conversion = FALSE, $round = FALSE)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = $settings->ecommerce_currency;
        $currency_info = C_NextGen_Pro_Currencies::$currencies[$currency];
        $pricelist_mapper = C_Pricelist_Mapper::get_instance();
        $exponent = intval($currency_info['exponent']);
        $exponent_2 = 4;
        $markup = isset($item->pricelist_id) ? $pricelist_mapper->get_default_markup_for_pricelist($item->pricelist_id) : NGG_PRO_ECOMMERCE_DEFAULT_MARKUP;
        $price = 0.0;
        // Calculate cost
        if ($item && isset($item->cost)) {
            $price = bcadd($item->cost, 0.0, $exponent_2);
            // Apply conversion rate
            if ($apply_conversion_rate) {
                $lab_manager = C_NextGEN_Printlab_Manager::get_instance();
                // TODO: This shouldn't be hard coded to 'whcc'.
                // The catalog should be associated with the pricelist item
                $catalog = $lab_manager->get_catalog('whcc');
                if ($currency != $catalog->currency || $force_conversion) {
                    $rate = C_NextGen_Pro_Currencies::get_conversion_rate($catalog->currency, $currency);
                    if ($rate != 0) {
                        $price = bcmul($price, bcmul($rate, 1.01, $exponent_2), $exponent_2);
                    }
                }
            }
            // Apply markup
            if ($apply_markup) {
                $price = bcmul(bcadd(1, bcdiv($markup['percentage'], 100, $exponent_2), $exponent_2), $price, $exponent_2);
                $price = $this->round($item, $price);
            }
        }
        $price = bcadd($price, 0.0, $exponent);
        if (floatval($price) < 0 && $exponent == 0) {
            $price = round($item->cost ? $item->cost : $price, 0, PHP_ROUND_HALF_UP);
        }
        return $round ? $this->round($item, $price) : $price;
    }
}
/**
 * @mixin Mixin_Pricelist_Mapper
 */
class C_Pricelist_Mapper extends C_CustomPost_DataMapper_Driver
{
    public static $_instances = array();
    /**
     * @param mixed $context
     * @return C_Pricelist_Mapper
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
        $object_name = 'ngg_pricelist';
        // Add the object name to the context of the object as well
        // This allows us to adapt the driver itself, if required
        if (!is_array($context)) {
            $context = array($context);
        }
        array_push($context, $object_name);
        parent::define($object_name, $context);
        $this->add_mixin('Mixin_Pricelist_Mapper');
        $this->set_model_factory_method($object_name);
        // Define columns
        $this->define_column('ID', 'BIGINT');
        $this->define_column('post_author', 'BIGINT');
        $this->define_column('title', 'VARCHAR(255)');
        $this->define_column('settings', 'TEXT');
        $this->define_column('digital_download_settings', 'TEXT');
        $this->define_column('ngg_catalog_versions', 'TEXT');
        // Mark the columns which should be unserialized
        $this->add_serialized_column('settings');
        $this->add_serialized_column('digital_download_settings');
        $this->add_serialized_column('ngg_catalog_versions');
    }
    function initialize($context = FALSE)
    {
        parent::initialize('ngg_pricelist');
    }
}
/**
 * @property C_CustomPost_DataMapper_Driver $object
 */
class Mixin_Pricelist_Mapper extends Mixin
{
    function destroy($entity)
    {
        if ($this->call_parent('destroy', $entity)) {
            return $this->destroy_items($entity);
        } else {
            return FALSE;
        }
    }
    function destroy_items($pricelist_id, $ids = array())
    {
        global $wpdb;
        // If no ids have been provided, then delete all items for the given pricelist
        if (!$ids) {
            // Ensure we have the pricelist id
            if (!is_int($pricelist_id)) {
                $pricelist_id = $pricelist_id->ID;
            }
            // Find all item ids
            $item_mapper = C_Pricelist_Item_Mapper::get_instance();
            $ids = array();
            $results = $item_mapper->select("ID, post_parent")->where(array('pricelist_id = %d', $pricelist_id))->run_query();
            foreach ($results as $row) {
                $ids[] = $row->ID;
                if ($row->post_parent) {
                    $ids[] = $row->post_parent;
                }
            }
        }
        // Get unique ids
        $ids = array_unique($ids);
        // Delete all posts and post meta for the item ids
        $sql = array();
        $sql[] = "DELETE FROM {$wpdb->posts} WHERE ID IN (" . implode(',', $ids) . ')';
        $sql[] = "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . implode(',', $ids) . ')';
        foreach ($sql as $query) {
            $wpdb->query($query);
        }
        return TRUE;
    }
    /**
     * Uses the title attribute as the post title
     * @param stdClass $entity
     * @return string
     */
    function get_post_title($entity)
    {
        return $entity->title;
    }
    /**
     * @param $id
     * @param bool $model
     * @return C_Pricelist|stdClass|null
     */
    function find_for_gallery($id, $model = FALSE)
    {
        $retval = NULL;
        if (is_object($id)) {
            $id = $id->{$id->id_field};
        }
        $mapper = C_Gallery_Mapper::get_instance();
        if ($gallery = $mapper->find($id)) {
            if (isset($gallery->pricelist_id)) {
                $retval = $this->object->find($gallery->pricelist_id, $model);
            }
        }
        return $retval;
    }
    /**
     * @param $id
     * @param bool $model
     * @return C_Pricelist|stdClass|null
     */
    function find_for_image($id, $model = FALSE)
    {
        $retval = NULL;
        $image = NULL;
        // Find the image
        if (is_object($id)) {
            $image = $id;
        } else {
            $mapper = C_Image_Mapper::get_instance();
            $image = $mapper->find($id);
        }
        // If we've found the image, then find it's pricelist
        if ($image) {
            if ($image->pricelist_id) {
                $retval = $this->object->find($image->pricelist_id, $model);
            } else {
                $retval = $this->find_for_gallery($image->galleryid, $model);
            }
        }
        return $retval;
    }
    function get_default_markup_for_pricelist($pricelist_id)
    {
        $retval = array('percentage' => NGG_PRO_ECOMMERCE_DEFAULT_MARKUP, 'rounding' => 'none');
        if ($pricelist_id) {
            $mapper = C_Pricelist_Mapper::get_instance();
            $pricelist = $mapper->find($pricelist_id);
            if ($pricelist) {
                // TODO: bulk_markup_amount shouldn't be part of manual settings
                $retval['percentage'] = floatval($pricelist->settings['bulk_markup_amount']);
                $retval['rounding'] = $pricelist->settings['bulk_markup_rounding'];
            }
        }
        return $retval;
    }
    function set_defaults($entity)
    {
        // Set defaults for manual pricelist settings
        if (!isset($entity->settings)) {
            $entity->settings = array();
        }
        if (!array_key_exists('bulk_markup_amount', $entity->settings)) {
            $entity->settings['bulk_markup_amount'] = NGG_PRO_ECOMMERCE_DEFAULT_MARKUP;
        }
        if (!array_key_exists('bulk_markup_rounding', $entity->settings)) {
            $entity->settings['bulk_markup_rounding'] = 'zero';
        }
        // Set defaults for digital download settings
        if (!isset($entity->digital_download_settings)) {
            $entity->digital_download_settings = array();
        }
        if (!isset($entity->digital_download_settings['show_licensing_link'])) {
            $entity->digital_download_settings['show_licensing_link'] = 0;
        }
        if (!isset($entity->digital_download_settings['licensing_page_id'])) {
            $entity->digital_download_settings['licensing_page_id'] = 0;
        }
        if (!isset($entity->digital_download_settings['skip_checkout'])) {
            $entity->digital_download_settings['skip_checkout'] = 0;
        }
        // Pricelists should be published by default
        $entity->post_status = 'publish';
        // TODO: This should be part of the datamapper actually
        $entity->post_title = $this->get_post_title($entity);
    }
}
class C_Pricelist_Shipping_Method_Manager
{
    /**
     * @var $_instance C_Pricelist_Shipping_Method_Manager
     */
    static $_instance = NULL;
    private $_registered = array();
    /**
     * @return C_Pricelist_Shipping_Method_Manager
     */
    static function get_instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new C_Pricelist_Shipping_Method_Manager();
        }
        return self::$_instance;
    }
    /**
     * Registers a shipping method
     * @param $id
     * @param array $properties
     */
    function register($id, $properties = array())
    {
        $this->_registered[$id] = $properties;
        return $this;
    }
    /**
     * Deregisters a shipping method with the system
     * @param $id
     */
    function deregister($id)
    {
        unset($this->_registered[$id]);
        return $this;
    }
    /**
     * Updates a shipping method properties
     * @param $id
     * @param array $properties
     */
    function update($id, $properties = array())
    {
        $retval = FALSE;
        if (isset($this->_registered[$id])) {
            foreach ($properties as $k => $v) {
                $this->_registered[$id][$k] = $v;
            }
            $retval = TRUE;
        }
        return $retval;
    }
    /**
     * Gets all or a specific property of a shipping method
     * @param $id
     * @param bool $property
     * @return null
     */
    function get($id, $property = FALSE)
    {
        $retval = NULL;
        if (isset($this->_registered[$id])) {
            if ($property && isset($this->_registered[$id][$property])) {
                $retval = $this->_registered[$id][$property];
            } else {
                if (!$property) {
                    $retval = $this->_registered[$id];
                }
            }
        }
        return $retval;
    }
    /**
     * Determines whether the shipping method is universal
     *
     * What does that mean?
     *
     * Your cart can have multiple shipments, and each shipment can offer multiple shipping methods. E.g.
     *
     * shipment A
     * - method A
     * - method B
     * - method C
     *
     * shipment B
     * -> method A
     * -> method C
     *
     * When we're presenting a list of shipping methods available for the end-user to select, we can only show
     * the common shipping methods. See C_NextGen_Pro_Cart::get_shipping_methods().
     *
     * However, some shipping methods can be universal. The manual shipping method, whether it be domestic or
     * international, can be combined with any other shipping method.
     */
    function is_universal_method($id)
    {
        return $this->get($id, 'universal') && TRUE;
    }
    /**
     * Gets ids of all registered shipping methods
     * @return array
     */
    function get_ids()
    {
        return array_keys($this->_registered);
    }
}
/**
 * Provides a parent interface for pricelist sources to extend if they wish to be listed in the Manage Pricelist
 * "Add Product" dialog
 *
 * Class C_Pricelist_Source
 */
class C_Pricelist_Source extends C_MVC_Controller
{
    public static function get_info()
    {
        // These fields are not i18n'ed but they're also never meant to be displayed to any user
        return array('title' => 'A Pricelist Source', 'settings_field' => '', 'description' => 'A dummy pricelist source to be extended', 'classname' => get_class(), 'lab_fulfilled' => FALSE);
    }
    public function has_shipping_address($cart_settings)
    {
        $retval = TRUE;
        if (!isset($cart_settings['shipping_address'])) {
            $retval = FALSE;
        } else {
            foreach ($cart_settings['shipping_address'] as $key => $val) {
                if (!$val && $key != 'address_line' && $key != 'zip' && $key != 'phone') {
                    $retval = FALSE;
                }
            }
        }
        return $retval;
    }
    public function get_shippable_countries()
    {
        $settings = C_NextGen_Settings::get_instance();
        $codes = array();
        if ($settings->ecommerce_intl_shipping && $settings->ecommerce_intl_shipping != 'disabled') {
            foreach (C_NextGen_Pro_Currencies::$countries as $country) {
                $codes[] = $country['code'];
            }
        } else {
            $codes[] = $settings->ecommerce_home_country;
        }
        return $codes;
    }
    public function get_shipments($items, $cart_setting, $cart)
    {
    }
    public function get_selected_shipping_method($cart_settings, $cart)
    {
        return $cart->get_selected_shipping_method($cart_settings);
    }
    public function get_subtotal($items)
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        $retval = 0.0;
        foreach ($items as $item) {
            if ($item->source == $this->id) {
                $retval = round(bcadd($retval, bcmul($item->cost, $item->quantity, $currency['exponent']), $currency['exponent']), $currency['exponent'], PHP_ROUND_HALF_UP);
            }
        }
        return $retval;
    }
    protected function _add_shipping_method_to_array($retval, $source, $item, $currency, $key, $alias, $price_in_percent, $surcharge = 0.0, $other_surcharge = 0.0, $data = array())
    {
        if (!isset($item->cost)) {
            $item->cost = $item->price;
        }
        if (!isset($retval[$key])) {
            $retval[$key] = array('alias' => $alias, 'data' => $data, 'price' => round(bcmul(bcmul($item->cost, $price_in_percent, $currency['exponent'] * 2), $item->quantity, $currency['exponent'] * 2), $currency['exponent'], PHP_ROUND_HALF_UP), 'surcharge' => $surcharge, 'other_surcharge' => $other_surcharge, 'source' => $source);
        } else {
            $retval[$key]['price'] = round(bcadd(bcmul(bcmul($item->cost, $price_in_percent, $currency['exponent'] * 2), $item->quantity, $currency['exponent'] * 2), $retval[$key]['price'], $currency['exponent'] * 2), $currency['exponent'], PHP_ROUND_HALF_UP);
            if ($surcharge) {
                $retval[$key]['surcharge'] = $surcharge;
            }
            if ($other_surcharge) {
                $retval[$key]['other_surcharge'] = $other_surcharge;
            }
        }
        return $retval;
    }
    public function add_new_item_template()
    {
        return '';
    }
}
class C_Pricelist_Source_Download extends C_Pricelist_Source
{
    public static function get_info()
    {
        return array_merge(parent::get_info(), array('title' => __('Digital Downloads', 'nextgen-gallery-pro'), 'settings_field' => 'digital_download_settings', 'description' => __('Image files are made available to users for download from your site', 'nextgen-gallery-pro'), 'classname' => get_class(), 'lab_fulfilled' => FALSE));
    }
    public function get_i18n()
    {
        return array('new_product_name' => __('New product name', 'nextgen-gallery-pro'), 'new_product_name_hint' => __('Low, Medium, or High Resolution', 'nextgen-gallery-pro'), 'new_product_price' => __('Price', 'nextgen-gallery-pro'), 'new_product_resolution' => __('Longest Image Dimension', 'nextgen-gallery-pro'), 'new_product_resolution_placeholder' => __('Enter 0 for maximum', 'nextgen-gallery-pro'));
    }
    public function add_new_item_template()
    {
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#add_new_download_item', array('i18n' => $this->get_i18n()), TRUE);
    }
}
class C_Pricelist_Source_Manager
{
    static $_instance = NULL;
    var $_registered = array();
    /**
     * @return C_Pricelist_Source_Manager
     */
    static function get_instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new C_Pricelist_Source_Manager();
        }
        return self::$_instance;
    }
    /**
     * Registers a pricelist source with the system
     * @param $id
     * @param array $properties
     */
    function register($id, $properties = array())
    {
        $this->_registered[$id] = $properties;
    }
    /**
     * Deregisters a pricelist source with the system
     * @param $id
     */
    function deregister($id)
    {
        unset($this->_registered[$id]);
    }
    /**
     * Updates a source properties
     * @param $id
     * @param array $properties
     */
    function update($id, $properties = array())
    {
        $retval = FALSE;
        if (isset($this->_registered[$id])) {
            foreach ($properties as $k => $v) {
                $this->_registered[$id][$k] = $v;
            }
            $retval = TRUE;
        }
        return $retval;
    }
    /**
     * Gets all or a specific property of a pricelist source
     * @param $id
     * @param bool $property
     * @return null
     */
    function get($id, $property = FALSE)
    {
        $retval = NULL;
        if (isset($this->_registered[$id])) {
            if ($property && isset($this->_registered[$id][$property])) {
                $retval = $this->_registered[$id][$property];
            } else {
                if (!$property) {
                    $retval = $this->_registered[$id];
                }
            }
        }
        return $retval;
    }
    /*
     * Returns the source class for the pricelist source id
     * @return C_Pricelist_Source
     */
    function get_handler($id)
    {
        $klass = $this->get($id, 'classname');
        $handler = new $klass();
        $handler->id = $id;
        return $handler;
    }
    /**
     * Gets ids of all registered sources
     * @return array
     */
    function get_ids()
    {
        return array_keys($this->_registered);
    }
}
class C_Pricelist_Source_Manual extends C_Pricelist_Source
{
    public static function get_info()
    {
        return array_merge(parent::get_info(), array('title' => __('Manual Fulfillment', 'nextgen-gallery-pro'), 'settings_field' => 'manual_settings', 'description' => __('Manual fulfillment of purchased goods and items', 'nextgen-gallery-pro'), 'classname' => get_class(), 'lab_fulfilled' => FALSE, 'id' => NGG_PRO_WHCC_PRICELIST_SOURCE));
    }
    public function get_i18n()
    {
        return array('new_product_name' => __('New product name', 'nextgen-gallery-pro'), 'new_product_name_hint' => __('8x10" Canvas or 4x6" Glossy', 'nextgen-gallery-pro'), 'new_product_price' => __('Price', 'nextgen-gallery-pro'), 'new_product_category' => __('Category', 'nextgen-gallery-pro'));
    }
    public function add_new_item_template()
    {
        $manager = C_Pricelist_Category_Manager::get_instance();
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#add_new_manual_item', array('categories' => $manager->get_by_source(NGG_PRO_MANUAL_PRICELIST_SOURCE), 'i18n' => $this->get_i18n()), TRUE);
    }
    public function get_shipments($items = array(), $cart_settings = array(), $cart)
    {
        // Variables used throughout the method
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        $domestic_shipping = $settings->get('ecommerce_domestic_shipping', 'flat');
        $domestic_shipping_rate = $settings->get('ecommerce_domestic_shipping_rate', 5);
        // A shipment will be generated for each pricelist in the cart
        $retval = array();
        if (is_array($items) && !empty($items)) {
            // First determine if there's items to manually fulfill
            $has_shipments = FALSE;
            foreach ($items as $item) {
                if ($item->source == NGG_PRO_MANUAL_PRICELIST_SOURCE) {
                    $has_shipments = TRUE;
                    break;
                }
            }
            // Create a single combined shipment for all manually fulfilled items
            if ($has_shipments) {
                $shipment = new stdClass();
                $shipment->name = NGG_PRO_MANUAL_PRICELIST_SOURCE . '-' . 'combined';
                $shipment->title = __('Manual shipping for combined items', 'nextgen-gallery-pro');
                $shipment->items = array();
                // We have one shipment with many items
                foreach ($items as $item) {
                    $shipment->items[] = $item;
                }
                $shipment->shipping_methods = array();
                $retval[NGG_PRO_MANUAL_PRICELIST_SOURCE . '-' . 'combined'] = $shipment;
            }
        }
        if ($retval and $this->has_shipping_address($cart_settings) and $home_country = strtoupper($settings->get('ecommerce_home_country'))) {
            $country = strtoupper($cart_settings['shipping_address']['country']);
            $allow_international = $settings->get('ecommerce_intl_shipping', FALSE);
            $allow_international = $allow_international && $allow_international != 'disabled';
            // For each shipment, calculate the shipping methods
            foreach ($retval as $shipment) {
                foreach ($shipment->items as $item) {
                    $global_rate = floatval($settings->ecommerce_intl_shipping_rate);
                    $local_rate = floatval($domestic_shipping_rate);
                    // Get domestic shipping method
                    if ($country == $home_country) {
                        $local_surcharge = 0.0;
                        // Use percentage rate?
                        if ($domestic_shipping == 'flat' || $domestic_shipping == 'flat_rate') {
                            $local_surcharge = $local_rate;
                            $local_rate = 0;
                        } else {
                            $local_rate = round(bcdiv($local_rate, 100, $currency['exponent']), $currency['exponent'] * 2, PHP_ROUND_HALF_UP);
                        }
                        // Add the shipping method
                        $shipment->shipping_methods = $this->_add_shipping_method_to_array($shipment->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_MANUAL, __('Manual Shipping', 'nextgen-gallery-pro'), $local_rate, $local_surcharge, 0.0);
                    } else {
                        if ($allow_international) {
                            $global_surcharge = 0.0;
                            // Use a flat international shipping rate
                            if ($settings->ecommerce_intl_shipping == 'flat_rate') {
                                $global_surcharge = $global_rate;
                                $global_rate = 0;
                            } else {
                                $global_rate = round(bcdiv($global_rate, 100, $currency['exponent']), $currency['exponent'] * 2, PHP_ROUND_HALF_UP);
                            }
                            // Add the shipping method
                            $shipment->shipping_methods = $this->_add_shipping_method_to_array($shipment->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_MANUAL, __('Manual Shipping', 'nextgen-gallery-pro'), $global_rate, $global_surcharge, 0.0);
                        }
                    }
                }
            }
        }
        return $retval;
    }
}
class C_Pricelist_Source_WHCC extends C_Pricelist_Source
{
    public static function get_info()
    {
        return array_merge(parent::get_info(), array('title' => __('WHCC Prints', 'nextgen-gallery-pro'), 'settings_field' => 'print_catalog_settings', 'description' => __('Automatic fulfillment of purchased goods and items through Printlab integration', 'nextgen-gallery-pro'), 'classname' => get_class(), 'lab_fulfilled' => TRUE, 'id' => NGG_PRO_WHCC_PRICELIST_SOURCE, 'has_shipping_calculators_for' => array('CA', 'US')));
    }
    public function get_i18n()
    {
        return array('new_product_name' => __('New product name', 'nextgen-gallery-pro'), 'new_product_name_hint' => __('8x10" Canvas or 4x6" Glossy', 'nextgen-gallery-pro'), 'new_product_price' => __('Price', 'nextgen-gallery-pro'), 'new_product_category' => __('Category', 'nextgen-gallery-pro'));
    }
    public function add_new_item_template()
    {
        return $this->object->render_partial('photocrati-nextgen_pro_ecommerce#add_new_print_item', array('catalog' => C_NextGEN_Printlab_Manager::get_instance()->get_catalog('whcc'), 'i18n' => $this->get_i18n()), TRUE);
    }
    public function get_shippable_countries()
    {
        // See Pro-817 for why this method does not honor $settings->ecommerce_whcc_intl_shipping
        return array('CA', 'US');
    }
    private function is_canada_or_us($country)
    {
        return $country == 'US' || $country == 'CA';
    }
    private function add_to_international_shipment($alias, $shipment, $item, $shipping_attributes = array())
    {
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        $global_rate = 0.0;
        $intl_shipping = 'disabled';
        // See Pro-817: $settings->ecommerce_whcc_intl_shipping;
        $intl_shipping_rate = $settings->get('ecommerce_whcc_intl_shipping_rate', 40);
        if ($intl_shipping == 'flat_rate') {
            $global_rate = round(bcadd($global_rate, $intl_shipping_rate, intval($currency['exponent'])), intval($currency['exponent']), PHP_ROUND_HALF_UP);
            if ($global_rate) {
                $shipment->shipping_methods = $this->_add_shipping_method_to_array($shipment->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_INTERNATIONAL, $alias, 0.0, 0.0, $global_rate, $shipping_attributes);
            }
        } else {
            if ($intl_shipping == 'percent_rate') {
                $global_rate = round(bcdiv($intl_shipping_rate, 100, $currency['exponent']), $currency['exponent'] * 2, PHP_ROUND_HALF_UP);
                if ($global_rate) {
                    $shipment->shipping_methods = $this->_add_shipping_method_to_array($shipment->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_INTERNATIONAL, $alias, $global_rate, 0.0, 0.0);
                }
            }
        }
        return $shipment;
    }
    public function get_shipments($items, $cart_settings, $cart)
    {
        $retval = array();
        // Variables used throughout the body of this method
        $settings = C_NextGen_Settings::get_instance();
        $currency = C_NextGen_Pro_Currencies::$currencies[$settings->ecommerce_currency];
        // Define shipments
        $gallery_wraps = new stdClass();
        $gallery_wraps->name = 'gallery_wraps';
        $gallery_wraps->title = __('Gallery Wraps', 'nextgen-gallery-pro');
        $gallery_wraps->items = array();
        $gallery_wraps->shipping_methods = array();
        $prints = new stdClass();
        $prints->name = 'prints';
        $prints->title = __('Prints', 'nextgen-gallery-pro');
        $prints->items = array();
        $prints->shipping_methods = array();
        if ($this->has_shipping_address($cart_settings) && ($home_country = strtoupper($settings->get('ecommerce_home_country')))) {
            // Get settings necessary for calculating shipping rates
            $country = strtoupper($cart_settings['shipping_address']['country']);
            $allow_international = 'disabled';
            // See: Pro-817: $settings->get('ecommerce_whcc_intl_shipping');
            $allow_international = $allow_international && $allow_international != 'disabled';
            // Determine if we only have items under a certain size. Those are less expensive to ship
            $only_small_items = FALSE;
            foreach ($items as $item) {
                if (isset($item->source_data) && isset($item->source_data['lab_properties']) && strpos($item->source_data['category_id'], 'gallery_wrap') === FALSE) {
                    $lab_properties = $item->source_data['lab_properties'];
                    $only_small_items = $lab_properties['H'] < 12 && $lab_properties['W'] < 8 || $lab_properties['W'] < 12 && $lab_properties['H'] < 8;
                    if (!$only_small_items) {
                        break;
                    }
                }
            }
            // Iterate over every item in the cart and allocate it to a particular shipment
            reset($items);
            foreach ($items as $item) {
                // Only process WHCC items
                if ($item->source == NGG_PRO_WHCC_PRICELIST_SOURCE) {
                    // GALLERY WRAPS
                    if (strpos($item->source_data['category_id'], 'gallery_wrap') !== false) {
                        $gallery_wraps->items[] = $item;
                        $shipping_methods = array();
                        // International shipping rates apply
                        if ($allow_international && !$this->is_canada_or_us($country)) {
                            $gallery_wraps = $this->add_to_international_shipment(__('International Shipping (Gallery Wraps)', 'nextgen-gallery-pro'), $gallery_wraps, $item, array(96, 105));
                        } else {
                            if ($country == 'US') {
                                // Add Economy Trackable
                                $gallery_wraps->shipping_methods = $this->_add_shipping_method_to_array($gallery_wraps->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_ECONOMY, __('Economy Trackable (Gallery Wraps)', 'nextgen-gallery-pro'), 0.09, 0.0, 7.25, array(96, 546));
                                // 3 Days or Less
                                $gallery_wraps->shipping_methods = $this->_add_shipping_method_to_array($gallery_wraps->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD, __('3 Days or Less (Gallery Wraps)', 'nextgen-gallery-pro'), 0.1, 0.0, 10.25, array(96, 100));
                                // Next Day Saver
                                $gallery_wraps->shipping_methods = $this->_add_shipping_method_to_array($gallery_wraps->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_EXPEDITED, __('Next Day Saver (Gallery Wraps)', 'nextgen-gallery-pro'), 0.15, 0.0, 19.95, array(96, 101));
                            } else {
                                if ($this->is_canada_or_us($country)) {
                                    $gallery_wraps->shipping_methods = $this->_add_shipping_method_to_array($gallery_wraps->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD, __('UPS Canada (Gallery Wraps)', 'nextgen-gallery-pro'), 0.15, 0.0, 15.5, array(96, 104));
                                }
                            }
                        }
                    } else {
                        $prints->items[] = $item;
                        // International shipping rates apply
                        if ($allow_international && !$this->is_canada_or_us($country)) {
                            $this->add_to_international_shipment(__("International Shipping (Prints)", 'nextgen-gallery-pro'), $prints, $item, array(96, 105));
                        } else {
                            if ($country == 'US') {
                                // Economy Small Trackable
                                if ($only_small_items) {
                                    $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_ECONOMY, __('Economy Trackable - Small sizes', 'nextgen-gallery-pro'), 0.06, 4.59, 0.0, array(96, 1719));
                                } else {
                                    $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_ECONOMY, __('Economy Trackable - All sizes', 'nextgen-gallery-pro'), 0.09, 7.25, 0.0, array(96, 546));
                                }
                                // 3 Days or Less
                                $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD, __('3 Days or Less', 'nextgen-gallery-pro'), 0.1, 10.25, 0.0, array(96, 100));
                                // Next Day Saver
                                $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_EXPEDITED, __('Next Day Saver', 'nextgen-gallery-pro'), 0.15, 19.95, 0.0, array(96, 101));
                                // Priority One-day
                                $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_PRIORITY, __('Priority One-day', 'nextgen-gallery-pro'), 0.15, 26.95, 0.0, array(96, 1728));
                            } else {
                                if ($this->is_canada_or_us($country)) {
                                    $prints->shipping_methods = $this->_add_shipping_method_to_array($prints->shipping_methods, $this->id, $item, $currency, NGG_PRO_ECOMMERCE_SHIPPING_METHOD_STANDARD, __('UPS Canada', 'nextgen-gallery-pro'), 0.15, 15.5, 0.0, array(96, 104));
                                }
                            }
                        }
                    }
                }
            }
            // Return shipments
            if ($prints->items) {
                $retval[$prints->name] = $prints;
            }
            if ($gallery_wraps->items) {
                $retval[$gallery_wraps->name] = $gallery_wraps;
            }
        }
        return $retval;
    }
}