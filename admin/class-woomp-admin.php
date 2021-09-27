<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woomp
 * @subpackage Woomp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woomp
 * @subpackage Woomp/admin
 * @author     More Power <a@a.a>
 */
class Woomp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public static $support_methods = array(
		'ry_newebpay_shipping_cvs' => 'RY_NewebPay_Shipping_CVS',
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woomp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woomp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woomp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woomp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woomp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woomp-admin.js', array( 'jquery' ), $this->version, true );

	}

	/**
	 * 訂單頁面新增地址編輯欄位
	 */
	public function custom_order_meta( $fields ) {
		$fields['full-address'] = array(
			'label'         => __( '完整地址', 'woomp' ),
			'show'          => true,
			'wrapper_class' => 'form-field-wide full-address',
		);
		return $fields;
	}

	/**
	 * 訂單頁面新增地址&姓名欄位
	 */
	public function add_address_meta( $order ) {
		if ( get_option( 'wc_woomp_setting_one_line_address', 1 ) === 'yes' ) {
			echo '<style>.order_data_column:nth-child(2) .address p:first-child {display: none;}</style>';
			echo '<p style="font-size: 14px;" id="billingName"><strong>帳單姓名:<br/></strong>' . get_post_meta( $order->get_id(), '_billing_last_name', true ) . get_post_meta( $order->get_id(), '_billing_first_name', true ) . '</p>';
			echo '<p style="font-size: 14px;" id="fullAddress"><strong>帳單地址:<br/></strong><span></span></p>';
		}
	}

	/**
	 * 在外掛列表頁加入「設定」按鈕
	 */
	public function add_settings_link( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woomp_setting' ) . '">' . __( 'Settings' ) . '</a>',
			),
			$links
		);
	}

	/**
	 * 讓 RY 藍新物流也能支援 woomp_cvs_payment
	 */
	public function only_newebpay_gateway( $_available_gateways ) {
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$chosen_shipping = wc_get_chosen_shipping_method_ids();
			$chosen_shipping = array_intersect( $chosen_shipping, array_keys( self::$support_methods ) );
			if ( count( $chosen_shipping ) ) {
				foreach ( $_available_gateways as $key => $gateway ) {
					if ( strpos( $key, 'ry_newebpay_' ) === 0 ) {
						continue;
					}
					if ( $key == 'cod' || $key == 'woomp_cvs_gateway' ) {
						continue;
					}
					unset( $_available_gateways[ $key ] );
				}
			}
		}
		return $_available_gateways;
	}

	/**
	 * 新增教學文件連結
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( 'woomp/woomp.php' === $file ) {
			return array_merge(
				$links,
				array(
					'doc' => '<a target="_blank" href="https://morepower.club/know_cate/addon/">教學文件</a>',
				),
			);
		}

		return $links;
	}

	/**
	 * 增加好用版選單
	 */
	public function add_woomp_submenu() {
		add_submenu_page( 'woocommerce', 'woomp-main', '主要設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting', '', 10 );
		add_submenu_page( 'woocommerce', 'woomp-main', '金流設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_gateway&section=ecpay', '', 10 );
		add_submenu_page( 'woocommerce', 'woomp-main', '物流設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_shipping&section=ecpay', '', 10 );
		add_submenu_page( 'woocommerce', 'woomp-main', '電子發票設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_invoice&section=ecpay', '', 10 );
	}
}
