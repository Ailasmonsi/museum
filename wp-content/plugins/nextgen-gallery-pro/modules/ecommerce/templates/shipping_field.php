<tr>
	<td class="label_column">
        <?php
        $label_class = isset($tooltip) ? 'tooltip' : '';
        $tooltip = isset($tooltip) ? $tooltip : '';
        ?>
		<label class="<?php esc_attr_e($label_class)?>" for="<?php esc_attr_e($display_type_name . '_' . $name); ?>" title="<?php esc_attr_e($tooltip)?>"><?php echo esc_html($label); ?></label>
	</td>
	<td>
		<select id="<?php esc_attr_e($display_type_name . '_' . $name); ?>"
            name="<?php print esc_attr($display_type_name . '[' . $name . ']'); ?>"
            class="<?php print esc_attr($display_type_name . '_' . $name); ?>"
            style="width:50%;">
			<?php
			foreach ($options as $opt_value => $opt_label) { ?>
				<option <?php selected($value, $opt_value); ?> value="<?php echo esc_attr($opt_value) ?>"><?php echo esc_html($opt_label); ?></option>
			<?php } ?>
		</select>
		<input type="text"
						id="<?php esc_attr_e($display_type_name . '_' . $name_amount); ?>"
            name="<?php print esc_attr($display_type_name . '[' . $name_amount . ']'); ?>"
            class="<?php print esc_attr($display_type_name . '_' . $name_amount); ?> show_on_flat_rate show_on_percent_rate"
			   value="<?php echo esc_attr($value_amount); ?>"
            style="width:30%;"/>
			<?php
			foreach ($options_pieces as $piece_value => $piece_label) { ?>
    		<span class="show_on_<?php esc_attr_e($piece_value) ?>"><?php esc_html_e($piece_label); ?></span>
			<?php } ?>
	</td>
</tr>
