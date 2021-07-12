<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-smilepay-shipping-info.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.2.2
 */
defined('ABSPATH') || exit;
if (count($shipping_info_list)) { ?>
<h2 class="woocommerce-order-details__title">
    <?=__('Shipping details', 'ry-woocommerce-tools-pro') ?>
</h2>

<table class="woocommerce-table woocommerce-table--shipping-details shop_table shipping_details">
    <thead>
        <tr>
            <th class="woocommerce-table__shipping-no shipping-no">
                <?=__('Shipping payment no', 'ry-woocommerce-tools-pro') ?>
            </th>
            <th class="woocommerce-table__shipping-status shipping-status">
                <?=__('Shipping status', 'ry-woocommerce-tools-pro') ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($shipping_info_list as $shipping_info) { ?>
        <tr>
            <td class="woocommerce-table__shipping-no shipping-no">
                <?php
                if (in_array($shipping_info['type'], ['7NET', 'FAMI'])) {
                    echo empty($shipping_info['PaymentNo']) ? $shipping_info['ID'] : $shipping_info['PaymentNo'];
                } elseif ($shipping_info['type'] == 'HOME') {
                    echo $shipping_info['BookingNote'];
                }
                ?>
            </td>
            <td class="woocommerce-table__shipping-status shipping-status">
                <?php
                if (in_array($shipping_info['status'], apply_filters('ry_smilepay_shipping_status_info_transporting', [0]))) {
                    _ex('Wait shipment', 'Shipping status', 'ry-woocommerce-tools-pro');
                } elseif (in_array($shipping_info['status'], apply_filters('ry_smilepay_shipping_status_info_transporting', [1]))) {
                    _ex('Transporting', 'Shipping status', 'ry-woocommerce-tools-pro');
                } elseif (in_array($shipping_info['status'], apply_filters('ry_smilepay_shipping_status_info_wait_pick', [2]))) {
                    _ex('Waiting for pick up', 'Shipping status', 'ry-woocommerce-tools-pro');
                } elseif (in_array($shipping_info['status'], apply_filters('ry_smilepay_shipping_status_info_completed', [3]))) {
                    _ex('Completed', 'Shipping status', 'ry-woocommerce-tools-pro');
                } elseif (in_array($shipping_info['status'], apply_filters('ry_smilepay_shipping_status_info_overdue', [4]))) {
                    _ex('Overdue return', 'Shipping status', 'ry-woocommerce-tools-pro');
                } else {
                    _ex('Unknow', 'Shipping status', 'ry-woocommerce-tools-pro');
                }
                ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php
}
