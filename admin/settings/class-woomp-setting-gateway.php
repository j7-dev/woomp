<?php

use \MGC\Logger\Logger;

class Woomp_Setting_Gateway extends WC_Settings_Page {

	public $setting_default = array();

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public function __construct() {
		$this->id    = 'woomp_setting_gateway';
		$this->label = __( '金流設定', 'woomp' );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 51 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	public function set_setting_default( $company ) {
		$this->setting_default = array(
			array(
				'title' => '尚未啟用' . esc_html( $company ) . '金流',
				'desc'  => '請前往<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woomp_setting' ) . '">設定</a>',
				'id'    => 'empty_options',
				'type'  => 'title',
			),
		);
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woomp_setting_gateway'] = __( '金流設定', 'woomp' );
		return $settings_tabs;
	}


	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses $this->get_settings()
	 */
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}


	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses $this->get_settings()
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	public function get_sections() {
		$sections['ecpay']    = __( '綠界', 'woomp' );
		$sections['newebpay'] = __( '藍新', 'woomp' );
		$sections['smilepay'] = __( '速買配', 'woomp' );
		$sections['paynow']   = __( '立吉富', 'woomp' );
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}


	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public function get_settings( $section = null ) {

		switch ( $section ) {
			case 'ecpay':
				if ( get_option( RY_WT::$option_prefix . 'ecpay_gateway', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '綠界' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'newebpay':
				if ( get_option( RY_WT::$option_prefix . 'newebpay_gateway', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '藍新' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'smilepay':
				if ( get_option( RY_WT::$option_prefix . 'newebpay_gateway', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings-smilepay-gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '速買配' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'paynow':
				if ( get_option( 'wc_woomp_setting_paynow_gateway', 1 ) === 'yes' ) {
					$settings = array(
						array(
							'title' => __( '立吉富金流設定', 'paynow-payment' ),
							'type'  => 'title',
							'id'    => 'payment_general_setting',
						),
						array(
							'title'   => __( 'Debug Log', 'paynow-payment' ),
							'type'    => 'checkbox',
							'default' => 'no',
							'desc'    => sprintf( __( 'Log PayNow payment message, inside <code>%s</code>', 'paynow-payment' ), wc_get_log_file_path( 'paynow-payment' ) ),
							'id'      => 'paynow_payment_debug_log_enabled',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'payment_general_setting',
						),
						array(
							'title' => __( 'API Settings', 'paynow-payment' ),
							'type'  => 'title',
							'desc'  => __( 'Enter your PayNow API credentials', 'paynow-payment' ),
							'id'    => 'paynow_payment_api_settings',
						),
						array(
							'title'   => __( '測試模式', 'paynow-payment' ),
							'type'    => 'checkbox',
							'default' => 'yes',
							'desc'    => __( '如果要使用測試模式，請勾選.', 'paynow-payment' ),
							'id'      => 'paynow_payment_testmode_enabled',
						),
						array(
							'title'    => __( 'WebNo', 'paynow-payment' ),
							'type'     => 'text',
							'desc'     => __( 'This is the WebNo when you apply PayNow API', 'paynow-payment' ),
							'desc_tip' => true,
							'id'       => 'paynow_payment_web_no',
						),
						array(
							'title'    => __( 'Transaction Password', 'paynow-payment' ),
							'type'     => 'text',
							'desc'     => __( 'This is the Transaction Password when you apply PayNow API', 'paynow-payment' ),
							'desc_tip' => true,
							'id'       => 'paynow_payment_trans_pwd',
						),
						array(
							'title'    => __( 'Merchant Name', 'paynow-payment' ),
							'type'     => 'text',
							'desc'     => __( 'This is the Merchant Name when you apply PayNow API', 'paynow-payment' ),
							'desc_tip' => true,
							'id'       => 'paynow_payment_merchant_name',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'paynow_payment_api_settings',
						),
					);
					return $settings;
				} else {
					$this->set_setting_default( '立吉富' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;

			default:
				// code...
				break;
		}
	}

	public function output() {
		global $current_section, $hide_save_button;
		if ( $current_section == '' ) {
			$current_section = 'ecpay';
		}
		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		global $current_section;
		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}

}

return new Woomp_Setting_Gateway();
