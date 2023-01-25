<?php

 /**
  * @link              https://morepower.club
  * @since             1.9.8
  * @package           woomp
  *
  * @wordpress-plugin
  * Plugin Name:       好用版擴充 MorePower Addon for WooCommerce
  * Plugin URI:        https://morepower.club/morepower-addon/
  * Description:       WooCommerce 好用版擴充，改善結帳流程與可變商品等區塊，讓 WooCommerce 更符合亞洲人使用習慣。
  * Version:           1.9.8
  * Author:            MorePower
  * Author URI:        https://morepower.club
  * License:           GPL-2.0+
  * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain:       woomp
  * Domain Path:       /languages
  * WC requires at least: 5
  * WC tested up to: 5.6.0
  */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check WooCommerce is required
 */
/**
 * Check WooCommerce exist
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		/**
		 * Error admin notice
		 */
		function require_woocommerce_notice() {
			echo '<div class="error"><p>好用版擴充啟用失敗，需要安裝並啟用 WooCommerce 5.3 以上版本。</p></div>';
		}
		add_action( 'admin_notices', 'require_woocommerce_notice' );
		return;
	}
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOMP_VERSION', '1.9.8' );
define( 'WOOMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOMP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require WOOMP_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woomp-activator.php
 */
function activate_woomp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woomp-activator.php';
	Woomp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woomp-deactivator.php
 */
function deactivate_woomp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woomp-deactivator.php';
	Woomp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woomp' );
register_deactivation_hook( __FILE__, 'deactivate_woomp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woomp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woomp() {

	$plugin = new Woomp();
	$plugin->run();

}
run_woomp();

/**
 * 指定 WC 結帳頁模板路徑
 *
 * @param string $template      Default template file path.
 * @param string $template_name Template file slug.
 * @param string $template_path Template file name.
 *
 * @return string The new Template file path.
 */
if ( get_option( 'wc_woomp_setting_mode', 1 ) === 'onepage' || get_option( 'wc_woomp_setting_mode', 1 ) === 'twopage' ) {
	add_filter( 'wc_get_template', 'intercept_wc_template', 99, 3 );
	function intercept_wc_template( $template, $template_name, $template_path ) {
		$template_directory = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
		$path               = $template_directory . $template_name;
		return file_exists( $path ) ? $path : $template;
	}
}

/**
 * 加入更新機制
 */
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	'https://wmp.oberonlai.blog',
	wp_normalize_path( __FILE__ ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) ),
);

/**
 * 引入 ry-woocommerce-tools
 */

if ( ! defined( 'RY_WT_VERSION' ) ) {
	define( 'RY_WT_VERSION', '1.9.8' );
	define( 'RY_WT_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'includes/ry-woocommerce-tools/' );
	define( 'RY_WT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/ry-woocommerce-tools/' );
	define( 'RY_WT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) . 'includes/ry-woocommerce-tools/' );

	require_once RY_WT_PLUGIN_DIR . 'class.ry-wt.main.php';

	register_activation_hook( __FILE__, array( 'RY_WT', 'plugin_activation' ) );
	register_deactivation_hook( __FILE__, array( 'RY_WT', 'plugin_deactivation' ) );

	add_action( 'init', array( 'RY_WT', 'init' ), 10 );
}

/**
 * 引入 paynow-payment
 */
if ( ! defined( 'PAYNOW_PLUGIN_URL' ) && 'yes' === get_option( 'wc_woomp_setting_paynow_gateway' ) ) {
	define( 'PAYNOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'includes/paynow-payment/' );
	define( 'PAYNOW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/paynow-payment/' );
	define( 'PAYNOW_BASENAME', plugin_basename( __FILE__ ) . 'includes/paynow-payment/' );

	/**
	 * Run PayNow Payment plugin.
	 *
	 * @return void
	 */
	function run_paynow_payment() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			wp_die( 'WC_Payment_Gateway not found' );
		}

		require_once PAYNOW_PLUGIN_DIR . 'includes/class-paynow-payment.php';
		Paynow_Payment::init();
	}

	add_action( 'plugins_loaded', 'run_paynow_payment' );
}

/**
 * 引入 paynow-shipping
 */
if ( ! defined( 'PAYNOW_SHIPPING_PLUGIN_URL' ) && 'yes' === get_option( 'wc_woomp_setting_paynow_shipping' ) ) {

	define( 'PAYNOW_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'includes/paynow-shipping/' );
	define( 'PAYNOW_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/paynow-shipping/' );
	define( 'PAYNOW_SHIPPING_BASENAME', plugin_basename( __FILE__ ) . 'includes/paynow-shipping/' );
	define( 'PAYNOW_SHIPPING_TEMPLATE_DIR', plugin_dir_path( __FILE__ ) . 'includes/paynow-shipping//templates/' );

	/**
	 * Add PayNow shipping methods.
	 *
	 * @param array $methods Payment methods.
	 * @return array
	 */
	function add_paynow_shipping_methods( $methods ) {
		$methods['paynow_shipping_c2c_711']    = 'PayNow_Shipping_C2C_711';
		$methods['paynow_shipping_c2c_family'] = 'PayNow_Shipping_C2C_Family';
		$methods['paynow_shipping_c2c_hilife'] = 'PayNow_Shipping_C2C_Hilife';
		$methods['paynow_shipping_hd_tcat']    = 'PayNow_Shipping_HD_TCat';
		return $methods;
	}


	/**
	 * Initialize PayNow shipping.
	 *
	 * @return void
	 */
	function run_paynow_shipping() {
		if ( ! class_exists( 'WC_Shipping_Method' ) ) {
			return;
		}

		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/class-paynow-shipping.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/admin/meta-boxes/class-paynow-shipping-order-meta-box.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/admin/meta-boxes/class-paynow-shipping-order-admin.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-logistic-service.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-order-meta.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-status.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/paynow-shipping-functions.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/abstract-paynow-shipping.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-711.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-family.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-hilife.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-hd-tcat.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/api/class-paynow-shipping-request.php';
		include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/api/class-paynow-shipping-response.php';

		add_filter( 'woocommerce_shipping_methods', 'add_paynow_shipping_methods' );

		PayNow_Shipping_Order_Admin::instance();
		PayNow_Shipping::init();
		PayNow_Shipping_Request::init();
		PayNow_Shipping_Response::init();

	}
	add_action( 'plugins_loaded', 'run_paynow_shipping' );
}

/**
 * 引入 paynow-invoice
 */
if ( ! defined( 'PAYNOW_EINVOICE_PLUGIN_URL' ) && 'yes' === get_option( 'wc_settings_tab_active_paynow_einvoice' ) ) {
	define( 'PAYNOW_EINVOICE_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'includes/paynow-einvoice/' );
	define( 'PAYNOW_EINVOICE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/paynow-einvoice/' );
	define( 'PAYNOW_EINVOICE_BASENAME', plugin_basename( __FILE__ ) . 'includes/paynow-einvoice/' );

	/**
	 * Currently plugin version.
	 * Start at version 1.0.0 and use SemVer - https://semver.org
	 * Rename this for your plugin and update it as you release new versions.
	 */
	define( 'PAYNOW_EINVOICE_VERSION', '1.0.0' );

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-paynow-einvoice-activator.php
	 */
	function activate_paynow_einvoice() {
		require_once PAYNOW_EINVOICE_PLUGIN_DIR . 'includes/class-paynow-einvoice-activator.php';
		Paynow_Einvoice_Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-paynow-einvoice-deactivator.php
	 */
	function deactivate_paynow_einvoice() {
		require_once PAYNOW_EINVOICE_PLUGIN_DIR . 'includes/class-paynow-einvoice-deactivator.php';
		Paynow_Einvoice_Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_paynow_einvoice' );
	register_deactivation_hook( __FILE__, 'deactivate_paynow_einvoice' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require_once PAYNOW_EINVOICE_PLUGIN_DIR . 'includes/class-paynow-einvoice.php';


	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_paynow_einvoice() {

		$plugin = new Paynow_Einvoice();
		$plugin->run();

	}
	run_paynow_einvoice();

}

/**
 * 引入 line-pay
 */
require_once WOOMP_PLUGIN_DIR . 'includes/line-pay-for-woo/line-pay-for-woo.php';

/**
 * 引入 wmp-ecpay-invoice
 */
require_once WOOMP_PLUGIN_DIR . 'includes/woomp-ecpay-invoice/woomp-ecpay-invoice.php';

/**
 * 引入 woomp-paynow-shipping
 */

if ( ! defined( 'WOOMP_PAYNOW_SHIPPING_PLUGIN_URL' ) && 'yes' === get_option( 'wc_woomp_setting_paynow_shipping' ) ) {

	define( 'WOOMP_PAYNOW_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'includes/woomp-paynow-shipping/' );
	define( 'WOOMP_PAYNOW_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/woomp-paynow-shipping/' );
	define( 'WOOMP_PAYNOW_SHIPPING_BASENAME', plugin_basename( __FILE__ ) . 'includes/woomp-paynow-shipping/' );
	define( 'WOOMP_PAYNOW_SHIPPING_VERSION', '1.0.0' );

	require_once WOOMP_PLUGIN_DIR . 'includes/woomp-paynow-shipping/woomp-paynow-shipping.php';

}

/**
 * 引入支付連
 */
if ( wc_string_to_bool( get_option( 'woocommerce_pchomepay_enabled' ) ) ) {
	add_action( 'plugins_loaded', 'pchomepay_gateway_init', 0 );

	function pchomepay_gateway_init() {
		 // Make sure WooCommerce is setted.
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

		function add_pchomepay_gateway_class( $methods ) {
			$methods[] = 'WC_Gateway_PChomePay';
			$methods[] = 'WC_PI_Gateway_PChomePay';
			return $methods;
		}

		function add_pchomepay_settings_link( $links ) {
			$mylinks = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=pchomepay' ) . '">' . __( '設定' ) . '</a>',
			);
			return array_merge( $links, $mylinks );
		}

		add_filter( 'woocommerce_payment_gateways', 'add_pchomepay_gateway_class' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_pchomepay_settings_link' );

		function customize_order_received_text( $text, $order ) {
			return WC_Gateway_PChomePay::$customize_order_received_text;
		}

		add_filter( 'woocommerce_thankyou_order_received_text', 'customize_order_received_text', 10, 2 );
	}

	// 審單功能
	add_action( 'woocommerce_order_actions', 'pchomepay_audit_order_action' );

	function pchomepay_audit_order_action( $actions ) {
		global $theorder;

		// bail if the order has been paid for or this action has been run
		if ( $theorder->get_status() != 'awaiting' || $theorder->payment_method != 'pchomepay' ) {
			return $actions;
		}

		$actions['wc_order_pass'] = __( 'PChomePay - 訂單過單', 'woocommerce' );
		$actions['wc_order_deny'] = __( 'PChomePay - 訂單取消', 'woocommerce' );
		return $actions;
	}

	// 過單
	add_action( 'woocommerce_order_action_wc_order_pass', 'pchomepay_audit_order_pass' );

	function pchomepay_audit_order_pass( $order ) {
		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

		$pchomepayGatway = new WC_Gateway_PChomePay();
		$result          = $pchomepayGatway->process_audit( $order->id, 'PASS' );

		if ( ! $result ) {
			WC_Admin_Meta_Boxes::add_error( '嘗試使用付款閘道 API 審單時發生錯誤!' );
		}
	}

	// 不過單
	add_action( 'woocommerce_order_action_wc_order_deny', 'pchomepay_audit_order_deny' );

	function pchomepay_audit_order_deny( $order ) {
		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
		require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

		$pchomepayGatway = new WC_Gateway_PChomePay();
		$result          = $pchomepayGatway->process_audit( $order->id, 'DENY' );

		if ( ! $result ) {
			WC_Admin_Meta_Boxes::add_error( '嘗試使用付款閘道 API 審單時發生錯誤!' );
		}
	}

	// Add to list of WC Order statuses
	add_action( 'init', 'register_awaiting_audit_order_status' );

	function register_awaiting_audit_order_status() {
		register_post_status(
			'wc-awaiting',
			array(
				'label'                     => '等待審單',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( '等待審單 <span class="count">(%s)</span>', '等待審單 <span class="count">(%s)</span>' ),
			)
		);
	}

	add_filter( 'wc_order_statuses', 'add_awaiting_audit_order_statuses' );

	function add_awaiting_audit_order_statuses( $order_statuses ) {
		$new_order_statuses = array();
		// add new order status after processing
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses['wc-awaiting'] = '等待審單';
			}
		}
		return $new_order_statuses;
	}

	// Add to list of WC Order statuses
	add_action( 'init', 'register_awaiting_pchomepay_audit_order_status' );

	function register_awaiting_pchomepay_audit_order_status() {
		register_post_status(
			'wc-awaitingforpcpay',
			array(
				'label'                     => '等待支付連審單',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( '等待支付連審單 <span class="count">(%s)</span>', '等待支付連審單 <span class="count">(%s)</span>' ),
			)
		);
	}

	add_filter( 'wc_order_statuses', 'add_awaiting_pchomepay_audit_order_statuses' );

	function add_awaiting_pchomepay_audit_order_statuses( $order_statuses ) {
		$new_order_statuses = array();
		// add new order status after processing
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses['wc-awaitingforpcpay'] = '等待支付連審單';
			}
		}
		return $new_order_statuses;
	}

	// 顧客訂單頁面 7-11物流歷程查詢
	add_filter( 'woocommerce_my_account_my_orders_actions', 'add_my_account_my_orders_custom_action', 10, 2 );
	function add_my_account_my_orders_custom_action( $actions, $order ) {
		if ( $order->get_meta( '_pchomepay_paytype' ) == 'IPL7' && $order->payment_method == 'pchomepay' ) {

			require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
			require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

			$pchomepayGateway = new WC_Gateway_PChomePay();
			$url              = $pchomepayGateway->process_query711_history_page( $order->id );

			$action_slug             = 'pchomepay_ipl7';
			$actions[ $action_slug ] = array(
				'url'  => $url,
				'name' => '物流歷程',
			);
		}
		return $actions;
	}

	// Jquery script
	add_action( 'woocommerce_after_account_orders', 'action_after_account_orders_js' );
	function action_after_account_orders_js() {
		$action_slug = 'pchomepay_ipl7';
		?>
		<script>
			jQuery(function($){
				$('a.<?php echo $action_slug; ?>').each( function(){
					$(this).attr('target','_blank');
				})
			});
		</script>
		<?php
	}

	// The column content by row
	add_action( 'manage_shop_order_posts_custom_column', 'add_custom_action_in_column_contents', 50, 2 );
	function add_custom_action_in_column_contents( $column, $post_id ) {

		$order = wc_get_order( $post_id );

		if ( in_array( $order->payment_method, array( 'pchomepay', 'pchomepay_pi' ) ) ) {
			if ( $column == 'order_number' ) {

				if ( $customer_phone = $order->get_billing_phone() ) {
					echo '<p><a href="tel:' . $customer_phone . '"><span class="dashicons dashicons-phone"></span> ' . $customer_phone . '</a></strong></p>';
				}

				if ( $customer_email = $order->get_billing_email() ) {
					echo '<p><a href="mailto:' . $customer_email . '"><span class="dashicons dashicons-email"></span> ' . $customer_email . '</a></strong></p>';
				}

				if ( $order->get_meta( '_pchomepay_paytype' ) == 'IPL7' ) {

					require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
					require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

					$pchomepayGateway = new WC_Gateway_PChomePay();
					$url              = $pchomepayGateway->process_query711_history_page( $order->get_order_number() );
					$slug             = 'pchomepay_ipl7';
					// Output the button
					echo '<p><a class="' . $slug . '" href="' . $url . '"><span class="dashicons dashicons-external ' . $slug . '"></span>查詢物流歷程</a></strong></p>';
				}
			}
		}
	}

	// The CSS styling
	add_action( 'admin_head', 'add_custom_action_button_css' );
	function add_custom_action_button_css() {
		$action_slug = 'pchomepay_ipl7';

		?>
		<script>
			jQuery(function($){
				$('a.<?php echo $action_slug; ?>').each( function(){
					$(this).attr('target','_blank');
				})
			});
		</script>
		<?php

		echo '<style>.wc-action-button-' . $action_slug . '::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
	}
}

