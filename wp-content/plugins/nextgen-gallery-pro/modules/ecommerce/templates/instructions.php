<div class="ngg-requirements">
    <p><?php echo ($i18n->intro) ?></p>
    <?php if ( $can_pro_wizard_run ): ?>
        <a data-ngg-wizard="nextgen.ecommerce.all_requirements"
           class="ngg-wizard-invoker button-primary"
           href="<?php echo esc_url(add_query_arg('ngg_wizard', 'nextgen.ecommerce.all_requirements'))?>">
            <?php esc_html_e('Launch Ecommerce and Print Lab Setup Wizard', 'nextgen-gallery-pro')?>
        </a>
    <?php endif ?><br>
    <iframe id="ngg-print-lab-video" width="672" height="378" src="https://www.youtube.com/embed/Gi5iZpisyeI" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    <h2>Requirement Checklist</h2>
    <form method="POST" action="/">
        <input type="hidden" id="ngg-status-nonce" name="ngg-status-nonce" value="<?php esc_attr_e($nonce)?>"/>
        <input type="button" id="check-ngg-status" value="<?php echo esc_attr($i18n->check_now)?>" class="button-secondary"/>
    </form>
    <p><?php echo ($i18n->ecom_requirements) ?><br> 
        <span style="font-style:italic;"><?php echo ($i18n->ecom_colors) ?></span><br></p>
    <ul>
        <?php foreach ($ecommerce_steps as $step_id => $step_label): ?>
        <li id="<?php echo esc_attr($step_id)?>">
            <?php $render_status($status, $step_id, 'unknown')?>
            <?php echo $step_label ?>
        </li>
        <?php endforeach ?>
    </ul>
    <p style="margin-top: 50px;"><?php echo ($i18n->print_requirements) ?></p>
    <ul class="ngg-requirements">
        <?php foreach ($printlab_steps as $step_id => $step_label): ?>
        <li id="<?php echo esc_attr($step_id) ?>">
	        <?php $render_status($status, $step_id, 'unknown')?>
	        <?php echo $step_label ?>
        </li>
        <?php endforeach ?>
    </ul>
    <h3 style="text-transform: uppercase; margin-top: 100px;"><?php echo ($i18n->additional_documentation)?></h3>
    <ul>
        <?php foreach ($i18n->documentation_links as $link => $label): ?>
            <li><a target='_blank' href="<?php echo esc_attr($link)?>"><?php esc_html_e($label)?></a></li>
        <?php endforeach ?>
    </ul>
</div>
<script type="text/javascript">
    jQuery(function($){
        $('.open_tab').on('click', function(e) {
            e.preventDefault();
            $('#' + $(this).attr('rel')).trigger('click');
        });
    });
</script>