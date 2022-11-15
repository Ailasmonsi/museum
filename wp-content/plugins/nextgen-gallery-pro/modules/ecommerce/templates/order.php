<?php
 /**
 * @var stdClass $i18n
 * @var array $images
 * @var int $currency
 */
 ?>
<div class="ngg_pro_order_info">
    <script type="text/javascript">
        (function($) {
            window.fixBrokenImage = function(img) {
                var width 	= $(img).attr('width');
                var height	= $(img).attr('height'); 
                var dummyUrl = "https://dummyimage.com/" + width + "x" + height + "/000/fff.jpg&text=Image+not+found";
                img.src = dummyUrl;
                return true;
            };

            var position_crop_previews = function() {
                var $items = $(".order-table .ngg_order_image_column");
                $items.each(function (index) {
                    $el = $(this);

                    var $thumbCont = $el.find('.thumbnail-container');
                    var $thumb = $thumbCont.find('img');
                    var thumbWidth = $thumb.width();
                    var thumbHeight = $thumb.height();
                    var imgWidth = $thumb.data('fullWidth');
                    var imgHeight = $thumb.data('fullHeight');
                    var crop_offset = $thumb.data('cropOffset');
                    var cropPoints = crop_offset ? crop_offset.split(',').map(function (str) { return parseInt(str.trim(), 10); }) : [];

                    if (cropPoints.length < 4) {
                        return;
                    } else {
                        cropPoints = cropPoints.slice(0,4);
                    }

                    var $crop_preview = $thumbCont.find('.crop-preview');

                    if ($crop_preview.length == 0) {
                        $crop_preview = $('<div class="crop-preview" />').appendTo($thumbCont);
                    }

                    var ratioX = thumbWidth / imgWidth;
                    var ratioY = thumbHeight / imgHeight;
                    if (Math.abs(ratioY) < /* */ 0.0001) {
                        ratioY = ratioX;
                    } else if (Math.abs(ratioX) < /* */ 0.0001) {
                        ratioX = ratioY;
                    }

                    var left = cropPoints[0] * ratioX;
                    var top = cropPoints[1] * ratioY;
                    var width = Math.ceil(((cropPoints[2] - cropPoints[0]) * ratioX));
                    var height = Math.ceil(((cropPoints[3] - cropPoints[1]) * ratioY));

                    var borderWidth = parseInt($crop_preview.css("border-left-width"), 10);

                    top = top + borderWidth;
                    width = width - (borderWidth * 2);
                    height = height - (borderWidth * 2);

                    $crop_preview.css({
                        'left': left,
                        'top': top
                    });
                    $crop_preview.width(width);
                    $crop_preview.height(height);
                });
            };

            $(window).on('load refreshed resize orientationchange', position_crop_previews);
        })(jQuery);
    </script>
    <table class="order-table">
        <thead>
        <tr>
            <th><?php esc_html_e($i18n->image)?></th>
            <th><?php esc_html_e($i18n->quantity)?></th>
            <th><?php esc_html_e($i18n->description)?></th>
            <th class="ngg_order_price_column"><?php esc_html_e($i18n->price)?></th>
            <th><?php esc_html_e($i18n->total)?></th>
        </tr>
        </thead>
        <tbody>
        <tr class='ngg_order_separator'>
            <td colspan="4"></td>
        </tr>
        <?php foreach ($images as $image): ?>
            <?php foreach ($image->items as $source => $source_pricelists): ?>
                <?php foreach ($source_pricelists as $pricelist_id => $items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="ngg_order_image_column">
                                <div class="thumbnail-container">
                                    <img src="<?php echo esc_attr($image->thumbnail_url)?>"
                                        alt="<?php echo esc_attr($image->alttext)?>"
                                        width="<?php echo esc_attr($image->dimensions['width'])?>"
                                        height="<?php echo esc_attr($image->dimensions['height'])?>"
                                        data-full-width="<?php echo esc_attr($image->crop_dimensions['width'])?>"
                                        data-full-height="<?php echo esc_attr($image->crop_dimensions['height'])?>"
                                        data-crop-offset="<?php echo esc_attr($item->crop_offset)?>"
                                        onerror="fixBrokenImage(this);"/>
                                </div>
                                <?php if (current_user_can('manage_options')) { ?>
                                    <span class='ngg_order_image_filename'
                                        style="max-width: <?php echo esc_attr($image->dimensions['width'])?>px">
                                        <?php esc_html_e($image->filename); ?>
                                    </span>
                                <?php } ?>
                            </td>
                            <td>
                                <span><?php esc_html_e($item->quantity)?></span>
                            </td>
                            <td>
                                <?php esc_html_e($item->title)?>
                            </td>
                            <td class='ngg_order_price_column'>
                                <?php echo(M_NextGen_Pro_Ecommerce::get_formatted_price($item->price, $currency)) ?>
                            </td>
                            <td>
                                <?php // TODO: replace this (price * quanity) with bcmath ?>
                                <?php echo(M_NextGen_Pro_Ecommerce::get_formatted_price($item->price * $item->quantity, $currency))?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php endforeach ?>
            <?php endforeach ?>
        <?php endforeach ?>
    </table>
</div>