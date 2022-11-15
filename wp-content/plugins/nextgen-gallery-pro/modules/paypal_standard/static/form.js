(function($) {
    $('#tr_ecommerce_paypal_std_enable').addClass('ngg_payment_gateway_enable_row');

    $('input[name="ecommerce[paypal_std_enable]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_std_currencies_supported'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_std_email'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_std_sandbox'))
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_paypal_std_http_warning'));
})(jQuery);
