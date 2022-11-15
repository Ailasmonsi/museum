jQuery(function($) {
    $('#tr_ecommerce_paypal_enable').addClass('ngg_payment_gateway_enable_row');

    $('input[name="ecommerce[paypal_enable]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_sandbox'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_currencies_supported'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_email'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_username'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_password'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_signature'));

    $('#tr_ecommerce_paypal_password input').attr('type', 'password');
});