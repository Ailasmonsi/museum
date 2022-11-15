(function() {
    var i18n = ngg_pro_stripe_manage_orders_i18n;

    // This is a stupid hack, but WordPress doesn't expose an easy way to add much content to edit.php?post_type=ngg_order
    var newButton = document.createElement('button');
    var buttonContent = document.createTextNode(i18n.name);
    newButton.setAttribute('id', 'ngg-pro-stripe-refresh');
    newButton.setAttribute('title', i18n.title);
    newButton.appendChild(buttonContent);
    var target = document.querySelector('body.post-type-ngg_order form#posts-filter p.search-box');
    var parent = document.querySelector('body.post-type-ngg_order form#posts-filter');
    parent.insertBefore(newButton, target.nextSibling);

    document.getElementById('ngg-pro-stripe-refresh')
            .addEventListener('click', function(event) {
        event.preventDefault();

        var self = this;
        var originalText = self.innerText;
        self.setAttribute('disabled', true);
        self.innerText = i18n.processing;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', photocrati_ajax.url);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                var response = this.response;
                if (typeof (response) != 'object') {
                    response = JSON.parse(response);
                }

                self.innerText = originalText;

                if (response.error) {
                    alert(response.error);
                } else {
                    if (response.updated === 0) {
                        alert(i18n.zero);
                    } else {
                        alert(i18n.count.replace(/%d/, response.updated));
                        window.location = window.location;
                    }
                }

                self.removeAttribute('disabled');
            }
        };

        xhr.send(encodeURI('action=stripe_poll_for_missed_webhook_events'));
    });

})();