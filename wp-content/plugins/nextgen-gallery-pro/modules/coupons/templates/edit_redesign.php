<?php if ($errors): ?>
        <?php foreach ($errors as $msg): ?>
            <?php echo $msg ?>
        <?php endforeach ?>
    <?php endif ?>
    <?php if ($success AND empty($errors)): ?>
        <div class='success updated'>
            <p><?php esc_html_e($success);?></p>
        </div>
<?php endif ?>

<div class="wrap ngg_manage_coupon" id='ngg_page_content' style='position: relative; visibility: hidden;'>

    <div class="ngg_page_content_header "><img src='<?php esc_html_e($logo) ?>' class='ngg_admin_icon'><h3><?php esc_html_e($page_heading)?></h3></div>
    
    <div class="ngg_page_content_main"">

        <form method="POST" action="<?php echo nextgen_esc_url($_SERVER['REQUEST_URI'])?>">
            <input type="hidden" name="coupon[ID]" value="<?php echo esc_attr($model->id()) ?>"/>
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>"/>
            <br/>
            <div id="titlediv">
                <div id="titlewrap">
                    <input type="text" placeholder='Title' autocomplete="off" id="title" value="<?php echo esc_attr($model->title)?>" size="30" name="coupon[title]">
                </div>
            </div>
            <?php if (isset($form_header)) { ?>
                <?php echo $form_header . "\n"; ?>
            <?php } ?>
            <input type="hidden" name="action"/>

            <div class="accordion" id="nextgen_admin_accordion">
                <?php foreach ($tabs as $tab) {
                    echo $tab;
                } ?>
            </div>

            <?php if ($show_save_button) { ?>
                <p>
                    <button type="submit"
                            id='save_btn'
                            name='action_proxy'
                            value="save"
                            class="button-primary">
                        <?php _e('Save', 'nextgen-gallery-pro'); ?>
                    </button>
                    <input type="submit"
                           value="<?php _e('Cancel', 'nextgen-gallery-pro'); ?>"
                           id="cancel_btn"
                           class="button-primary"
                           data-redirect="<?php echo admin_url('/edit.php?post_type=ngg_coupon')?>"/>
                </p>
            <?php } ?>
        </form>

    </div>

</div>
<script type="text/javascript">
    jQuery(function($){
        $('#cancel_btn').on('click', function(e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
            e.preventDefault();
            window.location = $(this).attr('data-redirect');
            return false;
        });
    });
</script>