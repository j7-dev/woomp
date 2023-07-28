<?php
/**
 * Payuni_Payment_Credit class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Payuni_Payment_Credit class for Credit Card payment
 */
class CreditSubscription extends AbstractGateway {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->plugin_name = 'payuni-payment-credit-subscription';
		$this->version     = '1.0.0';
		$this->has_fields  = true;
		// $this->order_button_text = __( '統一金流 PAYUNi 信用卡', 'woomp' );

		$this->id                 = 'payuni-credit-subscription';
		$this->method_title       = __( '統一金流 PAYUNi 信用卡定期定額', 'woomp' );
		$this->method_description = __( '透過統一金流 PAYUNi 信用卡定期定額付款', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->supports         = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
			'tokenization',
		);
		$this->api_endpoint_url = 'api/credit';

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_filter( 'payuni_transaction_args_' . $this->id, array( $this, 'add_args' ), 10, 2 );
	}

	/**
	 * Setup form fields for payment
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				/* translators: %s: Gateway method title */
				'label'   => sprintf( __( 'Enable %s', 'woomp' ), $this->method_title ),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'default'     => $this->method_title,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'css'         => 'width: 400px;',
				'default'     => $this->order_button_text,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Filter payment api arguments for atm
	 *
	 * @param array    $args  The payment api arguments.
	 * @param WC_Order $order The order object.
	 *
	 * @return array
	 */
	public function add_args( array $args, WC_Order $order ): array {

		$data = array(
			'TradeAmt' => 5,
		);

		if ( wc_string_to_bool( get_option( 'payuni_3d_auth' ) ) ) {
			$data['API3D']     = 1;
			$data['NotifyURL'] = home_url( 'wc-api/payuni_notify_card' );
			$data['ReturnURL'] = home_url( 'wc-api/payuni_notify_card' );
		}

		return array_merge(
			$args,
			$data
		);
	}

	/**
	 * Payment form on checkout page copy from WC_Payment_Gateway_CC
	 * To add the input name and get value with $_POST
	 *
	 * @return void
	 */

	public function form() {
		wp_enqueue_script( 'wc-credit-card-form' );

		$fields = array();

		$cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Card code', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" name="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px;font-size:15px" />
		</p>';

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Card number', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" name="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" style="font-size:15px" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Expiry (MM/YY)', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" name="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" maxlength="7" autocapitalize="no" spellcheck="no" style="font-size:15px" type="tel" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
			</p>',
		);

		if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			$default_fields['card-cvc-field'] = $cvc_field;
		}

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>

		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
			foreach ( $fields as $field ) {
				echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php

		if ( $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			echo '<fieldset>' . $cvc_field . '</fieldset>'; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
	}


	public function validate_fields() {
		if ( $this->id !== $_POST['payment_method'] ) {
			return false;
		}
		if ( 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] && isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) {
			return false;
		}

		if ( empty( $_POST[ $this->id . '-card-number' ] ) ) {
			wc_add_notice( __( 'Credit card number is required', 'woomp' ), 'error' );
		}

		if ( empty( $_POST[ $this->id . '-card-expiry' ] ) ) {
			wc_add_notice( __( 'Credit card expired date is required', 'woomp' ), 'error' );
		}

		if ( empty( $_POST[ $this->id . '-card-cvc' ] ) ) {
			wc_add_notice( __( 'Credit card security code is required', 'woomp' ), 'error' );
		}
	}

	/**
	 * Process payment
	 *
	 * @param string $order_id The order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ): array {

		$order = new WC_Order( $order_id );

		//@codingStandardsIgnoreStart
		$number   = ( isset( $_POST[ $this->id . '-card-number' ] ) ) ? wc_clean( wp_unslash( $_POST[ $this->id . '-card-number' ] ) ) : '';
		$expiry   = ( isset( $_POST[ $this->id . '-card-expiry' ] ) ) ? wc_clean( wp_unslash( str_replace( ' ', '', $_POST[ $this->id . '-card-expiry' ] ) ) ) : '';
		$cvc      = ( isset( $_POST[ $this->id . '-card-cvc' ] ) ) ? wc_clean( wp_unslash( $_POST[ $this->id . '-card-cvc' ] ) ) : '';
		$token_id = ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) : '';
		$new      = ( isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) : '';
		//@codingStandardsIgnoreEnd

		$card_data = array(
			'number'   => str_replace( ' ', '', $number ),
			'expiry'   => str_replace( '/', '', $expiry ),
			'cvc'      => $cvc,
			'token_id' => $token_id,
			'new'      => $new,
		);

		$request = new Request( new self() );

		return $request->build_request( $order, $card_data );
	}

	/**
	 * My Account page change payment method
	 *
	 * @return array
	 */
	public function add_payment_method(): array {

		//@codingStandardsIgnoreStart
		$number   = ( isset( $_POST[ $this->id . '-card-number' ] ) ) ? wc_clean( wp_unslash( $_POST[ $this->id . '-card-number' ] ) ) : '';
		$expiry   = ( isset( $_POST[ $this->id . '-card-expiry' ] ) ) ? wc_clean( wp_unslash( str_replace( ' ', '', $_POST[ $this->id . '-card-expiry' ] ) ) ) : '';
		$cvc      = ( isset( $_POST[ $this->id . '-card-cvc' ] ) ) ? wc_clean( wp_unslash( $_POST[ $this->id . '-card-cvc' ] ) ) : '';
		$token_id = ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) : '';
		$new      = ( isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) : '';
		//@codingStandardsIgnoreEnd

		$card_data = array(
			'number'   => str_replace( ' ', '', $number ),
			'expiry'   => str_replace( '/', '', $expiry ),
			'cvc'      => $cvc,
			'token_id' => $token_id,
			'new'      => $new,
		);

		$request = new Request( new self() );
		$result  = $request->build_hash_request( $card_data );

		if ( $result ) {
			$return['result'] = 'success';
		} else {
			$return['result'] = 'failure';
		}

		$return['redirect'] = wc_get_endpoint_url( 'payment-methods' );

		return $return;
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function get_detail_after_order_table( $order ) {
		if ( $order->get_payment_method() === $this->id ) {

			$status   = $order->get_meta( '_payuni_resp_status', true );
			$message  = $order->get_meta( '_payuni_resp_message', true );
			$trade_no = $order->get_meta( '_payuni_resp_trade_no', true );
			$card_4no = $order->get_meta( '_payuni_card_number', true );

			$html = '
				<h2 class="woocommerce-order-details__title">交易明細</h2>
				<div class="responsive-table">
					<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
						<tbody>
							<tr>
								<th>狀態碼：</th>
								<td>' . esc_html( $status ) . '</td>
							</tr>
							<tr>
								<th>交易訊息：</th>
								<td>' . esc_html( $message ) . '</td>
							</tr>
							<tr>
								<th>交易編號：</th>
								<td>' . esc_html( $trade_no ) . '</td>
							</tr>
							<tr>
								<th>卡號末四碼：</th>
								<td>' . esc_html( $card_4no ) . '</td>
							</tr>
						</tbody>
					</table>
				</div>
			';
			echo $html;
		}
	}
}
