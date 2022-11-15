<?php
/**
 * @var $items array
 * @var $settings array
 * @var $i18n stdClass
 * @var $pages array
 * @var $item_category string
 */
$currency = C_NextGen_Pro_Currencies::$currencies[C_NextGen_Settings::get_instance()->ecommerce_currency];
?>
	<tbody id="digital_download_options">
		<tr>
			<td class="label_column">
				<input type="checkbox"
				       name="pricelist[digital_download_settings][show_licensing_link]"
				       id="show_digital_downloads_licensing_link"
					   <?php checked($settings['show_licensing_link'], 1); ?>
					   value="1"/>

				<label for="show_digital_downloads_licensing_link"><?php echo esc_html($i18n->show_licensing_link); ?></label>

				<select class='hidden' id='digital_downloads_licensing_page' name="pricelist[digital_download_settings][licensing_page_id]">
					<?php foreach ($pages as $page) { ?>
						<option <?php selected($settings['licensing_page_id'], $page->ID); ?>
							    value="<?php echo esc_attr($page->ID); ?>">
							<?php echo esc_html($page->post_title); ?>
						</option>
					<?php } ?>
				</select>
			</td>
		</tr>
        <tr>
            <td class="skip_checkout_column">
                <input type="checkbox"
                       name="pricelist[digital_download_settings][skip_checkout]"
                       id="skip_checkout_input"
                       value="1"
                       <?php checked($settings['skip_checkout'], 1); ?>/>
                <label for="skip_checkout_input">
                    <?php print esc_html($i18n->free_items_label); ?>
                </label>
            </td>
        </tr>
	</tbody>
</table><?php // Do not remove, this is intentionally ending a parent table and starting a new one after the <script> ?>
<script type="ngg-template"
        data-table-id="digital_downloads"
        data-category-id="<?php echo esc_attr($item_category); ?>">
	<tr id="digital_download_{id}" class='digital_download item item_{id}'>
		<td class="pricelist_sort_handle">
			<i class="fa fa-sort" aria-hidden="true"></i>
		</td>
		<td class="pricelist_item_column">
			<input type='hidden' name='pricelist_item[{id}][sortorder]' class="pricelist_item_hidden_sortorder"/>
			<input type='hidden' name='pricelist_item[{id}][source]'    value=''/> <?php /* this is filled in by the JS script when adding items */ ?>
			<input type='hidden' name='pricelist_item[{id}][category]'  value='<?php echo esc_attr($item_category); ?>'/>
			<input class="title_field"
			       type="text"
			       name="pricelist_item[{id}][title]"
			       value=""
			       placeholder="<?php echo esc_attr($i18n->item_title_placeholder); ?>"/>
		</td>
		<td class='price_column'>
			<input class='price_field'
			       type="text"
			       name="pricelist_item[{id}][price]"
			       value=""
			       min="0.00"
			       placeholder="0.00"
                   autocomplete="off"/>
		</td>
		<td class='resolution_column'>
			<input class='resolution_field'
			       type="number"
			       step="any"
			       name="pricelist_item[{id}][resolution]"
			       value="0"
			       min="0"
			       data-default-value="0"
			       placeholder="<?php echo esc_attr($i18n->resolution_placeholder); ?>"/> px
		</td>
		<td>
            <i class="fa fa-times-circle delete_item" data-id="{id}" data-table-id="digital_downloads"></i>
		</td>
	</tr>
</script>
<table>
	<thead>
		<tr>
			<th class="sort_column"></th>
			<th class="title_column"><?php echo esc_attr($i18n->name_header); ?></th>
			<th class="price_column"><?php echo esc_attr($i18n->price_header); ?></th>
			<th colspan='2' class="resolution_column">
		            <span class='tooltip'
		                  title="<?php echo esc_attr($i18n->resolution_tooltip); ?>"><?php echo esc_attr($i18n->resolution_header); ?></span>
			</th>
		</tr>
	</thead>
	<tbody id="digital_downloads"
           class="pricelist_category_collection">
		<?php foreach ($items as $item) { ?>
            <?php /** @var $item stdClass */ ?>
			<tr id="digital_download_<?php echo esc_attr($item->ID); ?>" class='digital_download item item_<?php echo esc_attr($item->ID); ?>'>
				<td class="pricelist_sort_handle">
					<i class="fa fa-sort" aria-hidden="true"></i>
				</td>
				<td class="title_column">
					<input type="hidden"
					       class="pricelist_item_hidden_sortorder"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][sortorder]"
					       value="<?php echo esc_attr($item->sortorder); ?>"/>
					<input type="hidden"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][source]"
					       value="<?php echo esc_attr($item->source); ?>"/>
					<input type="hidden"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][category]"
					       value="<?php echo esc_attr($item->category); ?>"/>
					<input class="title_field"
					       type="text"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][title]"
					       value="<?php echo esc_attr($item->title); ?>"
					       placeholder="<?php echo esc_attr($i18n->item_title_placeholder); ?>"/>
				</td>
				<td class="price_column">
					<input class="price_field"
					       type="text"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][price]"
					       value="<?php echo esc_attr(sprintf("%.{$currency['exponent']}f", $item->price)); ?>"
					       min="0.00"
					       placeholder="0.00"
                           autocomplete="off"/>
				</td>
	            <td class='resolution_column'>
					<input class="resolution_field"
					       type="number"
					       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][resolution]"
					       value="<?php echo esc_attr($item->resolution); ?>"
					       min="0"
					       placeholder="<?php echo esc_attr($i18n->resolution_placeholder); ?>"/> px
				</td>
				<td>
	                <i class="fa fa-times-circle delete_item" data-id="<?php echo esc_attr($item->ID); ?>" data-table-id="digital_downloads"></i>
				</td>
			</tr>
		<?php } ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4"><p class="no_items <?php if (!empty($items)) { ?>hidden<?php } ?>"><?php echo esc_html($i18n->no_items); ?></p></td>
		</tr>
	</tfoot>
<?php // The </table> is provided by the parent template ?>
