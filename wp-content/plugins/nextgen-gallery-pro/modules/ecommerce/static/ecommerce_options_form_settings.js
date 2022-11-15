(function($) {
    $('span.tooltip, label.tooltip').tooltip();
    
    var previous_currency;
    $('#ecommerce_currency').on('focus', function() {
        previous_currency = $('#ecommerce_currency').val();

    }).on('change', function() {
        $(this).unbind('focus');
        var retval = confirm(NGG_Pro_EComm_Settings.i18n.currency_changed);
        
        if (!retval) {
            $(this).val(previous_currency).bind('focus');
        }

        return retval;
    });

    $('select[name="ecommerce[home_country]"]').on('change', function() {
        var $this = $(this);
        var countries = NGG_Pro_EComm_Settings.iso_4217_countries;
        var value = $this.val();
        var foundCountry = null;

        for (var key in countries) {
            if (countries.hasOwnProperty(key)) {
                var country = countries[key];
                if (country.code === value) {
                    foundCountry = country;
                    break;
                }
            }
        }
        
        if (foundCountry) {
            var $currency = $('select[name="ecommerce[currency]"]');
            var currency_id = foundCountry.currency_code;
            if ($currency.find('option[value="' + currency_id + '"]').length > 0) {
                $currency.val(currency_id);
            }
        }
    });
    
    var refreshShipping = function() {
        var $sel = $(this);
        var val = $sel.val();
        var $pieces = $sel.parent().children();
        $pieces.hide();
        $sel.show();
        $pieces.filter('.show_on_' + val).show();
    };
    
    $('select[name="ecommerce[intl_shipping]"]').on('change', refreshShipping).trigger('change');
    $('select[name="ecommerce[domestic_shipping]"]').on('change', refreshShipping).trigger('change');
    // $('select[name="ecommerce[whcc_intl_shipping]"]').on('change', refreshIntlShipping).trigger('change');

    // Hide/show e-amil receipt fields
    var enable_email_receipt = $('.ecommerce_enable_email_receipt');
    enable_email_receipt.on('change', function(e) {
        if (parseInt($(e.target).val()) === 1) {
            $('#tr_ecommerce_email_receipt_subject.hidden, #tr_ecommerce_email_receipt_body.hidden').each(function() {
                $(this).fadeIn('fast', function() {
                    $(this).removeClass('hidden');
                });
            });
        }
        else {
            $('#tr_ecommerce_email_receipt_subject, #tr_ecommerce_email_receipt_body').each(function() {
                $(this).fadeOut('fast', function() {
                    $(this).addClass('hidden');
                });
            });
        }
    });

    // Check if the user has a valid license or not
    $(function() {

        var validator = {
            settings        : {},
            field_selectors : {},
            field_rules     : {},
            i18n            : {},
            
            initialize: function(settings, i18n, callback) {
                this.settings        = settings;
                this.field_selectors = this.settings.field_selectors;
                this.field_rules     = this.settings.field_rules;
                this.i18n            = i18n;
                
                var _this = this;
                
                $.getJSON(this.settings.country_list_json_url, {}, function(data) {
                    _this.populate_country_list(data);
                    callback(_this);
                });
            },
            
            get_field: function(field_name, root) {
                if (typeof(root) === "undefined" || root === null) {
                    root = $(document);
                }
                    
                if (field_name in this.field_selectors) {
                    var selector = this.field_selectors[field_name];
                    return root.find(selector);
                }
                
                return $();
            },
            
            get_child_field: function(root, field_name) {
                return this.get_field(field_name, root);
            },
            
            get_all_fields: function() {
                var $list = $();
                var $root = this.get_field('root');
                
                for (var name in this.field_selectors) {
                    if (name !== 'root' && this.field_selectors.hasOwnProperty(name)) {
                        var $field = this.get_child_field($root, name);
                        $list = $list.add($field);
                    }
                }
                
                return $list;
            },
            
            populate_country_list: function(data) {
                var $shipping_fields = this.get_field('root');
                var $country         = this.get_child_field($shipping_fields, 'country');
                var $region_input    = this.get_child_field($shipping_fields, 'state').filter('input');
                var $regions_col     = $($region_input.get(0)).closest('td');
                var countryCount     = 0;
                
                if ($region_input.attr('name') && !$region_input.data('name')) {
                    $region_input.data('name', $region_input.attr('name'));
                    $region_input.attr('name', '');
                }
                
                if ($region_input.attr('id') && !$region_input.data('id')) {
                    $region_input.data('id', $region_input.attr('id'));
                }
                
                for (var i = 0; i < data.length; i++) {
                    var country     = data[i];
                    var countryCode = country[1];
                    var regions     = country[2];
                    var postcodeRe  = typeof(country[3]) != "undefined" ? country[3] : '';
                    var $option     = $('<option />');

                    $option.val(countryCode);
                    $option.data('postCodeRegex', postcodeRe);
                    $option.append(country[0]);
                    $country.append($option);
                    countryCount += 1;
                    
                    if (regions.length > 0) {
                        var $region = $('<select />');
                        $region.append($('<option />').attr('value', '').append(this.i18n.select_region));
                        $region.attr('class', $region_input.attr('class'));
                        $region.addClass('state-generated-dropdown');
                        $region.data('name', $region_input.data('name'));
                        $region.data('id', $region_input.data('id'));
                        $region.data('countryId', countryCode);
                                
                        for (var l = 0; l < regions.length; l++) {
                            var region = regions[l];
                            var $option_r = $('<option />');
                            $option_r.val(region[1]);
                            $option_r.append(region[0]);
                            $region.append($option_r);
                        }
                                
                        $regions_col.append($region);
                                
                        if (countryCode === this.settings.selected_country) {
                            $region.val(this.settings.selected_state);
                        }
                    }
                }
                
                if (countryCount > 1) {
                    $country.prepend($('<option />').attr('value', '').attr('selected', 'selected').append(this.i18n.select_country));
                }
                
                this.update_country_bound_fields();
            },

			getFieldName: function(field) {
				$parent = field.closest('td').siblings();
				return $parent.find('label').text().trim();
			},

			validationError: function(show_errors, field, error) {
				if (show_errors === 'never') {
					error = null;
				}

				var $parent = field.closest('td');
				var $errorCont = $parent.find('.ngg-field-error-container');

				if (($errorCont.length === 0 || !$errorCont.is(':visible')) && show_errors === 'only_clear') {
					error = null;
				}

				if ($errorCont.length === 0) {
					$errorCont = $('<span class="ngg-field-error-container"></span>');
					$errorCont.append(
						$('<i class="fa fa-exclamation-triangle ngg-error-icon" aria-hidden="true"></i>')
					);
					$parent.append($errorCont);
				}

				var overlay_icon = false;

				if (field.is('input') && $.inArray(field.attr('type'), ['checkbox', 'radio']) === -1) {
					overlay_icon = true;
					$errorCont.addClass('ngg-field-error-container-input');
				} else {
					$errorCont.removeClass('ngg-field-error-container-input');
				}

				if (error) {
					$errorCont.attr('title', error);
					$errorCont.css('display', 'inline');
					var width = field.data('originalWidth');
					if (!width) {
						width = field.width();
						field.data('originalWidth', width);
					}
					if (width && !overlay_icon) {
						field.width(width - $errorCont.width() - 10);
					}
					field.css({
                        border: '1px solid red'
                    });
				} else {
					$errorCont.css('display', 'none');
					field.width('');
					field.removeAttr('style');
				}
			},

            general_fields_validate: function(show_errors) {
                if (typeof show_errors === "undefined" || !show_errors) {
                    show_errors = 'never';
                }

                var $shipping_fields = this.get_field('root');
                var $address_line    = this.get_child_field($shipping_fields, 'address_line');
                var $city            = this.get_child_field($shipping_fields, 'city');
                var $country         = this.get_child_field($shipping_fields, 'country');
                var $fullName        = this.get_child_field($shipping_fields, 'name');
                var $street_address  = this.get_child_field($shipping_fields, 'street_address');
                var $zip             = this.get_child_field($shipping_fields, 'zip');
                var $email           = this.get_child_field($shipping_fields, 'email');
                var $state           = $shipping_fields.find('.state-generated-dropdown:visible');

                if ($state.length === 0) {
                    $state = this.get_child_field($shipping_fields, 'state');
                }

                var i18n = this.i18n;
                var err  = false;

                if ($fullName.val().length === 0) {
                    this.validationError(show_errors, $fullName, sprintf(i18n.error_empty, this.getFieldName($fullName)));
                    err = true;
                } else {
                    this.validationError(show_errors, $fullName, null);
                }

                if ($email.val().length === 0) {
                    this.validationError(show_errors, $email, sprintf(i18n.error_empty, this.getFieldName($email)));
                    err = true;
                } else {
                    this.validationError(show_errors, $email, null);
                }

                if ($street_address.val().length === 0 && $address_line.val().length === 0) {
                    this.validationError(show_errors, $street_address, sprintf(i18n.error_empty, this.getFieldName($street_address)));
                    err = true;
                } else {
                    this.validationError(show_errors, $street_address, null);
                }

                if ($city.val().length === 0) {
                    this.validationError(show_errors, $city, sprintf(i18n.error_empty, this.getFieldName($city)));
                    err = true;
                } else {
                    this.validationError(show_errors, $city, null);
                }

                // If the country is not valid then the corresponding state dropdown cannot be validated either
                // It also means the zip code will not validate (no regex to match against)
                // So if the country is invalid we hide both of those fields
                if (!$country.val() || $country.val().length === 0) {
                    this.validationError(show_errors, $country, sprintf(i18n.error_empty, this.getFieldName($country)));
                    err = true;
                    $('#tr_ecommerce_home_state').hide();
                    $('#tr_ecommerce_home_zip').hide();
                } else {
                    this.validationError(show_errors, $country, null);
                    $('#tr_ecommerce_home_state').show();
                    $('#tr_ecommerce_home_zip').show();
                }

                if ($state.length > 0 && $state.is('select') && $state.val() === '') {
                    this.validationError(show_errors, $state, sprintf(i18n.error_empty, this.getFieldName($state)));
                    err = true;
                } else {
                    this.validationError(show_errors, $state, null);
                }

                var postCodeRegex = $country.find('option[value="' + $country.val() + '"]').data('postCodeRegex');
                if (postCodeRegex !== '' && !(new RegExp(postCodeRegex, 'i')).test($zip.val())) {
                    this.validationError(show_errors, $zip, sprintf(i18n.error_invalid, this.getFieldName($zip)));
                    err = true;
                } else {
                    this.validationError(show_errors, $zip, null);
                }

                return err;
            },

			gateway_fields_validate: function(show_errors) {
				if (typeof show_errors === "undefined" || !show_errors) {
					show_errors = 'never';
				}

                var $shipping_fields = this.get_field('root');

				var i18n = this.i18n;
				var err  = false;

				// TODO: Move this into the paypal standard module
				var paypal_std_enabled = $('input[name="ecommerce[paypal_std_enable]"]:checked').val() === '1';
				var $paypal_std_email = this.get_child_field($shipping_fields, 'paypal_std_email');
				if (paypal_std_enabled && $paypal_std_email.val() === '') {
					this.validationError(show_errors, $paypal_std_email, sprintf(i18n.error_empty, this.getFieldName($paypal_std_email)));
					err = true;
				} else {
					this.validationError(show_errors, $paypal_std_email, null);
				}
				// End of PayPal Standard hack

				// TODO: Move this into the stripe module
				var stripe_enabled = $('input[name="ecommerce[stripe_enable]"]:checked').val() === '1';
				var $stripe_key_pub = this.get_child_field($shipping_fields, 'stripe_key_pub');
				if (stripe_enabled && $stripe_key_pub.val() === '') {
					this.validationError(show_errors, $stripe_key_pub, sprintf(i18n.error_empty, this.getFieldName($stripe_key_pub)));
					err = true;
				} else {
					this.validationError(show_errors, $stripe_key_pub, null);
				}

				var $stripe_key_priv = this.get_child_field($shipping_fields, 'stripe_key_priv');
				if (stripe_enabled && $stripe_key_priv.val() === '') {
					this.validationError(show_errors, $stripe_key_priv, sprintf(i18n.error_empty, this.getFieldName($stripe_key_priv)));
					err = true;
				} else {
					this.validationError(show_errors, $stripe_key_priv, null);
				}
				// END of Stripe hack

				// TODO: Move this into the paypal express module
				var paypal_enabled = $('input[name="ecommerce[paypal_enable]"]:checked').val() === '1';
				var $paypal_email = this.get_child_field($shipping_fields, 'paypal_email');
				if (paypal_enabled && $paypal_email.val() === '') {
					this.validationError(show_errors, $paypal_email, sprintf(i18n.error_empty, this.getFieldName($paypal_email)));
					err = true;
				} else {
					this.validationError(show_errors, $paypal_email, null);
				}

				var $paypal_user = this.get_child_field($shipping_fields, 'paypal_user');
				if (paypal_enabled && $paypal_user.val() === '') {
					this.validationError(show_errors, $paypal_user, sprintf(i18n.error_empty, this.getFieldName($paypal_user)));
					err = true;
				} else {
					this.validationError(show_errors, $paypal_user, null);
				}

				var $paypal_pass = this.get_child_field($shipping_fields, 'paypal_pass');
				if (paypal_enabled && $paypal_pass.val() === '') {
					this.validationError(show_errors, $paypal_pass, sprintf(i18n.error_empty, this.getFieldName($paypal_pass)));
					err = true;
				} else {
					this.validationError(show_errors, $paypal_pass, null);
				}

				var $paypal_sig = this.get_child_field($shipping_fields, 'paypal_sig');
				if (paypal_enabled && $paypal_sig.val() === '') {
					this.validationError(show_errors, $paypal_sig, sprintf(i18n.error_empty, this.getFieldName($paypal_sig)));
					err = true;
				} else {
					this.validationError(show_errors, $paypal_sig, null);
				}
				// End of PayPal Express hack

				return err;
			},

            validate_all_fields: function(show_errors) {
                var $formErrorRoot = $('.ngg-error-message-root');
                var $submitButton  = $('button.ngg_save_settings_button');

                var options_invalid  = validator.general_fields_validate(show_errors);
                var gateways_invalid = validator.gateway_fields_validate(show_errors);

                // Everything is valid, allow the submission to continue. Exit early here.
                if (!options_invalid && !gateways_invalid) {
                    $submitButton.prop('disabled', false);
                    $('.ngg-error-message-root').hide();
                    $('.ngg_page_content_menu .ngg-error-icon').hide();
                    $formErrorRoot.hide();
                    return false;
                }

                $submitButton.prop('disabled', 'disabled');

                // TODO: consolidate the way we add the tabErrorIcon item to the accordion entries
                var $optionsTab = $('.ngg_page_content_menu [data-id="ngg-ecommerce-options"]');
                if (options_invalid) {
                    // TODO this is hardcoded because right now we only perform validation in the "General Options" tab
                    $optionsTab.css({
                        'display': 'flex',
                        'align-items': 'center',
                        'padding-right': '0'
                    });
                    if ($optionsTab.length > 0) {
                        var $tabErrorIcon = $optionsTab.find('.ngg-error-icon');
                        if ($tabErrorIcon.length === 0) {
                            $optionsTab.css('position', 'relative');
                            $tabErrorIcon = $('<i class="fa fa-exclamation-triangle ngg-error-icon" aria-hidden="true" style="color: #ca090f; padding-right: 4px; padding-left: 4px; font-size: 12px !important;"></i>');
                            $tabErrorIcon.attr('title', NGG_Pro_EComm_Settings.i18n.form_invalid);
                            $tabErrorIcon.appendTo($optionsTab);
                        }
                        $tabErrorIcon.show();
                    }
                } else {
                    $optionsTab.css({
                        'display': 'block',
                        'padding-right': '20px'
                    });
                }

                // TODO: consolidate the way we add the tabErrorIcon item to the accordion entries
                var $gatewaysTab = $('.ngg_page_content_menu [data-id="ngg-payment-gateways"]');
                if (gateways_invalid) {
                    $gatewaysTab.css({
                        'display': 'flex',
                        'align-items': 'center',
                        'padding-right': '0'
                    });
                    if ($gatewaysTab.length > 0) {
                        var $tabErrorIcon = $gatewaysTab.find('.ngg-error-icon');
                        if ($tabErrorIcon.length === 0) {
                            $tabErrorIcon = $('<i class="fa fa-exclamation-triangle ngg-error-icon" aria-hidden="true" style="color: #ca090f; padding-right: 4px; font-size: 125% !important;"></i>');
                            $tabErrorIcon.attr('title', NGG_Pro_EComm_Settings.i18n.form_invalid);
                            $tabErrorIcon.appendTo($gatewaysTab);
                        }
                        $tabErrorIcon.show();
                    }
                } else {
                    $gatewaysTab.css({
                        'display': 'block',
                        'padding-right': '20px'
                    });
                }

                return true;
            },

            update_country_bound_fields: function() {
                var $shipping_fields = this.get_field('root');
                var $country = this.get_child_field($shipping_fields, 'country');
                var country = $country.find('option[selected]').val();
                if (country === '') {
                    country = $country.val();
                }
                
                var $region_input = this.get_child_field($shipping_fields, 'state').filter('input');
                var $regions = $shipping_fields.find(':input').not($region_input);
                var $region_field = null;

                $regions.each(function() {
                    var $this = $(this);
                    var is_region = $this.data('name') === $region_input.data('name');
                    var countryId = $this.data('countryId');
                    if (countryId) {
                        if (countryId === country) {
                            $this.show();
                            if (is_region) {
                                $region_field = $this;
                            }
                        } else {
                            if (is_region) {
                                $this.attr('id', '');
                                $this.attr('name', '');
                            }
                            $this.hide();
                        }
                    }
                });
                
                if ($region_field != null) {
                    $region_field.attr('id', $region_input.data('id')).attr('name', $region_input.data('name'));
                    $region_input.attr('name', '').hide();
                } else {
                    $region_input.attr('name', $region_input.data('name')).show();
                }
            }
        };
        
        validator.initialize(NGG_Pro_EComm_Settings, NGG_Pro_EComm_Settings.i18n, function(validator) {
            // Initial setup
            var $shipping_fields = validator.get_field('root');
            var $country = validator.get_child_field($shipping_fields, 'country');
            $country.val(NGG_Pro_EComm_Settings.selected_country);
            validator.update_country_bound_fields();
            validator.validate_all_fields(true);

            $country.on('change', function() {
                validator.update_country_bound_fields();
            });

            // Establish all the various times that we need to re-run the validation..
            // The state fields are generated by populate_country_list() and are not returned by get_all_fields()
            $('select.ecommerce_home_state.state-generated-dropdown').on('change', function() {
                validator.validate_all_fields(true);
            });

            var allfields = validator.get_all_fields();
            allfields.on('input change', function() {
                validator.validate_all_fields(true);
            });

            $('input[name="ecommerce[paypal_std_enable]"], input[name="ecommerce[paypal_enable]"], input[name="ecommerce[stripe_enable]"]').on('change', function(event) {
                validator.validate_all_fields(true);
            });
                
            $('#ngg_page_content form').on('submit', function(evt) {
                var invalid = validator.validate_all_fields(true);
                if (invalid) {
                    evt.preventDefault();
                    return false;
                }
            });
        });
    });

    /*****
     * Print Lab Integration Form
     */
    window.nggCardElement = null;

    $('#delete-stripe-card').on('click', function(e) {
        var $this = $(this);
        e.preventDefault();
        $.post(photocrati_ajax.url, {action: 'delete_credit_card_info', nonce: atob($this.attr('data-nonce'))}, function(response) {
            if (typeof(response) != 'object') {
                response = JSON.parse(response);
            }

            $this.attr('data-nonce', response.next_nonce);

            if (response.success) {
                $('.stripe_connect_declined').remove();
                $('#delete-stripe-card').hide();
                $('.stripe_connect_active').text(print_lab_i18n.card_removed)
                nggShowStripeForm();
            } else {
                alert(print_lab_i18n.remove_card_err);
            }
        });
    });

    window.nggAppendStripeErr = function() {
        $("div[data-id='ngg-ecommerce-printlab']").append(
            $('<span/>').addClass('stripe_connect_declined').text(print_lab_i18n.not_connected)
        );
    }

    window.nggShowDeleteCard = function(){
        $('#delete-stripe-card').show();
    }

    window.nggInitStripeElements = function(testing, nonce){
        var stripe = Stripe(testing ? 'pk_test_MTNtYD9qsldURz7OzkYOOxKa' : 'pk_live_yRBibCwDB4gh97T758I4VRYy');

        // Create an instance of Elements
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        // (Note that this demo uses a wider set of styles than the guide below.)
        var style = {
            base: {
                color: '#32325d',
                lineHeight: '24px',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        // Create an instance of the card Element
        var card = elements.create('card', {style: style, hidePostalCode: true});
        window.nggCardElement = card;

        // Add an instance of the card Element into the `card-element` <div>
        card.mount('#card-element');

        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        var form = document.getElementById('ngg-stripe-form');
        if ($('.stripe-connect-disabled').length == 0) form.addEventListener('click', function(event) {
            event.preventDefault();

            stripe.createPaymentMethod('card', card)
                .then(function(result){
                    if (result.error) return Promise.reject(result.error)
                    else {
                        return fetch(nggpro_stripe_data.server_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify($.extend(nggpro_stripe_data, {
                                payment_method_id: result.paymentMethod.id
                            }))
                        })
                    }
                })
                .then(function(res){
                    return res.json();
                })
                .then(function(res) {
                    return res.status == 'succeeded'
                        ? {setupIntent: res}
                        : stripe.handleCardSetup(res.client_secret, card);
                }) 
                .then(function(res){
                    if (res.error) return Promise.reject(res.error)
                    else return res.setupIntent;
                })
                .then(function(setupIntent){
                    var postData = {
                        testing: testing,
                        payment_method: setupIntent.payment_method,
                        action: 'update_credit_card_info',
                        nonce: atob(nonce)
                    }

                    $.post(photocrati_ajax.url, postData, function(response) {
                        if (typeof(response) != 'object') {
                            response = JSON.parse(response);
                        }
            
                        $('.stripe_connect_declined').remove();
                        $('.stripe_connect_active').remove();
            
                        nggpro_stripe_data.update_nonce = response.next_nonce;
            
                        if (response.success) {
                            $("div[data-id='ngg-ecommerce-printlab']").append(
                                $('<span/>').addClass('stripe_connect_active').text(print_lab_i18n.connected)
                            );
                            $('#delete-stripe-card').show();
                            $('#ngg-stripe-form').hide();
                            $('.stripe_connect_declined').remove();
                            window.nggCardElement.unmount();

                        } else {
                            $("div[data-id='ngg-ecommerce-printlab']").append(
                                $('<span/>').addClass('stripe_connect_declined').text(print_lab_i18n.not_connected)
                            );
                            $('.stripe_connect_active').remove();
                        }
                    });
                })
                .catch(nggAppendStripeErr);
        });
    }

    window.nggShowStripeForm = function(){
        $('#ngg-stripe-form').show();
        nggInitStripeElements(nggpro_stripe_data.testing, nggpro_stripe_data.update_nonce);
    }

    $(function(){
        nggpro_stripe_data.isSetupDone
        ? nggShowDeleteCard()
        : nggShowStripeForm();
    })

})(jQuery);
