<?php

class Woomp_Setting_Invoice extends WC_Settings_Page {

	public $setting_default = array();

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public function __construct() {
		$this->id    = 'woomp_setting_invoice';
		$this->label = __( '電子發票設定', 'woomp' );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 51 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	public function set_setting_default( $company ) {
		$this->setting_default = array(
			array(
				'title' => '尚未啟用' . esc_html( $company ) . '電子發票',
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
		$settings_tabs['woomp_setting_invoice'] = __( '電子發票設定', 'woomp' );
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
		$sections['ecpay']  = __( '綠界', 'woomp' );
		$sections['paynow'] = __( '立吉富', 'woomp' );
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
				if ( get_option( RY_WEI::$option_prefix . 'enabled_invoice', 1 ) === 'yes' ) {
					$settings = include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/settings-ecpay-invoice.php';
					return $settings;
				} else {
					$this->set_setting_default( '綠界' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'paynow':
				if ( get_option( 'wc_settings_tab_active_paynow_einvoice', 1 ) === 'yes' ) {
					$settings = array(
						'section_title'           => array(
							'name' => __( '立吉富電子發票設定', 'paynow-einvoice' ),
							'type' => 'title',
							'desc' => '',
							'id'   => 'wc_settings_tab_demo_section_title',
						),
						//'active_paynow_einvoice'  => array(
						//	'name' => __( 'Enable', 'paynow-einvoice' ),
						//	'type' => 'checkbox',
						//	'desc' => '',
						//	'id'   => 'wc_settings_tab_active_paynow_einvoice',
						//),
						'paynow_einvoice_sandbox' => array(
							'name' => __( 'Test Mode', 'paynow-einvoice' ),
							'type' => 'checkbox',
							'desc' => '',
							'id'   => 'wc_settings_tab_paynow_einvoice_sandbox',
						),
						'paynow_debug_log'        => array(
							'name'    => __( 'Debug Log', 'paynow-einvoice' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable Logging', 'paynow-einvoice' ),
							'default' => 'no',
							'desc'    => sprintf( __( 'Log PayNow E-Invoice message, inside <code>%s</code>', 'paynow-einvoice' ), wc_get_log_file_path( 'paynow-einvoice' ) ),
							'id'      => 'paynow_einvoice_debug_log_enabled',
						),
						'mem_cid'                 => array(
							'name' => __( 'Merchant ID', 'paynow-einvoice' ),
							'type' => 'text',
							'desc' => '',
							'id'   => 'wc_settings_tab_mem_cid',
						),
						'mem_password'            => array(
							'name' => __( 'Merchant Password', 'paynow-einvoice' ),
							'type' => 'text',
							'desc' => '',
							'id'   => 'wc_settings_tab_mem_password',
						),
						'issue_mode'              => array(
							'name'     => __( 'Issue Mode', 'paynow-einvoice' ),
							'type'     => 'radio',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'desc_tip' => true,
							'id'       => 'wc_settings_tab_issue_mode',
							'options'  => array(
								'auto'   => __( 'Automatic', 'paynow-einvoice' ),
								'manual' => __( 'Manual', 'paynow-einvoice' ),
							),
							'default'  => 'auto',
						),
						'issue_at'                => array(
							'name'     => __( 'Allowed Order Status', 'paynow-einvoice' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be issued automatically.' ),
							'id'       => 'wc_settings_tab_issue_at',
							'desc_tip' => true,
							'options'  => self::ww_get_order_status(),
						),
						'tax_type'                => array(
							'name'     => __( 'Tax Type', 'paynow-einvoice' ),
							'type'     => 'select',
							'desc'     => __( 'When input the product price, please input the price with tax-included.' ),
							'desc_tip' => true,
							'class'    => 'wc-enhanced-select',
							'options'  => array(
								'1' => '應稅(5%)',
								'2' => '零稅率(0%)',
								'3' => '免稅(0%)',
							),
							'id'       => 'wc_settings_tab_tax_type',
						),
						'carrier_type'            => array(
							'name'          => __( 'Carrier Type', 'paynow-einvoice' ),
							'type'          => 'checkbox',
							'desc'          => __( 'Mobile Code', 'paynow-einvoice' ),
							'default'       => 'yes',
							'id'            => 'wc_settings_tab_carrier_type_mobile_code',
							'checkboxgroup' => 'start',
						),
						array(
							'desc'          => __( 'Citizen Digital Certificate', 'paynow-einvoice' ),
							'id'            => 'wc_settings_tab_carrier_type_cdc_code',
							'default'       => 'yes',
							'type'          => 'checkbox',
							'checkboxgroup' => '',
						),
						array(
							'desc'          => __( 'Easy Card', 'paynow-einvoice' ),
							'id'            => 'wc_settings_tab_carrier_type_easycard_code',
							'default'       => 'yes',
							'type'          => 'checkbox',
							'checkboxgroup' => '',
						),
						// array(
						// 'desc'            => __( '捐贈發票', 'woocommerce' ),
						// 'id'              => 'wc_settings_tab_carrier_type_donate',
						// 'default'         => 'yes',
						// 'type'            => 'checkbox',
						// 'checkboxgroup'   => '',
						// ),
						'donate_org'              => array(
							'name'     => __( 'Donated Organization', 'paynow-einvoice' ),
							'type'     => 'textarea',
							'desc'     => '輸入捐增機構(每行一筆)，格式為：愛心碼|社福團體名稱',
							'desc_tip' => true,
							'id'       => 'wc_settings_tab_donate_org',
						),
						'section_end'             => array(
							'type' => 'sectionend',
							'id'   => 'wc_settings_tab_demo_section_end',
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

	private static function ww_get_order_status() {
		$order_statuses = array();

		foreach ( wc_get_order_statuses() as $slug => $name ) {
			if ( $slug == 'wc-cancelled' || $slug == 'wc-refunded' || $slug == 'wc-failed' ) {
				continue;
			}
			$order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
		}

		return $order_statuses;
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

return new Woomp_Setting_Invoice();
