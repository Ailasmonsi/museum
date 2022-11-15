jQuery(function($) {

    $('#ngg_free_button').on('click', function(event) {

        event.preventDefault();
        var $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }

        $button.attr('disabled', 'disabled');
        $button.text($button.attr('data-processing-msg'));

        var post_data = $('#ngg_pro_checkout').serialize();
        post_data += "&action=free_checkout";

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