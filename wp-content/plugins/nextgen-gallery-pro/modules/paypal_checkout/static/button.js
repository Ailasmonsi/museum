(function() {

    document.addEventListener('DOMContentLoaded', function() {

        var overlay = document.createElement('div');
        overlay.setAttribute('id', 'nextgen-pro-paypal-checkout-overlay');

        var icon = document.createElement('i');
        icon.classList.add('fa');
        icon.classList.add('fa-spin');
        icon.classList.add('fa-spinner');
        icon.setAttribute('id', 'nextgen-pro-paypal-checkout-overlay-icon');
        overlay.appendChild(icon);

        var button = paypal.Buttons({

            style: {
                size: 'small',
                height: 42,
                layout: 'horizontal',
                color: 'black',
                shape: 'rect',
                tagline: false
            },

            createOrder: function(data, actions) {
                return fetch(photocrati_ajax.url + '&action=paypal_checkout_create_order', {
                    method: 'post',
                    headers: { 'content-type': 'application/x-www-form-urlencoded' },
                    body: jQuery('#ngg_pro_checkout').serialize()
                }).then(function(result) {
                    return result.json();
                }).then(function(data) {
                    if (data.error) {
                        setTimeout(function() {
                            alert(data.error);
                        }, 500);
                        return;
                    }
                    return data.paypal_order_id;
                }).catch(function(error) {
                    alert(error);
                });
            },

            onApprove: function(data, actions) {
                // First expose the overlay so users don't think the process is stopped and/or broken
                document.body.appendChild(overlay);

                // Trigger the server into capturing the funds just authorized
                return fetch(photocrati_ajax.url + '&action=paypal_checkout_finalize', {
                    method: 'post',
                    headers: { 'content-type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + encodeURIComponent(data.orderID)

                }).then(function(result) {
                    return result.json();
                }).then(function(details) {
                    overlay.parentNode.removeChild(overlay);
                    if (details.error) {
                        Ngg_Pro_Cart.get_instance().empty_cart();
                        alert(details.error);
                        return;
                    }
                    window.location = details.confirmation_url;
                }).catch(function(error) {
                    Ngg_Pro_Cart.get_instance().empty_cart();
                    alert(error);
                });
            }
        });

        button.render('#paypal-button-container');

        // Emulate the mouse hovering over the dummy button
        var button_container = document.getElementById('paypal-button-container');
        var dummy            = document.getElementById('ngg-pro-paypal-checkout-dummy');

        button_container.addEventListener('mouseover', function() {
            dummy.classList.add('ngg_force_hover');
        });
        button_container.addEventListener('mouseleave', function() {
            dummy.classList.remove('ngg_force_hover');
        });

        // Disable / enable the transparent PayPal button when the cart is valid
        Ngg_Pro_Cart.get_instance().on('disable_checkout_buttons', function(isValid) {
            if (isValid) {
                button.show();
                button_container.classList.remove('disabled');
            } else {
                button.hide();
                button_container.classList.add('disabled');
            }
        });

    });
})();