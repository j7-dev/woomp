<?php

namespace PAYUNI\Gateways;

use WC_Order;
use WC_Product_Factory;
use WC_Subscription;

defined('ABSPATH') || exit;

/**
 * Subscription class
 */
class Subscription
{
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init()
	{
		$class = new self();
		add_action('admin_enqueue_scripts', array($class, 'enqueue_script'));
		add_action(
			'woocommerce_subscription_renewal_payment_failed',
			array(
				$class,
				'subscription_fail_handler',
			),
			99,
			2,
		);
		add_action(
			'woocommerce_scheduled_subscription_payment_payuni-credit-subscription',
			array(
				$class,
				'process_subscription_payment',
			),
			10,
			2
		);
		add_filter('woocommerce_available_payment_gateways', array($class, 'conditional_payment_gateways'), 10, 1);
	}

	/**
	 * Process subscription payment
	 *
	 * @param int      $amount The amount.
	 * @param WC_Order $order  The order object.
	 *
	 * @return void
	 */
	public function process_subscription_payment(int $amount, WC_Order $order): void
	{
		$request = new Request(new CreditSubscription());
		$request->build_subscription_request($amount, $order);
	}

	/**
	 * Subscription fail handler
	 *
	 * @param WC_Subscription $subscription The subscription object.
	 * @param WC_Order        $last_order   The last order object.
	 *
	 * @return void|bool
	 */
	public function subscription_fail_handler(WC_Subscription $subscription, WC_Order $last_order)
	{

		$order = $last_order;
		if ('failed' !== $order->get_status()) {
			return false;
		}
		if ('payuni-credit-subscription' !== $order->get_payment_method()) {
			return false;
		}

		if ('SUCCESS' === $order->get_meta('_payuni_resp_status')) {
			return false;
		}

		$order->update_status('pending');
	}

	/**
	 * Enqueue script
	 *
	 * @return void
	 */
	public function enqueue_script(): void
	{
		wp_register_script('woomp_payuni_subscription', PAYUNI_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.3', true);
		wp_localize_script(
			'woomp_payuni_subscription',
			'woomp_payuni_subscription_params',
			array(
				'ajax_nonce' => wp_create_nonce('pay_manual'),
				'post_id'    => get_the_ID(),
			)
		);
		wp_enqueue_script('woomp_payuni_subscription');
	}

	/**
	 * 根據可變商品規格顯示付款方式
	 *
	 * @param array $available_gateways The available gateways.
	 */
	public function conditional_payment_gateways(array $available_gateways): array
	{
		if (!empty(WC()->cart)) {
			$product_type  = array();
			$cart_contents = WC()->cart->get_cart_contents();
			foreach ($cart_contents as $values) {
				$product_type[] = WC_Product_Factory::get_product_type($values['product_id']);
			}

			if (count($product_type) < 1) {
				return $available_gateways;
			}

			if (is_checkout() && !in_array('subscription', $product_type, true) && !in_array('variable-subscription', $product_type, true)) {
				unset($available_gateways['payuni-credit-subscription']);
			}
		}

		return $available_gateways;
	}
}

Subscription::init();
