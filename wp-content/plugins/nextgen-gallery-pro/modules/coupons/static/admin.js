(function($) {

    $('span.tooltip, label.tooltip').tooltip();

    const $date_start = $('#coupon_date_start');
    const $date_end   = $('#coupon_date_end');

    $date_start.flatpickr({
        onOpen: function (selectedDates, dateStr, instance) {
            instance.set('maxDate', $date_end.val() ? $date_end.val() : false);
        }
    });
    $date_end.flatpickr({
        onOpen: function (selectedDates, dateStr, instance) {
            instance.set('minDate', $date_start.val() ? $date_start.val() : false);
        }
    });

})(jQuery);