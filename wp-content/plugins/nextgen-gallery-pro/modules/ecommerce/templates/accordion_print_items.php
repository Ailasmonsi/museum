<?php
/** @var array $cost_currency */
/** @var string $cost_currency_id */
/** @var float $cost_step */
/** @var object $i18n */
/** @var string $item_category */
/** @var array $items */
/** @var array $price_currency */
/** @var string $price_currency_id */
/** @var float $price_step */
/** @var C_NextGEN_Printlab_Manager $printlab_manager */
/** @var bool $show_alt_headers */
/** @var C_Pricelist_Item_Mapper $item_mapper */
?>
<script type="ngg-template"
        data-table-id="pricelist_category_<?php echo esc_attr($item_category); ?>"
        data-category-id="<?php echo esc_attr($item_category); ?>">
    <tr id="pricelist_category_item_{id}" class='item pricelist_category_item item_{id}'>
        <td class="pricelist_sort_handle">
            <i class="fa fa-sort" aria-hidden="true"></i>
        </td>
        <td class="pricelist_item_column">
            <input type='hidden' name='pricelist_item[{id}][sortorder]' class="pricelist_item_hidden_sortorder"/>
            <input type='hidden' name='pricelist_item[{id}][source]'    value=''/><?php /* this is filled in by the JS script when adding items */ ?>
            <input type='hidden' name='pricelist_item[{id}][category]'  value='<?php echo esc_attr($item_category); ?>'/>
            <input class="title_field"
                   type="text"
                   name="pricelist_item[{id}][title]"
                   value=""
                   placeholder="<?php echo esc_attr($i18n->item_title_placeholder); ?>"/>
        </td>
        <td class="cost_column">
            <div class="cost_column_wrapper">
                <?php
                echo A_Print_Category_Form::render_price_field(array(
                    'class'         => 'cost_field',
                    'type'          => 'number',
                    'name'          => 'pricelist_item[{id}][cost]',
                    'value'         => '',
                    'placeholder'   => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol(0.00, $cost_currency_id),
                    'readonly'      => 'readonly',
                ), $cost_currency_id);
                ?>
            </div>
        </td>
        <td class="price_column">
            <div class="price_column_wrapper">
                <?php
                echo A_Print_Category_Form::render_price_field(array(
                    'class'         => 'price_field',
                    'type'          => 'number',
                    'name'          => 'pricelist_item[{id}][price]',
                    'value'         => '',
                    'placeholder'   => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol(0.00, $price_currency_id),
                    'min'           => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol(0.00, $price_currency_id),
                    'step'          => $price_step
                ));
                ?>
            </div>
        </td>
        <td class="delete_column">
            <i class="fa fa-times-circle delete_item"
               data-id="{id}"
               data-table-id="pricelist_category_<?php echo esc_attr($item_category); ?>"></i>
        </td>
    </tr>
</script>
<thead class="pricelist_category_collection">
    <tr>
        <th class="sort_column"></th>
        <th class="title_column"><?php echo esc_html($i18n->name_header); ?></th>
        <th class="cost_column">
            <?php
            if (!$show_alt_headers) {
                print esc_html($i18n->cost_header);
            } else { ?>
                <span title="<?php printf($i18n->cost_header_tooltip, $price_currency['name']); ?>" class="tooltip">
                    <?php print esc_html(sprintf($i18n->cost_header_alt, $cost_currency['code'])); ?>
                </span>
            <?php } ?>
        </th>
        <th class="price_column">
            <?php
            if (!$show_alt_headers) {
                print esc_html($i18n->price_header);
            } else { ?>
                <span title="<?php printf($i18n->price_header_tooltip, $price_currency['name']); ?>" class="tooltip">
                    <?php print esc_html(sprintf($i18n->price_header_alt, $price_currency['code'])); ?>
                </span>
            <?php } ?>
        </th>
        <th class="delete_column"></th>
    </tr>
</thead>
<tbody id="pricelist_category_<?php echo esc_attr($item_category); ?>"
       class="pricelist_category_collection">  
    <?php foreach ($items as $item) { ?>
        <?php 
            $item_cost = 0.0; 
            $source_data = isset($item->source_data) && $item->source_data != null ? $item->source_data : array();
        ?>
        <tr data-catalog-id="<?php echo esc_attr(isset($source_data['catalog_id']) ? $source_data['catalog_id'] : ''); ?>" data-product-id="<?php echo esc_attr(isset($source_data['product_id']) ? $source_data['product_id'] : ''); ?>" class='item pricelist_category_item item_<?php echo esc_attr($item->ID); ?>'
            id="pricelist_category_item_<?php echo $item->ID; ?>">
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

                <?php foreach ($source_data as $data_name => $data_value) { ?>
                    <?php if (in_array($data_name, array('catalog_id', 'product_id'))) { ?>
                        <input type="hidden"
                               class="pricelist_item_hidden_source_data"
                               name="pricelist_item[<?php echo esc_attr($item->ID); ?>][source_data][<?php echo esc_attr($data_name); ?>]"
                               value="<?php echo esc_attr($data_value); ?>"/>
                    <?php } ?>
                <?php } ?>

                <input type="hidden"
                       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][category]"
                       value="<?php echo esc_attr($item->category); ?>"/>
                <input class="title_field"
                       type="text"
                       name="pricelist_item[<?php echo esc_attr($item->ID); ?>][title]"
                       value="<?php echo esc_attr($item->title); ?>"
                       placeholder="<?php echo esc_attr($i18n->item_title_placeholder); ?>"/>
            </td>
            <td class="cost_column">
                <div class="cost_column_wrapper">
                    <?php // Only show cost for catalog items ?>
                    <?php if (C_Pricelist_Source_Manager::get_instance()->get($item->source, 'lab_fulfilled')) { ?>
                        <?php
                            $item_cost = 0;
                            if ($printlab_manager && isset($source_data['product_id']) && isset($source_data['catalog_id']))
                            {
                                $catalog = $printlab_manager->get_catalog($source_data['catalog_id']);
                                if (($product = $catalog->find_product($source_data['product_id'])))
                                    $item_cost = $product->get_cost_estimate();
                            }
                            echo A_Print_Category_Form::render_price_field(array(
                                'class'       => 'cost_field',
                                'type'        => 'number',
                                'name'        => "pricelist_item[{$item->ID}][cost]",
                                'value'       => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol($item_cost, $cost_currency_id),
                                'placeholder' => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol(0.00, $cost_currency_id),
                                'readonly'    => 'readonly',
                                'step'        => $cost_step
                            ), $cost_currency_id);
                        ?>
                    <?php } ?>
                </div>
            </td>
            <td class="price_column">
                <div class="price_column_wrapper">
                    <?php
                    echo A_Print_Category_Form::render_price_field(array(
                        'class'       => 'price_field',
                        'type'        => 'number',
                        'name'        => "pricelist_item[{$item->ID}][price]",
                        'value'       => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol($item->price, $price_currency_id),
                        'placeholder' => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol(0.00, $price_currency_id),
                        'min'         => M_NextGen_Pro_Ecommerce::get_formatted_price_without_symbol($item_cost, $price_currency_id),
                        'step'        => $price_step
                    ));
                    ?>
                </div>
            </td>
            <td class="delete_column">
                <i class="fa fa-times-circle delete_item"
                   data-id="<?php echo esc_attr($item->ID); ?>"
                   data-table-id="pricelist_category_<?php echo esc_attr($item_category); ?>"></i>
            </td>
        </tr>
    <?php } ?>
</tbody>
<tfoot>
    <tr>
        <td colspan="3">
            <p class="no_items <?php if (!empty($items)) { ?>hidden<?php } ?>"><?php echo esc_html($i18n->no_items); ?></p>
        </td>
    </tr>
</tfoot>