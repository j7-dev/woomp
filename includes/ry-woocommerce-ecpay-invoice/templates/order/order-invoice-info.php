<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-invoice-info.php
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

<h2 class="woocommerce-column__title">
    <?=__('Invoice info', 'ry-woocommerce-ecpay-invoice') ?>
</h2>

<table class="woocommerce-table woocommerce-table--invoice-info shop_table invoice-info">
    <tbody>
        <?php foreach ($invoice_info as $info) { ?>
        <tr>
            <td class="woocommerce-table__<?=$info['key'] ?> <?=$info['key'] ?>">
                <?=$info['name'] ?>
            </td>
            <td class="woocommerce-table__<?=$info['key'] ?> <?=$info['key'] ?>">
                <?=$info['value'] ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
