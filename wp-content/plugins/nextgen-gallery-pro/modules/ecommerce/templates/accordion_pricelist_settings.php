<?php
// TODO: When we add legit settings for a pricelist
// we can remove this JS
?>
<script type='text/javascript'>
jQuery('#ngg_manual_pricelist').hide()
jQuery('#ngg_manual_pricelist_content table').hide()
jQuery('#ngg_manual_pricelist_content').css({
	'visibility': 'hidden',
	'height': '0px',
	'padding': '0px',
	'border': '0px'
})
</script>

<span class="hidden">
				<input type="hidden"
					   name="pricelist[settings][bulk_markup_amount]"
					   value="<?php echo esc_attr(isset($settings['bulk_markup_amount']) ? $settings['bulk_markup_amount'] : '400'); ?>"/>
				<input type="hidden"
					   name="pricelist[settings][bulk_markup_rounding]"
					   value="<?php echo esc_attr(isset($settings['bulk_markup_rounding']) ? $settings['bulk_markup_rounding'] : 'none'); ?>"/></span>
