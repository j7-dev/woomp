<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-invoice.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0.0
 */
?>
</div>
<div class="woocommerce-invoice-fields">
    <h3><?=__('Invoice info', 'ry-woocommerce-ecpay-invoice') ?>
    </h3>
    <div class="woocommerce-invoice-fields__field-wrapper">
        <?php
        $fields = $checkout->get_checkout_fields('invoice');
        foreach ($fields as $key => $field) {
            woocommerce_form_field($key, $field, $checkout->get_value($key));
        }
        ?>
    </div>
