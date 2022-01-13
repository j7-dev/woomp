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
	 * 訂單頁面新增地址&姓名欄位
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
		add_submenu_page( 'woocommerce', 'woomp-main', '好用版擴充設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting', '', 10 );
		add_submenu_page( 'woocommerce', 'woomp-main', '- 金流設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_gateway&section=ecpay', '', 11 );
		add_submenu_page( 'woocommerce', 'woomp-main', '- 物流設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_shipping&section=ecpay', '', 12 );
		add_submenu_page( 'woocommerce', 'woomp-main', '- 電子發票設定', 'manage_options', admin_url() . 'admin.php?page=wc-settings&tab=woomp_setting_invoice&section=ecpay', '', 13 );
	}

	/**
	 * 停用訂單列表點擊事件
	 */
	public function disable_order_table_link( $classes ) {
		if ( is_admin() ) {
			$current_screen = get_current_screen();
			if ( $current_screen->base == 'edit' && $current_screen->post_type == 'shop_order' ) {
				$classes[] = 'no-link';
			}
		}
		return $classes;
	}

	/**
	 * Ajax 更新訂單物流單號
	 */
	public function update_order_shipping_number() {
		if ( isset( $_POST['shippingNo'] ) && isset( $_POST['orderId'] ) ) {
			$shipping_no = $_POST['shippingNo'];
			$order_id    = $_POST['orderId'];
			update_post_meta( $order_id, 'wmp_shipping_no', $shipping_no );
			echo wp_json_encode( '變更完成' );
		} else {
			echo wp_json_encode( '發生錯誤' );
		}
		die();
	}

	/**
	 * 新增訂單觸發電子郵件
	 */
	public function add_email_action( $actions ) {
		$actions[] = 'ry_ecpay_shipping_cvs_to_store';
		$actions[] = 'ry_ecpay_shipping_cvs_to_transporting';
		$actions[] = 'ry_ecpay_shipping_cvs_get_remind';
		$actions[] = 'ry_ecpay_shipping_cvs_get_expired';
		return $actions;
	}

	/**
	 * 排程 Hook 註冊
	 */
	public function register_cron_hook() {
		$cron = array(
			array(
				'type'      => 'recurring',
				'hook_name' => 'wmp_cron_every_morning',
				'start'     => strtotime( '10:00:00' ) - get_option( 'gmt_offset' ) * 3600,
				'interval'  => DAY_IN_SECONDS,
			),
		);

		foreach ( $cron as $arg ) {
			if ( ! as_next_scheduled_action( $arg['hook_name'] ) ) {
				as_schedule_recurring_action( $arg['start'], $arg['interval'], $arg['hook_name'] );
			}
		}
	}

	/**
	 * 綠界超商訂單取貨到期前一天通知
	 */
	public function set_ecpay_cvs_get_remind() {
		$args   = array(
			'status'     => array( 'wc-ry-at-cvs' ),
			'meta_key'   => 'ecpay_cvs_at_store_expired',
			'meta_value' => date( 'Y-m-d', strtotime( '+1 days' ) ),
		);
		$orders = wc_get_orders( $args );

		if ( $orders ) {
			$wc_emails = WC_Emails::instance();
			$emails    = $wc_emails->get_emails();
			$email     = $emails['RY_ECPay_Shipping_Email_Customer_CVS_Get_Remind'];

			if ( $email->is_enabled() ) {
				foreach ( $orders as $order ) {
					$email->object    = $order;
					$email->recipient = $email->object->get_billing_email();
					$email->send( $email->get_recipient(), $email->get_subject(), $email->get_content(), $email->get_headers(), $email->get_attachments() );
				}
			}
		}

	}

	/**
	 * 綠界超商訂單取貨到期當天通知
	 */
	public function set_ecpay_cvs_get_expired() {
		$args   = array(
			'status'     => array( 'wc-ry-at-cvs' ),
			'meta_key'   => 'ecpay_cvs_at_store_expired',
			'meta_value' => date( 'Y-m-d' ),
		);
		$orders = wc_get_orders( $args );

		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();
		$email     = $emails['RY_ECPay_Shipping_Email_Customer_CVS_Get_Expired'];

		if ( $email->is_enabled() ) {
			foreach ( $orders as $order ) {
				$email->object    = $order;
				$email->recipient = $email->object->get_billing_email();
				$email->send( $email->get_recipient(), $email->get_subject(), $email->get_content(), $email->get_headers(), $email->get_attachments() );
			}
		}
	}

	/**
	 * 將單一費率類別改成好用版的
	 */
	public function set_flat_rate_class( $shipping_methods ){
		$shipping_methods['flat_rate'] = 'WooMP_Shipping_Flat_Rate';
		return $shipping_methods;
	}

}
