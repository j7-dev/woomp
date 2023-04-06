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
class Credit extends AbstractGateway {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->plugin_name       = 'payuni-payment-credit';
		$this->version           = '1.0.0';
		$this->has_fields        = true;
		$this->order_button_text = __( 'PAYUNi Credit Card', 'woomp' );

		$this->id                 = 'payuni-credit';
		$this->method_title       = __( 'PAYUNi Credit Card Payment', 'woomp' );
		$this->method_description = __( 'Pay with PAYUNi Credit Card', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->api_endpoint_url = 'api/credit';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// add_filter( 'payuni_transaction_args_' . $this->id, array( $this, 'add_args' ), 10, 2 );
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
			<?php if ( ! $this->has_token() ) : ?>
			<div class="payuni-form-container">
				<?php echo $this->render_card_form(); ?>
			</div>
			<?php else : ?>
			<div>
				<div>使用上次紀錄的信用卡結帳
					<p style="background:#efefef;padding:10px 20px; margin:5px 0 0 0; letter-spacing:5px"> **** **** **** <?php echo esc_html( get_user_meta( get_current_user_id(), '_payuni_card_4no', true ) ); ?></p>
					<input type="hidden" name="<?php echo esc_html( $this->id ); ?>-card_hash" value="hash">
				</div>
				<!--<div>
					<input id="change" type="checkbox" name="payuni_card_change" style="width:auto; margin:0">
					<label for="change" style="padding:0; position:relative; top:-2px; cursor:pointer">更換信用卡</label>
				</div>-->
				<!--<div style="display:none" >
					<?php // echo $this->render_card_form(); ?>
				</div>-->
			</div>
			<?php endif; ?>
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
			$order->update_meta_data( '_' . $this->id . '-card_hash', get_user_meta( get_current_user_id(), '_payuni_card_hash', true ) );
		}

		$order->save();

		$request = new Request( new self() );
		$resp    = $request->build_request( $order, $card_data );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Filter payment api arguments for virtual account payment
	 *
	 * @param array    $args The payment api arguments.
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	// public function add_args( $args, $order ) {
	// if ( $order->get_meta( '_payuni_card_hash' ) ) {
	// 有記憶卡號的情況
	// $data = array(
	// 'CreditToken' => $order->get_billing_email(),
	// 'CreditHash'  => $order->get_meta( '_payuni_card_hash' ),
	// );
	// } else {
	// $data = array(
	// 'CardNo'      => $order->get_meta( '_payuni_card_number' ),
	// 'CardExpired' => $order->get_meta( '_payuni_card_expiry' ),
	// 'CardCVC'     => $order->get_meta( '_payuni_card_cvc' ),
	// 'CreditToken' => $order->get_billing_email(),
	// );
	// }
	// return array_merge(
	// $args,
	// $data
	// );
	// }

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function get_detail_after_order_table( $order ) {
		if ( get_post_meta( $order->get_id(), '_payment_method', true ) === $this->id ) {

			$status   = $order->get_meta( '_payuni_resp_status', true );
			$message  = $order->get_meta( '_payuni_resp_message', true );
			$trade_no = $order->get_meta( '_payuni_resp_trande_no', true );
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
