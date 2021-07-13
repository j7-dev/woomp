<?php

 /**
 * @link              https://morepower.club
 * @since             1.1.9
 * @package           woomp
 *
 * @wordpress-plugin
 * Plugin Name:       好用版擴充 MorePower Addon for WooCommerce
 * Plugin URI:        https://morepower.club/morepower-addon/
 * Description:       WooCommerce 好用版擴充，改善結帳流程與可變商品等區塊，讓 WooCommerce 更符合亞洲人使用習慣。
 * Version:           1.1.9
 * Author:            MorePower
 * Author URI:        https://morepower.club
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woomp
 * Domain Path:       /languages
 * WC requires at least: 5
 * WC tested up to: 5.4.1
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check WooCommerce is required
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function require_woocommerce_notice(){
		echo '<div class="error"><p>好用版擴充啟用失敗，需要安裝並啟用 WooCommerce 5.3 以上版本。</p></div>';
	}
    add_action('admin_notices', 'require_woocommerce_notice' );
    return;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOMP_VERSION', '1.1.9' );

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
if( get_option( 'wc_woomp_setting_replace', 1 ) === 'yes' ){
	add_filter('wc_get_template', 'intercept_wc_template', 99, 3 );
	function intercept_wc_template( $template, $template_name, $template_path ) {
		$template_directory = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
		$path = $template_directory . $template_name;
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

if( !defined('RY_WT_VERSION') ){
	define('RY_WT_VERSION', '1.7.3');
	define('RY_WT_PLUGIN_URL', plugin_dir_url(__FILE__) . 'includes/ry-woocommerce-tools/');
	define('RY_WT_PLUGIN_DIR', plugin_dir_path(__FILE__). 'includes/ry-woocommerce-tools/');
	define('RY_WT_PLUGIN_BASENAME', plugin_basename(__FILE__). 'includes/ry-woocommerce-tools/');
	
	require_once RY_WT_PLUGIN_DIR . 'class.ry-wt.main.php';
	
	register_activation_hook(__FILE__, ['RY_WT', 'plugin_activation']);
	register_deactivation_hook(__FILE__, ['RY_WT', 'plugin_deactivation']);
	
	add_action('init', ['RY_WT', 'init'], 10);
}


/**
 * 引入 ry-woocommerce-tools-pro
 */
if( !defined('RY_WTP_VERSION') ){
	define('RY_WTP_VERSION', '1.2.11');
	define('RY_WTP_PLUGIN_URL', plugin_dir_url(__FILE__) . 'includes/ry-woocommerce-tools-pro/');
	define('RY_WTP_PLUGIN_DIR', plugin_dir_path(__FILE__)  . 'includes/ry-woocommerce-tools-pro/');
	define('RY_WTP_PLUGIN_BASENAME', plugin_basename(__FILE__)  . 'includes/ry-woocommerce-tools-pro/');
	
	require_once RY_WTP_PLUGIN_DIR . 'class.ry-wt-p.main.php';
	
	register_activation_hook(__FILE__, ['RY_WTP', 'plugin_activation']);
	register_deactivation_hook(__FILE__, ['RY_WTP', 'plugin_deactivation']);
	
	add_action('init', ['RY_WTP', 'init'], 11);
}