<?php
/**
 * @var string $processing_msg
 * @var string $value
 */ ?>
<a href="javascript:void(0)"
   id="paypal_express_checkout_button"
   data-processing-msg="<?php echo esc_attr($processing_msg)?>"
   data-submit-msg="<?php echo esc_attr($value)?>"
   class="ngg_pro_btn paypal"><?php esc_html_e($value); ?></a>