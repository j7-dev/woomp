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
			//add_action( 'woocommerce_email_after_order_table', array( $class, 'add_payment_info' ), 10, 4 );
		}

		/**
		 * 加入付款資訊
		 */
		public function add_payment_info( $order, $sent_to_admin, $plain_text, $email ) { ?>
			<h2 style="color: #96588a;font-family: &quot;Helvetica Neue&quot;, Helvetica, Roboto, Arial, sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left">付款資訊</h2>
			<div style="margin-bottom: 40px">
				<table class="td" cellspacing="0" cellpadding="6" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;width: 100%;font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif">
					<tr>
						<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">轉帳銀行:</th>
						<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
							<span>台新銀行</span>
						</td>
					</tr>
					<tr>
						<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">銀行代碼:</th>
						<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
							<span>812</span>
						</td>
					</tr>
					<tr>
						<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">轉帳帳號:</th>
						<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left;">
							<span>12345678</span>
						</td>
					</tr>
				</table>
			</div>
		<?php }


	}
	WooMP_Email::init();
}
