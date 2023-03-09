<?php

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
		$sections['linepay']  = __( 'LINE Pay', 'woomp' );
		if ( ! in_array( 'PChomePay-Cart-for-WooCommerce/pchomepay.php', WOOMP_ACTIVE_PLUGINS, true ) && ! in_array( 'PChomePay-Cart-for-WooCommerce-master/pchomepay.php', WOOMP_ACTIVE_PLUGINS, true ) ) {
			$sections['pchomepay'] = __( '支付連', 'woomp' );
		}
		$sections['payuni']   = __( 'PAYUNi', 'woomp' );
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}


	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public function get_settings( $section = null ) {

		switch ( $section ) {
			case 'payuni':
				if ( get_option( 'wc_woomp_enabled_payuni_gateway', 1 ) === 'yes' ) {
					$settings = include WOOMP_PLUGIN_DIR . 'includes/payuni/settings/gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '統一金流' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'ecpay':
				if ( get_option( RY_WT::$option_prefix . 'enabled_ecpay_gateway', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '綠界' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'newebpay':
				if ( get_option( RY_WT::$option_prefix . 'enabled_newebpay_gateway', 1 ) === 'yes' ) {
					$settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway.php';
					return $settings;
				} else {
					$this->set_setting_default( '藍新' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'smilepay':
				if ( get_option( RY_WT::$option_prefix . 'enabled_smilepay_gateway', 1 ) === 'yes' ) {
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
			case 'linepay':
				if ( wc_string_to_bool( get_option( 'woocommerce_linepay_enabled' ) ) ) {
					// Administrator's refundable status.
					$admin_statuses = wc_get_order_statuses( WC_Gateway_LINEPay_Const::USER_STATUS_ADMIN );

					// Consumer's refundable status.
					$customer_statuses = wc_get_order_statuses( WC_Gateway_LINEPay_Const::USER_STATUS_CUSTOMER );

					$settings = array(
						array(
							'title' => __( 'LINE Pay 金流設定', 'woomp' ),
							'type'  => 'title',
							'id'    => 'linepay',
						),
						array(
							'title'   => __( 'Log Enable', 'woocommerce-gateway-linepay' ),
							'type'    => 'checkbox',
							'default' => 'no',
							'desc'    => sprintf( __( 'Your log information will be saved in the following location.', 'woocommerce-gateway-linepay' ) . '<code>%s</code>', wc_get_log_file_path( 'linepay' ) ),
							'id'      => 'linepay_log_enabled',
						),
						array(
							'title'    => __( 'Log Level', 'woocommerce-gateway-linepay' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'Select the level of information to log. You can select Debug, Info, or Error. However, please note that website performance may decrease if you log too much information. We recommend you log only the important items at the Error level.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => 'error',
							'options'  => array(
								'error' => __( 'Error', 'woocommerce-gateway-linepay' ),
								'info'  => __( 'Info', 'woocommerce-gateway-linepay' ),
								'Debug' => __( 'debug', 'woocommerce-gateway-linepay' ),
							),
							'id'       => 'linepay_log_level',
						),
						array(
							'title'   => __( 'Sandbox Mode', 'woocommerce-gateway-linepay' ),
							'type'    => 'checkbox',
							'desc'    => __( 'Enable sandbox mode.', 'woocommerce-gateway-linepay' ),
							'default' => 'no',
							'id'      => 'linepay_sandbox',
						),
						array(
							'title'    => __( 'Sandbox Channel ID', 'woocommerce-gateway-linepay' ),
							'type'     => 'text',
							'desc'     => __( 'Enter your Channel ID.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => '',
							'id'       => 'linepay_sandbox_channel_id',
						),
						array(
							'title'    => __( 'Sandbox Channel Secret Key', 'woocommerce-gateway-linepay' ),
							'type'     => 'text',
							'desc'     => __( 'Enter your Channel SecretKey.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => '',
							'id'       => 'linepay_sandbox_channel_secret',
						),
						array(
							'title'    => __( 'Channel ID', 'woocommerce-gateway-linepay' ),
							'type'     => 'text',
							'desc'     => __( 'Enter your Channel ID.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => '',
							'id'       => 'linepay_channel_id',
						),
						array(
							'title'    => __( 'Channel Secret Key', 'woocommerce-gateway-linepay' ),
							'type'     => 'text',
							'desc'     => __( 'Enter your Channel SecretKey.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => '',
							'id'       => 'linepay_channel_secret',
						),
						array(
							'title'    => __( 'Payment Type', 'woocommerce-gateway-linepay' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'You can only select regular payment.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => 'normal',
							'options'  => array(
								'normal' => __( 'Normal', 'woocommerce-gateway-linepay' ),
							),
							'id'       => 'linepay_payment_type',
						),
						array(
							'title'    => __( 'Payment Action', 'woocommerce-gateway-linepay' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'desc'     => __( 'You can only select auto-acquisition.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => 'authorization/capture',
							'options'  => array(
								'authorization/capture' => __( 'Authorization/Capture', 'woocommerce-gateway-linepay' ),
							),
							'id'       => 'linepay_payment_action',
						),
						array(
							'title'    => __( 'Statuses that Allow Managers to Refund Orders', 'woocommerce-gateway-linepay' ),
							'type'     => 'multiselect',
							'class'    => 'chosen_select',
							'desc'     => __( 'Please select the statuses in which managers can refund orders.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'options'  => $admin_statuses,
							'id'       => 'linepay_admin_refund',
						),
						array(
							'title'    => __( 'Statuses that Allow Customers to Request Refunds', 'woocommerce-gateway-linepay' ),
							'type'     => 'multiselect',
							'class'    => 'chosen_select',
							'desc'     => __( 'Please select the statuses that allow customers to request refunds. Some statuses do not allow refunds.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'options'  => $customer_statuses,
							'id'       => 'linepay_customer_refund',
						),
						array(
							'title'    => __( 'General Logo Size', 'woocommerce-gateway-linepay' ),
							'type'     => 'select',
							'desc'     => __( 'Please select the size of your main LINE Pay logo.', 'woocommerce-gateway-linepay' ),
							'desc_tip' => true,
							'default'  => '5',
							'options'  => array(
								'1' => '238x78',
								'2' => '119x39',
								'3' => '98x32',
								'4' => '85x28',
								'5' => '74x24',
								'6' => '61x20',
							),
							'id'       => 'linepay_general_logo_size',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'linepay',
						),
						// 'custom_logo'            => array(
						// 'title'    => __( 'Custom Logo', 'woocommerce-gateway-linepay' ),
						// 'id'       => 'wc_linepay_custom_logo',
						// 'type'     => 'text',
						// 'desc_tip' => true,
						// 'desc'     => __( 'You can also customize your LINE Pay logo by uploading an image or entering an image URL.', 'woocommerce-gateway-linepay' ),
						// ),
					);
					return $settings;
				} else {
					$this->set_setting_default( 'LINE Pay' );
					$settings = $this->setting_default;
					return $settings;
				}
				break;
			case 'pchomepay':
				if ( wc_string_to_bool( get_option( 'woocommerce_pchomepay_enabled' ) ) ) {
					$settings = array(
						array(
							'title' => __( '支付連金流設定', 'woomp' ),
							'type'  => 'title',
							'id'    => 'payment_general_setting',
						),
						array(
							'id'      => 'woomp_pchomepay_test_mode',
							'title'   => __( '測試模式', 'woocommerce' ),
							'type'    => 'checkbox',
							'desc'    => __( '使用支付連 SandBox 測試環境。', 'woocommerce' ),
							'default' => 'no',
						),
						array(
							'id'      => 'woomp_pchomepay_app_id',
							'title'   => __( 'APP ID', 'woocommerce' ),
							'type'    => 'text',
							'default' => '',
						),
						array(
							'id'      => 'woomp_pchomepay_secret',
							'title'   => __( 'SECRET', 'woocommerce' ),
							'type'    => 'text',
							'desc'    => __( '供正式環境使用之Secret。', 'woocommerce' ),
							'default' => '',
						),
						array(
							'id'      => 'woomp_pchomepay_sandbox_secret',
							'title'   => __( 'SECRET for test mode', 'woocommerce' ),
							'type'    => 'text',
							'desc'    => __( '供測試環境使用之Secret。', 'woocommerce' ),
							'default' => '',
						),
						array(
							'id'      => 'woomp_pchomepay_debug',
							'title'   => __( 'Debug log', 'woocommerce' ),
							'type'    => 'checkbox',
							'default' => '',
							'desc'    => sprintf( __( '記錄 PChomePay 事件，位於 %s', 'woocommerce' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'pchomepay' ) . '</code>' ),
						),
						array(
							'id'      => 'woomp_pchomepay_payment_methods',
							'title'   => __( '付款方式', 'woocommerce' ),
							'type'    => 'multiselect',
							'class'   => 'chosen_select',
							'desc'    => __( '按下 CTRL 與 滑鼠右鍵 以選擇多種付款方式<br>7-11超商取貨不適用金額低於65元之訂單。', 'woocommerce' ),
							'options' => array(
								'CARD' => __( '信用卡' ),
								'ATM'  => __( 'ATM' ),
								'EACH' => __( '銀行支付' ),
								'ACCT' => __( '支付連餘額支付' ),
								'IPL7' => __( '7-11超商取貨' ),
							),
						),
						array(
							'id'      => 'woomp_pchomepay_card_installment',
							'title'   => __( '信用卡分期', 'woocommerce' ),
							'type'    => 'multiselect',
							'class'   => 'chosen_select',
							'desc'    => __( '信用卡分期不適用於金額低於30元之訂單。', 'woocommerce' ),
							'options' => array(
								'CRD_0'  => __( '一次付清', 'woocommerce' ),
								'CRD_3'  => __( '3 期', 'woocommerce' ),
								'CRD_6'  => __( '6 期', 'woocommerce' ),
								'CRD_12' => __( '12 期', 'woocommerce' ),
							),
						),
						array(
							'id'      => 'woomp_pchomepay_atm_expiredate',
							'title'   => __( 'ATM 虛擬帳號繳費期限', 'woocommerce' ),
							'type'    => 'text',
							'desc'    => __( '請輸入 ATM 虛擬帳號繳費期限 (1~5 天)，預設 5 天。', 'woocommerce' ),
							'default' => 5,
						),
						array(
							'id'      => 'woomp_pchomepay_card_last_number',
							'title'   => __( '記錄信用卡末四碼', 'woocommerce' ),
							'type'    => 'checkbox',
							'desc'    => __( '紀錄買家信用卡末四碼資訊於訂單備註。', 'woocommerce' ),
							'default' => 'yes',
						),
						array(
							'id'    => 'woomp_pchomepay_customize_order_received_text',
							'title' => __( '訂單成立後顯示訊息', 'woocommerce' ),
							'type'  => 'textarea',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'payment_general_setting',
						),
					);
					return $settings;
				} else {
					$this->set_setting_default( '支付連' );
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
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=woomp_setting_gateway&section=ecpay' ) );
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
