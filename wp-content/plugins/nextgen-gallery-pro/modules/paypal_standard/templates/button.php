<?php
/**
 * @var string $cancel_url
 * @var string $continue_shopping_url
 * @var string $currency
 * @var string $email
 * @var string $notify_url
 * @var string $paypal_url
 * @var string $processing_msg
 * @var string $return_url
 * @var string $value
 */ ?>
<a href="javascript:void(0)"
   id="ngg_paypal_standard_button"
   data-processing-msg="<?php echo esc_attr($processing_msg)?>"
   data-submit-msg="<?php echo esc_attr($value)?>"
   class="ngg_pro_btn paypal"
   data-business-email='<?php echo esc_attr($email); ?>'
   data-cancel-url = '<?php echo esc_attr($cancel_url); ?>'
   data-continue-shopping-url='<?php echo esc_attr($continue_shopping_url); ?>'
   data-currency-code='<?php echo esc_attr($currency);  ?>'
   data-notify-url='<?php echo esc_attr($notify_url); ?>'
   data-paypal-url='<?php echo esc_attr($paypal_url); ?>'
   data-return-url = '<?php echo esc_attr($return_url); ?>'><?php esc_html_e($value); ?></a>