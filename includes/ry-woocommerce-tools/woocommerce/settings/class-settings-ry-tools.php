<?php
class WC_Settings_RY_Tools extends WC_Settings_Page {

	public function __construct() {
		$this->id    = 'rytools';
		$this->label = __( 'RY 設定', 'ry-woocommerce-tools' );

		parent::__construct();
	}

	public function get_sections() {
		$sections = [
			'' => __( 'Base options', 'ry-woocommerce-tools' ),
		];

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	public function output() {
		global $current_section, $hide_save_button;

		if ( $current_section == 'pro_info' ) {
			$hide_save_button = true;
			$this->output_pro_info();
		} elseif ( apply_filters( 'ry_setting_section_' . $current_section, true ) ) {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		} else {
			do_action( 'ry_setting_section_ouput_' . $current_section );
		}
	}

	public function save() {
		global $current_section;

		if ( apply_filters( 'ry_setting_section_' . $current_section, true ) ) {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		}

		if ( $current_section == '' ) {
			if ( 'yes' == RY_WT::get_option( 'enabled_newebpay_shipping', 'no' ) ) {
				if ( 'no' == RY_WT::get_option( 'enabled_newebpay_gateway', 'no' ) ) {
					WC_Admin_Settings::add_error( __( 'NewebPay shipping method need enable NewebPay gateway.', 'ry-woocommerce-tools' ) );
					RY_WT::update_option( 'enabled_newebpay_shipping', 'no' );
				}
			}

			if ( 'yes' == RY_WT::get_option( 'enabled_smilepay_shipping', 'no' ) ) {
				if ( 'no' == RY_WT::get_option( 'enabled_smilepay_gateway', 'no' ) ) {
					WC_Admin_Settings::add_error( __( 'SmilePay shipping method need enable SmilePay gateway.', 'ry-woocommerce-tools' ) );
					RY_WT::update_option( 'enabled_smilepay_shipping', 'no' );
				}
			}
		} else {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}

	public function get_settings( $current_section = '' ) {
		$settings = [];
		if ( $current_section == '' ) {
			$settings = [
				[
					'title' => __( 'ECPay support', 'ry-woocommerce-tools' ),
					'type'  => 'title',
					'id'    => 'ecpay_support',
				],
				[
					'title'   => __( 'Gateway method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable ECPay gateway method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with ECPay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_ecpay_gateway',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'Shipping method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable ECPay shipping method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with ECPay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_ecpay_shipping',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'ecpay_support',
				],
				[
					'title' => __( 'NewebPay support', 'ry-woocommerce-tools' ),
					'type'  => 'title',
					'id'    => 'newebpay_support',
				],
				[
					'title'   => __( 'Gateway method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable NewebPay gateway method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with NewebPay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_newebpay_gateway',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'Shipping method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable NewebPay shipping method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with NewebPay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_newebpay_shipping',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'newebpay_support',
				],
				[
					'title' => __( 'SmilePay support', 'ry-woocommerce-tools' ),
					'type'  => 'title',
					'id'    => 'smilepay_support',
				],
				[
					'title'   => __( 'Gateway method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable SmilePay gateway method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with SmilePay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_smilepay_gateway',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'Shipping method', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable SmilePay shipping method', 'ry-woocommerce-tools' )
						. ( wc_checkout_is_https() ? '' : '<br>' . __( 'For correct link with SmilePay API, need enable secure checkout.', 'ry-woocommerce-tools' ) ),
					'id'      => RY_WT::$option_prefix . 'enabled_smilepay_shipping',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'smilepay_support',
				],
				[
					'title' => __( 'General options', 'ry-woocommerce-tools' ),
					'type'  => 'title',
					'id'    => 'general_options',
				],
				[
					'title'   => __( 'Repay action', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable order to change payment', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'repay_action',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'strength password', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Enable the strength password check.', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'strength_password',
					'type'    => 'checkbox',
					'default' => 'yes',
				],
				[
					'title'   => __( 'show not paid info at order detail', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Show not paid info at order detail payment method info.', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'show_unpay_title',
					'type'    => 'checkbox',
					'default' => 'yes',
				],
				[
					'type' => 'sectionend',
					'id'   => 'general_options',
				],
				[
					'title' => __( 'Address options', 'ry-woocommerce-tools' ),
					'type'  => 'title',
					'id'    => 'checkout_page_options',
				],
				[
					'title'   => __( 'Show Country', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Show Country select item', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'show_country_select',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'Last name first', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Show Last name before first name input item', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'last_name_first',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'title'   => __( 'Address zip first', 'ry-woocommerce-tools' ),
					'desc'    => __( 'Show address input item in zip state address', 'ry-woocommerce-tools' ),
					'id'      => RY_WT::$option_prefix . 'address_zip_first',
					'type'    => 'checkbox',
					'default' => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'checkout_page_options',
				],
			];
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	public function output_pro_info() {
		include RY_WT_PLUGIN_DIR . 'woocommerce/admin/view/html-setting-pro_info.php';
	}
}

// return new WC_Settings_RY_Tools();
