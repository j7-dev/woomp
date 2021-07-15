<?php

use \MGC\Logger\Logger;

class Woomp_Setting_Invoice {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 60 );
		add_action( 'woocommerce_settings_tabs_woomp_setting_invoice', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_woomp_setting_invoice', __CLASS__ . '::update_settings' );
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woomp_setting_invoice'] = __( '電子發票設定', 'woomp' );
		return $settings_tabs;
	}


	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		woocommerce_admin_fields( self::get_settings() );
	}


	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}


	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings() {

		if ( get_option( RY_WEI::$option_prefix . 'enabled_invoice', 1 ) === 'yes' ) {

			$order_statuses = wc_get_order_statuses();
			$paid_status    = array();
			foreach ( wc_get_is_paid_statuses() as $status ) {
				$paid_status[] = $order_statuses[ 'wc-' . $status ];
			}
			$paid_status = implode( ', ', $paid_status );

			$settings = array(
				array(
					'title' => __( 'Base options', 'ry-woocommerce-ecpay-invoice' ),
					'id'    => 'base_options',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Enable/Disable', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'enabled_invoice',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Enable ECPay invoice method', 'ry-woocommerce-ecpay-invoice' ),
				),
				array(
					'title'   => __( 'Debug log', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'invoice_log',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Enable logging', 'ry-woocommerce-ecpay-invoice' ) . '<br>'
						. sprintf(
							/* translators: %s: Path of log file */
							__( 'Log ECPay invoice events/message, inside %s', 'ry-woocommerce-ecpay-invoice' ),
							'<code>' . WC_Log_Handler_File::get_log_file_path( 'ry_ecpay_invoice' ) . '</code>'
						),
				),
				array(
					'title'    => __( 'Order no prefix', 'ry-woocommerce-ecpay-invoice' ),
					'id'       => RY_WEI::$option_prefix . 'order_prefix',
					'type'     => 'text',
					'desc'     => __( 'The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-ecpay-invoice' ),
					'desc_tip' => true,
				),
				array(
					'title'   => __( 'Show invoice number', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'show_invoice_number',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Show invoice number in Frontend order list', 'ry-woocommerce-ecpay-invoice' ),
				),
				array(
					'title'   => __( 'Move billing company', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'move_billing_company',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Move billing company to invoice area', 'ry-woocommerce-ecpay-invoice' ),
				),
				array(
					'id'   => 'base_options',
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'Invoice options', 'ry-woocommerce-ecpay-invoice' ),
					'id'    => 'invoice_options',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'support paper type', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'support_carruer_type_none',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'You need print invoice and seed to orderer.', 'ry-woocommerce-ecpay-invoice' ),
				),
				array(
					'title'   => __( 'Get mode', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'get_mode',
					'type'    => 'select',
					'default' => 'manual',
					'options' => array(
						'manual'         => _x( 'manual', 'get mode', 'ry-woocommerce-ecpay-invoice' ),
						'auto_paid'      => _x( 'auto ( when order paid )', 'get mode', 'ry-woocommerce-ecpay-invoice' ),
						'auto_completed' => _x( 'auto ( when order completed )', 'get mode', 'ry-woocommerce-ecpay-invoice' ),
					),
					/* translators: %s: paid status */
					'desc'    => sprintf( __( 'Order paid status: %s', 'ry-woocommerce-ecpay-invoice' ), $paid_status ),
				),
				array(
					'title'   => __( 'Delay get days', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'get_delay_days',
					'type'    => 'text',
					'default' => '0',
					'desc'    => '如設定為 <strong>0</strong> 天表示立即開立。<br>'
						. '將於達成自動開立的條件下連結至綠界的系統，並設定延遲 N 天後<strong>自動完成</strong>開立發票的相關動作。<br>'
						. '受限於綠界 API 的限制，於設定自動開立到發票完成開立的這段期間中，只能至綠界的管理後台進行待開立發票的取消動作。',
				),
				array(
					'title'   => __( 'Invalid mode', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'invalid_mode',
					'type'    => 'select',
					'default' => 'manual',
					'options' => array(
						'manual'       => _x( 'manual', 'invalid mode', 'ry-woocommerce-ecpay-invoice' ),
						'auto_cancell' => _x( 'auto ( when order status cancelled OR refunded )', 'invalid mode', 'ry-woocommerce-ecpay-invoice' ),
					),
				),
				array(
					'id'   => 'invoice_options',
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'API credentials', 'ry-woocommerce-ecpay-invoice' ),
					'id'    => 'api_options',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'ecpay_testmode',
					'type'    => 'checkbox',
					'default' => 'yes',
					'desc'    => __( 'Enable ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice' ),
				),
				array(
					'title'   => __( 'MerchantID', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'ecpay_MerchantID',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashKey', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'ecpay_HashKey',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashIV', 'ry-woocommerce-ecpay-invoice' ),
					'id'      => RY_WEI::$option_prefix . 'ecpay_HashIV',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'id'   => 'api_options',
					'type' => 'sectionend',
				),
			);

			return $settings;
		}

		$settings = array(
			array(
				'title' => __( '尚未啟用電子發票功能', 'woomp' ),
				'desc'  => '請前往<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woomp_setting' ) . '">設定</a>',
				'id'    => 'empty_options',
				'type'  => 'title',
			),
		);

		return $settings;

	}

	public static function set_checkbox_toggle() {
		global $pagenow;
		if ( 'admin.php' === $pagenow ) { ?>
		<style>
			input.toggle[type=checkbox]{
				height: 0;
				width: 0;
				visibility: hidden;
			}

			input.toggle + label {
				cursor: pointer;
				text-indent: -9999px;
				width: 50px;
				height: 26px;
				background: grey;
				display: block;
				border-radius: 100px;
				position: relative;
			}

			input.toggle + label:after {
				content: '';
				position: absolute;
				top: 3px;
				left: 3px;
				width: 20px;
				height: 20px;
				background: #fff;
				border-radius: 40px;
				transition: 0.3s;
			}

			input.toggle:checked + label {
				background: #cc99c2;
			}

			input.toggle:checked + label:after {
				left: calc(100% - 3px);
				transform: translateX(-100%);
			}

			input.toggle + label:active:after {
				width: 130px;
			}

			.form-table td fieldset label[for=wc_woomp_setting_replace],
			.form-table td fieldset label[for=wc_woomp_setting_billing_country_pos],
			.form-table td fieldset label[for=wc_woomp_setting_tw_address],
			.form-table td fieldset label[for=wc_woomp_setting_one_line_address],
			.form-table td fieldset label[for=wc_woomp_setting_cod_payment],
			.form-table td fieldset label[for=wc_woomp_setting_product_variations_ui] {
				margin-top: 0!important;
				margin-left: -10px!important;
				margin-bottom: 3px!important;
			}

			legend + label[for=wc_woomp_setting_replace]:after,
			legend + label[for=wc_woomp_setting_billing_country_pos]:after,
			legend + label[for=wc_woomp_setting_tw_address]:after,
			legend + label[for=wc_woomp_setting_one_line_address]:after,
			legend + label[for=wc_woomp_setting_cod_payment]:after,
			legend + label[for=wc_woomp_setting_product_variations_ui]:after {
				content: '停用 / 啟用';
				margin-left: 10px;
			}
		</style>
		<script>
			var $ = jQuery.noConflict();
			$(document).ready(function(){
				$('#wc_woomp_setting_replace').after('<label for="wc_woomp_setting_replace">Toggle</label>')
				$('#wc_woomp_setting_billing_country_pos').after('<label for="wc_woomp_setting_billing_country_pos">Toggle</label>')
				$('#wc_woomp_setting_tw_address').after('<label for="wc_woomp_setting_tw_address">Toggle</label>')
				$('#wc_woomp_setting_one_line_address').after('<label for="wc_woomp_setting_one_line_address">Toggle</label>')
				$('#wc_woomp_setting_cod_payment').after('<label for="wc_woomp_setting_cod_payment">Toggle</label>')
				$('#wc_woomp_setting_product_variations_ui').after('<label for="wc_woomp_setting_product_variations_ui">Toggle</label>')
			})
		</script>
			<?php
		}
	}

}

Woomp_Setting_Invoice::init();
