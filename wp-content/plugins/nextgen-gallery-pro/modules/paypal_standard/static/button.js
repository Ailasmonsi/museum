jQuery(function($) {

    function create_field(name, value) {
        return $('<input/>').attr({
            name: name,
            value: value,
            type: 'hidden'
        });
    }

    $('#ngg_paypal_standard_button').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        if ($button.prop('disabled'))
            return;

        $button.attr('disabled', 'disabled');

        // Change the text of the button to indicate that we're processing
        $button.text($button.attr('data-processing-msg'));

        // Create temporary order
        var post_data = $('#ngg_pro_checkout').serialize();
        post_data += "&action=paypal_standard_order";
        $.post(photocrati_ajax.url, post_data, function(response){
            if (typeof(response) != 'object') {
                response = JSON.parse(response);
            }

            // If there's an error display it
            if (typeof(response.error) != 'undefined') {
                $button.removeAttr('disabled');
                $button.text($button.attr('data-submit-msg'));
                alert(response.error);
            }

            // Send the order to PayPal
            else {
                // Create paypal form
                var $form = $('<form/>').attr({
                    action: $button.data('paypal-url'),
                    method: 'POST'
                });

                // Modify return url
                var return_url = $button.data('return-url');
                if (return_url.indexOf('?') == -1)
                    return_url += '?order='+ response.order;
                else
                    return_url += '&order='+ response.order;

                // Modify the cancel url
                var cancel_url = $button.data('cancel-url');
                if (cancel_url.indexOf('?') == -1)
                    cancel_url += '?order='+ response.order;
                else
                    cancel_url += '&order='+ response.order;

                console.log(response);

                $form.append(create_field('cmd', '_cart'));
                $form.append(create_field('upload', 1));
                $form.append(create_field('invoice', response.order));
                $form.append(create_field('custom', response.order));
                $form.append(create_field('bn', 'NextGENGallery_BuyNow_WPS_US'));
                $form.append(create_field('currency_code', $button.data('currency-code')));
                $form.append(create_field('business', $button.data('business-email')));
                $form.append(create_field('shopping_url', $button.data('continue-shopping-url')));
                $form.append(create_field('return', return_url));
                $form.append(create_field('cancel_return', cancel_url));
                $form.append(create_field('notify_url', $button.data('notify-url')));
                $form.append(create_field('amount', response.total));

                if (response.shipping_enabled) {
                    $form.append(create_field('address_override', '1'));
                    $form.append(create_field('address1', response.shipping_street_address));
                    $form.append(create_field('address2', response.shipping_address_line));
                    $form.append(create_field('city', response.shipping_city));
                    $form.append(create_field('country', response.shipping_country));
                    $form.append(create_field('state', response.shipping_state));
                    $form.append(create_field('zip', response.shipping_zip));
                }
                else {
                    $form.append(create_field('no_shipping', '1'));
                }

                // For coupons
                if ('undefined' != response.discount_amount_cart)
                    $form.append(create_field('discount_amount_cart', response.discount_amount_cart));
                if ('undefined' != response.discount_rate_cart)
                    $form.append(create_field('discount_rate_cart', response.discount_rate_cart));

                // Add items
                var item_number = 1;
                Ngg_Pro_Cart.get_instance().each(function(image){
                    image.get('items').each(function(item){
                        $form.append(create_field('amount_'+item_number, item.get('price')));
                        $form.append(create_field('quantity_'+item_number, item.get('quantity')));
                        $form.append(create_field('item_name_'+item_number, item.get('title')+ ' / ' + image.get('alttext')));
                        $form.append(create_field('item_number_'+item_number, image.get('filename')));
                        item_number++;
                    });
                });

                $form.append(create_field('handling_cart', Ngg_Pro_Cart.get_instance().shipping));
                $form.append(create_field('tax_cart', Ngg_Pro_Cart.get_instance().tax));

                // Submit the form
                $('body').append($form);
                $form.submit();
            }
        });
    });
});