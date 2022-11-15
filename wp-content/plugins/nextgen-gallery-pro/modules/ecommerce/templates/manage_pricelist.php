<?php
/**
 * @var array $pricelist_sources
 * @var array $settings
 * @var string $page_heading
 * @var string $success
 * @var array $errors
 * @var string $wrap_css_class
 * @var string $logo
 * @var C_Pricelist $model
 * @var bool $show_save_button
 */
?>
<?php // The "Add Product" source selector popup ?>
<div id="new_product_parent" style="display: none;">
    <div id="new_product_wrapper">
        <div id="new_product_source_list">
            <ul>
				<?php foreach ($pricelist_sources as $source_id => $source) { ?>
                    <li class="product-source-item <?php echo esc_attr($source_id); ?>"
                        data-source-id="<?php echo esc_attr($source_id); ?>"
                        data-source-title="<?php echo esc_attr($source['title']); ?>">
                        <span class="new_product_source_title">
                            <?php echo $source['title']; ?>
                        </span>
                        <span class="new_product_source_description">
                            <?php echo $source['description']; ?>
                        </span>
                    </li>
				<?php } ?>
            </ul>
        </div>
        <div id="new_product_source_form"></div>
        <div id="new_product_buttons_wrapper">
			<?php // It's important the name attribute exist or nextgen_admin_page.js will generate errors ?>
            <button id="new_product_button_add"
                    type="submit"
                    class="button-primary"
                    name="">
				<?php _e('Add product', 'nextgen-gallery-pro'); ?>
            </button>
            <button id="new_product_button_cancel"
                    class="button-secondary dialog-cancel"
                    name="">
				<?php _e('Cancel', 'nextgen-gallery-pro'); ?>
            </button>
        </div>
    </div>
</div>
<div id="bulk_markup_dialog" class="bulk-markup-dialog" style="display: none;">
    <div class="bulk-markup-wrap">
        <form>
            <div class="new_pricelist_product_row">
                <span><?php _e('Markup Percent', 'nextgen-gallery-pro'); ?></span>
                <input class="percent_field"
                       type="text"
                       name="markup_percent"
                       value="<?php echo esc_attr(isset($settings['bulk_markup_amount']) ? $settings['bulk_markup_amount'] : '400'); ?>"
                       placeholder="400"
                       required/>
            </div>
            <div class="new_pricelist_product_row">
                <span><?php _e('Round prices up to', 'nextgen-gallery-pro'); ?></span>
                <select name="markup_rounding"
                        required>
					<?php $rounding = isset($settings['bulk_markup_rounding']) ? $settings['bulk_markup_rounding'] : "zero"; ?>
                    <option value="zero" <?php selected("zero", $rounding); ?>><?php _e('.00', 'nextgen-gallery-pro'); ?></option>
                    <option value="cent" <?php selected("cent", $rounding); ?>><?php _e('.99', 'nextgen-gallery-pro'); ?></option>
                    <option value="none" <?php selected("none", $rounding); ?>><?php _e('No Rounding', 'nextgen-gallery-pro'); ?></option>
                </select>
            </div>
            <div>
                <button id="bulk_markup_button_apply"
                        class="button-primary"
                        name="">
					<?php _e('Apply to all', 'nextgen-gallery-pro'); ?>
                </button>
                <button id="bulk_markup_button_cancel"
                        type="submit"
                        class="button-secondary dialog-cancel"
                        name="">
					<?php _e('Cancel', 'nextgen-gallery-pro'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php // The individual source 'add new item' templates ?>
<?php foreach ($pricelist_sources as $source_id => $source): ?>
	<?php if (empty($source['add_new_template'])) continue ?>
    <div class="new_pricelist_item_wrapper"
         data-source-id="<?php echo esc_attr($source_id); ?>"
         style="display: none;">
		<?php echo $source['add_new_template']; ?>
    </div>
<?php endforeach ?>

<?php // The manage pricelist page itself ?>
<div class="wrap ngg_manage_pricelist <?php esc_attr_e($wrap_css_class)?>" id='ngg_page_content'>
    <h2>
		<?php esc_html_e($page_heading); ?>
    </h2>
    <?php if ($errors): ?>
        <?php foreach ($errors as $msg): ?>
            <?php echo $msg ?>
        <?php endforeach ?>
    <?php endif ?>
    <?php if ($success AND empty($errors)): ?>
        <div class='success updated'>
            <p><?php esc_html_e($success);?></p>
        </div>
    <?php endif ?>

    <div class="ngg_page_content_header "><img src='<?php esc_html_e($logo) ?>' class='ngg_admin_icon'><h3><?php esc_html_e($page_heading)?></h3></div>

    <div class="ngg_page_content_main"">
        <div class="ngg_pricelist_actions">

            <?php // WordPress' version of thickbox is altered to always be 630px regardless of the setting here ?>
            <a title='<?php _e('Add Product', 'nextgen-gallery-pro'); ?>'
               class='thickbox page-title-action new_item'
               href='#TB_inline?width=300&height=280&inlineId=new_product_parent'>
                <?php _e('Add Product', 'nextgen-gallery-pro'); ?>
            </a>
            <a title='<?php _e('Apply Markup to All Products', 'nextgen-gallery-pro'); ?>'
               class='thickbox page-title-action new_item'
               style="margin-left: 1em;"
               href='#TB_inline?width=300&height=280&inlineId=bulk_markup_dialog'>
                <?php _e('Bulk Markup', 'nextgen-gallery-pro'); ?>
            </a>
        </div>
        <form id="ngg_pricelist_form" method="POST" action="<?php echo nextgen_esc_url($_SERVER['REQUEST_URI']); ?>">
            <input type="hidden"
                   name="pricelist[ID]"
                   value="<?php echo esc_attr($model->id()); ?>"/>
            <br/>
            <div id="titlediv">
                <div id="titlewrap">
                    <input type="text"
                           placeholder='<?php _e('Enter title here', 'nextgen-gallery-pro'); ?>'
                           autocomplete="off"
                           id="title"
                           value="<?php echo esc_attr($model->title); ?>"
                           size="30"
                           name="pricelist[title]"
                    />
                </div>
            </div>
            <?php if (isset($form_header)): ?>
                <?php echo $form_header."\n"; ?>
            <?php endif ?>
            <input type="hidden" name="action"/>
            <div class="accordion" id="nextgen_admin_accordion">
            <?php foreach($tabs as $tab): ?>
                <?php echo $tab ?>
            <?php endforeach ?>
            </div>
            <?php if ($show_save_button): ?>
                <p>
                    <button type="submit"
                            name='action_proxy'
                            class="button-primary ngg_save_pricelist_button"
                            data-executing="<?php _e('Saving...', 'nextgen-gallery-pro'); ?>"
                            value="Save">
                        <?php _e('Save', 'nextgen-gallery-pro'); ?>
                    </button>

                    <input type="submit"
                           value="<?php _e('Cancel', 'nextgen-gallery-pro'); ?>"
                           id="cancel_btn"
                           class="button-primary"
                           data-redirect="<?php echo admin_url('/edit.php?post_type=ngg_pricelist')?>"/>
                </p>
            <?php endif ?>
        </form>
    </div>
</div>
