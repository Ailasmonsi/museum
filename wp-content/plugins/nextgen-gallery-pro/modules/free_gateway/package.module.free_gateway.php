<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_Free_Checkout_Ajax extends Mixin
{
    function free_checkout_action()
    {
        $checkout = new C_NextGen_Pro_Checkout();
        try {
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'verified', 'free', __('Order was free; no payment was charged', 'nextgen-gallery-pro'), TRUE, TRUE);
        } catch (Exception $error) {
            return array('error' => $error->getMessage());
        }
        return $result;
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_Free_Checkout_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        $buttons[] = 'free_checkout';
        return $buttons;
    }
    function enqueue_free_checkout_resources()
    {
        wp_enqueue_script('ngg-free-checkout', $this->object->get_static_url('photocrati-free_gateway#button.js'));
        wp_enqueue_style('ngg-free-checkout', $this->object->get_static_url('photocrati-free_gateway#button.css'));
    }
    function get_i18n_strings()
    {
        $i18n = $this->call_parent('get_i18n_strings');
        $i18n->button_text = __('Free checkout', 'nextgen-gallery-pro');
        $i18n->processing_msg = __('Processing...', 'nextgen-gallery-pro');
        return $i18n;
    }
    function _render_free_checkout_button()
    {
        return $this->object->render_partial('photocrati-free_gateway#button', array('countries' => C_NextGen_Pro_Currencies::$countries, 'i18n' => $this->get_i18n_strings()), TRUE);
    }
}