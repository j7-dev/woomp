<?php
/**
 * ShopSubscription
 */

declare (strict_types = 1);

namespace J7\Woomp\Admin\Resources\ShopSubscription;

/**
 * Class ShopSubscription
 */
final class ShopSubscription {


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter( 'woocommerce_can_subscription_be_updated_to_active', [ $this, 'allowed_canceled_wcs_active' ], 10, 2 );
	}

	/**
	 * 讓已取消的訂閱，可以重新啟用
	 *
	 * @param bool            $can_be_updated 訂閱是否可更新.
	 * @param WC_Subscription $subscription 訂閱物件.
	 * @return bool 如果訂閱已取消，返回 true，否則返回 $can_be_updated.
	 */
	public function allowed_canceled_wcs_active( $can_be_updated, $subscription ) {

		$is_cancelled = $subscription->has_status( 'cancelled' );

		if ( $is_cancelled ) {
			return true;
		}

		return $can_be_updated;
	}
}

new ShopSubscription();
