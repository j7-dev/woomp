<?php
/**
 * ShopSubscription
 */

declare (strict_types = 1);

namespace J7\Woomp\Admin\Resources\ShopSubscription;

// 如果沒有啟用訂閱，就不初始化卡片管理
if (!class_exists('WC_Subscriptions')) {
	return;
}

/**
 * Class ShopSubscription
 */
final class ShopSubscription {


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter( 'woocommerce_can_subscription_be_updated_to_active', [ __CLASS__, 'allowed_canceled_wcs_active' ], 10, 2 );
		\add_action( 'plugins_loaded', [ __CLASS__, 'run_after_plugins_loaded' ] );
	}

	/**
	 * 讓已取消的訂閱，可以重新啟用
	 *
	 * @param bool             $can_be_updated 訂閱是否可更新.
	 * @param \WC_Subscription $subscription 訂閱物件.
	 * @return bool 如果訂閱已取消，返回 true，否則返回 $can_be_updated.
	 */
	public static function allowed_canceled_wcs_active( $can_be_updated, $subscription ) {

		$is_cancelled = $subscription->has_status( 'cancelled' );

		if ( $is_cancelled ) {
			return true;
		}

		return $can_be_updated;
	}


	/**
	 * 整合 WooCommerce Subscriptions
	 */
	public static function run_after_plugins_loaded() {
		\add_filter( 'wc_subscriptions_object_data', [ __CLASS__, 'sync_invoice_data_at_renew_subscription' ], 100, 4 );
	}



	/**
	 * 產生新訂閱時，複製訂單時同步發票資訊
	 * 1. 每次訂閱訂單續訂時，複製訂單時觸發，將發票資訊同步至新訂單 #35
	 * 2. 避免有人更換預設的付款方式，結果還是用原始(第一筆)訂單的付款資訊扣款
	 *
	 * @param array            $data order data
	 * @param \WC_Order        $to_object new order object
	 * @param \WC_Subscription $from_object original order object
	 * @param string           $copy_type copy type "renewal_order"
	 *
	 * @return array
	 */
public static function sync_invoice_data_at_renew_subscription( $data, $to_object, $from_object, $copy_type ) { // phpcs:ignore
		if ( ! method_exists( $from_object, 'get_meta' ) || ! method_exists( $to_object, 'update_meta_data' ) ) {
			return $data;
		}

		$fields = [
			// 處理 綠界 ECPAY 發票
			'_ecpay_invoice_data',
			// 處理藍新 EZPAY 發票
			'_ezpay_invoice_data',
			// 處理 立吉富 PAYNOW 發票
			'_paynow_ei_issue_type',
			'_paynow_ei_carrier_type',
			'_paynow_ei_buyer_name',
			'_paynow_ei_donate_org',
			'_paynow_ei_issued',
			'_paynow_ei_result_invoice_number',
			'_paynow_invoice_url',
		];
		foreach ( $fields as $field ) {
			if ( ! metadata_exists( 'post', $from_object->get_id(), $field ) ) {
				continue;
			}
			$to_object->update_meta_data( $field, $from_object?->get_meta( $field ) );
		}

		return $data;
	}
}

new ShopSubscription();
