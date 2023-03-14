<?php
/**
 * Payuni_Abstract_Payment_Gateway class file
 *
 * @package Payuni
 */

namespace PAYUNI\Gateways;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	/**
	 * Payuni Payment main class for handling all checkout related process.
	 */
	abstract class AbstractGateway extends \WC_Payment_Gateway {

		/**
		 * Plugin name
		 *
		 * @var string
		 */
		protected $plugin_name;

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		protected $version;

		/**
		 * Merchant ID
		 *
		 * @var string
		 */
		protected $mer_id;

		/**
		 * Hash Key
		 *
		 * @var string
		 */
		protected $hash_key;

		/**
		 * Hash IV
		 *
		 * @var string
		 */
		protected $hash_iv;

		/**
		 * Pay Type
		 *
		 * @var string
		 */
		protected $pay_type;

		/**
		 * Merchant Name
		 *
		 * @var string
		 */
		protected $merchant_name;

		/**
		 * Test mode
		 *
		 * @var boolean
		 */
		public $testmode;

		/**
		 * API url
		 *
		 * @var string
		 */
		protected $api_url;

		/**
		 * API Endpoint url
		 *
		 * @var string
		 */
		protected $api_endpoint_url;

		/**
		 * Payment orgno
		 *
		 * @var string
		 */
		protected $orgno;

		/**
		 * Payment secret key
		 *
		 * @var string
		 */
		protected $secret;

		/**
		 * Constructor
		 */
		public function __construct() {

			$this->icon       = $this->get_icon();
			$this->has_fields = false;
			$this->supports   = array(
				'products',
			);

			$this->mer_id     = strtoupper( get_option( 'payuni_payment_merchant_no' ) );
			$this->hash_key   = get_option( 'payuni_payment_hash_key' );
			$this->hash_iv    = get_option( 'payuni_payment_hash_iv' );
			$this->min_amount = 10;

			$this->testmode = wc_string_to_bool( get_option( 'payuni_payment_testmode' ) );
			$this->api_url  = ( $this->testmode ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';

			add_action( 'woocommerce_order_details_before_order_table', array( $this, 'get_detail_after_order_table' ), 10, 1 );

		}

		/**
		 * Payment method settings
		 *
		 * @return void
		 */
		public function admin_options() {
			echo '<h3>' . esc_html( $this->get_method_title() ) . '</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 * Check if the gateway is available for use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$is_available = ( 'yes' === $this->enabled );

			if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
				$is_available = false;
			}

			if ( WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total() ) {
				$is_available = false;
			}

			return $is_available;
		}

		/**
		 * Payment gateway icon output
		 *
		 * @return string
		 */
		public function get_icon() {
			$icon_html  = '';
			$icon_html .= '<img src="' . WOOMP_PLUGIN_URL . 'includes/payuni/assets/img/logo_p.png " style="background:#5c3a93" alt="' . __( 'PAYUNi Payment Gateway', 'woomp' ) . '" />';
			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Return payment gateway method title
		 *
		 * @return string
		 */
		public function get_method_title() {
			return $this->method_title;
		}

		/**
		 * Return payuni web no
		 *
		 * @return string
		 */
		public function get_mer_id() {
			return $this->mer_id;
		}

		/**
		 * Return payuni merchant name
		 *
		 * @return string
		 */
		public function get_merchant_name() {
			return $this->merchant_name;
		}

		/**
		 * Return payuni payment url
		 *
		 * @return string
		 */
		public function get_api_url() {
			return $this->api_url;
		}

		/**
		 * Return payment endpoint url
		 *
		 * @return string
		 */
		public function get_api_endpoint_url() {
			return $this->api_endpoint_url;
		}

		/**
		 * Return payuni payment pay type
		 *
		 * @return string
		 */
		public function get_pay_type() {
			return $this->pay_type;
		}

		/**
		 * Build items as string
		 *
		 * @param WC_Order $order The order object.
		 * @return string
		 */
		public function get_items_infos( $order ) {
			$items  = $order->get_items();
			$item_s = '';
			foreach ( $items as $item ) {
				$item_s .= $item['name'] . 'X' . $item['quantity'];
				if ( end( $items )['name'] !== $item['name'] ) {
					$item_s .= ',';
				}
			}
			$resp = ( mb_strlen( $item_s ) > 200 ) ? mb_substr( $item_s, 0, 200 ) : $item_s;
			return $resp;
		}

		/**
		 * Get plugin name
		 *
		 * @return string
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * Get plugin version
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Get payment orgno
		 *
		 * @return string
		 */
		public function get_orgno() {
			$this->orgno = ( 'yes' === get_option( 'payuni_payment_testmode_enabled' ) ) ? get_option( 'payuni_payment_testmode_orgno' ) : get_option( 'payuni_payment_orgno' );
			return $this->orgno;
		}

		/**
		 * Get payment secret
		 *
		 * @return string
		 */
		public function get_secret() {
			$this->secret = ( 'yes' === get_option( 'payuni_payment_testmode_enabled' ) ) ? get_option( 'payuni_payment_testmode_secret' ) : get_option( 'payuni_payment_secret' );
			return $this->secret;
		}

		/**
		 * Get payment api endpoint
		 *
		 * @return string
		 */
		public function get_endpoint() {
			return $this->api_endpoint_url;
		}

		/**
		 * PAYUNi encrypt
		 */
		public function encrypt( $encryptInfo ) {
			$tag       = '';
			$encrypted = openssl_encrypt( http_build_query( $encryptInfo ), 'aes-256-gcm', trim( $this->hash_key ), 0, trim( $this->hash_iv ), $tag );
			return trim( bin2hex( $encrypted . ':::' . base64_encode( $tag ) ) );
		}

		public function hash_info( string $encrypt = '' ) {
			return strtoupper( hash( 'sha256', $this->hash_key . $encrypt . $this->hash_iv ) );
		}

		/**
		 * PAYUNi decrypt
		 */
		public function decrypt( string $encryptStr = '' ) {
			list($encryptData, $tag) = explode( ':::', hex2bin( $encryptStr ), 2 );
			$encryptInfo             = openssl_decrypt( $encryptData, 'aes-256-gcm', trim( $this->hash_key ), 0, trim( $this->hash_iv ), base64_decode( $tag ) );
			parse_str( $encryptInfo, $encryptArr );
			return $encryptArr;
		}

		public function get_response( $result ) {
			$msg = '';
			if ( is_array( $result ) ) {
				$resultArr = $result;
			} else {
				$resultArr = json_decode( $result, true );
				if ( ! is_array( $resultArr ) ) {
					$msg = 'Result must be an array';
					return array(
						'success' => false,
						'message' => $msg,
					);
				}
			}
			//if ( isset( $resultArr['EncryptInfo'] ) ) {
			//	if ( isset( $resultArr['HashInfo'] ) ) {
			//		$chkHash = $this->hash_info( $resultArr['EncryptInfo'] );
			//		if ( $chkHash != $resultArr['HashInfo'] ) {
			//			$msg = 'Hash mismatch';
			//			return array(
			//				'success' => false,
			//				'message' => $msg,
			//			);
			//		}
			//		$resultArr['EncryptInfo'] = $this->decrypt( $resultArr['EncryptInfo'] );
			//		return array(
			//			'success' => true,
			//			'message' => $resultArr,
			//		);
			//	} else {
			//		$msg = 'missing HashInfo';
			//		return array(
			//			'success' => false,
			//			'message' => $msg,
			//		);
			//	}
			//} else {
			//	$msg = 'missing EncryptInfo';
			//	return array(
			//		'success' => false,
			//		'message' => $msg,
			//	);
			//}
		}
	}
}
