<?php
/**
 * @var stdClass $i18n
 */
?>
<div id="ngg-pro-paypal-checkout-wrapper">
    <button class="ngg_pro_btn"
            id="ngg-pro-paypal-checkout-dummy"
            type="submit"
            name="ngg_pro_checkout"
            value="paypal_checkout">
        <span><?php print $i18n->pay_with_card; ?></span>
    </button>
    <div id="paypal-button-container"></div>
</div>