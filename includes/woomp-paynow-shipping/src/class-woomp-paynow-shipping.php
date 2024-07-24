<?php
/**
 * WOOMP PayNow Shipping main file.
 *
 * @package woomp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WOOMP_PayNow_Shipping class
 */
class WOOMP_PayNow_Shipping {
	/**
	 * WOOMP_PayNow_Shipping instance.
	 *
	 * @var WOOMP_PayNow_Shipping
	 */
	private static $instance;

	/**
	 * Whether or not logging is enabled.
	 *
	 * @var boolean
	 */
	public static $log_enabled = false;

	/**
	 * WC_Logger instance.
	 *
	 * @var WC_Logger Logger instance
	 * */
	public static $log = false;

	/**
	 * Class init function. Hook needed actions and filters.
	 *
	 * @return void
	 */
	public static function init() {

		self::get_instance();

		if ( class_exists( 'PayNow_Abstract_Shipping_Method' ) ) {

			// 全家冷凍c2c.
			add_filter( 'paynow_shipping_cvs_callback', [ self::get_instance(), 'woomp_paynow_shipping_c2c_family_frozen_callback' ], 10, 2 );
			add_filter( 'paynow_shipping_cvs_fields', [ self::get_instance(), 'woomp_paynow_shipping_c2c_family_frozen_checkout_fields' ], 20, 1 );
			add_filter( 'paynow_shipping_order_request_args', [ self::get_instance(), 'woomp_paynow_shipping_c2c_family_frozen_request_args' ], 10, 2 );
			add_action( 'paynow_shipping_save_cvs_order_meta', [ self::get_instance(), 'woomp_paynow_shipping_save_frozen_cvs_data' ], 10, 2 );
			add_action( 'paynow_shipping_admin_meta_before_last_query', [ self::get_instance(), 'woomp_paynow_shipping_c2c_family_frozen_admin_meta_fields' ], 10, 1 );

			// add tcat deadline parameter.
			add_filter( 'paynow_shipping_order_request_args', [ self::get_instance(), 'woomp_shipping_tcat_request_args' ], 10, 2 );

			add_filter( 'paynow_shipping_settings', [ self::get_instance(), 'woomp_paynow_shipping_settings' ], 10, 1 );
		}
	}

	/**
	 * Add Deadline parameter to TCAT request.
	 *
	 * @param  array    $args The request arguments.
	 * @param  WC_Order $order The order object.
	 * @return array $args The request arguments.
	 */
	public static function woomp_shipping_tcat_request_args( $args, $order ) {
		if ( $order->get_meta( PayNow_Shipping_Order_Meta::LogisticServiceId ) === PayNow_Shipping_Logistic_Service::TCAT ) {
			$deadline         = get_option( 'woomp_shipping_tcat_shipping_deadline', 3 ); // 1~7
			$args['Deadline'] = $deadline;
		}
		return $args;
	}

	/**
	 * Add additional arguments for Family frozen after choosing CVS store
	 *
	 * @param array $cvs_info The cvs info data.
	 * @param array $posted The cleaned post data from PayNow.
	 * @return array
	 */
	public static function woomp_paynow_shipping_c2c_family_frozen_callback( $cvs_info, $posted ) {
		if ( array_key_exists( 'ReservedNo', $posted ) ) {
			$cvs_info['paynow_reservedno'] = $posted['ReservedNo'];
		}

		if ( array_key_exists( 'ShipDate', $posted ) ) {
			$cvs_info['paynow_shipdate'] = $posted['ShipDate'];
		}

		return $cvs_info;
	}

	/**
	 * Add additional fields for Family Frozen shipping method
	 *
	 * @param array $fields The checkout shipping fields.
	 * @return array
	 */
	public static function woomp_paynow_shipping_c2c_family_frozen_checkout_fields( $fields ) {
		$fields['shipping']['paynow_reservedno'] = [
			'required'          => false,
			'label'             => __( 'Reserved NO', 'wc-paynow-pro' ),
			'type'              => 'text',
			'custom_attributes' => [
				'readonly' => true,
			],
			'class'             => [ 'form-row-wide', 'paynow-shipping-field', 'paynow-shipping-family-frozen-field' ],
			'priority'          => 123,
		];
		$fields['shipping']['paynow_shipdate']   = [
			'required'          => false,
			'label'             => __( 'Ship Date', 'wc-paynow-pro' ),
			'type'              => 'text',
			'custom_attributes' => [
				'readonly' => true,
			],
			'class'             => [ 'form-row-wide', 'paynow-shipping-field', 'paynow-shipping-family-frozen-field' ],
			'priority'          => 124,
		];

		return $fields;
	}

	/**
	 * Add ReservedNO to request args
	 *
	 * @param array    $args The request arguments.
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public static function woomp_paynow_shipping_c2c_family_frozen_request_args( $args, $order ) {
		if ( $order->get_meta( PayNow_Shipping_Order_Meta::LogisticServiceId ) === PayNow_Shipping_Logistic_Service::FAMIFROZEN_C2C ) {
			$args['ReservedNo'] = $order->get_meta( PayNow_Shipping_Order_Meta::ReservedNo );
		}

		return $args;
	}

	/**
	 * Save family frozen cvs data
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $data The order meta value.
	 * @return void
	 */
	public static function woomp_paynow_shipping_save_frozen_cvs_data( $order, $data ) {
		if ( PayNow_Shipping_Logistic_Service::FAMIFROZEN_C2C === $data['paynow_service'] ) {
			$order->update_meta_data( PayNow_Shipping_Order_Meta::ReservedNo, $data['paynow_reservedno'] );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::ShipDate, $data['paynow_shipdate'] );
			$order->save();
		}
	}

	/**
	 * Display additional meta for Family frozen shipping
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public static function woomp_paynow_shipping_c2c_family_frozen_admin_meta_fields( $order ) {
		if ( $order->has_shipping_method( 'woomp_paynow_shipping_c2c_family_frozen' ) ) {
			echo '<tr><th>' . esc_html__( 'Reserved No', 'woomp' ) . '</th><td>' . esc_html( $order->get_meta( PayNow_Shipping_Order_Meta::ReservedNo ) ) . '</td></tr>';
			echo '<tr><th>' . esc_html__( 'Ship Date', 'woomp' ) . '</th><td>' . esc_html( $order->get_meta( PayNow_Shipping_Order_Meta::ShipDate ) ) . '</td></tr>';
		}
	}

	/**
	 * Bulk print label action
	 *
	 * @param array $bulk_actions The bulk actions array.
	 * @return array
	 */
	public static function paynow_register_shipping_bulk_actions( $bulk_actions ) {
		$bulk_actions['paynow_bulk_print_711']           = __( 'Print 7-11 shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_711_bulk']      = __( 'Print 7-11 Bulk shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_711_frozen']    = __( 'Print 7-11 Frozen shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_family']        = __( 'Print Family shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_family_bulk']   = __( 'Print Family Bulk shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_family_frozen'] = __( 'Print Family Frozen shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_hilife']        = __( 'Print HiLfe shipping Label', 'paynow-pro' );
		$bulk_actions['paynow_bulk_print_tcat']          = __( 'Print TCat shipping Label', 'paynow-pro' );
		return $bulk_actions;
	}


	/**
	 * Eneuque admin js
	 *
	 * @return void
	 */
	public static function paynow_pro_enqueue_admin_script() {

		wp_enqueue_style( 'paynow-pro-admin', PAYNOW_PRO_PLUGIN_URL . 'assets/css/paynow-pro-admin.css', [], '1.0.0', 'all' );

		wp_enqueue_script( 'paynow-pro-admin', PAYNOW_PRO_PLUGIN_URL . 'assets/js/paynow-pro-admin.js', [ 'jquery' ], '1.0.0', false );
		wp_localize_script(
			'paynow-pro-admin',
			'paynow_pro',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'paynow-pro' ),
			]
		);
	}

	/**
	 * Add TCAT related shipping settings.
	 *
	 * @param array $settings The PayNow Shipping settings.
	 * @return array
	 */
	public static function woomp_paynow_shipping_settings( $settings ) {
		$tcat_settings = [
			[
				'title' => __( 'TCat Shipping Settings', 'woomp' ),
				'type'  => 'title',
				'id'    => 'shipping_tcat_setting',
			],
			[
				'title'             => __( 'Estimate shipping date deadline(days)', 'woomp' ),
				'type'              => 'number',
				'default'           => 1,
				'desc'              => __( 'When will the estimate shipping date end. Default is 1, which means the estimate shipping end date is 1 day after the date of order get logistic number.', 'woomp' ),
				'desc_tip'          => true,
				'custom_attributes' => [
					'min' => 1,
					'max' => 7,
				],
				'id'                => 'woomp_shipping_tcat_shipping_deadline',
			],
			[
				'type' => 'sectionend',
				'id'   => 'shipping_tcat_setting',
			],
		];

		return array_merge( array_slice( $settings, 0, 15, false ), $tcat_settings, array_slice( $settings, 15, count( $settings ) - 1, false ) );
	}
	/**
	 * Log method.
	 *
	 * @param string $message The message to be logged.
	 * @param string $level The log level. Optional. Default 'info'. Possible values: emergency|alert|critical|error|warning|notice|info|debug.
	 * @return void
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->log( $level, $message, [ 'source' => 'paynow-shipping' ] );
		}
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// do nothing.
	}

	/**
	 * Returns the single instance of the PayNow_Shipping object
	 *
	 * @return WOOMP_PayNow_Shipping
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
