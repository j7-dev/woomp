<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-newebpay-payment-info-barcode.php
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
if ($order->get_payment_method() != 'ry_newebpay_barcode') {
    return;
}

if ($order->get_meta('_newebpay_payment_type') != 'BARCODE') {
    return;
}

$text_align = is_rtl() ? 'right' : 'left';
?>
<h2>
	<?=__('Payment details', 'ry-woocommerce-tools') ?>
</h2>
<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
		<tbody>
			<tr>
				<th class="td" scope="row" style="text-align:<?=esc_attr($text_align) ?>;">
					<?=__('Barcode 1', 'ry-woocommerce-tools') ?>
				</th>
				<td class="td" style="text-align:<?=esc_attr($text_align) ?>;">
					<span><?=$order->get_meta('_newebpay_barcode_Barcode1') ?></span>
				</td>
			</tr>
			<tr>
				<th class="td" scope="row" style="text-align:<?=esc_attr($text_align) ?>;">
					<?=__('Barcode 2', 'ry-woocommerce-tools') ?>
				</th>
				<td class="td" style="text-align:<?=esc_attr($text_align) ?>;">
					<span><?=$order->get_meta('_newebpay_barcode_Barcode2') ?></span>
				</td>
			</tr>
			<tr>
				<th class="td" scope="row" style="text-align:<?=esc_attr($text_align) ?>;">
					<?=__('Barcode 3', 'ry-woocommerce-tools') ?>
				</th>
				<td class="td" style="text-align:<?=esc_attr($text_align) ?>;">
					<span><?=$order->get_meta('_newebpay_barcode_Barcode3') ?></span>
				</td>
			</tr>
			<tr>
				<th class="td" scope="row" style="text-align:<?=esc_attr($text_align) ?>;">
					<?=__('Payment deadline', 'ry-woocommerce-tools') ?>
				</th>
				<?php $expireDate = wc_string_to_datetime($order->get_meta('_newebpay_barcode_ExpireDate')); ?>
				<?php $expireDate = $expireDate->date_i18n(wc_date_format()); ?>
				<td class="td" style="text-align:<?=esc_attr($text_align) ?>;">
					<?=$expireDate ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
