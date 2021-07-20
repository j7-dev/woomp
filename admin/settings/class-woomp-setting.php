<?php

class Woomp_Setting {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_woomp_setting', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_woomp_setting', __CLASS__ . '::update_settings' );
		add_action( 'admin_head', __CLASS__ . '::set_checkbox_toggle' );
		add_filter( 'woocommerce_get_settings_pages', __CLASS__ . '::set_more_tabs' );
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['woomp_setting'] = __( '好用版 Woo', 'woomp' );
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

		$settings = array(
			'section_ecpay'          => array(
				'name' => __( '綠界設定', 'woomp' ),
				'type' => 'title',
				'id'   => 'wc_woomp_setting_ecpay',
			),
			'ecpay_gateway'          => array(
				'name'     => __( '啟用綠界金流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable ECPay gateway method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'ecpay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'ecpay_shipping'         => array(
				'name'     => __( '啟用綠界物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable ECPay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'ecpay_shipping',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'ecpay_invoice'          => array(
				'name'     => __( '啟用綠界電子發票', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable ECPay invoice method', 'ry-woocommerce-ecpay-invoice' ),
				'id'       => RY_WEI::$option_prefix . 'enabled_invoice',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'section_ecpay_end'      => array(
				'type' => 'sectionend',
			),
			'section_newebpay'       => array(
				'name' => __( '藍新設定', 'woomp' ),
				'type' => 'title',
				'id'   => 'wc_woomp_setting_newebpay',
			),
			'newebpay_gateway'       => array(
				'name'     => __( '啟用藍新金流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable NewebPay gateway method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'newebpay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'newebpay_shipping'      => array(
				'name'     => __( '啟用藍新物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable NewebPay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'newebpay_shipping',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'section_newebpay_end'   => array(
				'type' => 'sectionend',
			),
			'section_smilepay'       => array(
				'name' => __( '速買配設定', 'woomp' ),
				'type' => 'title',
				'id'   => 'wc_woomp_setting_smilepay',
			),
			'smilepay_gateway'       => array(
				'name'     => __( '啟用速買配金流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable SmilePay gateway method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'smilepay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'smilepay_shipping'      => array(
				'name'     => __( '啟用速買配物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable SmilePay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'smilepay_shipping',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'section_smilepay_end'   => array(
				'type' => 'sectionend',
			),
			'section_paynow'         => array(
				'name' => __( '立吉富設定', 'woomp' ),
				'type' => 'title',
				'id'   => 'wc_woomp_setting_paynow',
			),
			'paynow_gateway'         => array(
				'name'     => __( '啟用立吉富金流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '啟用 立吉富金流 模組', 'woomp' ),
				'id'       => 'wc_woomp_setting_paynow_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'paynow_shipping'        => array(
				'name'     => __( '啟用立吉富物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '啟用 立吉富物流 模組', 'woomp' ),
				'id'       => 'wc_woomp_setting_paynow_shipping',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'paynow_invoice'         => array(
				'name'     => __( '啟用立吉富電子發票', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '啟用 立吉富電子發票 模組', 'woomp' ),
				'id'       => 'wc_settings_tab_active_paynow_einvoice',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'section_paynow_end'     => array(
				'type' => 'sectionend',
			),
			'section_checkout_title' => array(
				'name' => __( '結帳相關設定', 'woomp' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'wc_woomp_setting_section_checkout_title',
			),
			'replace'                => array(
				'name'     => __( '一頁結帳模式', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '將原本兩段式結帳改成一頁式結帳，並改變結帳順序為 「選物流 -> 選金流 -> 填結帳欄位」，以適應超商取貨等物流模式', 'woomp' ),
				'id'       => 'wc_woomp_setting_replace',
				'class'    => 'toggle',
				'default'  => 'yes',
				'desc_tip' => true,
			),
			'billing_country_pos'    => array(
				'name'     => __( '結帳頁國家欄位置頂', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '若需運送至多個國家，建議開啟此選項，將國家欄位移至物流選項之前', 'woomp' ),
				'id'       => 'wc_woomp_setting_billing_country_pos',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => 'yes',
				'std'      => 'yes',
			),
			'tw_address'             => array(
				'name'     => __( '縣市/鄉鎮市下拉式選單', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '開啟此選項套用結帳中的台灣地址下拉選單', 'woomp' ),
				'id'       => 'wc_woomp_setting_tw_address',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => 'yes',
				'std'      => 'yes',
			),
			'one_line_address'       => array(
				'name'     => __( '訂單地址欄位整併', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '開啟此選項套用後台訂單管理地址欄位整合為一行', 'woomp' ),
				'id'       => 'wc_woomp_setting_one_line_address',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => 'yes',
				'std'      => 'yes',
			),
			'cod_payment'            => array(
				'name'     => __( '新增取代貨到付款方式', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '開啟此選項以取代貨到付款方式', 'woomp' ),
				'id'       => 'wc_woomp_setting_cod_payment',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => 'yes',
				'std'      => 'yes',
			),
			'product_variations_ui'  => array(
				'name'     => __( '變化商品編輯介面', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( '開啟此選項以套用好用版變化商品操作介面', 'woomp' ),
				'id'       => 'wc_woomp_setting_product_variations_ui',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => 'yes',
				'std'      => 'yes',
			),
			'place_order_text'       => array(
				'name'     => __( '結帳按鈕文字設定', 'woomp' ),
				'type'     => 'text',
				'desc'     => __( '設定結帳頁確定購買按鈕的文字內容', 'woomp' ),
				'id'       => 'wc_woomp_setting_place_order_text',
				'class'    => 'toggle',
				'desc_tip' => true,
				'default'  => '',
			),
			'section_end'            => array(
				'type' => 'sectionend',
				'id'   => 'wc_woomp_setting_section_checkout_title',
			),
		);

		return apply_filters( 'wc_woomp_setting_settings', $settings );
	}

	public static function set_checkbox_toggle() {
		if ( isset( $_GET['tab'] ) && 'woomp_setting' === $_GET['tab'] ) { ?>
		<style>
			h2 {
				position: relative;
				border-top: 1px solid #ccc;
				padding: 2rem 0 0 1rem;
				margin: 2rem 0 1rem 0;
			}
			h2:before{
				content: '';
				position: absolute;
				top: 31px;
				left: 0;
				width: 5px;
				height: 20px;
				background-color: #cc99c2;
			}
			h1 + h2,
			h1 + #message + h2 {
				border-top: 0;
				margin-top: 0;
			}
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

			.form-table td fieldset label[for=RY_WT_ecpay_gateway],
			.form-table td fieldset label[for=RY_WT_ecpay_shipping],
			.form-table td fieldset label[for=RY_WT_newebpay_gateway],
			.form-table td fieldset label[for=RY_WT_newebpay_shipping],
			.form-table td fieldset label[for=RY_WT_smilepay_gateway],
			.form-table td fieldset label[for=RY_WT_smilepay_shipping],
			.form-table td fieldset label[for=wc_woomp_setting_paynow_gateway],
			.form-table td fieldset label[for=wc_woomp_setting_paynow_shipping],
			.form-table td fieldset label[for=wc_settings_tab_active_paynow_einvoice],
			.form-table td fieldset label[for=RY_WEI_enabled_invoice],
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

			legend + label[for=RY_WT_ecpay_gateway]:after,
			legend + label[for=RY_WT_ecpay_shipping]:after,
			legend + label[for=RY_WT_newebpay_gateway]:after,
			legend + label[for=RY_WT_newebpay_shipping]:after,
			legend + label[for=RY_WT_smilepay_gateway]:after,
			legend + label[for=RY_WT_smilepay_shipping]:after,
			legend + label[for=RY_WEI_enabled_invoice]:after,
			legend + label[for=wc_woomp_setting_paynow_gateway]:after,
			legend + label[for=wc_woomp_setting_paynow_shipping]:after,
			legend + label[for=wc_settings_tab_active_paynow_einvoice]:after,
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
				$('#RY_WT_ecpay_gateway').after('<label for="RY_WT_ecpay_gateway">Toggle</label>')
				$('#RY_WT_ecpay_shipping').after('<label for="RY_WT_ecpay_shipping">Toggle</label>')
				$('#RY_WT_newebpay_gateway').after('<label for="RY_WT_newebpay_gateway">Toggle</label>')
				$('#RY_WT_newebpay_shipping').after('<label for="RY_WT_newebpay_shipping">Toggle</label>')
				$('#RY_WT_smilepay_gateway').after('<label for="RY_WT_smilepay_gateway">Toggle</label>')
				$('#RY_WT_smilepay_shipping').after('<label for="RY_WT_smilepay_shipping">Toggle</label>')
				$('#RY_WEI_enabled_invoice').after('<label for="RY_WEI_enabled_invoice">Toggle</label>')
				$('#wc_woomp_setting_paynow_gateway').after('<label for="wc_woomp_setting_paynow_gateway">Toggle</label>')
				$('#wc_woomp_setting_paynow_shipping').after('<label for="wc_woomp_setting_paynow_shipping">Toggle</label>')
				$('#wc_settings_tab_active_paynow_einvoice').after('<label for="wc_settings_tab_active_paynow_einvoice">Toggle</label>')
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

	public static function set_more_tabs( $settings ) {
		$settings[] = include WOOMP_PLUGIN_DIR . 'admin/settings/class-woomp-setting-gateway.php';
		$settings[] = include WOOMP_PLUGIN_DIR . 'admin/settings/class-woomp-setting-shipping.php';
		$settings[] = include WOOMP_PLUGIN_DIR . 'admin/settings/class-woomp-setting-invoice.php';
		return $settings;
	}

}

Woomp_Setting::init();
