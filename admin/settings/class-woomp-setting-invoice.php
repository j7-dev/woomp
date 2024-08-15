<?php

class Woomp_Setting_Invoice extends WC_Settings_Page {


	public $setting_default = [];

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public function __construct() {
		$this->id    = 'woomp_setting_invoice';
		$this->label = __( '電子發票設定', 'woomp' );
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 51 );
		add_action( 'woocommerce_sections_' . $this->id, [ $this, 'output_sections' ] );
		add_action( 'woocommerce_settings_' . $this->id, [ $this, 'output' ] );
		add_action( 'woocommerce_settings_save_' . $this->id, [ $this, 'save' ] );
	}

	public function set_setting_default( $company ) {
		$this->setting_default = [
			[
				'title' => '尚未啟用' . esc_html( $company ) . '電子發票',
				'desc'  => '請前往<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woomp_setting' ) . '">設定</a>',
				'id'    => 'empty_options',
				'type'  => 'title',
			],
		];
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
		// $sections['ecpay']       = __( '綠界', 'woomp' );
		$sections['ecpay']  = __( '綠界(好用版)', 'woomp' );
		$sections['ezpay']  = __( '藍新 ezPay', 'woomp' );
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
				if ( wc_string_to_bool( get_option( 'wc_woomp_enabled_ecpay_invoice' ) ) ) {
					$settings = [
						[
							'title' => '綠界電子發票設定',
							'type'  => 'title',
							'id'    => 'wc_woomp_general_setting',
						],
						[
							'title'   => '除錯資訊',
							'type'    => 'checkbox',
							'default' => 'no',
							'desc'    => sprintf( '紀錄日誌於以下路徑：<code>%s</code>', wc_get_log_file_path( 'woomp-ecpay-invoice' ) ),
							'id'      => 'wc_woomp_ecpay_invoice_debug_log_enabled',
						],
						[
							'title'    => __( 'Order number prefix', 'woomp' ),
							'id'       => 'wc_woomp_ecpay_invoice_order_prefix',
							'type'     => 'text',
							'desc'     => __( 'The prefix string of order number. Only letters and numbers allowed.', 'woomp' ),
							'desc_tip' => true,
						],

						[
							'type' => 'sectionend',
							'id'   => 'wc_woomp_general_setting',
						],
						[
							'title' => __( 'Invoice options', 'woomp' ),
							'id'    => 'invoice_options',
							'type'  => 'title',
						],
						[
							'name'     => __( 'Issue Mode', 'paynow-einvoice' ),
							'type'     => 'select',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'class'    => 'wc-enhanced-select',
							'desc_tip' => true,
							'id'       => 'wc_woomp_ecpay_invoice_issue_mode',
							'options'  => [
								'manual' => __( 'Issue Manual', 'woomp' ),
								'auto'   => __( 'Issue automatic', 'woomp' ),
							],
							'default'  => 'manual',
						],
						[
							'name'     => __( 'Allowed Order Status for issue', 'woomp' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be issued automatically.' ),
							'id'       => 'wc_woomp_ecpay_invoice_issue_at',
							'desc_tip' => true,
							'options'  => wc_get_order_statuses(),
						],
						[
							'name'     => __( 'Invalid mode', 'woomp' ),
							'type'     => 'select',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'class'    => 'wc-enhanced-select',
							'desc_tip' => true,
							'id'       => 'wc_woomp_ecpay_invoice_invalid_mode',
							'options'  => [
								'manual' => __( 'Invalid manual', 'woomp' ),
								'auto'   => __( 'Invalid automatic', 'woomp' ),
							],
							'default'  => 'auto',
						],
						[
							'name'     => __( 'Allowed Order Status for invalid', 'woomp' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be invalid automatically.' ),
							'id'       => 'wc_woomp_ecpay_invoice_invalid_at',
							'desc_tip' => true,
							'options'  => [
								'wc-refunded' => __( 'Refunded', 'woocommerce' ),
								'wc-failed'   => __( 'Failed', 'woocommerce' ),
							],
						],
						[
							'name'    => __( 'Carrier Type', 'paynow-einvoice' ),
							'desc'    => __( 'Allowed invoice carrier type', 'woomp' ),
							'id'      => 'wc_woomp_ecpay_invoice_carrier_type',
							'class'   => 'wc-enhanced-select',
							'type'    => 'multiselect',
							'options' => [
								__( 'Cloud Invoice', 'woomp' )  => __( 'Cloud Invoice', 'woomp' ),
								__( 'Mobile Code', 'paynow-einvoice' ) => __( 'Mobile Code', 'paynow-einvoice' ),
								__( 'Citizen Digital Certificate', 'paynow-einvoice' )    => __( 'Citizen Digital Certificate', 'paynow-einvoice' ),
								__( 'Paper Invoice', 'woomp' )  => __( 'Paper Invoice', 'woomp' ),
							],
						],

						[
							'name'        => __( 'Donated Organization', 'paynow-einvoice' ),
							'type'        => 'textarea',
							'desc'        => '輸入捐增機構(每行一筆)，格式為：愛心碼|社福團體名稱，預設為伊甸社會福利基金會',
							'desc_tip'    => true,
							'id'          => 'wc_woomp_ecpay_invoice_donate_org',
							'placeholder' => '25885|伊甸社會福利基金會',
						],
						[
							'id'   => 'invoice_options',
							'type' => 'sectionend',
						],
						[
							'title' => '商家資料設定',
							'type'  => 'title',
							'id'    => 'wc_woomp_ecpay_invoice_api_settings',
						],
						[
							'title' => '測試模式',
							'type'  => 'checkbox',
							'desc'  => '請勾選時會測試模式，未勾選則會使用下方資料作為正式交易環境',
							'id'    => 'wc_woomp_ecpay_invoice_testmode_enabled',
						],
						[
							'title' => '商家編號',
							'type'  => 'text',
							'desc'  => '請輸入正式商家代號',
							'id'    => 'wc_woomp_ecpay_invoice_merchant_id',
						],
						[
							'title' => '正式 HashKey',
							'type'  => 'text',
							'desc'  => '請輸入 HashKey',
							'id'    => 'wc_woomp_ecpay_invoice_hashkey',
						],
						[
							'title' => '正式 HashIV',
							'type'  => 'text',
							'desc'  => '請輸入 HashIV',
							'id'    => 'wc_woomp_ecpay_invoice_hashiv',
						],
						[
							'type' => 'sectionend',
							'id'   => 'wc_woomp_ecpay_invoice_api_settings',
						],
					];
					return $settings;
				} else {
					$this->set_setting_default( '綠界' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'ezpay':
				if ( wc_string_to_bool( get_option( 'wc_woomp_enabled_ezpay_invoice' ) ) ) {
					$settings = [
						[
							'title' => 'ezPay 電子發票設定',
							'type'  => 'title',
							'id'    => 'wc_woomp_general_setting',
						],
						[
							'title'   => '除錯資訊',
							'type'    => 'checkbox',
							'default' => 'no',
							'desc'    => sprintf( '紀錄日誌於以下路徑：<code>%s</code>', wc_get_log_file_path( 'woomp-ezpay-invoice' ) ),
							'id'      => 'wc_woomp_ezpay_invoice_debug_log_enabled',
						],
						[
							'title'    => __( 'Order number prefix', 'woomp' ),
							'id'       => 'wc_woomp_ezpay_invoice_order_prefix',
							'type'     => 'text',
							'desc'     => __( 'The prefix string of order number. Only letters and numbers allowed.', 'woomp' ),
							'desc_tip' => true,
						],

						[
							'type' => 'sectionend',
							'id'   => 'wc_woomp_general_setting',
						],
						[
							'title' => __( 'Invoice options', 'woomp' ),
							'id'    => 'invoice_options',
							'type'  => 'title',
						],
						[
							'name'     => __( 'Issue Mode', 'paynow-einvoice' ),
							'type'     => 'select',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'class'    => 'wc-enhanced-select',
							'desc_tip' => true,
							'id'       => 'wc_woomp_ezpay_invoice_issue_mode',
							'options'  => [
								'manual' => __( 'Issue Manual', 'woomp' ),
								'auto'   => __( 'Issue automatic', 'woomp' ),
							],
							'default'  => 'manual',
						],
						[
							'name'     => __( 'Allowed Order Status for issue', 'woomp' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be issued automatically.' ),
							'id'       => 'wc_woomp_ezpay_invoice_issue_at',
							'desc_tip' => true,
							'options'  => wc_get_order_statuses(),
						],
						[
							'name'     => __( 'Invalid mode', 'woomp' ),
							'type'     => 'select',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'class'    => 'wc-enhanced-select',
							'desc_tip' => true,
							'id'       => 'wc_woomp_ezpay_invoice_invalid_mode',
							'options'  => [
								'manual' => __( 'Invalid manual', 'woomp' ),
								'auto'   => __( 'Invalid automatic', 'woomp' ),
							],
							'default'  => 'auto',
						],
						[
							'name'     => __( 'Allowed Order Status for invalid', 'woomp' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be invalid automatically.' ),
							'id'       => 'wc_woomp_ezpay_invoice_invalid_at',
							'desc_tip' => true,
							'options'  => [
								'wc-refunded' => __( 'Refunded', 'woocommerce' ),
								'wc-failed'   => __( 'Failed', 'woocommerce' ),
							],
						],
						[
							'name'    => __( 'Carrier Type', 'paynow-einvoice' ),
							'desc'    => __( 'Allowed invoice carrier type', 'woomp' ),
							'id'      => 'wc_woomp_ezpay_invoice_carrier_type',
							'class'   => 'wc-enhanced-select',
							'type'    => 'multiselect',
							'options' => [
								__( 'ezPay 電子發票載具', 'woomp' )  => __( 'ezPay 電子發票載具', 'woomp' ),
								__( 'Mobile Code', 'paynow-einvoice' ) => __( 'Mobile Code', 'paynow-einvoice' ),
								__( 'Citizen Digital Certificate', 'paynow-einvoice' )    => __( 'Citizen Digital Certificate', 'paynow-einvoice' ),
							],
						],

						[
							'name'        => __( 'Donated Organization', 'paynow-einvoice' ),
							'type'        => 'textarea',
							'desc'        => '輸入捐增機構(每行一筆)，格式為：愛心碼|社福團體名稱，預設為伊甸社會福利基金會',
							'desc_tip'    => true,
							'id'          => 'wc_woomp_ezpay_invoice_donate_org',
							'placeholder' => '25885|伊甸社會福利基金會',
						],
						[
							'id'   => 'invoice_options',
							'type' => 'sectionend',
						],
						[
							'title' => '商家資料設定（測試模式）',
							'desc'  => '請於 ezPay 電子發票測試平台 <a href="https://cinv.ezpay.com.tw/">https://cinv.ezpay.com.tw/</a> 申請會員並建立測試商店',
							'type'  => 'title',
							'id'    => 'wc_woomp_ezpay_invoice_api_settings_test',
						],
						[
							'title' => '測試模式',
							'type'  => 'checkbox',
							'desc'  => '勾選時為測試模式',
							'id'    => 'wc_woomp_ezpay_invoice_testmode_enabled',
						],
						[
							'title' => '測試商家編號',
							'type'  => 'text',
							'desc'  => '請輸入測試商家代號',
							'id'    => 'wc_woomp_ezpay_invoice_merchant_id_test',
						],
						[
							'title' => '測試 HashKey',
							'type'  => 'text',
							'desc'  => '請輸入測試 HashKey',
							'id'    => 'wc_woomp_ezpay_invoice_hashkey_test',
						],
						[
							'title' => '測試 HashIV',
							'type'  => 'text',
							'desc'  => '請輸入測試 HashIV',
							'id'    => 'wc_woomp_ezpay_invoice_hashiv_test',
						],
						[
							'type' => 'sectionend',
							'id'   => 'wc_woomp_ezpay_invoice_api_settings_test',
						],
						[
							'title' => '商家資料設定（正式環境）',
							'desc'  => '請於 ezPay 電子發票加值服務平台 <a href="https://inv.ezpay.com.tw/">https://inv.ezpay.com.tw/</a> 申請會員並建立商店',
							'type'  => 'title',
							'id'    => 'wc_woomp_ezpay_invoice_api_settings',
						],
						[
							'title' => '正式商家編號',
							'type'  => 'text',
							'desc'  => '請輸入正式商家代號',
							'id'    => 'wc_woomp_ezpay_invoice_merchant_id',
						],
						[
							'title' => '正式 HashKey',
							'type'  => 'text',
							'desc'  => '請輸入正式 HashKey',
							'id'    => 'wc_woomp_ezpay_invoice_hashkey',
						],
						[
							'title' => '正式 HashIV',
							'type'  => 'text',
							'desc'  => '請輸入正式 HashIV',
							'id'    => 'wc_woomp_ezpay_invoice_hashiv',
						],
						[
							'type' => 'sectionend',
							'id'   => 'wc_woomp_ezpay_invoice_api_settings',
						],
					];
					return $settings;
				} else {
					$this->set_setting_default( 'ezPay' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'paynow':
				if ( get_option( 'wc_settings_tab_active_paynow_einvoice', 1 ) === 'yes' ) {
					$settings = [
						'section_title'           => [
							'name' => __( '立吉富電子發票設定', 'paynow-einvoice' ),
							'type' => 'title',
							'desc' => '',
							'id'   => 'wc_settings_tab_demo_section_title',
						],
						// 'active_paynow_einvoice'  => array(
						// 'name' => __( 'Enable', 'paynow-einvoice' ),
						// 'type' => 'checkbox',
						// 'desc' => '',
						// 'id'   => 'wc_settings_tab_active_paynow_einvoice',
						// ),
						'paynow_einvoice_sandbox' => [
							'name' => __( 'Test Mode', 'paynow-einvoice' ),
							'type' => 'checkbox',
							'desc' => '',
							'id'   => 'wc_settings_tab_paynow_einvoice_sandbox',
						],
						'paynow_debug_log'        => [
							'name'    => __( 'Debug Log', 'paynow-einvoice' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable Logging', 'paynow-einvoice' ),
							'default' => 'no',
							'desc'    => sprintf( __( 'Log PayNow E-Invoice message, inside <code>%s</code>', 'paynow-einvoice' ), wc_get_log_file_path( 'paynow-einvoice' ) ),
							'id'      => 'paynow_einvoice_debug_log_enabled',
						],
						'mem_cid'                 => [
							'name' => __( 'Merchant ID', 'paynow-einvoice' ),
							'type' => 'text',
							'desc' => '',
							'id'   => 'wc_settings_tab_mem_cid',
						],
						'mem_password'            => [
							'name' => __( 'Merchant Password', 'paynow-einvoice' ),
							'type' => 'text',
							'desc' => '',
							'id'   => 'wc_settings_tab_mem_password',
						],
						'issue_mode'              => [
							'name'     => __( 'Issue Mode', 'paynow-einvoice' ),
							'type'     => 'radio',
							'desc'     => __( 'You can issue the e-invoice manually even if you choose Automatic mode' ),
							'desc_tip' => true,
							'id'       => 'wc_settings_tab_issue_mode',
							'options'  => [
								'auto'   => __( 'Automatic', 'paynow-einvoice' ),
								'manual' => __( 'Manual', 'paynow-einvoice' ),
							],
							'default'  => 'auto',
						],
						'issue_at'                => [
							'name'     => __( 'Allowed Order Status', 'paynow-einvoice' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'When order status changes to the status, the e-invoice will be issued automatically.' ),
							'id'       => 'wc_settings_tab_issue_at',
							'desc_tip' => true,
							'options'  => self::ww_get_order_status(),
						],
						'tax_type'                => [
							'name'     => __( 'Tax Type', 'paynow-einvoice' ),
							'type'     => 'select',
							'desc'     => __( 'When input the product price, please input the price with tax-included.' ),
							'desc_tip' => true,
							'class'    => 'wc-enhanced-select',
							'options'  => [
								'1' => '應稅(5%)',
								'2' => '零稅率(0%)',
								'3' => '免稅(0%)',
							],
							'id'       => 'wc_settings_tab_tax_type',
						],
						'carrier_type'            => [
							'name'          => __( '雲端會員載具', 'paynow-einvoice' ),
							'type'          => 'checkbox',
							'desc'          => __( '雲端會員載具', 'paynow-einvoice' ),
							'default'       => 'yes',
							'id'            => 'wc_settings_tab_carrier_type_cloud',
							'checkboxgroup' => 'start',
						],
						[
							'name'          => __( 'Carrier Type', 'paynow-einvoice' ),
							'type'          => 'checkbox',
							'desc'          => __( 'Mobile Code', 'paynow-einvoice' ),
							'default'       => 'yes',
							'id'            => 'wc_settings_tab_carrier_type_mobile_code',
							'checkboxgroup' => '',
						],
						[
							'desc'          => __( 'Citizen Digital Certificate', 'paynow-einvoice' ),
							'id'            => 'wc_settings_tab_carrier_type_cdc_code',
							'default'       => 'yes',
							'type'          => 'checkbox',
							'checkboxgroup' => '',
						],
						[
							'desc'          => __( 'Easy Card', 'paynow-einvoice' ),
							'id'            => 'wc_settings_tab_carrier_type_easycard_code',
							'default'       => 'yes',
							'type'          => 'checkbox',
							'checkboxgroup' => '',
						],
						// array(
						// 'desc'            => __( '捐贈發票', 'woocommerce' ),
						// 'id'              => 'wc_settings_tab_carrier_type_donate',
						// 'default'         => 'yes',
						// 'type'            => 'checkbox',
						// 'checkboxgroup'   => '',
						// ),
						'donate_org'              => [
							'name'     => __( 'Donated Organization', 'paynow-einvoice' ),
							'type'     => 'textarea',
							'desc'     => '輸入捐增機構(每行一筆)，格式為：愛心碼|社福團體名稱，例如：919|創世基金會',
							'desc_tip' => false,
							'id'       => 'wc_settings_tab_donate_org',
						],
						'section_end'             => [
							'type' => 'sectionend',
							'id'   => 'wc_settings_tab_demo_section_end',
						],
					];
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
		$order_statuses = [];

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
		$order_statuses = [
			'' => __( 'No action', 'paynow-shipping' ),
		];

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
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=woomp_setting_invoice&section=ecpay' ) );
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
