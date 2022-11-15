(function($) {
    // Hide/show licensing page options
    $('#show_digital_downloads_licensing_link').on('change', function() {
        if ($(this).prop('checked')) {
            $('#digital_downloads_licensing_page').fadeIn();
        } else {
            $('#digital_downloads_licensing_page').fadeOut(400);
        }
    }).trigger('change');
})(jQuery);