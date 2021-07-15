<?php

use \MGC\Logger\Logger;

class Woomp_Setting_Shipping {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 60 );
		add_action( 'woocommerce_settings_tabs_woomp_setting_shipping', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_woomp_setting_shipping', __CLASS__ . '::update_settings' );
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woomp_setting_shipping'] = __( '物流設定', 'woomp' );
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

		if ( get_option( RY_WT::$option_prefix . 'ecpay_shipping', 1 ) === 'yes' ) {

			$settings = array(
				array(
					'title' => __( 'Base options', 'ry-woocommerce-tools' ),
					'id'    => 'base_options',
					'type'  => 'title',
				),
				//array(
				//	'title'   => __( 'Enable/Disable', 'woocommerce' ),
				//	'id'      => RY_WT::$option_prefix . 'ecpay_shipping',
				//	'type'    => 'checkbox',
				//	'default' => 'no',
				//	'desc'    => __( 'Enable ECPay shipping method', 'ry-woocommerce-tools' ),
				//),
				array(
					'title'   => __( 'Debug log', 'woocommerce' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_log',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Enable logging', 'woocommerce' ) . '<br>'
						. sprintf(
							/* translators: %s: Path of log file */
							__( 'Log ECPay shipping events/message, inside %s', 'ry-woocommerce-tools' ),
							'<code>' . WC_Log_Handler_File::get_log_file_path( 'ry_ecpay_shipping' ) . '</code>'
						),
				),
				array(
					'title'   => __( 'Log status change', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_log_status_change',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Log status change at order notes.', 'ry-woocommerce-tools' ),
				),
				array(
					'title'   => __( 'Auto get shipping payment no', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_auto_get_no',
					'type'    => 'checkbox',
					'default' => 'yes',
					'desc'    => __( 'Auto get shipping payment no when order status is change to processing.', 'ry-woocommerce-tools' ),
				),
				array(
					'title'   => __( 'Keep shipping phone', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'keep_shipping_phone',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Always show shipping phone field in checkout form.', 'ry-woocommerce-tools' ),
				),
				array(
					'title'   => __( 'Auto completed order', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_auto_completed',
					'type'    => 'checkbox',
					'default' => 'yes',
					'desc'    => __( 'Auto completed order when user getted products.', 'ry-woocommerce-tools' ),
				),
				array(
					'id'   => 'base_options',
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'Shipping note options', 'ry-woocommerce-tools' ),
					'id'    => 'note_options',
					'type'  => 'title',
				),
				array(
					'title'    => __( 'Order no prefix', 'ry-woocommerce-tools' ),
					'id'       => RY_WT::$option_prefix . 'ecpay_shipping_order_prefix',
					'type'     => 'text',
					'desc'     => __( 'The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-tools' ),
					'desc_tip' => true,
				),
				array(
					'title'   => __( 'Cvs shipping type', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_cvs_type',
					'type'    => 'select',
					'default' => 'C2C',
					'options' => array(
						'C2C' => _x( 'C2C', 'Cvs type', 'ry-woocommerce-tools' ),
					),
				),
				array(
					'title'    => __( 'Sender name', 'ry-woocommerce-tools' ),
					'id'       => RY_WT::$option_prefix . 'ecpay_shipping_sender_name',
					'type'     => 'text',
					'desc'     => __( 'Name length between 1 to 10 letter', 'ry-woocommerce-tools' ),
					'desc_tip' => true,
				),
				array(
					'title'             => __( 'Sender phone', 'ry-woocommerce-tools' ),
					'id'                => RY_WT::$option_prefix . 'ecpay_shipping_sender_phone',
					'type'              => 'text',
					'desc'              => __( 'Phone format (0x)xxxxxxx#xx', 'ry-woocommerce-tools' ),
					'desc_tip'          => true,
					'placeholder'       => '(0x)xxxxxxx#xx',
					'custom_attributes' => array(
						'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
					),
				),
				array(
					'title'             => __( 'Sender cellphone', 'ry-woocommerce-tools' ),
					'id'                => RY_WT::$option_prefix . 'ecpay_shipping_sender_cellphone',
					'type'              => 'text',
					'desc'              => __( 'Cellphone format 09xxxxxxxx', 'ry-woocommerce-tools' ),
					'desc_tip'          => true,
					'placeholder'       => '09xxxxxxxx',
					'custom_attributes' => array(
						'pattern' => '09\d{8}',
					),
				),
				array(
					'title' => __( 'Sender zipcode', 'ry-woocommerce-tools' ),
					'id'    => RY_WT::$option_prefix . 'ecpay_shipping_sender_zipcode',
					'type'  => 'text',
				),
				array(
					'title' => __( 'Sender address', 'ry-woocommerce-tools' ),
					'id'    => RY_WT::$option_prefix . 'ecpay_shipping_sender_address',
					'type'  => 'text',
				),
				array(
					'id'   => 'note_options',
					'type' => 'sectionend',
				),
				array(
					'title' => __( 'API credentials', 'ry-woocommerce-tools' ),
					'id'    => 'api_options',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'ECPay shipping sandbox', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_testmode',
					'type'    => 'checkbox',
					'default' => 'yes',
					'desc'    => __( 'Enable ECPay shipping sandbox', 'ry-woocommerce-tools' ),
				),
				array(
					'title'   => __( 'MerchantID', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_MerchantID',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashKey', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_HashKey',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'   => __( 'HashIV', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'ecpay_shipping_HashIV',
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
				'title' => __( '尚未啟用物流功能', 'woomp' ),
				'desc'  => '請前往<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woomp_setting' ) . '">設定</a>',
				'id'    => 'empty_shipping_options',
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

Woomp_Setting_Shipping::init();
