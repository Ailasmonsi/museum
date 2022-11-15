<tr id='tr_<?php print esc_attr("{$display_type_name}_{$name}"); ?>'
    class='<?php print !empty($hidden) ? 'hidden' : ''; ?>'>
    <td>
        <label for="<?php print esc_attr("{$display_type_name}_{$name}"); ?>"
               <?php if (!empty($text) && !$isValid) { ?>title='<?php print esc_attr($text); ?>'<?php } ?>
               <?php if (!empty($text) && !$isValid) { ?>class='tooltip'<?php } ?>>
            <?php print $label; ?>
        </label>
    </td>
    <td>
        <input type="radio"
               id="<?php print esc_attr($display_type_name . '_' . $name); ?>"
               name="<?php print esc_attr($display_type_name . '[' . $name . ']'); ?>"
               class="<?php print esc_attr($display_type_name . '_' . $name); ?>"
               value="1"
               <?php if (!$isValid) { ?>disabled<?php } ?>
            <?php checked(True, !empty($value)); ?>/>
        <label for="<?php print esc_attr($display_type_name . '_' . $name); ?>"><?php _e('Yes'); ?></label>
        &nbsp;
        <input type="radio"
               id="<?php print esc_attr($display_type_name . '_' . $name); ?>_no"
               name="<?php print esc_attr($display_type_name . '[' . $name . ']'); ?>"
               class="<?php print esc_attr($display_type_name . '_' . $name); ?>"
               value="0"
               <?php if (!$isValid) { ?>disabled<?php } ?>
            <?php checked(True, empty($value)); ?>/>
        <label for="<?php print esc_attr($display_type_name . '_' . $name); ?>_no"><?php _e('No'); ?></label>
    </td>
    
</tr>
<tr>
    <td colspan="2">
        <p class="sales-tax-note"><?php print esc_attr($tax1); ?><a href="https://www.imagely.com/docs/intro-to-sales-tax/" target="_blank" style="font-weight:bold;"><?php print esc_attr($tax2); ?></a><?php print esc_attr($tax3); ?></p>
    </td>
</tr>