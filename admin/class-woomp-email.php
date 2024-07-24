<?php

/**
 * 電子郵件相關功能
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Email' ) ) {
	class WooMP_Email {
		/**
		 * 初始化
		 */
		public static function init() {
			$class = new self();
			add_action( 'woocommerce_email_after_order_table', [ $class, 'add_payment_info' ], 10, 4 );
		}

		/**
		 * 加入付款資訊
		 */
		public function add_payment_info( $order, $sent_to_admin, $plain_text, $email ) {
			if ( 'customer_on_hold_order' === $email->id ) {
				$payment_method = $order->get_payment_method();
				switch ( $payment_method ) {
					case 'ry_ecpay_atm':
						echo '<h2 style="color: #96588a;font-family: &quot;Helvetica Neue&quot;, Helvetica, Roboto, Arial, sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left">付款資訊</h2>
						<div style="margin-bottom: 40px">
							<table class="td" cellspacing="0" cellpadding="6" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;width: 100%;font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif">
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">轉帳銀行:</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										<span>' . _x( $order->get_meta( '_ecpay_atm_BankCode' ), 'Bank code', 'ry-woocommerce-tools' ) . '</span>
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">銀行代碼:</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										<span>' . $order->get_meta( '_ecpay_atm_BankCode' ) . '</span>
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">ATM 繳費帳號:</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										' . wordwrap( $order->get_meta( '_ecpay_atm_vAccount' ), 4, '<span> </span>', true ) . '
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">付款截止日:</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										' . wc_string_to_datetime( $order->get_meta( '_ecpay_atm_ExpireDate' ) )->date_i18n( wc_date_format() ) . '
									</td>
								</tr>
							</table>
						</div>';
						break;
					case 'ry_ecpay_cvs':
						echo '<h2 style="color: #96588a;font-family: &quot;Helvetica Neue&quot;, Helvetica, Roboto, Arial, sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left">付款資訊</h2>
						<div style="margin-bottom: 40px">
							<table class="td" cellspacing="0" cellpadding="6" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;width: 100%;font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif">
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Bank code', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										' . $order->get_meta( '_ecpay_cvs_PaymentNo' ) . '
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Payment deadline', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										' . sprintf( _x( '%1$s %2$s', 'Datetime', 'ry-woocommerce-tools' ), wc_string_to_datetime( $order->get_meta( '_ecpay_cvs_ExpireDate' ) )->date_i18n( wc_date_format() ), wc_string_to_datetime( $order->get_meta( '_ecpay_cvs_ExpireDate' ) )->date_i18n( wc_time_format() ) ) . '
									</td>
								</tr>
							</table>
						</div>';
						break;
					case 'ry_ecpay_barcode':
						echo '<link href="https://fonts.googleapis.com/css?family=Libre+Barcode+39+Text" rel="stylesheet"><style type="text/css">.free3of9 {font-family: "Libre Barcode 39 Text", cursive;font-size: 40px;line-height: normal;letter-spacing: 0;}
						</style><h2 style="color: #96588a;font-family: &quot;Helvetica Neue&quot;, Helvetica, Roboto, Arial, sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left">付款資訊</h2>
						<div style="margin-bottom: 40px">
							<table class="td" cellspacing="0" cellpadding="6" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;width: 100%;font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif">
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Barcode 1', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px 0;text-align: left; white-space: nowrap;">
										<span class="free3of9"><font style="Libre Barcode 39 Text">* ' . $order->get_meta( '_ecpay_barcode_Barcode1' ) . ' *</font></span>
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Barcode 2', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px 0;text-align: left; white-space: nowrap;">
										<span class="free3of9">* ' . $order->get_meta( '_ecpay_barcode_Barcode2' ) . ' *</span>
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Barcode 3', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px 0;text-align: left; white-space: nowrap;">
										<span class="free3of9">* ' . $order->get_meta( '_ecpay_barcode_Barcode3' ) . ' *</span>
									</td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">' . __( 'Payment deadline', 'ry-woocommerce-tools' ) . ':</th>
									<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
										' . sprintf( _x( '%1$s %2$s', 'Datetime', 'ry-woocommerce-tools' ), wc_string_to_datetime( $order->get_meta( '_ecpay_barcode_ExpireDate' ) )->date_i18n( wc_date_format() ), wc_string_to_datetime( $order->get_meta( '_ecpay_barcode_ExpireDate' ) )->date_i18n( wc_time_format() ) ) . '
									</td>
								</tr>
							</table>
						</div>';
						break;

					default:
						// code...
						break;
				}
			}
		}
	}
	WooMP_Email::init();
}
