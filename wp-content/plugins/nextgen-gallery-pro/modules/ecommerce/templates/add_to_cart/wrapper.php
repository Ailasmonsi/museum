<?php
/**
 * @var $i18n stdClass
 */
?>
<div id="nggpl-cart-static-header">
    <h2><?php esc_html_e($i18n->add_to_cart); ?></h2>
    <div class='nggpl-cart_summary'>
        <a href='#' class='nggpl-cart_count'></a>
        <span class='nggpl-cart_total'></span>
    </div>

    <div class='nggpl-sidebar-thumbnail'><img id='nggpl-sidebar-thumbnail-img' src=""/></div>

    <div id='nggpl-category-headers'></div>
    <div id='nggpl-updated-message'></div>
    
</div>

<div id='nggpl-items_for_sale'>
    <div class='nggpl-pricelist_category_wrapper'>
        <?php foreach($categories as $category) {
            print $category;
        } ?>
    </div>
    <div id='nggpl-cart_sidebar_checkout_buttons'>
        <div id='nggpl-cart_updated_wrapper'>
            <?php print $i18n->nggpl_cart_updated; ?>
        </div>
        <input class='nggpl-button'
               type='button'
               id='ngg_update_cart_btn'
               value='<?php echo esc_attr($i18n->update_cart); ?>'
               data-update-string='<?php echo esc_attr($i18n->update_cart); ?>'
               data-add-string='<?php echo esc_attr($i18n->add_to_cart); ?>'/>
        <input class='nggpl-button'
               type='button'
               id='ngg_checkout_btn'
               value='<?php echo esc_attr($i18n->checkout); ?>'/>
    </div>
</div>

<div id='nggpl-not_for_sale'>
    <?php esc_html_e($not_for_sale_msg); ?>
</div>