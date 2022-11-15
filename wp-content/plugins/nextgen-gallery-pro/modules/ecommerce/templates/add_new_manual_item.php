<?php // The name, price, and category fields are required. {id} will be replaced in JS with a random number ?>
<?php // data-field-name is necessary for manage_pricelist_page.js to parse the category ?>
<?php // manage_pricelist.php wraps the following in div.new_pricelist_item_wrapper ?>
<form> <?php // no id is necessary, manage_pricelist_page.js will look for the form belonging to the above wrapper ?>
    <div class="new_pricelist_product_row">
        <span><?php echo $i18n['new_product_name']; ?></span>
        <input class="title_field"
               type="text"
               name="pricelist_item[{id}][title]"
               value=""
               placeholder="<?php echo esc_attr($i18n['new_product_name_hint']); ?>"
               data-field-name="title"
               required/>
    </div>
    <div class="new_pricelist_product_row half_row">
        <div class="new_pricelist_product_half_row">
            <span><?php echo $i18n['new_product_price']; ?></span>
            <input class='price_field'
                   type="number"
                   name="pricelist_item[{id}][price]"
                   value=""
                   min="0.00"
                   step="0.01"
                   placeholder="0.00"
                   data-field-name="price"
                   required/>
        </div>
        <div class="new_pricelist_product_half_row">
            <span><?php echo $i18n['new_product_category']; ?></span>
            <select name="pricelist_item[{id}][category]"
                    data-field-name="category"
                    required>
                <?php foreach ($categories as $category_id => $category) { ?>
                    <option value="<?php echo esc_attr($category_id); ?>"><?php echo $category['title']; ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
</form>