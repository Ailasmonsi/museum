<?php
/**
 * @property C_Ajax_Controller $object
 */
class A_Cheque_Checkout_Ajax extends Mixin
{
    function cheque_checkout_action()
    {
        $checkout = new C_NextGen_Pro_Checkout();
        try {
            $result = $checkout->save_order($this->object->param('settings'), $this->object->param('items'), $this->object->param('coupon'), 'awaiting_payment', 'cheque', __("If you are using automatic print fulfillment, you MUST VERIFY THE ORDER to release it to the print lab. You can do this in the Ecommerce > View Orders area of your website. We recommend waiting until you've received payment, because an order cannot be canceled once it is sent to the print lab.", 'nextgen-gallery-pro'));
        } catch (Exception $error) {
            return array('error' => $error->getMessage());
        }
        return $result;
    }
}
/**
 * @property C_NextGen_Pro_Checkout $object
 */
class A_Cheque_Checkout_Button extends Mixin
{
    function get_checkout_buttons()
    {
        $buttons = parent::call_parent('get_checkout_buttons');
        if (C_NextGen_Settings::get_instance()->get('ecommerce_cheque_enable', FALSE)) {
            $buttons[] = 'cheque_checkout';
        }
        return $buttons;
    }
    function enqueue_cheque_checkout_resources()
    {
        wp_enqueue_script('cheque-checkout', $this->object->get_static_url('photocrati-cheque#button.js'));
        wp_enqueue_style('cheque-checkout', $this->object->get_static_url('photocrati-cheque#button.css'));
    }
    function get_i18n_strings()
    {
        $i18n = $this->call_parent('get_i18n_strings');
        $i18n->headline = __('Shipping information', 'nextgen-gallery-pro');
        $i18n->button_text = __('Pay by check', 'nextgen-gallery-pro');
        $i18n->button_text_submit = __('Place order', 'nextgen-gallery-pro');
        $i18n->button_text_cancel = __('Cancel', 'nextgen-gallery-pro');
        $i18n->processing_msg = __('Processing...', 'nextgen-gallery-pro');
        $i18n->field_name = __('Name', 'nextgen-gallery-pro');
        $i18n->field_email = __('Email', 'nextgen-gallery-pro');
        $i18n->field_address = __('Address', 'nextgen-gallery-pro');
        $i18n->field_city = __('City', 'nextgen-gallery-pro');
        $i18n->field_state = __('State', 'nextgen-gallery-pro');
        $i18n->field_postal = __('Zip', 'nextgen-gallery-pro');
        $i18n->field_country = __('Country', 'nextgen-gallery-pro');
        return $i18n;
    }
    function _render_cheque_checkout_button()
    {
        return $this->object->render_partial('photocrati-cheque#button', array('countries' => C_NextGen_Pro_Currencies::$countries, 'i18n' => $this->get_i18n_strings()), TRUE);
    }
}
/**
 * @property C_Form $object
 */
class A_Cheque_Checkout_Form extends Mixin
{
    function _get_field_names()
    {
        $fields = $this->call_parent('_get_field_names');
        $fields[] = 'nextgen_pro_ecommerce_cheque_enable';
        $fields[] = 'nextgen_pro_ecommerce_cheque_instructions';
        return $fields;
    }
    function enqueue_static_resources()
    {
        $this->call_parent('enqueue_static_resources');
        wp_enqueue_script('ngg_pro_cheque_form_js', $this->object->get_static_url('photocrati-cheque#form.js'));
        wp_enqueue_style('ngg_pro_cheque_form_css', $this->object->get_static_url('photocrati-cheque#form.css'));
    }
    function _render_nextgen_pro_ecommerce_cheque_enable_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_radio_field($model, 'cheque_enable', __('Enable Checks', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_cheque_enable);
    }
    function _render_nextgen_pro_ecommerce_cheque_instructions_field($model)
    {
        $model = new stdClass();
        $model->name = 'ecommerce';
        return $this->object->_render_textarea_field($model, 'cheque_instructions', __('Instructions', 'nextgen-gallery-pro'), C_NextGen_Settings::get_instance()->ecommerce_cheque_instructions, __('Use this to inform users how to pay and where they should send their payment', 'nextgen-gallery-pro'), !C_NextGen_Settings::get_instance()->ecommerce_cheque_enable ? TRUE : FALSE);
    }
}