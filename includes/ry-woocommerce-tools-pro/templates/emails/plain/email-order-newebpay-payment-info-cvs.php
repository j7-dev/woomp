<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/emails-order-newebpay-payment-info-cvs.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0.15
 */
defined('ABSPATH') || exit;
if ($order->get_payment_method() != 'ry_newebpay_cvs') {
    return;
}

if ($order->get_meta('_newebpay_payment_type') != 'CVS') {
    return;
}

echo "\n==========\n\n";

echo wp_kses_post(__('Payment details', 'ry-woocommerce-tools')) . "\n";

echo wp_kses_post(__('CVS code', 'ry-woocommerce-tools') . "\t " . $order->get_meta('_newebpay_cvs_PaymentNo')) . "\n";
$expireDate = wc_string_to_datetime($order->get_meta('_newebpay_cvs_ExpireDate'));
$expireDate = $expireDate->date_i18n(wc_date_format());
echo wp_kses_post(__('Payment deadline', 'ry-woocommerce-tools') . "\t " . $expireDate) . "\n";
