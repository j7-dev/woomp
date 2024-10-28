<?php
/**
 * Checkout
 */

declare (strict_types = 1);

namespace J7\Woomp\Admin\Resources;

/**
 * Class Checkout
 */
final class Checkout {


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter('woocommerce_checkout_fields', [ __CLASS__, 'hide_shipping_fields_virtual_products' ]);
	}

	/**
	 * 隱藏虛擬商品的地址欄位
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields 結帳頁面欄位
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function hide_shipping_fields_virtual_products( array $fields ): array {
		$hide_address_for_virtual_product = \get_option( 'wc_woomp_setting_virtual_product_address' ) === 'yes';

		if (!$hide_address_for_virtual_product) {
			return $fields;
		}

		// 檢查購物車中是否全部為虛擬商品
		$cart_has_virtual_product = true;
		foreach (\WC()->cart->get_cart() as $cart_item) {
			if (!$cart_item['data']->is_virtual()) {
				$cart_has_virtual_product = false;
				break;
			}
		}

		// 如果全部都是虛擬商品，隱藏地址欄位並將其設置為非必填
		if ($cart_has_virtual_product) {
			unset($fields['billing']['billing_address_1']);
			unset($fields['billing']['billing_address_2']);
			unset($fields['billing']['billing_city']);
			unset($fields['billing']['billing_postcode']);
			unset($fields['billing']['billing_country']);
			unset($fields['billing']['billing_state']);
		}

		return $fields;
	}
}

new Checkout();
