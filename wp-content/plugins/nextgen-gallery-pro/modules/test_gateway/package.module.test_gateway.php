<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_Test_Gateway_Checkout_Ajax extends Mixin
{
    function test_gateway_checkout_action()
    {
        $checkout = new C_NextGen_Pro_Checkout();
        try {
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'paid', 'test_gateway', __('Payment was successfully made via the Test Gateway, with no further payment action required.', 'nextgen-gallery-pro'));
        } catch (Exception $error) {
            return array('error' => $error->getMessage());
        }
        return $result;
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_Test_Gateway_Checkout_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        if (C_NextGen_Settings::get_instance()->ecommerce_test_gateway_enable) {
            $buttons[] = 'test_gateway_checkout';
        }
        return $buttons;
    }
    function enqueue_test_gateway_checkout_resources()
    {
        wp_enqueue_script('ngg_pro_test_gateway_button', $this->object->get_static_url('photocrati-test_gateway#button.js'), ['jquery']);
    }
    function get_i18n_strings()
    {
        $i18n = $this->call_parent('get_i18n_strings');
        $i18n->button_text = __('Place order', 'nextgen-gallery-pro');
        $i18n->processing_msg = __('Processing...', 'nextgen-gallery-pro');
        return $i18n;
    }
    function _render_test_gateway_checkout_button()
    {
        return $this->object->render_partial('photocrati-test_gateway#button', array('i18n' => $this->get_i18n_strings()), TRUE);
    }
}
/**
 * @property C_Form $object
 */
class A_Test_Gateway_Checkout_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'nextgen_pro_ecommerce_test_gateway_enable';
        return $fields;
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        wp_enqueue_script('ngg_pro_test_gateway_form', $this->object->get_static_url('photocrati-test_gateway#form.js'));
    }
    function _render_nextgen_pro_ecommerce_test_gateway_enable_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_radio_field($model, 'test_gateway_enable', __('Enable Testing Gateway', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_test_gateway_enable, __('Enables a gateway that does not collect payments and sends users directly to their order confirmation', 'nextgen-gallery-pro'));
    }
}