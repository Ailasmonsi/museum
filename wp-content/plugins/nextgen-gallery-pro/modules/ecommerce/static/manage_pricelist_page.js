jQuery(function($) {
    
    // When "Save" is clicked determine if the title is empty and notify the user
    $('.button-primary').on('click', function (e) {
        var retval = true;
        var $title = $('#title');
        if ($title.val().trim().length == 0) {
            e.preventDefault();
            $title.addClass('title_empty');
            $(window).scrollTop(0);
            retval = false;
        } else {
            $title.removeClass('title_empty');
        }

        return retval;
    });
        
    $('#ngg_page_content form#ngg_pricelist_form input[type=submit], #ngg_page_content form#ngg_pricelist_form button[type=submit]').on('click', function() {
        $("input[type=submit], button[type=submit]", $(this).parents("form")).removeAttr("clicked");
        $(this).attr("clicked", "true");
    });

    $('#ngg_page_content form#ngg_pricelist_form').on('submit', function (e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
            
        var $form = $(this);
        var $submits = $form.find('[type=submit]');
        var $submit = $submits.filter('[clicked=true]');
            
        $submits.prop('disabled', true);
            
        if (!$submit.data('originalText')) {
			$submit.data('originalText', $submit.html());
		}
            
        $submit.html($submit.data('executing'));
            
        // We need our serialized form data *WITHOUT* any pricelist_item entries
        var postData = $form.serializeArray().filter(function(value, index, arr) {
            return !value.name.match(/^pricelist_item/i);
        });

        // Find all of our pricelist_item[] elements and combine them into a JSON array
        var tmp = $('#ngg_page_content form input[name^="pricelist_item"]').serializeJSON();
        if ('undefined' !== typeof tmp.pricelist_item) {
            postData.push({name: 'pricelist_item', value: JSON.stringify(tmp.pricelist_item)});
        }

        // Ensure the server side knows that we're saving this form..
        postData.push({name: 'action', value: 'save_pricelist'});

        $.post(photocrati_ajax.url, postData, function (response, textStatus, request) {
            $submit.html($submit.data('originalText'));

            if (typeof(response) != 'object') {
                response = JSON.parse(response);
            }

            // TODO: this needs to integrate with the wizard better.
            // If the nextgen setup wizard is currently running then we DO NOT REDIRECT when the form has been saved
            // This is necessary for the next step in the wizard to run after the user has saved their first pricelist
            var action = $form.attr('action');
            if (action.indexOf("ngg_wizard") === -1) {
                window.setTimeout(function () {
                    if (response.error) {
                        alert(response.error);
                        window.location = window.location;
                    } else {
                        window.location = response.redirect_url;
                    }
                }, 300);
            }
        });
            
        return false;
    });

    // Hide above notification when the title field has been given a value
    $('#title').on('keypress', function () {
        var $title = $(this);
        if ($title.val().trim().length == 0) {
            $title.addClass('title_empty');
        } else {
            $title.removeClass('title_empty');
        }
    });

    $('#ngg_pricelist_form').on('change', '.price_field', function() {
        var $this = $(this);
        var parts = $this.val().split('.');
        var new_parts = [parts[0]];
        if (parts.length > 1) {
        	new_parts.push(parts[1]);
		}
        var val = new_parts.join('.');
        val = val.replace(/[^0-9\.]/g, '');
        if (val.length > 0 && val != '.') {
        	val = sprintf("%.2f", parseFloat(val));
		}
        $this.val(val);
    }).trigger('change');

    // Pricelist page 'cancel' button
    $('#cancel_btn').on('click', function(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
        window.location = $(this).attr('data-redirect');
        return false;
    });

    // Deletes an item
    $('#ngg_pricelist_form').on('click', '.delete_item', function() {
        var id        = $(this).attr('data-id');
        var table_id  = $(this).attr('data-table-id');
        var $table    = $('#' + table_id).parent();
        var $no_items = $table.find('.no_items');

        $table.find('.item_' + id).fadeOut(400, function() {
            $(this).remove();
            if (id.indexOf('new') == -1) {
                var $deleted = $('<input/>').attr({
                    name:  'deleted_items[]',
                    type:  'hidden',
                    value: id
                });
                $('#ngg_page_content form').prepend($deleted);
            }

            if ($table.find('.item').length == 0) {
                $no_items.fadeIn();
            }
        });
    });

    // "Add Product" dialog cancel button
    $('#new_product_button_cancel, #bulk_markup_button_cancel').on('click', function(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
        tb_remove();
    });

    var isSourceLabFulfilled = function(source_id) {
        var retval = false;
        if (ngg_pro_pricelist_sources.hasOwnProperty(source_id)) {
            retval = ngg_pro_pricelist_sources[source_id];
        }
        return retval;
    };

    // Thickbox is not responsive; this is a cludge and hack to force it work when launched on small screens
    // and to prevent its display from going haywire when the browser is resized. THIS IS NOT PERFECT BY ANY MEANS.
    // TODO: Replace Thickbox entirely, the amount of hard-coded sizing and positioning involved in this file is insane
    var adaptThickboxSize = function() {
        var maxHeight = $(window).height();
        var maxWidth  = $(window).width();

        var height = TB_HEIGHT;
        var width  = TB_WIDTH;

        if (maxHeight < height) {
            height = maxHeight - 25;
            $('#TB_ajaxContent').outerHeight(height - 90);
        }
        if (maxWidth < width) {
            width = maxWidth - 40;
            $('#TB_ajaxContent').outerWidth(width);
        }

        $("#TB_window").css({
            "height": height,
            "width": width,
            "margin-left": -width / 2,
            "margin-top":  -height / 2
        });

        var container = $('#new_product_source_form');
        var $dialog = container.find('.catalog-dialog');
        if ($dialog.data('loaded') != 'yes') {
            var $tabsCont = $dialog.find('.catalog-categories');
            var $panelsCont = $dialog.find('.catalog-panel-container');
            var panelHeight = $dialog.parents('#TB_ajaxContent').height();
            panelHeight -= $tabsCont.outerHeight(true);
            panelHeight -= (25 + 40 + 3 * 2); /* XXX remove paddings and borders */
            panelHeight -= $panelsCont.outerHeight(true) - $panelsCont.outerHeight();
            $panelsCont.outerHeight(panelHeight);
        }

    };

    $(window).on('resize', function() {
        adaptThickboxSize();
    });

    var setThickboxSize = function (width, height, root, updateSize) {
        if (typeof(root) === "undefined") {
			root = $('#TB_window');
		}

        TB_HEIGHT = height;
        TB_WIDTH  = width;

        var titleCont = root.find('#TB_title');
        var left = -width / 2;
        var top = -((320 / 2) + ((height - 383) / 2));

        root.outerWidth(width);
        root.css({ 'margin-left': left, 'margin-top': top });
        root.find('#TB_ajaxContent').outerWidth(width).outerHeight(height - titleCont.outerHeight(true));

        if (updateSize) {
            adaptThickboxSize();
        }
    };

    var updatePrintDialog = function(container) {
        var $dialog = container.find('.catalog-dialog');
            
        if ($dialog.data('loaded') != 'yes') {
            var $tabsCont = $dialog.find('.catalog-categories');
            var $tabs = $tabsCont.find('li');
            var $panelsCont = $dialog.find('.catalog-panel-container');
            var $panels = $panelsCont.find('.catalog-panel');
            var panelHeight = $dialog.parents('#TB_ajaxContent').height();
            panelHeight -= $tabsCont.outerHeight(true);
            panelHeight -= (25 + 40 + 3 * 2); /* XXX remove paddings and borders */
            panelHeight -= $panelsCont.outerHeight(true) - $panelsCont.outerHeight();
                
            $panels.hide();
            $panels.first().show();
            $tabs.removeClass('selected');
            $tabs.first().addClass('selected');
            $panelsCont.outerHeight(panelHeight);
                
            $dialog.find('.catalog-tabs-container li').on('click', function (e) {
                var $tab = $(this);
                $tabs.removeClass('selected');
                $tab.addClass('selected');
                $panels.hide();
                $panels.filter($tab.find('a').attr('href')).show();
            });
                
            $dialog.find('.items-table tr.item-content').on('click', function (e) {
                var $check = $(this).find('.item-added input.item-check');
                $check.prop('checked', !$check.is(':checked'));
                if ($check.is(':checked')) {
                   $check.attr('checked', 'checked');
                } else {
                    $check.prop('checked', false);
                }
            });
                
            $dialog.find('.items-table th.item-added label.check-all').on('click', function (e) {
                var $this = $(this);
                var $table = $this.parents('table.items-table');
                var $check = $this.siblings('.item-check');
                $check.prop('checked', !$check.is(':checked'));
                $check.attr('checked', $check.is(':checked') ? 'checked' : null);
                $table.find('td.item-added input.item-check').prop('checked', $check.prop('checked'));
            });
                
            $dialog.find('.button.cancel').on('click', function(e) {
                $dialog.hide();
            });
                
            $dialog.data('loaded', 'yes');
        }
            
        var $tables = $('.pricelist_category_collection');
        var $items = $tables.find('tr');
        var itemCount = 0;
        var $dialogItems = $dialog.find('table.items-table tr.item-group');
            
        for (var i = 0; i < $items.length; i++) {
            var $item = $($items.get(i));
            var $source = $item.find(':input[name$="[source]"]');

            if (isSourceLabFulfilled($source.val())) {
                itemCount++;
                var $itemData = $item.find(':input[name*="[source_data]"]');
                    
                for (var l = 0; l < $dialogItems.length; l++) {
                    var $dialogItem = $($dialogItems.get(l));
                    var prefix = $dialogItem.data('itemPrefix');
                    var isMatch = true;
                    for (var k = 0; k < $itemData.length; k++) {
                        var $dataItem = $($itemData.get(k));
                        var dataName = $dataItem.attr('name');
                        var dataValue = $dataItem.val();
                        var nameIdx = dataName.indexOf('[source_data]');
                        var nameSuffix = dataName.substr(nameIdx);
                        var $dialogData = $dialogItem.find(':input[name$="' + nameSuffix + '"]');
                        if ($dialogData.val() != dataValue) {
                            isMatch = false;
                            break;
                        }
                    }
                        
                    if (isMatch) {
                        $dialogItem.find(':input[name$="[added]"]').prop('checked', true);
                        $dialogItems.splice(l, 1);
                        l--;
                    }
                }
            }
        }
            
        // if itemCount == 0, no printlab items were added, so don't uncheck any in the dialog, which will leave items that are included by default
        if (itemCount > 0) {
            for (var l = 0; l < $dialogItems.length; l++) {
                var $dialogItem = $($dialogItems.get(l));
                $dialogItem.find(':input[name$="[added]"]').prop('checked', false);
            }
        }
    };

    var isValid = function(event) {
        var allvalid = true;

        _(jQuery('#new_product_source_form :input')).each(function(input) {
            if (!input.checkValidity()) {
                allvalid = false;
            }
        });

        if (allvalid) {
            $('#new_product_button_add').show();

            // Handle the 'return' button since the browser won't (the <form> lacks nearly all attributes..)
            if (typeof event !== 'undefined') {
                if (event.keyCode === 13) {
                    $('#new_product_button_add').trigger('click');
                }
            }

        } else {
            $('#new_product_button_add').hide();
        }
    };
        
    // When a source has been chosen in the "Add Product" dialog
    $('#new_product_source_list li.product-source-item').on('click', function() {
        var $this = $(this);
        _.each($('.new_pricelist_item_wrapper'), function(template) {
            if ($(template).data('sourceId') == $this.data('sourceId')) {
                $('#new_product_source_list').hide();
                $('#new_product_source_form').html($(template).html());
                $('#new_product_source_form').show();

                isValid();

                if (isSourceLabFulfilled($this.data('sourceId'))) {
                    setThickboxSize(1000, 600, undefined, true);
                    updatePrintDialog($('#new_product_source_form'));
                } else {
					setThickboxSize(630, 383, undefined, false);
				}

                $('#TB_ajaxWindowTitle').html($this.data('sourceTitle'));
                $('#new_product_source_form form').data('sourceId', $this.data('sourceId'));
            }
        });
    });
        
    // Only display the "Add product" button if the fields presented are valid
    $('body').on('change paste keyup', '#new_product_source_form :input', isValid);

    // Reset the "Add Product" dialog when thickbox closes
    $('body').on('thickbox:removed', function() {
        $('#new_product_source_form').hide();
        $('#new_product_button_add').hide();
        $('#new_product_source_list').show();
        $('#new_product_source_form form').data('sourceId', '');
    });
        
    var applyMarkupToItem = function (item, amount, rounding) {
        var cost = parseFloat(item.find(':input[name$="[cost]"]').val());
        var price = cost * (1+(amount / 100));

        if (rounding != "none") {
            price = Math.ceil(price);
            if (rounding == 'cent') {
                price -= 0.01;
            }
        }

        price = price.toFixed(2);    

        item.find(':input[name$="[price]"]').val(price);
    };

    var findItem = function (source, sourceData, itemInfoOut) {
        if (typeof(sourceData) === "undefined" || !sourceData)
            sourceData = [];
            
        var catalog_id = null;
        var product_id = null;
            
        for (var i = 0; i < sourceData.length; i++) {
            var dataEntry = sourceData[i];
                
            if (dataEntry.name == '[catalog_id]')
                catalog_id = dataEntry.value;
            else if (dataEntry.name == '[product_id]')
                product_id = dataEntry.value;
                    
            if (catalog_id != null && product_id != null)
                break;
        }
            
        if (typeof(itemInfoOut) !== "undefined") {
            itemInfoOut.catalog_id = catalog_id;
            itemInfoOut.product_id = product_id;
        }
                
        if (catalog_id != null && product_id != null) {
                
            var selector = '.pricelist_category_item[data-catalog-id="' + catalog_id + '"][data-product-id="' + product_id + '"]';
                
            var $items = $('.pricelist_category_collection').children(selector);
                
            if ($items.length > 0)
                return $($items.get(0));
        }
            
        return null;
    };
        
    var MARKUP_AMOUNT = 0;
    var MARKUP_ROUNDING = 0;
    var SCRIPT_CACHE = {};
    var $scripts = $('script[type="ngg-template"]');
    for (var i = 0; i < $scripts.length; i++) {
        var $script = $($scripts.get(i));
        var source = $script.data('sourceId');
        var category = $script.data('categoryId');
            
        if (typeof(source) === "undefined" || source == null)
            source = '__default__';
            
        if (!(category in SCRIPT_CACHE)) {
            SCRIPT_CACHE[category] = {};
            SCRIPT_CACHE[category]['__default__'] = [];
        }
            
        if (!(source in SCRIPT_CACHE[category])) {
            SCRIPT_CACHE[category][source] = [];
        }
            
        var script = {
            table_id: $script.data('tableId'),
            template: $($script.html())
        };
            
        SCRIPT_CACHE[category][source].push(script);
    }
        
    var addItem = function (source, category, values) {
        var $element  = null;
        var itemInfo  = {};
        var newElem   = true;
        var elementId = '';
        var $table    = null;
            
        if (isSourceLabFulfilled(source)) {
            var sourceData = [];
            for (var i = 0; i < values.length; i++) {
                var value = values[i];
                var str = '[source_data]';
                var idx = value.name.indexOf(str);
                if (idx > -1) {
                    sourceData.push({
                        name: value.name.substr(idx + str.length),
                        value: value.value
                    });
                }
            }
                
            $element = findItem(source, sourceData, itemInfo);
        }
            
        if ($element != null) {
            newElem = false;
            elementId = $element.attr('id');
            $table = $element.parents('tbody').first();
        }
            
        if (newElem) {
            var script = null;
            if (typeof(SCRIPT_CACHE[category][source]) !== "undefined")
                script = SCRIPT_CACHE[category][source][0];
            else if (typeof(SCRIPT_CACHE[category]) !== "undefined")
                script = SCRIPT_CACHE[category]['__default__'][0];
                
            if (script == null)
                return null;
                    
            var table_id = script.table_id;
            $table       = $('#' + table_id);
            elementId    = 'new-' + Math.random().toString(10).substr(2);

            // A few items must have {id} replaced with new-(rand())
            var $element = script.template.clone();
            $element.attr('id', $element.attr('id').replace(/\{id\}/g,  elementId));
            $element.attr('class', $element.attr('class').replace(/\{id\}/g, elementId));
            $element.attr('data-catalog-id', itemInfo.catalog_id);
            $element.attr('data-product-id', itemInfo.product_id);
        }
            
        $element.find('[name="pricelist_item[{id}][source]"]').val(source);
        $element.find('[name="pricelist_item[{id}][category]"]').val(category);
            
        var item_cost_str = '[cost]';
        var item_price_str = '[price]';
        var item_cost = 0;

        var $cost_field  = null;
        var $price_field = null;
            
        for (var i = 0; i < values.length; i++) {
            var dialog_field = values[i];
            var $field = $element.find('[name="' + dialog_field.name + '"]');
                
            if (newElem && $field.length == 0 && dialog_field.name.indexOf('[source_data]') > -1) {
                $field = $('<input type="hidden" class="pricelist_item_hidden_source_data" />').attr('name', dialog_field.name);
                $element.find('td').first().append($field);
            }
                
            if ($field.length > 0) {
                var cost_idx = dialog_field.name.indexOf(item_cost_str);
                if (cost_idx > -1 && cost_idx + item_cost_str.length == dialog_field.name.length) {
                    $cost_field = $field;
                }
                        
                var price_idx = dialog_field.name.indexOf(item_price_str);
                if (price_idx > -1 && price_idx + item_price_str.length == dialog_field.name.length) {
                    $price_field = $field;
                    $field.attr('min', item_cost);
                }
                    
                // Allow fields to go unrequired but provide a default value. Note: because we're inspecting the template
                // provided by the source the data-default-value is found there instead of add_new_(source)_item.php
                if (dialog_field.value == "" && 'undefined' != typeof($field.data('defaultValue'))) {
                    dialog_field.value = $field.data('defaultValue');
                }

                $field.val(dialog_field.value);
                $field.attr('name', $field.attr('name').replace(/\{id\}/g, elementId));
            }
        }

        // When adding an item to the pricelist we're only interested in the results of the 'price' column
        // should it happen to differ from the 'cost' column (should the printlab currency not match the site setting)
        if ($cost_field) { // $cost_field will only exist for printlab items
            $cost_field.val($price_field.val());
            $price_field.val($price_field.val());
            item_cost = $price_field.val();
        }
            
        if (item_cost == 0) {
            $element.find('[name="pricelist_item[{id}][cost]"]').replaceWith('<div style="text-align: center;">-</div>');
        } else {
            if (MARKUP_AMOUNT == 0) {
				MARKUP_AMOUNT = parseFloat($(':input[name="pricelist[settings][bulk_markup_amount]"]').val());
			}
                
            if (MARKUP_ROUNDING == 0) {
				MARKUP_ROUNDING = $(':input[name="pricelist[settings][bulk_markup_rounding]"]').val();
			}
                
            var amount = MARKUP_AMOUNT;
            var rounding = MARKUP_ROUNDING;
            applyMarkupToItem($element, amount, rounding);
        }

        if (newElem) {
            _.each($element.find('input[type=hidden]'), function(hidden_field) {
                var $field = $(hidden_field);
                var name = $field.attr('name');

                // Inject the sortorder attribute with this item at the end
                if (name == 'pricelist_item[{id}][sortorder]') {
                    $field.val($table.find('tr.item').length); // No +1 is necessary, jQuery-UI-sortorder indexes from zero
                }

                $field.attr('name', name.replace(/\{id\}/g, elementId));
            });

            _.each($element.find('i.delete_item'), function(link) {
                var newval = $(link).data('id').replace(/\{id\}/g, elementId);
                link.setAttribute('data-id', newval);
            });

            return {'element': $element,
                    'table':    $table};
        }

        return false;
    };

    function show_gallery_wrap_confirmation()
    {
        setTimeout(function(){
            alert(manage_pricelist_page_i18n.gallery_wrap_notice);
        }, 250);
        
    }

    // Adds a new item from a script template
    $('#new_product_button_add').on('click', function() {
        var addedGalleryWrapItem = false;
        var $form = $('#new_product_source_form form');
        var source = $form.data('sourceId');
        var values = $form.serializeArray();
        var $itemGroups = $form.find('.item-group');
        if ($itemGroups.length == 0) {
			$itemGroups = $form;
		}
                
        _.each($itemGroups, function (group) {
            var $group = $(group);
            var prefix = $group.data('itemPrefix');
            var fields = $group.find(':input');
            var $included = fields.filter('.item-check[type=checkbox]');
                
            if ($included.length == 0 || $included.is(':checked')) {
                var target_category = $(_(fields).filter(function (x) {
                    return $(x).data('fieldName') == 'category';
                })).val();
                    
                var itemValues = values;
                if (prefix && prefix != '') {
                    itemValues = [];
                    for (var i = 0; i < values.length; i++) {
                        var field = values[i];
                        if (field.name.indexOf(prefix) === 0) {
                            itemValues.push({
                                name: field.name.replace(prefix, ''),
                                value: field.value
                            });
                        }
                    }
                }

                var newItem = addItem(source, target_category, itemValues);

                if (newItem !== false) {
                    // An item is being added, so hide the 'no items' message
                    newItem.table.parent().find('.no_items').hide();
                    newItem.table.append(newItem.element);

                    if (target_category == "ngg_category_canvas") {
                        addedGalleryWrapItem = true;
                    }
                }
            }
        });
            
        // Close thickbox, this will trigger the above code resetting the form div
        if (addedGalleryWrapItem) {
            show_gallery_wrap_confirmation();
        }
        tb_remove();

    });
        
    $('#bulk_markup_dialog form').on('submit', function(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
            
        var $form = $(this);
        var values = $form.serializeArray();
            
        var amount = 0;
        var rounding = "none";
            
        for (var i = 0; i < values.length; i++) {
            var field = values[i];
                
            if (field.name == 'markup_percent') {
                amount = parseInt(field.value);
            } else if (field.name == 'markup_rounding') {
                rounding = field.value;
            }
        }
            
        if (amount > 0) {
            $(':input[name="pricelist[settings][bulk_markup_amount]"]').val(amount.toString());
            $(':input[name="pricelist[settings][bulk_markup_rounding]"]').val(rounding);
                
            var $tables = $('.pricelist_category_collection');
            var $items = $tables.find('tr');
                
            for (var i = 0; i < $items.length; i++) {
                var $item = $($items.get(i));
                var $source = $item.find(':input[name$="[source]"]');

                if (isSourceLabFulfilled($source.val())) {
                    applyMarkupToItem($item, amount, rounding);
                }
            }
        }
            
        tb_remove();
    });

    // Handle sorting pricelist order through drag & drop
    $("#nextgen_admin_accordion .pricelist_category_collection").sortable({
        axis: "y",
        handle: "td.pricelist_sort_handle i",
        start : function(event, ui) {
            // Without the starting position we can't later process other rows based
            // on whether they pre- or post-cede and determine new indexes
            var start_pos = ui.item.index();
            ui.item.data('start_position', start_pos);
        },
        change : function(event, ui) {
            var start_pos = ui.item.data('start_position');
            var index     = ui.placeholder.index();
            var cur_index = (start_pos < index) ? index - 2 : index - 1;

            // Updated the sortorder of items preceding that which has been moved
            ui.placeholder.prevAll('tr.item').each(function() {
                $this = $(this);
                if ($this.is(ui.item)) {
                    return;
                }
                $this.find('.pricelist_item_hidden_sortorder').val(cur_index);
                cur_index--;
            });

            // Update the sortorder of items after that which has been moved
            cur_index = (start_pos < index) ? index : index + 1;
            ui.placeholder.nextAll('tr.item').each(function() {
                $this = $(this);
                if ($this.is(ui.item)) {
                    return;
                }
                $this.find('.pricelist_item_hidden_sortorder').val(cur_index);
                cur_index++;
            });

            // Update the sortorder of the moved item
            ui.item.find('.pricelist_item_hidden_sortorder').val(new_position = (start_pos < index) ? index - 1 : index);
        }
    });
});
