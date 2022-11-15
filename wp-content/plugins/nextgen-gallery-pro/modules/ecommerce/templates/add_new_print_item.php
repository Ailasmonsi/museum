<div class="catalog-dialog"
     id="print_catalog_dialog">
    <div class="catalog-wrap">
        <div class="catalog-container">
            <form>
                <div class="catalog-tabs-container">
                    <ul class="catalog-categories">
                        <?php
                        /**
                         * @var $catalog C_NextGEN_Printlab_Catalog
                         */
                        $categories = $catalog->get_root_categories();

                        $settings                  = C_NextGen_Settings::get_instance();
                        $hide_cost_estimate        = $settings->ecommerce_currency == $catalog->_currency;
                        $transient_error_name      = C_NextGen_Pro_Currencies::get_conversion_error_transient_name($catalog->currency, $settings->ecommerce_currency);
                        $currency_conversion_error = get_transient($transient_error_name);
                        if ($currency_conversion_error !== FALSE)
                            $hide_cost_estimate = TRUE;

                        foreach ($categories as $category_id) {
                            $category = $catalog->get_category_info($category_id);
                            $products = $catalog->get_category_items($category_id);
                            $category_html_id = preg_replace('/[\\.\\:\\s]/i', '_', $category['gid']);
	
                            if ($products == null)
                                continue;
                            ?>

                            <li class="catalog-category">
                                <a href="<?php echo esc_attr('#catalog-category-' . $category_html_id); ?>"><?php esc_html_e($category['label']); ?></a>
                            </li>

                        <?php } ?>
                    </ul>
                    <div class="catalog-panel-container">
                        <?php
                        foreach ($categories as $category_id) {
                            $category = $catalog->get_category_info($category_id);
                            $products = $catalog->get_category_items($category_id);
                            $category_html_id = preg_replace('/[\\.\\:\\s]/i', '_', $category['gid']);
	
                            if ($products == null)
                                continue;
                            ?>
                            <div id="<?php echo esc_attr('catalog-category-' . $category_html_id); ?>" class="catalog-panel">
                                <div class="items-container">
                                    <table class="items-table <?php if ($hide_cost_estimate) { print "hide-cost-estimate"; } ?>">
                                        <thead>
                                            <tr class="item-header">
                                                <th class="item-label"><?php _e('Product', 'nextgen-gallery-pro'); ?></th>
                                                <th class="item-cost"><?php _e('Cost', 'nextgen-gallery-pro'); ?></th>
                                                <th class="item-cost-estimate"><?php _e('Estimated Cost', 'nextgen-gallery-pro'); ?></th>
                                                <th class="item-added"><?php _e('Add', 'nextgen-gallery-pro'); ?><input id="category-item-add-<?php echo esc_attr($category_html_id); ?>" class="item-check" value="1" name="products_all_check[<?php echo $category_html_id; ?>]" data-category-id="<?php echo esc_attr($category['id']); ?>" data-checkbox-role="individual" type="checkbox" />
                                                    <label class="check-all"><i class="fa fa-check"></i></label></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            /**
                                             * @var $product C_NextGEN_Printlab_Item
                                             */
                                            foreach ($products as $product) {
                                                $product_id = strval($product->id);
                                                ?>
                                                <tr class="item-content item-group<?php echo $product->is_default() ? ' item-default' : ''; ?>"
                                                    data-item-prefix="product_<?php echo esc_attr($product_id); ?>_">
                                                    <td class="item-label"><?php esc_html_e($product->label); ?>
                                                        <input class="title_field"
                                                               type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][title]"
                                                               value="<?php echo esc_attr($product->label); ?>"
                                                               data-field-name="title"/>
                                                        <input class="category_field"
                                                               type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][category]"
                                                               value="<?php echo esc_attr($category['ngg_id']); ?>"
                                                               data-field-name="category"/>
                                                        <input type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][source_data][product_id]"
                                                               value="<?php echo esc_attr($product_id); ?>"/>
                                                        <input type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][source_data][catalog_id]"
                                                               value="<?php echo esc_attr($catalog->id); ?>"/>
                                                    </td>
                                                    <td class="item-cost"><?php echo($product->get_cost_display()); ?>
                                                        <input class="cost_field"
                                                               type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][cost]"
                                                               value="<?php echo esc_attr(M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol($product->get_cost())); ?>"
                                                               data-field-name="cost"/>
                                                    </td>
                                                    <td class="item-cost-estimate"><?php echo($product->get_cost_estimate_display()); ?>
                                                        <input class="price_field"
                                                               type="hidden"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][price]"
                                                               value="<?php echo esc_attr($product->get_cost_estimate()); ?>"
                                                               data-field-name="price"/>
                                                    </td>
                                                    <td class="item-added">
                                                        <input class="item-check"
                                                               id="item-add-<?php echo esc_attr($product_id); ?>"
                                                               value="1"
                                                               name="product_<?php echo esc_attr($product_id); ?>_pricelist_item[{id}][added]"
                                                               <?php echo $product->is_default() ? 'checked="checked"' : ''; ?>
                                                               data-category-id="<?php echo esc_attr($category['id']); ?>"
                                                               data-parent-product-id="<?php echo esc_attr($product_id); ?>"
                                                               data-checkbox-role="individual"
                                                               type="checkbox"
                                                               data-field-name="included"/>
                                                        <label for="item-add-<?php echo esc_attr($product_id); ?>"><i class="fa fa-check"></i></label>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>