(function($) {
    // this moves the optionto under "Display comments" rather than the very bottom of the form
    var $header_row = $('#tr_photocrati-nextgen_pro_lightbox_ecommerce');
    var $option_row = $('#tr_photocrati-nextgen_pro_lightbox_display_cart');

    $header_row.detach();
    $option_row.detach();

    $header_row.insertAfter('#tr_photocrati-nextgen_pro_lightbox_enable_carousel');
    $option_row.insertAfter($header_row);

    var $context_tbody = $('select#lightbox_global').parents('tbody');
    $context_tbody.detach();

    var $effect_tbody = $('select#lightbox_library').parents('tbody');
    $context_tbody.insertAfter($effect_tbody);

    // The following two fields are incompatible; when a change occurs disable the other option
    $('input[name="photocrati-nextgen_pro_lightbox[display_cart]"]').on('change', function() {
        if ($(this).val() == 1) {
            $('#photocrati-nextgen_pro_lightbox_display_comments_no').attr('checked', 'checked');
        }
    });

    $('input[name="photocrati-nextgen_pro_lightbox[display_comments]"]').on('change', function() {
        if ($(this).val() == 1) {
            $('#photocrati-nextgen_pro_lightbox_display_cart_no').attr('checked', 'checked');
        }
    });

})(jQuery);
