<?php
class RY_ECPay_Gateway_Response extends RY_ECPay_Gateway_Api {

	public static function init() {
		add_action( 'woocommerce_api_request', [ __CLASS__, 'set_do_die' ] );
		add_action('woocommerce_api_ry_ecpay_gateway_return', [ __CLASS__, 'gateway_return' ]);
		add_action( 'woocommerce_api_ry_ecpay_callback', [ __CLASS__, 'check_callback' ] );
		add_action( 'valid_ecpay_gateway_request', [ __CLASS__, 'doing_callback' ] );

		add_action( 'ry_ecpay_gateway_response_status_1', [ __CLASS__, 'payment_complete' ], 10, 2 );
		add_action( 'ry_ecpay_gateway_response_status_2', [ __CLASS__, 'payment_wait_atm' ], 10, 2 );
		add_action( 'ry_ecpay_gateway_response_status_10100073', [ __CLASS__, 'payment_wait_cvs' ], 10, 2 );
		add_action( 'ry_ecpay_gateway_response_status_10100058', [ __CLASS__, 'payment_failed' ], 10, 2 );
	}

	public static function check_callback() {
		if ( ! empty( $_POST ) ) {
			$ipn_info = wp_unslash( $_POST );
			RY_ECPay_Gateway::log( 'check_callback IPN info: ' . var_export( $ipn_info, true ) );
			if ( self::ipn_request_is_valid( $ipn_info ) ) {
				do_action( 'valid_ecpay_gateway_request', $ipn_info );
			} else {
				self::die_error();
			}
		}
		RY_ECPay_Gateway::log( 'check_callback $_POST is empty' );
	}

	protected static function ipn_request_is_valid( $ipn_info ) {
		$check_value = self::get_check_value( $ipn_info );
		if ( $check_value ) {
			RY_ECPay_Gateway::log( 'IPN request: ' . var_export( $ipn_info, true ) );
			list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

			$ipn_info_check_value = self::generate_check_value( $ipn_info, $HashKey, $HashIV, 'sha256' );
			if ( $check_value == $ipn_info_check_value ) {
				return true;
			} else {
				RY_ECPay_Gateway::log( 'IPN request check failed. Response:' . $check_value . ' Self:' . $ipn_info_check_value, 'error' );
				return false;
			}
		}
	}

	public static function doing_callback( $ipn_info ) {
		$order_id = self::get_order_id( $ipn_info, RY_WT::get_option( 'ecpay_gateway_order_prefix' ) );
		if ( $order = wc_get_order( $order_id ) ) {
			$payment_status = self::get_status( $ipn_info );
			RY_ECPay_Gateway::log( 'Found order #' . $order->get_id() . ' Payment status: ' . $payment_status );

			$order = self::set_transaction_info( $order, $ipn_info );

			do_action( 'ry_ecpay_gateway_response_status_' . $payment_status, $ipn_info, $order );
			do_action( 'ry_ecpay_gateway_response', $ipn_info, $order );

			self::die_success();
		} else {
			RY_ECPay_Gateway::log( 'Order not found', 'error' );
			self::die_error();
		}
	}

	protected static function set_transaction_info( $order, $ipn_info ) {
		$transaction_id = (string) $order->get_transaction_id();
		if ( $transaction_id == '' || ! $order->is_paid() || $transaction_id != self::get_transaction_id( $ipn_info ) ) {
			list($payment_type, $payment_subtype) = self::get_payment_info( $ipn_info );
			$order->set_transaction_id( self::get_transaction_id( $ipn_info ) );
			$order->update_meta_data( '_ecpay_payment_type', $payment_type );
			$order->update_meta_data( '_ecpay_payment_subtype', $payment_subtype );
			$account = $order->get_meta('_ecpay_atm_vAccount');

			if ('ATM' === $payment_type) {
				if ($account) {
					$expireDate = $order->get_meta('_ecpay_atm_ExpireDate');
					// 如果 $expireDate 字串滿足 ISO 8601 格式，用 regex 驗證
					if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/', $expireDate)) {
						$timestamp = strtotime($expireDate);
					$expireDate = date('Y-m-d H:i:s', $timestamp); // phpcs:ignore
					}

					$order_note = sprintf(
					/*html*/'
			<strong>綠界金流交易紀錄</strong><br>
			狀態碼：%1$s<br>
			交易訊息：%2$s<br>
			交易編號：%3$s<br>
			轉帳銀行：%4$s<br>
			轉帳帳號：%5$s<br>
			轉帳期限：%6$s
			',
					$ipn_info['RtnCode'] === '1' ? '1 (成功)' : $ipn_info['RtnCode'] . ' (尚未付款)',
					$ipn_info['RtnMsg'],
					$ipn_info['TradeNo'],
					$order->get_meta('_ecpay_atm_BankCode'),
					$account,
					$expireDate,
					);
					$order->add_order_note($order_note, true );
				}
			} else {
				$order_note = sprintf(
				/*html*/'綠界付款%1$s: <br/>
				_ecpay_payment_type: %2$s <br/>
				_ecpay_payment_subtype: %3$s <br/>
				',
				$ipn_info['RtnCode'] === '1' ? '成功' : '',
				$payment_type,
				$payment_subtype
				);
				// $order->add_order_note( $order_note );
			}

			$order->save();
			$order = wc_get_order( $order->get_id() );

			// 紀錄 log
			$log = new \WC_Logger();
			ob_start();
			var_dump($ipn_info);
			$msg = ob_get_clean();
			$log->info(
				'set_transaction_info $ipn_info: ' . $msg,
				[
					'source' => 'woomp-ecpay',
				]
				);

		}
		return $order;
	}

	protected static function get_payment_info( $ipn_info ) {
		if ( isset( $ipn_info['PaymentType'] ) ) {
			$payment_type = $ipn_info['PaymentType'];
			$payment_type = explode( '_', $payment_type );
			if ( count( $payment_type ) == 1 ) {
				$payment_type[] = '';
			}
			return $payment_type;
		}
		return false;
	}

	public static function payment_complete( $ipn_info, $order ) {
		if ( ! $order->is_paid() ) {
			// $order = self::set_transaction_info( $order, $ipn_info );
			$order->add_order_note( __( 'Payment completed', 'ry-woocommerce-tools' ) );
			$order->payment_complete();
		}
	}

	public static function payment_wait_atm( $ipn_info, $order ) {
		if ( ! $order->is_paid() ) {
			$expireDate = new DateTime( $ipn_info['ExpireDate'], new DateTimeZone( 'Asia/Taipei' ) );

			$order->update_meta_data( '_ecpay_atm_BankCode', $ipn_info['BankCode'] );
			$order->update_meta_data( '_ecpay_atm_vAccount', $ipn_info['vAccount'] );
			$order->update_meta_data( '_ecpay_atm_ExpireDate', $expireDate->format( DATE_ATOM ) );
			$order->save_meta_data();

			$order->update_status( 'on-hold' );
		}
	}

	public static function payment_wait_cvs( $ipn_info, $order ) {
		if ( ! $order->is_paid() ) {
			list($payment_type, $payment_subtype) = self::get_payment_info( $ipn_info );
			$expireDate                           = new DateTime( $ipn_info['ExpireDate'], new DateTimeZone( 'Asia/Taipei' ) );

			if ( $payment_type == 'CVS' ) {
				$order->update_meta_data( '_ecpay_cvs_PaymentNo', $ipn_info['PaymentNo'] );
				$order->update_meta_data( '_ecpay_cvs_ExpireDate', $expireDate->format( DATE_ATOM ) );
			} else {
				$order->update_meta_data( '_ecpay_barcode_Barcode1', $ipn_info['Barcode1'] );
				$order->update_meta_data( '_ecpay_barcode_Barcode2', $ipn_info['Barcode2'] );
				$order->update_meta_data( '_ecpay_barcode_Barcode3', $ipn_info['Barcode3'] );
				$order->update_meta_data( '_ecpay_barcode_ExpireDate', $expireDate->format( DATE_ATOM ) );
			}
			$order->save_meta_data();

			$order->update_status( 'on-hold' );
		}
	}

	public static function payment_failed( $ipn_info, $order ) {
		if ( $order->is_paid() ) {
			$order->add_order_note( __( 'Payment failed within paid order', 'ry-woocommerce-tools' ) );
			$order->save();
			return;
		}

		$order->update_status(
			'failed',
			sprintf(
			/* translators: Error status message */
				__( 'Payment failed (%s)', 'ry-woocommerce-tools' ),
				self::get_status_msg( $ipn_info )
			)
		);
	}

	public static function gateway_return() {
		$order_key = wp_unslash($_GET['key'] ?? '');
		$order_ID  = (int) wp_unslash($_GET['id'] ?? 0);
		$order     = wc_get_order($order_ID);
		if ($order && hash_equals($order->get_order_key(), $order_key)) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());
		}

		$return_url = apply_filters('woocommerce_get_return_url', $return_url, $order);
		wp_redirect($return_url);

		exit();
	}
}
