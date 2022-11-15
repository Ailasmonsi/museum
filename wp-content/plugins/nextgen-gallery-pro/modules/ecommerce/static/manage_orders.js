jQuery(function($){

    $('#doaction').on('click', function(e) {
        if ($('#bulk-action-selector-top').val() == 'mark_as_paid') {
            if (!confirm(ngg_order_i18n.mark_as_paid_prompt)) {
                e.preventDefault();
                return false;
            }
        }
        return true;
    });


    $('.resubmit-lab-order').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var data = {
            order: $this.data('order'),
            nonce: $this.data('nonce'),
            action: 'resubmit_lab_order'
        }
        $this.text($this.data('alt-label'));
        $.post(photocrati_ajax.url, data, function(response){
            try {
                if (typeof(response) != 'object') response = JSON.parse(response);
                if (response.success) {
                    var $lab_order_status = $('.lab-order-status[data-order="'+$this.data('order')+'"]');
                    $lab_order_status.parent('.order_status').removeClass('ngg-lab-order-error').addClass('ngg-lab-order-success');
                    $lab_order_status
                        .find('.lab-order-status-label')
                        .text($this.data('success-label'))
                    $this.remove();
                }
            }
            catch (err) {
                console.log(err);
            }
            $this.text($this.data('label'));
        });
        console.log(data);
    });


    $('.lab-order-status').each(function(){
       var $this = $(this);
       var $order_column = $this.parent('.order_status');
       var order_hash = $this.data('order');
       var error_label = $this.data('error-label');
       var error_msg = $this.data('error-msg');
       var cancel_label = $this.data('cancel-label');
       var success_label = $this.data('success-label');
       var not_done_label = $this.data('not-done-label');
       var confirm_payment_label = $this.data('confirm-payment-label');
       var data = JSON.stringify({
           order: order_hash
       })
       $.ajax('https://850t23mohg.execute-api.us-east-1.amazonaws.com/latest/', {
           accepts: "application/json",
           contentType: "application/json",
           data: data,
           dataType: 'json',
           method: 'POST',
           error: function(jqxhr, status, err) {
                $order_column
                    .addClass('ngg-lab-order-error')
                    .find('.lab-order-status-label')
                    .text(error_label).attr('title', error_msg);
           },
           success: function(response, status, jqxhr) {
                if (typeof(response) != 'object') response = JSON.parse(response);

                var currentTime = Date.now() / 1000;
                var startedTime = Date.parse(response.startDate) / 1000;
                var notDone = ((currentTime - startedTime) < 30)

                if (response.statusCode != 'Submitted' && !notDone) {
                    $order_column
                        .attr('title', response.message)
                        .addClass('ngg-lab-order-error')
                        .find('.lab-order-status-label')
                        .text(cancel_label)

                    if (response.statusCode == 'Requires3DS') {
                        $confirm_payment_link = $('<a/>')
                            .attr('target', '_blank')
                            .attr('href', response.confirmPaymentUrl)
                            .text(confirm_payment_label)

                        $order_column
                            .removeClass('ngg-lab-order-error')
                            .addClass('ngg-lab-order-awaiting-confirmation')
                            .find('.lab-order-status-label')
                            .empty()
                            .append($confirm_payment_link)
                    }
                }
                else {
                    const costOfGoods = notDone ? '0.00' : response.costOfGoods;
                    const successLabel = notDone ? not_done_label : success_label;

                    $order_column
                        .attr('title', '')
                        .removeClass('ngg-lab-order-error')
                        .find('.lab-order-status-label')
                        .text(successLabel)
                    $order_column
                        .parent()
                        .find('.ngg-lab-order-cost-amount')
                        .text(costOfGoods);
                }
           }
       });
    });
});