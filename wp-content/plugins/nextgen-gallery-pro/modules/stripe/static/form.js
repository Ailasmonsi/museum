(function($) {
    $('input[name="stripe[enable]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_stripe_key_public'))
        .nextgen_radio_toggle_tr('1', $('#tr_stripe_key_private'));
})(jQuery);

(function() {
    document.getElementById('tr_stripe_enable')
            .classList.add('ngg_payment_gateway_enable_row');

    document.querySelector('#tr_stripe_key_private input')
            .setAttribute('type', 'password');
})();