<?php

use \MGC\Logger\Logger;

class Woomp_Setting_Gateway {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 60 );
		add_action( 'woocommerce_settings_tabs_woomp_setting_gateway', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_woomp_setting_gateway', __CLASS__ . '::update_settings' );
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woomp_setting_gateway'] = __( '金流設定', 'woomp' );
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

		if ( get_option( RY_WT::$option_prefix . 'ecpay_gateway', 1 ) === 'yes' ) {

			$settings = array(
				array(
					'title' => __( 'Base options', 'ry-woocommerce-tools' ),
					'id'    => 'base_options',
					'type'  => 'title',
				),
				// array(
				// 'title'   => __( 'Enable/Disable', 'woocommerce' ),
				// 'id'      => RY_WT::$option_prefix . 'ecpay_gateway',
				// 'type'    => 'checkbox',
				// 'default' => 'no',
				// 'desc'    => __( 'Enable ECPay gateway method', 'ry-woocommerce-tools' ),
				// ),
				array(
					'title'   => __( 'Debug log', 'woocommerce' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_gateway_log',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Enable logging', 'woocommerce' ) . '<br>'
						. sprintf(
							/* translators: %s: Path of log file */
							__( 'Log ECPay gateway events/message, inside %s', 'ry-woocommerce-tools' ),
							'<code>' . WC_Log_Handler_File::get_log_file_path( 'ry_ecpay_gateway' ) . '</code>'
						),
				),
				array(
					'title'    => __( 'Order no prefix', 'ry-woocommerce-tools' ),
					'id'       => RY_WT::$option_prefix . 'ecpay_gateway_order_prefix',
					'type'     => 'text',
					'desc'     => __( 'The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-tools' ),
					'desc_tip' => true,
				),
				array(
					'id'   => 'base_options',
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'API credentials', 'ry-woocommerce-tools' ),
					'id'    => 'api_options',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'ECPay gateway sandbox', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_gateway_testmode',
					'type'    => 'checkbox',
					'default' => 'yes',
					'desc'    => __( 'Enable ECPay gateway sandbox', 'ry-woocommerce-tools' ),
				),
				array(
					'title'   => __( 'MerchantID', 'ECPay', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_gateway_MerchantID',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashKey', 'ECPay', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_gateway_HashKey',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashIV', 'ECPay', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_gateway_HashIV',
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
				'title' => __( '尚未啟用金流功能', 'woomp' ),
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

Woomp_Setting_Gateway::init();
