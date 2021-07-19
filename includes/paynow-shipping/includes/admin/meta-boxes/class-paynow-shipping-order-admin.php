<?php
/**
 * Class PayNow_Shipping_Order_Admin file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin related functions.
 */
class PayNow_Shipping_Order_Admin {

	/**
	 * The instance object.
	 *
	 * @var PayNow_Shipping_Order_Admin
	 */
	protected static $instance = null;

	/**
	 * Initialize the class and add hooks.
	 *
	 * @return PayNow_Shipping_Order_Admin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_filter( 'woocommerce_admin_shipping_fields', array( self::$instance, 'paynow_shipping_cvs_fields' ), 10, 1 );
			add_filter( 'woocommerce_order_actions', array( self::$instance, 'paynow_order_actions' ) );
			add_filter( 'woocommerce_order_action_renew_paynow_logistic_no', array( PayNow_Shipping_Request::get_instance(), 'paynow_get_logistic_no' ) );
			add_filter( 'woocommerce_order_action_create_paynow_shipping_order', array( PayNow_Shipping_Request::get_instance(), 'paynow_get_logistic_no' ) );

			add_action( 'add_meta_boxes', array( 'PayNow_Shipping_Order_Meta_Box', 'add_meta_box' ), 40, 2 );

		}
		return self::$instance;
	}

	/**
	 * Admin shipping fields for PayNow shipping order
	 *
	 * @param array $shipping_fields The admin shipping fields.
	 * @return array
	 */
	public static function paynow_shipping_cvs_fields( $shipping_fields ) {
		global $theorder;
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $theorder ) ) {
			if ( isset( $_POST['post_ID'] ) ) {
				$theorder = wc_get_order( wc_clean( wp_unslash( $_POST['post_ID'] ) ) );
			} else {
				return $shipping_fields;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$shipping_method = false;

		$items_shipping = $theorder->get_items( 'shipping' );
		$items_shipping = array_shift( $items_shipping );

		if ( $items_shipping ) {
			$shipping_method = PayNow_Shipping::is_paynow_shipping_cvs( $items_shipping->get_method_id() );
		}

		if ( false !== $shipping_method ) {

			$shipping_fields['paynow_storeid']      = array(
				'label' => __( 'Store ID', 'paynow-shipping' ),
				'show'  => false,
			);
			$shipping_fields['paynow_storename']    = array(
				'label' => __( 'Store Name', 'paynow-shipping' ),
				'show'  => false,
			);
			$shipping_fields['paynow_storeaddress'] = array(
				'label' => __( 'Store Address', 'paynow-shipping' ),
				'show'  => false,
			);

			$shipping_fields['phone'] = array(
				'label' => __( 'Shipping Phone', 'paynow-shipping' ),
			);
		} else {
			if ( $items_shipping ) {
				if ( PayNow_Shipping::is_paynow_shipping_hd( $items_shipping->get_method_id() ) ) {
					$shipping_fields['phone'] = array(
						'label' => __( 'Shipping Phone', 'paynow-shipping' ),
					);
				}
			}
		}

		return $shipping_fields;
	}

	/**
	 * The action for get PayNow Logistic Number.
	 *
	 * @param array $order_actions The order actions.
	 * @return array
	 */
	public static function paynow_order_actions( $order_actions ) {
		global $theorder;

		foreach ( $theorder->get_items( 'shipping' ) as $item_id => $item ) {
			if ( PayNow_Shipping::is_paynow_shipping( $item->get_method_id() ) !== false ) {
				if ( ! empty( $theorder->get_meta( PayNow_Shipping_Order_Meta::LogisticNumber ) ) ) {
					$order_actions['renew_paynow_logistic_no'] = __( 'Get new PayNow Logistic Number', 'paynow-shipping' );
				} else {
					$order_actions['create_paynow_shipping_order'] = __( 'Create PayNow Shipping Order', 'paynow-shipping' );
				}
			}
		}
		return apply_filters( 'paynow_admin_order_actions', $order_actions );
	}


}
