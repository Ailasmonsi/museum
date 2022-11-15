<?php /** @var stdClass $i18n
        * @var string $stripe_vars
        */ ?>
<style type="text/css">
    #stripe-checkout-button {
        height: 48px;
    }
</style>
<span id="stripe-checkout-button">
    <script>
        window.ngg_stripe_vars = <?php echo $stripe_vars ?>;
    </script>
	<button class="stripe-button-el ngg_pro_btn" type="submit" name="ngg_pro_checkout" value="stripe_checkout">
		<span><?php  echo esc_html($i18n->pay_with_card)?></span>
	</button>
</span>