<?php
/**
 * @var $i18n array
 * @var $hidden bool
 * @var $secret_is_known bool
 */
?>
<tr id="tr_paypal_checkout_instructions" class="<?php if ($hidden) print 'hidden'; ?>">
    <td colspan="2">
        <div id="ngg_pro_paypal_checkout_instructions">
            <p><?php print $i18n['setup_instructions']; ?></p>
        </div>
    </td>
</tr>