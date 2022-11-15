(function($) {
    $('input[name="paypal_checkout[enable]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_paypal_checkout_instructions'))
        .nextgen_radio_toggle_tr('1', $('#tr_paypal_checkout_sandbox'))
        .nextgen_radio_toggle_tr('1', $('#tr_paypal_checkout_client_id'))
        .nextgen_radio_toggle_tr('1', $('#tr_paypal_checkout_client_secret'));
})(jQuery);

(function() {
    document.getElementById('tr_paypal_checkout_enable')
            .classList.add('ngg_payment_gateway_enable_row');

    document.querySelector('#tr_paypal_checkout_client_secret input')
            .setAttribute('type', 'password');
})();