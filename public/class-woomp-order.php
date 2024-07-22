<?php

/**
 * 前台訂單相關功能
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Order_Public' ) ) {
	class WooMP_Order_Public {
		/**
		 * 初始化
		 */
		public static function init() {
			$class = new self();
			add_action( 'woocommerce_view_order', array( $class, 'display_order_shipping_number' ) );
		}

		/**
		 * 前台訂單查詢顯示物流單號
		 */
		public function display_order_shipping_number( $order_id ) {

			// 綠界物流單號.
			$ecpay_shipping_info = get_post_meta( $order_id, '_ecpay_shipping_info' );
			if ( $ecpay_shipping_info ) {
				$ecpay_no = array();
				foreach ( $ecpay_shipping_info as $data ) {
					foreach ( $data as $i ) {
						$shipping_no = $i['PaymentNo'] . ' ' . $i['ValidationNo'];
						$ecpay_no[]  = $shipping_no;
					}
				}
				if ( count( $ecpay_no ) > 0 ) { ?>
				<h2 class="woocommerce-order-details__title">
					<?php echo __( 'Ecpay Shipping details', 'woomp' ); ?>
				</h2>

				<table class="woocommerce-table woocommerce-table--shipping-details shop_table shipping_details">
					<thead>
						<tr>
							<th class="woocommerce-table__shipping-no shipping-no">
					<?php echo __( 'Shipping payment no', 'woomp' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $ecpay_no as $value ) : ?>
						<tr>
							
							<td class="woocommerce-table__shipping-no shipping-no">
						<?php echo esc_html( $value ); ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
					<?php
				}
			}

			// 自行輸入物流單號.
			if ( get_post_meta( $order_id, 'wmp_shipping_no' ) ) {
				?>
				<h2 class="woocommerce-order-details__title">
				<?php echo __( 'Shipping details', 'woomp' ); ?>
				</h2>

				<table class="woocommerce-table woocommerce-table--shipping-details shop_table shipping_details">
					<thead>
						<tr>
							<th class="woocommerce-table__shipping-no shipping-no">
				<?php echo __( 'Shipping payment no', 'woomp' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="woocommerce-table__shipping-no shipping-no">
				<?php
				echo get_post_meta( $order_id, 'wmp_shipping_no', true );
				?>
							</td>
						</tr>
					</tbody>
				</table>
				<?php
			}
		}
	}
	WooMP_Order_Public::init();
}
