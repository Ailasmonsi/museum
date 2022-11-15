<p><?php echo ($i18n->faq1) ?></p>
<p><?php echo ($i18n->faq3) ?></p>
<p><?php echo ($i18n->faq2) ?></p>
<p><?php echo ($i18n->agreement) ?></p>

<?php if (!$is_ssl) { ?>
    <p><?php echo $i18n->non_https_warning; ?></p>
<?php } ?>

	<div class="ngg-stripe-status">
		<?php if ($last_4_digits): ?>
			<?php if ($expired): ?>
	            <span class="stripe_connect_declined"><?php echo esc_html(sprintf($i18n->invalid_card, $last_4_digits)) ?></span>
			<?php else: ?>
	            <span class="stripe_connect_active"><?php echo esc_html(sprintf($i18n->valid_card, $last_4_digits)) ?></span>
			<?php endif ?>
		<?php else: ?>
	        <span class="stripe_connect_declined"><?php echo esc_html($i18n->no_card) ?></span>
		<?php endif ?>
	</div>

	<div id='ngg-stripe-form' class="form-row" style="display:none">
		<div id="card-element">
			<!-- a Stripe Element will be inserted here. -->
		</div>

		<?php if ($is_ssl): ?>
		<div class="btn"><a id='update-card' href="#" class="stripe-connect light-blue"><span>Update</span></a></div>
		<?php else: ?>
		<div class="btn"><a title="<?php esc_attr($i18n->btn_disabled)?>" id='update-card' href="#" class="stripe-connect-disabled stripe-connect light-blue"><span>Update</span></a></div>
		<?php endif ?>

		<!-- Used to display form errors -->
		<div id="card-errors" role="alert"></div>
	</div>
	<a data-nonce="<?php esc_attr_e($delete_nonce)?>" id="delete-stripe-card" href="#" style="display:none" class="stripe-connect light-blue"><span><?php esc_html_e($i18n->remove_card)?></span></a>
	<i class="fas fa-lock ngg-stripe-lock"></i>
