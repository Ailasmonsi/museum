jQuery(function($){
    var renderStatus = function(status, step_id, defaultVal) {
        var i18n = ecommerce_instructions_i18n;
        var classname = defaultVal;

        if (status.hasOwnProperty(step_id)) {
            if (status[step_id] == 'optional') classname = 'optional';
            else if (status[step_id]) classname = 'done';
            else classname = 'required';
        }

        $('#'+step_id+' .ngg-status')
            .removeClass('done required unknown optional')
            .addClass(classname)
            .text(i18n[classname])
            .fadeTo(500, .5)
            .fadeTo(500, 1.0);
    }

    var checkStatus = function(cb) {
        $.post(photocrati_ajax.url, {action: 'check_ecommerce_requirements', nonce: $('#ngg-status-nonce').val()}, function(response){
            if (response.success) {
                for (step_id in response.status) {
                    renderStatus(response.status, step_id, 'unknown');
                }
            }
            setTimeout(cb, 1000);
        });
    }

    $('#check-ngg-status').on('click', function(e) {
        var $btn = $(this).addClass('ngg-checking-status').val(ecommerce_instructions_i18n.checking);
        e.preventDefault();
        checkStatus(function(){
            $btn.val(ecommerce_instructions_i18n.check_again);
            $btn.removeClass('ngg-checking-status');
        });
    });
});