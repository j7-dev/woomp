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
				'id'       => RY_WT::$option_prefix . 'enabled_ecpay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'ecpay_shipping'         => array(
				'name'     => __( '啟用綠界物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable ECPay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'enabled_ecpay_shipping',
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
				'id'       => RY_WT::$option_prefix . 'enabled_newebpay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'newebpay_shipping'      => array(
				'name'     => __( '啟用藍新物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable NewebPay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'enabled_newebpay_shipping',
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
				'id'       => RY_WT::$option_prefix . 'enabled_smilepay_gateway',
				'class'    => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			'smilepay_shipping'      => array(
				'name'     => __( '啟用速買配物流', 'woomp' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Enable SmilePay shipping method', 'ry-woocommerce-tools' ),
				'id'       => RY_WT::$option_prefix . 'enabled_smilepay_shipping',
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
				'id'       => 'wc_woomp_setting_paynow_einvoice',
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
			array(
				'title'   => __( 'Repay action', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Enable order to change payment', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'repay_action',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'    => 'toggle',
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'strength password', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Enable the strength password check.', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'strength_password',
				'type'    => 'checkbox',
				'default' => 'yes',
				'class'    => 'toggle',
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'show not paid info at order detail', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Show not paid info at order detail payment method info.', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'show_unpay_title',
				'type'    => 'checkbox',
				'default' => 'yes',
				'class'    => 'toggle',
				'desc_tip' => true,
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
			array(
				'title' => __( 'Address options', 'ry-woocommerce-tools' ),
				'type'  => 'title',
				'id'    => 'checkout_page_options',
			),
			array(
				'title'   => __( 'Show Country', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Show Country select item', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'show_country_select',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'    => 'toggle',
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Last name first', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Show Last name before first name input item', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'last_name_first',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'    => 'toggle',
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Address zip first', 'ry-woocommerce-tools' ),
				'desc'    => __( 'Show address input item in zip state address', 'ry-woocommerce-tools' ),
				'id'      => RY_WT::$option_prefix . 'address_zip_first',
				'type'    => 'checkbox',
				'default' => 'no',
				'class'    => 'toggle',
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'checkout_page_options',
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

			.form-table td fieldset label {
				margin-top: 0!important;
				margin-left: -10px!important;
				margin-bottom: 3px!important;
			}

			legend + label:after {
				content: '停用 / 啟用';
				margin-left: 10px;
			}
		</style>
		<script>
			var $ = jQuery.noConflict();
			$(document).ready(function(){
				$('.form-table td fieldset label').each(function(){
					$(this).find('input').after('<label for="'+ $(this).attr('for') +'">Toggle</label>');
				})
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