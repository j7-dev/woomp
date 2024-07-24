<?php
/**
 * PayNow setting class.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;
/**
 * Settings class.
 */
class WC_Settings_Tab_PayNow extends WC_Settings_Page {

	private static $sections;
	/**
	 * Setting constructor.
	 */
	public function __construct() {

		$this->id    = 'paynow';
		$this->label = __( 'PayNow', 'paynow' );

		self::$sections = [
			'payment' => __( 'Payment Settings', 'paynow-payment' ),
		];

		// add_filter( 'woocommerce_settings_tabs_array', array( $this, 'paynow_payment_sections' ), 51 );
		add_action( 'woocommerce_sections_' . $this->id, [ $this, 'output_sections' ] );
		add_action( 'woocommerce_settings_' . $this->id, [ $this, 'output' ] );
		add_action( 'woocommerce_settings_save_' . $this->id, [ $this, 'save' ] );

		add_action( 'admin_init', [ $this, 'paynow_redirect_default_tab' ] );

		add_filter( 'woocommerce_get_sections_' . $this->id, [ $this, 'paynow_payment_sections' ] );

		parent::__construct();
	}

	public function paynow_payment_sections( $sections ) {
		// Paynow_Payment::log('paynow_payment_sections:'.wc_print_r($sections, true));
		// if (!array_key_exists('payment', $sections)) {
		// Paynow_Payment::log('paynow_payment_sections payment key not exists');

		// }

		Paynow_Payment::log( 'current filter:' . current_filter() );
		Paynow_Payment::log( 'paynow_payment_sections:' . wc_print_r( $sections, true ) );

		if ( ! array_key_exists( 'payment', $sections ) ) {
			$sections['payment'] = __( 'Payment Settings', 'paynow' );
		}
		// if (is_plugin_active('paynow-shipping/paynow-shipping.php')) {

		// if(!array_key_exists('shipping', $sections)) {
		// $sections['shipping'] = __( 'Shipping Settings', 'paynow' );
		// Paynow_Payment::log('paynow_payment_sections:'.wc_print_r($sections, true));
		// }

		// }
		// if (is_plugin_active('paynow-einvoice/paynow-einvoice.php')) {
		// if(!array_key_exists('einvoice', $sections)) {
		// $sections['einvoice'] = __( 'E-Invoice Settings', 'paynow' );
		// }
		// }

		return $sections;
	}

	/**
	 * Get setting sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = self::$sections;

		// Paynow_Payment::log( 'current filter:' . current_filter() );
		// Paynow_Payment::log( 'paynow_payment_sections:' . wc_print_r( $sections, true ) );

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public function get_settings( $current_section = '' ) {

		if ( 'payment' === $current_section ) {
			$settings = apply_filters(
				'paynow_payment_settings',
				[
					[
						'title' => __( 'General Payment Settings', 'paynow-payment' ),
						'type'  => 'title',
						'id'    => 'payment_general_setting',
					],
					[
						'title'   => __( 'Debug Log', 'paynow-payment' ),
						'type'    => 'checkbox',
						'default' => 'no',
						'desc'    => sprintf( __( 'Log PayNow payment message, inside <code>%s</code>', 'paynow-payment' ), wc_get_log_file_path( 'paynow-payment' ) ),
						'id'      => 'paynow_payment_debug_log_enabled',
					],
					[
						'type' => 'sectionend',
						'id'   => 'payment_general_setting',
					],
					[
						'title' => __( 'API Settings', 'paynow-payment' ),
						'type'  => 'title',
						'desc'  => __( 'Enter your PayNow API credentials', 'paynow-payment' ),
						'id'    => 'paynow_payment_api_settings',
					],
					[
						'title'   => __( '測試模式', 'paynow-payment' ),
						'type'    => 'checkbox',
						'default' => 'yes',
						'desc'    => __( '如果要使用測試模式，請勾選.', 'paynow-payment' ),
						'id'      => 'paynow_payment_testmode_enabled',
					],
					[
						'title'    => __( 'WebNo', 'paynow-payment' ),
						'type'     => 'text',
						'desc'     => __( 'This is the WebNo when you apply PayNow API', 'paynow-payment' ),
						'desc_tip' => true,
						'id'       => 'paynow_payment_web_no',
					],
					[
						'title'    => __( 'Transaction Password', 'paynow-payment' ),
						'type'     => 'text',
						'desc'     => __( 'This is the Transaction Password when you apply PayNow API', 'paynow-payment' ),
						'desc_tip' => true,
						'id'       => 'paynow_payment_trans_pwd',
					],
					[
						'title'    => __( 'Merchant Name', 'paynow-payment' ),
						'type'     => 'text',
						'desc'     => __( 'This is the Merchant Name when you apply PayNow API', 'paynow-payment' ),
						'desc_tip' => true,
						'id'       => 'paynow_payment_merchant_name',
					],
					[
						'type' => 'sectionend',
						'id'   => 'paynow_payment_api_settings',
					],
				]
			);
		}

		// return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
		return $settings;
	}

	function paynow_redirect_default_tab() {

		global $pagenow;

		if ( 'admin.php' !== $pagenow ) {
			return;
		}

		$page    = $_GET['page'];
		$tab     = $_GET['tab'];
		$section = $_GET['section'];

		if ( $page === 'wc-settings' && $tab === 'paynow' ) {

			if ( empty( $section ) ) {
				wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=paynow&section=payment' ) );
				exit;
			}
		}
	}

	public function output() {

		global $current_section;

		if ( $current_section !== 'payment' ) {
			return;
		}

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::output_fields( $settings );
	}

	function output_order_section( $order_status ) {
		echo $order_status;
	}

	public function save() {

		global $current_section;

		if ( $current_section !== 'payment' ) {
			return;
		}

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}
}
