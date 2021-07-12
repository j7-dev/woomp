<?php
/*
Plugin Name: RY WooCommerce Tools Pro
Plugin URI: https://richer.tw/ry-woocommerce-tools-pro/
Version: 1.2.11
Author: Richer Yang
Author URI: https://richer.tw/
Text Domain: ry-woocommerce-tools-pro
Domain Path: /languages

WC requires at least: 5
WC tested up to: 5.4.1
*/

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WTP_VERSION', '1.2.11');
define('RY_WTP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WTP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WTP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once RY_WTP_PLUGIN_DIR . 'class.ry-wt-p.main.php';

register_activation_hook(__FILE__, ['RY_WTP', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WTP', 'plugin_deactivation']);

add_action('init', ['RY_WTP', 'init'], 11);
