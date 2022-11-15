jQuery(function($) {
    $('#ngg_test_gateway_button').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        if ($button.attr('disabled'))
            return;

        $button.attr('disabled', 'disabled');

        // Change the text of the button to indicate that we're processing
        $button.text($button.attr('data-processing-msg'));

        var post_data = $('#ngg_pro_checkout').serialize();
        post_data += "&action=test_gateway_checkout";
        $.post(photocrati_ajax.url, post_data, function(response) {
            if (typeof(response) != 'object') {
                response = JSON.parse(response);
            }
            if (typeof(response.error) != 'undefined') {
                $button.removeAttr('disabled');
                $button.text($button.attr('data-submit-msg'));
                alert(response.error);
            } else {
                window.location = response.redirect;
            }
        });
    });
});
