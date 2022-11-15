jQuery(function($) {
    $('#tr_ecommerce_cheque_enable').addClass('ngg_payment_gateway_enable_row');

    $('input[name="ecommerce[cheque_enable]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_ecommerce_cheque_instructions'));
});