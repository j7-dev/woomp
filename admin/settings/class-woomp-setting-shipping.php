<?php

class Woomp_Setting_Shipping extends WC_Settings_Page {

	public $setting_default = array();

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public function __construct() {
		$this->id    = 'woomp_setting_shipping';
		$this->label = __( '物流設定', 'woomp' );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 51 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	public function set_setting_default( $company ) {
		$this->setting_default = array(
			array(
				'title' => '尚未啟用' . esc_html( $company ) . '物流',
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
		$settings_tabs['woomp_setting_shipping'] = __( '物流設定', 'woomp' );
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
				if ( get_option( RY_WT::$option_prefix . 'ecpay_shipping', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping.php';
					return $settings;
				} else {
					$this->set_setting_default( '綠界' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'newebpay':
				if ( get_option( RY_WT::$option_prefix . 'newebpay_shipping', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/settings-newebpay-shipping.php';
					return $settings;
				} else {
					$this->set_setting_default( '藍新' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'smilepay':
				if ( get_option( RY_WT::$option_prefix . 'newebpay_shipping', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/settings-smilepay-shipping.php';
					return $settings;
				} else {
					$this->set_setting_default( '速買配' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'paynow':
				if ( get_option( 'wc_woomp_setting_paynow_shipping', 1 ) === 'yes' ) {
					$settings = array(
						array(
							'title' => __( 'General Shipping Settings', 'paynow-shipping' ),
							'type'  => 'title',
							'id'    => 'shipping_general_setting',
						),
						array(
							'title'   => __( 'Debug Log', 'paynow-shipping' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable logging', 'paynow-shipping' ),
							'default' => 'no',
							'desc'    => sprintf( __( 'Log PayNow Shipping message, inside <code>%s</code>', 'paynow-shipping' ), wc_get_log_file_path( 'paynow-shipping' ) ),
							'id'      => 'paynow_shipping_debug_log_enabled',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'shipping_general_setting',
						),
						array(
							'title' => __( 'Store Settings', 'paynow-shipping' ),
							'type'  => 'title',
							'desc'  => __( 'Enter your store information', 'paynow-shipping' ),
							'id'    => 'paynow_shipping_store_settings',
						),
						array(
							'title'    => __( 'Sender Name', 'paynow-shipping' ),
							'type'     => 'text',
							'desc'     => __( 'Please enter the sender name. It may be used when the order is returned.', 'paynow-shipping' ),
							'desc_tip' => true,
							'id'       => 'paynow_shipping_sender_name',
						),
						array(
							'title' => __( 'Sender Address', 'paynow-shipping' ),
							'type'  => 'text',
							'id'    => 'paynow_shipping_sender_address',
						),
						array(
							'title' => __( 'Sender Phone', 'paynow-shipping' ),
							'type'  => 'text',
							'id'    => 'paynow_shipping_sender_phone',
						),
						array(
							'title' => __( 'Sender Email', 'paynow-shipping' ),
							'type'  => 'email',
							'id'    => 'paynow_shipping_sender_email',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'shipping_store_setting',
						),
						array(
							'title' => __( 'Shipping Order Status Settings', 'paynow-shipping' ),
							'type'  => 'title',
							'desc'  => __( 'Manage your shipping order status', 'paynow-shipping' ),
							'id'    => 'paynow_shipping_shipping_settings',
						),
						array(
							'title'   => __( 'When products are located at sender CVS store, change order status to', 'paynow-shipping' ),
							'type'    => 'select',
							'options' => self::paynow_get_order_status(),
							'id'      => 'paynow_shipping_order_status_at_sender_cvs',
						),
						array(
							'title'   => __( 'When products are at located receiver CVS store, change order status to', 'paynow-shipping' ),
							'type'    => 'select',
							'options' => self::paynow_get_order_status(),
							'id'      => 'paynow_shipping_order_status_at_receiver_cvs',
						),
						array(
							'title'   => __( 'When customer pickuped or received products, change order status to', 'paynow-shipping' ),
							'type'    => 'select',
							'options' => self::paynow_get_order_status(),
							'id'      => 'paynow_shipping_order_status_pickuped',
						),
						array(
							'title'   => __( "When the customer doesn't pickup products and the products are returned, change order status to", 'paynow-shipping' ),
							'type'    => 'select',
							'options' => self::paynow_get_order_status(),
							'id'      => 'paynow_shipping_order_status_returned',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'shipping_order_setting',
						),
						array(
							'title' => __( 'API Settings', 'paynow-shipping' ),
							'type'  => 'title',
							'desc'  => __( 'Enter your PayNow shipping user account and API Code', 'paynow-shipping' ),
							'id'    => 'paynow_shipping_api_settings',
						),
						array(
							'title'   => __( 'Test Mode', 'paynow-shipping' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable Test Mode', 'paynow-shipping' ),
							'default' => 'yes',
							'desc'    => __( 'When enabled, you need to use the test-only User Account and API Code.', 'paynow-shipping' ),
							'id'      => 'paynow_shipping_testmode_enabled',
						),
						array(
							'title'    => __( 'User Account', 'paynow-shipping' ),
							'type'     => 'text',
							'desc'     => __( 'This is the user account when you apply PayNow shipping', 'paynow-shipping' ),
							'desc_tip' => true,
							'id'       => 'paynow_shipping_user_account',
						),
						array(
							'title'    => __( 'API Code', 'paynow-shipping' ),
							'type'     => 'text',
							'desc'     => __( 'This is the API Code when you apply PayNow shipping', 'paynow-shipping' ),
							'desc_tip' => true,
							'id'       => 'paynow_shipping_api_code',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'paynow_shipping_api_settings',
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

	/**
	 * Get order status
	 *
	 * @return array
	 */
	private static function paynow_get_order_status() {
		$order_statuses = array(
			'' => __( 'No action', 'paynow-shipping' ),
		);

		foreach ( wc_get_order_statuses() as $slug => $name ) {
			if ( $slug == 'wc-cancelled' || $slug == 'wc-refunded' || $slug == 'wc-failed' ) {
				continue;
			}
			$order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
		}


		return $order_statuses;
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

return new Woomp_Setting_Shipping();
