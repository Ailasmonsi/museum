jQuery(function($) {
    $('#paypal_express_checkout_button').on('click', function(e) {
        e.preventDefault();

        $button = $(this);
        if ($button.prop('disabled'))
            return;

        $button.attr('disabled', 'disabled');

        // Change the text of the button to indicate that we're processing
        $(this).text($(this).attr('data-processing-msg'));

        // Start express checkout with PayPal
        var post_data = $('#ngg_pro_checkout').serialize();
        post_data += "&action=paypal_express_checkout";

        $.post(photocrati_ajax.url, post_data, function(response) {
            if (typeof(response) != 'object') {
                response = JSON.parse(response);
            }

            // If there's an error display it
            if (typeof(response.error) != 'undefined') {
                $button.removeAttr('disabled');
                $button.text($button.attr('data-submit-msg'));
                alert(response.error);
            }

            // Redirect to PayPal
            else {
                window.location = response.redirect;
            }
        });
    });
});