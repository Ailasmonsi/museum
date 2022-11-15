<?php
/**
 * @var $i18n stdClass
 */ ?>
<td class='nggpl-quantity_field nggpl-download-quantity_field'>
    <div class='nggpl-quantity_field_wrapper'>
        <button class="nggpl-add-download-button"
                data-free-text="<?php esc_attr_e($i18n->download_free); ?>"
                data-add-text="<?php esc_attr_e($i18n->download_add); ?>"
                data-remove-text="<?php esc_attr_e($i18n->download_remove); ?>">
            <?php esc_html_e($i18n->download_add); ?>
        </button>
    </div>
</td>
<td class='nggpl-description_field'></td>
<td class='nggpl-price_field' data-free-label="<?php esc_attr_e($i18n->free_price); ?>"></td>
<td class='nggpl-total_field'></td>