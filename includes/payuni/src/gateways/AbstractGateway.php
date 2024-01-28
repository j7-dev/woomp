<?php

/**
 * Payuni_Abstract_Payment_Gateway class file
 *
 * @package Payuni
 */

namespace PAYUNI\Gateways;

use WC_Order;
use WC_Payment_Token;

defined('ABSPATH') || exit;

if (class_exists('WC_Payment_Gateway')) {
	/**
	 * Payuni Payment main class for handling all checkout related process.
	 */
	abstract class AbstractGateway extends \WC_Payment_Gateway_CC
	{

		/**
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
		public function __construct()
		{

			$this->icon       = $this->get_icon();
			$this->has_fields = false;
			$this->supports   = array(
				'products',
			);

			$this->testmode       = wc_string_to_bool(get_option('payuni_payment_testmode'));
			$this->mer_id         = strtoupper(($this->testmode) ? get_option('payuni_payment_merchant_no_test') : get_option('payuni_payment_merchant_no'));
			$this->hash_key       = ($this->testmode) ? get_option('payuni_payment_hash_key_test') : get_option('payuni_payment_hash_key');
			$this->hash_iv        = ($this->testmode) ? get_option('payuni_payment_hash_iv_test') : get_option('payuni_payment_hash_iv');
			$this->min_amount     = 10;
			$this->api_url        = ($this->testmode) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
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
		public function admin_options()
		{
			echo '<h3>' . esc_html($this->get_method_title()) . '</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 * Check if the gateway is available for use.
		 *
		 * @return bool
		 */
		public function is_available()
		{
			$is_available = ('yes' === $this->enabled);

			if (WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total()) {
				$is_available = false;
			}

			if (WC()->cart && 0 < $this->get_order_total() && $this->min_amount > $this->get_order_total()) {
				$is_available = false;
			}

			return $is_available;
		}

		/**
		 * Payment gateway icon output
		 *
		 * @return string
		 */
		public function get_icon()
		{
			$icon_html = '';
			$icon_html .= '<img src="' . WOOMP_PLUGIN_URL . 'includes/payuni/assets/img/logo_p.png " style="background:#5c3a93" alt="' . __('PAYUNi Payment Gateway', 'woomp') . '" />';

			return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
		}

		/**
		 * Return payment gateway method title
		 *
		 * @return string
		 */
		public function get_method_title()
		{
			return $this->method_title;
		}

		/**
		 * Return payuni web no
		 *
		 * @return string
		 */
		public function get_mer_id()
		{
			return $this->mer_id;
		}

		/**
		 * Return payuni merchant name
		 *
		 * @return string
		 */
		public function get_merchant_name()
		{
			return $this->merchant_name;
		}

		/**
		 * Return payuni payment url
		 *
		 * @return string
		 */
		public function get_api_url()
		{
			return $this->api_url;
		}

		/**
		 * Return payment endpoint url
		 *
		 * @return string
		 */
		public function get_api_endpoint_url()
		{
			return $this->api_endpoint_url;
		}

		/**
		 * Return payuni payment pay type
		 *
		 * @return string
		 */
		public function get_pay_type()
		{
			return $this->pay_type;
		}

		/**
		 * Build items as string
		 *
		 * @param WC_Order $order The order object.
		 *
		 * @return string
		 */
		public function get_items_infos($order)
		{
			$items  = $order->get_items();
			$item_s = '';
			foreach ($items as $item) {
				$item_s .= $item['name'] . 'X' . $item['quantity'];
				if (end($items)['name'] !== $item['name']) {
					$item_s .= ',';
				}
			}
			$resp = (mb_strlen($item_s) > 200) ? mb_substr($item_s, 0, 200) : $item_s;

			return $resp;
		}

		/**
		 * Get plugin name
		 *
		 * @return string
		 */
		public function get_plugin_name()
		{
			return $this->plugin_name;
		}

		/**
		 * Get plugin version
		 *
		 * @return string
		 */
		public function get_version()
		{
			return $this->version;
		}

		/**
		 * Get payment orgno
		 *
		 * @return string
		 */
		public function get_orgno()
		{
			$this->orgno = ('yes' === get_option('payuni_payment_testmode_enabled')) ? get_option('payuni_payment_testmode_orgno') : get_option('payuni_payment_orgno');

			return $this->orgno;
		}

		/**
		 * Get payment secret
		 *
		 * @return string
		 */
		public function get_secret()
		{
			$this->secret = ('yes' === get_option('payuni_payment_testmode_enabled')) ? get_option('payuni_payment_testmode_secret') : get_option('payuni_payment_secret');

			return $this->secret;
		}

		/**
		 * Get payment api endpoint
		 *
		 * @return string
		 */
		public function get_endpoint()
		{
			return $this->api_endpoint_url;
		}

		/**
		 * 檢查是否有記憶卡號
		 */
		public function has_token()
		{
			if (is_user_logged_in()) {
				$user_id = get_current_user_id();
				if (get_user_meta($user_id, '_' . $this->id . '_hash')) {
					return true;
				}

				return false;
			}

			return false;
		}

		/**
		 * Payment form on checkout page copy from WC_Payment_Gateway_CC
		 * To add the input name and get value with $_POST
		 *
		 * @return void
		 */

		public function form()
		{
			wp_enqueue_script('wc-credit-card-form');

			$fields = array();

			$cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr($this->id) . '-card-cvc">' . esc_html__('Card code', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
			<input id="' . esc_attr($this->id) . '-card-cvc" name="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="3" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px;font-size:15px" />
		</p>';

			$default_fields = array(
				'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr($this->id) . '-card-number">' . esc_html__('Card number', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-number" name="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" style="font-size:15px" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
			</p>',
				'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr($this->id) . '-card-expiry">' . esc_html__('Expiry (MM/YY)', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-card-expiry" name="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" maxlength="7" autocapitalize="no" spellcheck="no" style="font-size:15px" type="tel" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
			</p>',
			);

			if (!$this->supports('credit_card_form_cvc_on_saved_method')) {
				$default_fields['card-cvc-field'] = $cvc_field;
			}

			$fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
?>

			<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
				<?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
				<?php
				foreach ($fields as $field) {
					echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				}
				?>
				<?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
				<div class="clear"></div>
			</fieldset>
<?php

			if ($this->supports('credit_card_form_cvc_on_saved_method')) {
				echo '<fieldset>' . $cvc_field . '</fieldset>'; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Validate payment fields
		 *
		 * @return void|bool
		 */
		public function validate_fields()
		{
			//@codingStandardsIgnoreStart
			if ($this->id !== $_POST['payment_method']) {
				return false;
			}

			if (!isset($_POST['wc-' . $this->id . '-payment-token'])) {
				return false;
			}

			if ('new' !== $_POST['wc-' . $this->id . '-payment-token'] && isset($_POST['wc-' . $this->id . '-payment-token'])) {
				return false;
			}

			if (empty($_POST[$this->id . '-card-number'])) {
				wc_add_notice(__('Credit card number is required', 'woomp'), 'error');
			}

			if (empty($_POST[$this->id . '-card-expiry'])) {
				wc_add_notice(__('Credit card expired date is required', 'woomp'), 'error');
			}

			if (empty($_POST[$this->id . '-card-cvc'])) {
				wc_add_notice(__('Credit card security code is required', 'woomp'), 'error');
			}
			//@codingStandardsIgnoreEnd
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
		public function process_refund($order_id, $amount = null, $reason = '')
		{
			$order        = \wc_get_order($order_id);
			$order_status = $order->get_status();

			$args = array(
				'MerID'     => $this->get_mer_id(),
				'TradeNo'   => $order->get_meta('_payuni_resp_trade_no'),
				'TradeAmt'  => $amount,
				'Timestamp' => time(),
				'CloseType' => 2,
			);

			$parameter['MerID']       = $this->get_mer_id();
			$parameter['Version']     = '1.0';
			$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt($args);
			$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info($parameter['EncryptInfo']);

			$options = array(
				'method'  => 'POST',
				'timeout' => 60,
				'body'    => $parameter,
			);

			$request = wp_remote_request($this->get_api_url() . $this->api_refund_url, $options);
			$resp    = json_decode(wp_remote_retrieve_body($request));
			$data    = \Payuni\APIs\Payment::decrypt($resp->EncryptInfo);

			if ('SUCCESS' === $data['Status']) {
				$note = '<strong>統一金流退費紀錄</strong><br>退費結果：' . $data['Message'];
				if ($reason) {
					$note .= '<br>退費原因：' . $reason;
				}
				$order->add_order_note($note, true);
				$order->save();

				return true;
			} else {
				return false;
			}
		}

		public function get_id(): string
		{
			return $this->id;
		}

		/**
		 * My Account page change payment method
		 *
		 * @return array
		 */
		public function add_payment_method(): array
		{

			//@codingStandardsIgnoreStart
			$number   = (isset($_POST[$this->id . '-card-number'])) ? wc_clean(wp_unslash($_POST[$this->id . '-card-number'])) : '';
			$expiry   = (isset($_POST[$this->id . '-card-expiry'])) ? wc_clean(wp_unslash(str_replace(' ', '', $_POST[$this->id . '-card-expiry']))) : '';
			$cvc      = (isset($_POST[$this->id . '-card-cvc'])) ? wc_clean(wp_unslash($_POST[$this->id . '-card-cvc'])) : '';
			$token_id = (isset($_POST['wc-' . $this->id . '-payment-token'])) ? wc_clean(wp_unslash($_POST['wc-' . $this->id . '-payment-token'])) : '';
			$new      = (isset($_POST['wc-' . $this->id . '-new-payment-method'])) ? wc_clean(wp_unslash($_POST['wc-' . $this->id . '-new-payment-method'])) : '';
			//@codingStandardsIgnoreEnd

			$card_data = array(
				'number'   => str_replace(' ', '', $number),
				'expiry'   => str_replace('/', '', $expiry),
				'cvc'      => $cvc,
				'token_id' => $token_id,
				'new'      => $new,
			);



			switch ($this->id) {
				case 'payuni-credit-installment':
					$class = new CreditInstallment();
					break;
				case 'payuni-credit-subscription':
					$class = new CreditSubscription();
					break;
				case 'payuni-credit':
					$class = new Credit();
					break;
				default:
					$class = '';
					break;
			}

			$request = new Request($class);
			$result  = $request->build_hash_request($card_data);

			if ($result) {
				$return['result'] = 'success';
			} else {
				$return['result'] = 'failure';
			}

			$return['redirect'] = wc_get_endpoint_url('payment-methods');

			return $return;
		}

		/**
		 * Gets saved payment method HTML from a token.
		 *
		 * @param WC_Payment_Token $token Payment Token.
		 *
		 * @return string Generated payment method HTML
		 * @since 2.6.0
		 */
		public function get_saved_payment_method_option_html($token)
		{
			$html = sprintf(
				'<li class="woocommerce-SavedPaymentMethods-token">
				<input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
				<label for="wc-%1$s-payment-token-%2$s">%3$s</label>
			</li>',
				esc_attr($this->id),
				esc_attr($token->get_id()),
				esc_html($this->get_display_name($token)),
				checked($token->is_default(), true, false)
			);

			return apply_filters('woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this);
		}

		/**
		 * Get type to display to user.
		 *
		 * @param WC_Payment_Token $token Payment Token.
		 *
		 * @return string
		 * @since  2.6.0
		 */
		public function get_display_name(WC_Payment_Token $token): string
		{
			$display = sprintf(
				/* translators: 1: last 4 digits 2: expiry month 3: expiry year */
				__('卡號末四碼：%1$s（到期日 %2$s / %3$s ）', 'woomp'),
				$token->get_last4(),
				$token->get_expiry_month(),
				substr($token->get_expiry_year(), 2)
			);

			return $display;
		}
	}
}
