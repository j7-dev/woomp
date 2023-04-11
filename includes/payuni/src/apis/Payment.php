<?php
/**
 * Payuni Payment class file
 *
 * @package payuni
 */

namespace PAYUNI\APIs;

defined( 'ABSPATH' ) || exit;

/**
 * Payuni Payment main class for handling all checkout related process.
 */
class Payment {

	/**
	 * Class instance
	 *
	 * @var Payment
	 */
	private static $instance;

	/**
	 * Whether or not logging is enabled.
	 *
	 * @var boolean
	 */
	public static $log_enabled = false;

	/**
	 * WC_Logger instance.
	 *
	 * @var WC_Logger Logger instance
	 * */
	public static $log = false;

	/**
	 * Supported payment gateways
	 *
	 * @var array
	 * */
	public static $allowed_payments;

	/**
	 * Constructor
	 */
	public function __construct() {
		// do nothing.
	}

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {

		self::get_instance();

		self::$log_enabled = 'yes' === get_option( 'payuni_payment_log', 'no' );

		// Payuni_Payment_Response::init();

		self::$allowed_payments = array(
			'payuni-credit'             => '\PAYUNI\Gateways\Credit',
			'payuni-credit-installment' => '\PAYUNI\Gateways\CreditInstallment',
			'payuni-atm'                => '\PAYUNI\Gateways\Atm',
			'payuni-cvs'                => '\PAYUNI\Gateways\Cvs',
			// 'payuni-credit-subscription' => 'Payuni_Payment_Credit_Subscription',
			// 'payuni-atm'                 => 'Payuni_Payment_Atm',
			// 'payuni-cvs'                 => 'Payuni_Payment_Cvs',
			// 'payuni-aftee'               => 'Payuni_Payment_Aftee',
		);
		add_filter( 'woocommerce_payment_gateways', array( self::get_instance(), 'add_payment_gateway' ) );
		// add_action( 'rest_api_init', array( self::get_instance(), 'register_api_route' ) );
		add_action( 'wp_ajax_payuni_card_change', array( self::get_instance(), 'card_change' ) );

	}

	/**
	 * Add payment gateways
	 *
	 * @param array $methods PayNow payment gateways.
	 * @return array
	 */
	public function add_payment_gateway( $methods ) {
		$merged_methods = array_merge( $methods, self::$allowed_payments );
		return $merged_methods;
	}

	/**
	 * Log method.
	 *
	 * @param string $message The message to be logged.
	 * @param string $level The log level. Optional. Default 'info'. Possible values: emergency|alert|critical|error|warning|notice|info|debug.
	 * @return void
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new \WC_Logger();
			}
			self::$log->log( $level, wc_print_r( $message, true ), array( 'source' => 'payuni_payment' ) );
		}
	}

	/**
	 * Returns the single instance of the payuni_Payment object
	 *
	 * @return payuni_Payment
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * PAYUNi encrypt
	 */
	public static function encrypt( $encryptInfo ) {
		$tag       = '';
		$encrypted = openssl_encrypt( http_build_query( $encryptInfo ), 'aes-256-gcm', trim( get_option( 'payuni_payment_hash_key' ) ), 0, trim( get_option( 'payuni_payment_hash_iv' ) ), $tag );
		return trim( bin2hex( $encrypted . ':::' . base64_encode( $tag ) ) );
	}

	public static function hash_info( string $encrypt = '' ) {
		return strtoupper( hash( 'sha256', get_option( 'payuni_payment_hash_key' ) . $encrypt . get_option( 'payuni_payment_hash_iv' ) ) );
	}

	/**
	 * PAYUNi decrypt
	 */
	public static function decrypt( string $encryptStr = '' ) {
		list($encryptData, $tag) = explode( ':::', hex2bin( $encryptStr ), 2 );
		$encryptInfo             = openssl_decrypt( $encryptData, 'aes-256-gcm', trim( get_option( 'payuni_payment_hash_key' ) ), 0, trim( get_option( 'payuni_payment_hash_iv' ) ), base64_decode( $tag ) );
		parse_str( $encryptInfo, $encryptArr );
		return $encryptArr;
	}

	/**
	 * Get bank name
	 *
	 * @param string $type bank code.
	 * @return string bank name.
	 */
	public static function get_bank_name( $type ) {
		switch ( $type ) {
			case '004':
				return '台灣銀行';
				break;
			case '812':
				return '台新銀行';
				break;
			case '822':
				return '中信銀行';
				break;
			default:
				// code...
				break;
		}
	}

	/**
	 * API Callback
	 */
	public function card_change() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'payuni_card_change' ) ) {
			return rest_ensure_response( 'Nonce Error' );
		}

		$user_id = $_POST['user_id'];

		$result = delete_user_meta( $user_id, '_payuni_card_4no' );
		$result = delete_user_meta( $user_id, '_payuni_card_hash' );

		// _payuni_card_4no 0001
		// _payuni_card_hash 0526DE570FD17065F82534D57FC303EE8AAD16EB15C029C74734EBE87DE607C2

		echo $result;
		die;
	}
}
