<?php
/**
 * @var $i18n stdClass
 */ ?>
<td class='nggpl-quantity_field'>
    <div class='nggpl-quantity_field_wrapper'>
        <i class="fa fa-minus"></i>
        <input type='number' value='0' min='0' step='1' max='999' pattern="[0-9]*"/>
        <i class="fa fa-plus"></i>
    </div>
</td>
<td class='nggpl-description_field'></td>
<td class='nggpl-price_field' data-free-label="<?php esc_attr_e($i18n->free_price); ?>"></td>
<td class='nggpl-total_field'></td>