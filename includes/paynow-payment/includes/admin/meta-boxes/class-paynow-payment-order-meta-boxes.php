<?php
/**
 * PayNow_Payment_Order_Meta_Boxes class file
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Payment main class for handling all checkout related process.
 */
class PayNow_Payment_Order_Meta_Boxes {

	/**
	 * Class instance
	 *
	 * @var PayNow_Payment_Order_Meta_Boxes
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		// do nothing.
	}

	/**
	 * Initialize class andd add hooks
	 *
	 * @return void
	 */
	public static function init() {
		self::get_instance();

		add_action( 'add_meta_boxes', array( self::get_instance(), 'paynow_add_meta_boxes' ) );
	}

	/**
	 * Add meta box
	 *
	 * @param object $post The post object.
	 * @return void
	 */
	public function paynow_add_meta_boxes( $post ) {

		global $post;

		if ( array_key_exists( get_post_meta( $post->ID, '_payment_method', true ), Paynow_Payment::$allowed_payments ) ) {
			add_meta_box(
				'paynow-order-meta-boxes',
				__( 'PayNow Payment Detail', 'taishin-payment' ),
				array(
					self::get_instance(),
					'paynow_order_admin_meta_box',
				),
				'shop_order',
				'side',
				'default'
			);
		}
	}

	/**
	 * Meta box ouput
	 *
	 * @param object $post The post object.
	 * @return void
	 */
	public function paynow_order_admin_meta_box( $post ) {

		$payment_method = get_post_meta( $post->ID, '_payment_method', true );
		$gateway        = Paynow_Payment::$allowed_payments[ $payment_method ];

		foreach ( $gateway::order_metas() as $key => $value ) {
			echo '<div><strong>' . esc_html( $value ) . ':</strong> ' . esc_html( get_post_meta( $post->ID, $key, true ) ) . '</div>';
		}

		$tran_status = get_post_meta( $post->ID, '_paynow_tran_status', true );
		$errordesc   = get_post_meta( $post->ID, '_paynow_errdesc', true );
		if ( 'F' === $tran_status && $errordesc ) {
			echo '<div><strong>' . esc_html( __( 'Payment Error Description', 'paynow-payment' ) ) . ':</strong> ' . esc_html( $errordesc ) . '</div>';
		}
	}

	/**
	 * Returns the single instance of the PayNow_Shipping object
	 *
	 * @return PayNow_Payment_Order_Meta_Boxes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
