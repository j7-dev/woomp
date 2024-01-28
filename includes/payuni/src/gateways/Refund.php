<?php

namespace PAYUNI\Gateways;

defined('ABSPATH') || exit;

class Refund
{
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init()
	{
		$class = new self();
		add_action('wp_ajax_payuni_refund', array($class, 'card_refund')); // 這個好像沒有用
		add_action('payuni_hash_refund', array($class, 'hash_refund'));
	}

	/**
	 * Handle refund
	 *
	 * @param array|null $auto_refund The data of 0 dollar order.
	 * @param bool       $force       Force refund for subscription 0 dollar order.
	 */
	public function card_refund(array $auto_refund = null, bool $force = false): void
	{

		$nonce = ($auto_refund) ? $auto_refund['nonce'] : $_POST['nonce'];
		if (!wp_verify_nonce($nonce, 'payuni_refund')) {
			echo wp_json_encode('退款發生錯誤，請聯繫管理員!');
			die();
		}

		if ($auto_refund) {
			$order       = $auto_refund['order'];
			$amount      = $auto_refund['amount'];
			$customer_id = $auto_refund['user_id'];
			$user_id     = $auto_refund['user_id'];
		} else {
			$order       = \wc_get_order($_POST['order_id']);
			$amount      = $_POST['amount'];
			$customer_id = (int) $order->get_customer_id();
			$user_id     = (int) $_POST['user_id'];
		}

		if ($customer_id !== $user_id) {
			echo wp_json_encode('退款發生錯誤，請聯繫管理員!');
			die();
		}

		$order_status = $order->get_status();

		if (!in_array('wc-' . $order_status, (array) get_option('payuni_admin_refund')) && !$force) {
			$order->add_order_note('<strong>統一金流退費紀錄</strong><br>退費結果：該訂單狀態不允許退費', true);
			echo wp_json_encode('該訂單狀態不允許退費');
			die();
		}

		//TODO 1天之內的 訂單可以順利退款嗎?

		$args = array(
			'MerID'     => (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no'),
			'TradeNo'   => $order->get_meta('_payuni_resp_trade_no'),
			'TradeAmt'  => $amount,
			'Timestamp' => time(),
			'CloseType' => 2,
		);

		$parameter['MerID']       = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no');
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt($args);
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info($parameter['EncryptInfo']);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request($url . 'api/trade/close', $options);
		$resp    = json_decode(wp_remote_retrieve_body($request));
		$data    = \Payuni\APIs\Payment::decrypt($resp->EncryptInfo);

		$note = '<strong>統一金流退費紀錄</strong><br>退費結果：' . $data['Message'];

		if ($auto_refund) {
			return;
		}

		$order->update_status('refunded');
		$order->add_order_note($note, true);
		$order->save();

		echo wp_json_encode($data['Message']);
		die();
	}

	/**
	 * Handle refund
	 * 定期定額退款 5 元 的 hook
	 * @param string $trade_no
	 *
	 * @return void
	 */
	public function hash_refund(string $trade_no): void
	{
		$args = array(
			'MerID'     => (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no'),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		);

		$parameter['MerID']       = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no');
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt($args);
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info($parameter['EncryptInfo']);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request("{$url}api/trade/cancel", $options);
		$resp    = json_decode(wp_remote_retrieve_body($request));
	}

	/**
	 * Handle cancel credit bind
	 * 取消綁卡
	 * @param string $CreditHash The card hash.
	 *
	 * @return void
	 */
	public function cancel_credit_bind(string $CreditHash): void
	{
		$args = array(
			'MerID'     => (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no'),
			'UseTokenType' => 1,
			'BindVal' => $CreditHash,
			'Timestamp' => time(),
		);

		$parameter['MerID']       = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no');
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt($args);
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info($parameter['EncryptInfo']);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = (wc_string_to_bool(get_option('payuni_payment_testmode'))) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request($url . 'api/credit_bind/cancel', $options);
		$resp    = json_decode(wp_remote_retrieve_body($request));
	}
}

Refund::init();
