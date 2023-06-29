<?php
/**
 * Payuni_Payment_Credit class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

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
		);
		$this->api_endpoint_url = 'api/credit';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
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
	 * Payment fields
	 */
	public function payment_fields() { ?>
		<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
		<div>
			<?php echo $this->render_card_form(); ?>
		</div>
		<?php
		do_action( 'woocommerce_credit_card_form_end', $this->id );
	}

	public function validate_fields() {

		if ( ! $this->has_token() ) {
			if ( empty( $_POST[ $this->id . '-card_number' ] ) ) {
				wc_add_notice( __( 'Credit card number is required', 'woomp' ), 'error' );
			}

			if ( empty( $_POST[ $this->id . '-card_expiry' ] ) ) {
				wc_add_notice( __( 'Credit card expired date is required', 'woomp' ), 'error' );
			}

			if ( empty( $_POST[ $this->id . '-card_cvc' ] ) ) {
				wc_add_notice( __( 'Credit card security code is required', 'woomp' ), 'error' );
			}
		}
	}

	/**
	 * Filter payment api arguments for atm
	 *
	 * @param array    $args The payment api arguments.
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public function add_args( $args, $order ) {
		$data = array();
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
	 * Process payment
	 *
	 * @param string $order_id The order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = new \WC_Order( $order_id );

		$card_data = array(
			'number' => str_replace( ' ', '', sanitize_text_field( $_POST[ $this->id . '-card_number' ] ) ),
			'expiry' => str_replace( '/', '', sanitize_text_field( $_POST[ $this->id . '-card_expiry' ] ) ),
			'cvc'    => sanitize_text_field( $_POST[ $this->id . '-card_cvc' ] ),
		);

		if ( isset( $_POST[ $this->id . '-card_remember' ] ) && ! empty( $_POST[ $this->id . '-card_remember' ] ) ) {
			$order->update_meta_data( '_' . $this->id . '-card_remember', sanitize_text_field( $_POST[ $this->id . '-card_remember' ] ) );
		}

		if ( isset( $_POST[ $this->id . '-card_hash' ] ) && 'hash' === $_POST[ $this->id . '-card_hash' ] ) {
			$order->update_meta_data( '_' . $this->id . '-card_hash', get_user_meta( get_current_user_id(), '_' . $this->id . '_hash', true ) );
		}

		$order->save();

		$request  = new Request( new self() );
		$redirect = $request->build_request( $order, $card_data );

		return array(
			'result'   => 'success',
			'redirect' => $redirect,
		);
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
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
