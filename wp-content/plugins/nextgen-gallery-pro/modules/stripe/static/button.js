jQuery('.stripe-button-el').attr('style', 'visibility: inherit !important');

jQuery(function($) {
    $('.stripe-button-el').hover(
        function() {
            $(this).find('span').css('background', 'inherit');
        },
        function() {
            $(this).find('span').css('background', 'linear-gradient(#7DC5EE, #008CDD 85%, #30A2E4) repeat scroll 0 0 #1275FF');
        }
    );

    $('#stripe-checkout-button button').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }
        $button.attr('disabled', 'disabled');

        var post_data = $('#ngg_pro_checkout').serialize();
            post_data += "&action=stripe_session_start";

        $.post(photocrati_ajax.url, post_data, function(response) {
            if (typeof (response) != 'object') {
                response = JSON.parse(response);
            }

            // If there's an error display it
            if (typeof (response.error) != 'undefined') {
                $button.removeAttr('disabled');
                alert(response.error);
            } else {
                var stripe = Stripe(window.ngg_stripe_vars.key);
                stripe.redirectToCheckout({ sessionId: response.id })
                    .then(function(result) {
                        alert("Something bad happened" + result.error.message);
                    });
            }
        });
    });
});