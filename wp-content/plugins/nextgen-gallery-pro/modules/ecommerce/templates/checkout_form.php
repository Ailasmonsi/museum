<script type="text/template" id="ngg_pro_cart_item_tmpl">
    <td class='thumbnail_column ngg_pro_cart_image_column'>
    	<div class="thumbnail-container">
        <img src="{image.thumbnail_url}" width="{image.width}" height="{image.height}" alt="{image.alttext}" title="{image.alttext}" data-fullSrc="{image.full_url}" />
      </div>
        <input type="hidden" name="items[{item.image_id}][{item.id}][crop_offset]" value="{item.crop_offset}" />
    </td>
    <td class='quantity_column'>
        <div class="nggpl-quantity_field_wrapper">
            <i class="fa fa-minus"></i>
            <input type='number' min='0' name='items[{item.image_id}][{item.id}][quantity]' value='{item.quantity}' class='nggpl-quantity_field'/>
            <i class="fa fa-plus"></i>
        </div>
        <a class='ngg_pro_delete_item' href='#'>
            <i class='fa fa-times-circle'></i>
        </a>
    </td>
    <td class='title_column'>{item.title}<br/>{image.filename}<br/><a class="ngg-btn-action ngg-edit-crop" href="#"><?php esc_html_e($i18n->crop_button); ?></a></td>
    <td class='price_column'>{item.price_formatted}</td>
    <td class='subtotal_column'>
        <span>{item.subtotal_formatted}</span>
    </td>
</script>
<form id="ngg_pro_checkout"
      action="<?php echo $_SERVER['REQUEST_URI']?>"
      method="post"
      style="visibility: hidden">
		<div id="ngg_crop_ui" class="ngg-crop-ui ngg-crop-root" style="display:none;">
			<div class="crop-container">
				<div class="crop-canvas">
				</div>
				<div class="crop-controls"><div class="crop-buttons"><button class="ngg_pro_btn crop-button-close"><?php esc_html_e($i18n->crop_button_close); ?></button></div></div>
			</div>
		</div>
    <?php // TODO: Remove this. It is a placeholder for testing until the frontend is complete. ?>
    <div id="ngg_pro_links_wrapper">
        <?php if ($referrer_url): ?>
            <a class='ngg_pro_btn' href="<?php echo esc_attr($referrer_url)?>" id="ngg_pro_continue_shopping"><?php esc_html_e($i18n->continue_shopping)?></a>
        <?php endif ?>
        <a class='ngg_pro_btn' href="javascript:Ngg_Pro_Cart.get_instance().empty_cart();window.location.reload();"><?php esc_html_e($i18n->empty_cart)?></a>
    </div>
    <table class='ngg_pro_cart_items'>
		    <thead>
		    <tr class="header">
		        <th class="thumbnail_column"><?php echo esc_html($i18n->image_header)?></th>
		        <th class="quantity_column"><?php echo esc_html($i18n->quantity_header)?></th>
		        <th class="title_column"><?php echo esc_html($i18n->item_header)?></th>
		        <th class="price_column"><?php echo esc_html($i18n->price_header)?></th>
		        <th class="subtotal_column"><?php echo esc_html($i18n->total_header)?></th>
		    </tr>
		    </thead>
        <tbody class="ngg_pro_cart_images">
        </tbody>
        <tfoot>
            <tr id="ngg_pro_no_items">
                <td colspan="5"><?php echo esc_html($i18n->no_items)?></td>
            </tr>
            <?php if ($display_coupon) { ?>
            <tr id="ngg_pro_cart_coupon_tr">
                <td colspan="5">
                    <input type="hidden" name="coupon" id="ngg_pro_cart_coupon_hidden_field"/>
                    <input type="text" value="" id="ngg_pro_cart_coupon_field" placeholder="<?php esc_html_e($i18n->coupon_placeholder); ?>"/>
                    <button value="Apply" id='ngg_pro_cart_coupon_apply' class="ngg_pro_btn"><?php esc_html_e($i18n->coupon_apply); ?></button>
                    <br/>
                    <div id="ngg_pro_cart_coupon_notice"><?php print esc_html_e($i18n->coupon_notice); ?></div>
                    <div id="ngg_pro_cart_coupon_errors"></div>
                </td>
            </tr>
            <?php } ?>
            <tr id="ngg_pro_cart_fields">
            	<td colspan="5">
                    <table class="ngg-cart-shipping-fields">
                        <?php
                        // Note: validation is performed in cart.js
                        $fields = array(
                            'name' => '<input type="text" name="settings[shipping_address][name]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'email' => '<input type="text" name="settings[shipping_address][email]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'street_address' => '<input type="text" name="settings[shipping_address][street_address]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'address_line' => '<input type="text" name="settings[shipping_address][address_line]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'city' => '<input type="text" name="settings[shipping_address][city]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'country' => '<select name="settings[shipping_address][country]" class="shipping_country" placeholder="%%placeholder%%" id="%%id%%"></select><div style="display:none" id="unshippable_notice">'.esc_html($i18n->unshippable).'</div>',
                            'state' => '<input type="text" data-name="settings[shipping_address][state]" class="shipping_state" placeholder="%%placeholder%%" data-id="%%id%%" />' . "\n",
                            'zip' => '<input type="text" name="settings[shipping_address][zip]" placeholder="%%placeholder%%" id="%%id%%" />',
                            'phone' => '<input type="tel" name="settings[shipping_address][phone]" placeholder="%%placeholder%%" id="%%id%%" />',
                        );
                        foreach ($fields as $key => $field) {
                            $label = esc_html($i18n->{'shipping_' . $key . '_label'});
                            $tip = esc_attr($i18n->{'shipping_' . $key . '_tip'});
                            $id = 'ngg_shipping_field_' . $key;
                            $field = str_replace(array('%%placeholder%%', '%%id%%'), array($tip, $id), $field);
                            ?>
                            <tr class="ngg-shipping-field ngg-field-<?php echo $key; ?>">
                                <td class="ngg-field-label">
                                    <label for="<?php esc_attr_e($id); ?>"><?php echo $label; ?></label>
                                </td>
                                <td class="ngg-field-input" colspan="3">
                                    <?php echo $field; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
            	</td>
            </tr>
            <tr id="ngg_pro_cart_subitems">
                <td colspan="5">
                    <div id="ngg_pro_cart_subitems_wrapper">
                        <div id="ngg_pro_cart_subitems_overlay">
                            <i class='fa fa-spin fa-spinner'></i>
                        </div>
                        <table>
                            <tr id="ngg_pro_cart_coupon_undiscounted_subtotal_tr">
                                <th class="combined_column" colspan="4"><label><?php esc_html_e($i18n->coupon_undiscounted_subtotal); ?></label></th>
                                <th id="nggpl-undiscounced_subtotal_field"></th>
                            </tr>
                            <tr id="ngg_pro_cart_coupon_discount_amount_tr">
                                <th class="combined_column" colspan="4"><label><?php esc_html_e($i18n->coupon_discount_amount); ?></label></th>
                                <th id="nggpl-discount_amount_field"></th>
                            </tr>
                            <tr>
                                <th class='combined_column' colspan="4"><label><?php esc_html_e($i18n->subtotal)?></label></th>
                                <th id="nggpl-subtotal_field">$0.00</th>
                            </tr>
                            <tr id="ship_via_row">
                                <th class="combined_column" colspan="4"><label><?php esc_html_e($i18n->ship_via)?></label></th>
                                <th id="nggpl-ship_via_field">
                                    <input
                                        class='ngg_pro_btn'
                                        type="button"
                                        id="recalculate"
                                        value="<?php echo str_replace(' ', '&nbsp;', esc_attr($i18n->update_shipping)) ?>"
                                        style="display: none !important;"
                                    />
                                    <select name="settings[shipping_method]">
                                    </select>
                                </th>
                            </tr>
                            <tr id="shipping_field_row">
                                <th class='combined_column' colspan="4"><label><?php esc_html_e($i18n->shipping)?></label></th>
                                <th id="nggpl-shipping_field">$0.00</th>
                            </tr>
                            <?php if ($display_taxes) { ?>
                            <tr id="tax_field_row">
                                <th class='combined_column' colspan="4"><label><?php esc_html_e($i18n->tax); ?></label></th>
                                <th id="nggpl-tax_field">$0.00</th>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th class='combined_column' colspan="4"><label><?php esc_html_e($i18n->total)?></label></th>
                                <th id="nggpl-total_field">$0.00</th>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
    <div id="ngg_pro_checkout_buttons">
        <?php foreach ($buttons as $button): ?>
            <?php echo $button ?>
        <?php endforeach ?>
    </div>
</form>
