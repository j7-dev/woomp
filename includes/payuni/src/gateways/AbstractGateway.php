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
	abstract class AbstractGateway extends \WC_Payment_Gateway_CC {

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

			$this->testmode       = wc_string_to_bool( get_option( 'payuni_payment_testmode' ) );
			$this->mer_id         = strtoupper( ( $this->testmode ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ) );
			$this->hash_key       = ( $this->testmode ) ? get_option( 'payuni_payment_hash_key_test' ) : get_option( 'payuni_payment_hash_key' );
			$this->hash_iv        = ( $this->testmode ) ? get_option( 'payuni_payment_hash_iv_test' ) : get_option( 'payuni_payment_hash_iv' );
			$this->min_amount     = 10;
			$this->api_url        = ( $this->testmode ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
			$this->api_refund_url = 'api/trade/close';

			add_action(
				'woocommerce_order_details_before_order_table',
				array(
					$this,
					'get_detail_after_order_table',
				),
				10,
				1
			);

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
		 *
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
		 * 檢查是否有記憶卡號
		 */
		public function has_token() {
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				if ( get_user_meta( $user_id, '_' . $this->id . '_hash' ) ) {
					return true;
				}

				return false;
			}

			return false;
		}

		/**
		 * 信用卡表單
		 */
		public function render_card_form() {
			if ( ! $this->has_token() ) {
				$style       = ( 'payuni-credit-subscription' === $this->id ) ? 'none' : 'flex';
				$checked     = ( 'payuni-credit-subscription' === $this->id ) ? 'checked' : '';
				$description = ( $this->description ) ? '<p style="margin:0;padding:10px">' . $this->description . '</p>' : '';
				$html        = $description . '
				<div class="payuni-form-container">
					<div class="payuni-field-container">
						<label for="' . $this->id . '-cardnumber">卡號</label>
						<input id="' . $this->id . '-cardnumber" class="cardnumber" placeholder="ex:0123 4567 8910 1112" type="text" pattern="[0-9]*" inputmode="numeric" name="' . $this->id . '-card_number" value="" style="background:#fff">
					</div>
					<div class="payuni-field-container">
						<label for="' . $this->id . '-expirationdate">到期日 (mm/yy)</label>
						<input id="' . $this->id . '-expirationdate" class="expirationdate" placeholder="ex:01/23" type="text" pattern="[0-9]*" inputmode="numeric" name="' . $this->id . '-card_expiry" style="background:#fff">
					</div>
					<div class="payuni-field-container">
						<label for="' . $this->id . '-securitycode">安全碼</label>
						<input id="' . $this->id . '-securitycode" class="securitycode" placeholder="ex:123" type="text" maxlength=3 pattern="[0-9]*" inputmode="numeric" name="' . $this->id . '-card_cvc" style="background:#fff">
					</div>
					<div style="display:' . $style . '">
						<input id="' . $this->id . '-remember" type="checkbox" name="' . $this->id . '-card_remember" style="width:auto; margin:0" ' . $checked . '>
						<label for="' . $this->id . '-remember" style="padding:0 0 0 5px; position:relative; margin-bottom:0; cursor:pointer">記憶卡號</label>
					</div>
				</div>';
			} else {
				$card4 = get_user_meta( get_current_user_id(), '_' . $this->id . '_4no', true );
				$html  = '
				<div>
					<div>使用上次紀錄的信用卡結帳
						<p style="background:#efefef;padding:10px 20px; margin:5px 0 0 0; letter-spacing:5px; border:1px solid #ccc;"> **** **** ****  ' . esc_html( $card4 ) . '</p>
						<input type="hidden" name="' . esc_html( $this->id ) . '-card_hash" value="hash">
					</div>
					<div style="display:flex; margin-top: 5px;">
						<a id="' . esc_html( $this->id ) . '" class="card-change" style="padding:0; position:relative; cursor:pointer; font-size:14px">更換信用卡?</a>
					</div>
				</div>
				';
			}

			return $html;
		}

		/**
		 * Process refund
		 *
		 * @param string $order_id The order id.
		 * @param string $amount   The refund amount.
		 * @param string $reason   The refund reason.
		 *
		 * @return bool
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order        = \wc_get_order( $order_id );
			$order_status = $order->get_status();

			if ( ! in_array( 'wc-' . $order_status, get_option( 'payuni_admin_refund' ) ) ) {
				$order->add_order_note( '<strong>統一金流退費紀錄</strong><br>退費結果：該訂單狀態不允許退費', true );

				return false;
			}

			$args = array(
				'MerID'     => $this->get_mer_id(),
				'TradeNo'   => $order->get_meta( '_payuni_resp_trade_no' ),
				'TradeAmt'  => $amount,
				'Timestamp' => time(),
				'CloseType' => 2,
			);

			$parameter['MerID']       = $this->get_mer_id();
			$parameter['Version']     = '1.0';
			$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt( $args );
			$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info( $parameter['EncryptInfo'] );

			$options = array(
				'method'  => 'POST',
				'timeout' => 60,
				'body'    => $parameter,
			);

			$request = wp_remote_request( $this->get_api_url() . $this->api_refund_url, $options );
			$resp    = json_decode( wp_remote_retrieve_body( $request ) );
			$data    = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );

			if ( 'SUCCESS' === $data['Status'] ) {
				$note = '<strong>統一金流退費紀錄</strong><br>退費結果：' . $data['Message'];
				if ( $reason ) {
					$note .= '<br>退費原因：' . $reason;
				}
				$order->add_order_note( $note, true );
				$order->save();

				return true;
			} else {
				return false;
			}
		}

		public function get_id(): string {
			return $this->id;
		}

	}
}
