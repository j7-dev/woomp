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

		$this->plugin_name        = 'payuni-payment-credit';
		$this->version            = '1.0.0';
		$this->has_fields         = true;
		$this->order_button_text  = __( 'PAYUNi Credit Card', 'woomp' );
		$this->id                 = 'payuni-credit';
		$this->method_title       = __( 'PAYUNi Credit Card Payment', 'woomp' );
		$this->method_description = __( 'Pay with PAYUNi Credit Card', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->api_endpoint_url = 'api/credit';

		// add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
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
		<div class="payuni-container preload" style="display:none">
			<div class="creditcard">
				<div class="front">
					<div id="ccsingle"></div>
					<svg version="1.1" id="cardfront" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
						x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
						<g id="Front">
							<g id="CardBackground">
								<g id="Page-1_1_">
									<g id="amex_1_">
										<path id="Rectangle-1_1_" class="lightcolor grey" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
								C0,17.9,17.9,0,40,0z" />
									</g>
								</g>
								<path class="darkcolor greydark" d="M750,431V193.2c-217.6-57.5-556.4-13.5-750,24.9V431c0,22.1,17.9,40,40,40h670C732.1,471,750,453.1,750,431z" />
							</g>
							<text transform="matrix(1 0 0 1 60.106 295.0121)" id="svgnumber" class="st2 st3 st4">0123 4567 8910 1112</text>
							<text transform="matrix(1 0 0 1 54.1064 428.1723)" id="svgname" class="st2 st5 st6"></text>
							<text transform="matrix(1 0 0 1 54.1074 389.8793)" class="st7 st5 st8">cardholder name</text>
							<text transform="matrix(1 0 0 1 479.7754 388.8793)" class="st7 st5 st8">expiration</text>
							<text transform="matrix(1 0 0 1 65.1054 241.5)" class="st7 st5 st8">card number</text>
							<g>
								<text transform="matrix(1 0 0 1 574.4219 433.8095)" id="svgexpire" class="st2 st5 st9">01/23</text>
								<text transform="matrix(1 0 0 1 479.3848 417.0097)" class="st2 st10 st11">VALID</text>
								<text transform="matrix(1 0 0 1 479.3848 435.6762)" class="st2 st10 st11">THRU</text>
								<polygon class="st2" points="554.5,421 540.4,414.2 540.4,427.9 		" />
							</g>
							<g id="cchip">
								<g>
									<path class="st2" d="M168.1,143.6H82.9c-10.2,0-18.5-8.3-18.5-18.5V74.9c0-10.2,8.3-18.5,18.5-18.5h85.3
							c10.2,0,18.5,8.3,18.5,18.5v50.2C186.6,135.3,178.3,143.6,168.1,143.6z" />
								</g>
								<g>
									<g>
										<rect x="82" y="70" class="st12" width="1.5" height="60" />
									</g>
									<g>
										<rect x="167.4" y="70" class="st12" width="1.5" height="60" />
									</g>
									<g>
										<path class="st12" d="M125.5,130.8c-10.2,0-18.5-8.3-18.5-18.5c0-4.6,1.7-8.9,4.7-12.3c-3-3.4-4.7-7.7-4.7-12.3
								c0-10.2,8.3-18.5,18.5-18.5s18.5,8.3,18.5,18.5c0,4.6-1.7,8.9-4.7,12.3c3,3.4,4.7,7.7,4.7,12.3
								C143.9,122.5,135.7,130.8,125.5,130.8z M125.5,70.8c-9.3,0-16.9,7.6-16.9,16.9c0,4.4,1.7,8.6,4.8,11.8l0.5,0.5l-0.5,0.5
								c-3.1,3.2-4.8,7.4-4.8,11.8c0,9.3,7.6,16.9,16.9,16.9s16.9-7.6,16.9-16.9c0-4.4-1.7-8.6-4.8-11.8l-0.5-0.5l0.5-0.5
								c3.1-3.2,4.8-7.4,4.8-11.8C142.4,78.4,134.8,70.8,125.5,70.8z" />
									</g>
									<g>
										<rect x="82.8" y="82.1" class="st12" width="25.8" height="1.5" />
									</g>
									<g>
										<rect x="82.8" y="117.9" class="st12" width="26.1" height="1.5" />
									</g>
									<g>
										<rect x="142.4" y="82.1" class="st12" width="25.8" height="1.5" />
									</g>
									<g>
										<rect x="142" y="117.9" class="st12" width="26.2" height="1.5" />
									</g>
								</g>
							</g>
						</g>
						<g id="Back">
						</g>
					</svg>
				</div>
				<div class="back">
					<svg version="1.1" id="cardback" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
						x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
						<g id="Front">
							<line class="st0" x1="35.3" y1="10.4" x2="36.7" y2="11" />
						</g>
						<g id="Back">
							<g id="Page-1_2_">
								<g id="amex_2_">
									<path id="Rectangle-1_2_" class="darkcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
							C0,17.9,17.9,0,40,0z" />
								</g>
							</g>
							<rect y="61.6" class="st2" width="750" height="78" />
							<g>
								<path class="st3" d="M701.1,249.1H48.9c-3.3,0-6-2.7-6-6v-52.5c0-3.3,2.7-6,6-6h652.1c3.3,0,6,2.7,6,6v52.5
						C707.1,246.4,704.4,249.1,701.1,249.1z" />
								<rect x="42.9" y="198.6" class="st4" width="664.1" height="10.5" />
								<rect x="42.9" y="224.5" class="st4" width="664.1" height="10.5" />
								<path class="st5" d="M701.1,184.6H618h-8h-10v64.5h10h8h83.1c3.3,0,6-2.7,6-6v-52.5C707.1,187.3,704.4,184.6,701.1,184.6z" />
							</g>
							<text transform="matrix(1 0 0 1 621.999 227.2734)" id="svgsecurity" class="st6 st7"></text>
							<g class="st8">
								<text transform="matrix(1 0 0 1 518.083 280.0879)" class="st9 st6 st10">security code</text>
							</g>
							<rect x="58.1" y="378.6" class="st11" width="375.5" height="13.5" />
							<rect x="58.1" y="405.6" class="st11" width="421.7" height="13.5" />
							<text transform="matrix(1 0 0 1 59.5073 228.6099)" id="svgnameback" class="st12 st13"></text>
						</g>
					</svg>
				</div>
			</div>
		</div>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
		<div class="payuni-form-container">
			<div class="payuni-field-container">
				<label for="cardnumber">卡號</label>
				<input id="cardnumber" placeholder="ex:0123 4567 8910 1112" type="text" pattern="[0-9]*" inputmode="numeric" name="payuni_card_number">
				<svg id="ccicon" class="ccicon" width="750" height="471" viewBox="0 0 750 471" version="1.1" xmlns="http://www.w3.org/2000/svg"
					xmlns:xlink="http://www.w3.org/1999/xlink">

				</svg>
			</div>
			<div class="payuni-field-container">
				<label for="expirationdate">到期日 (mm/yy)</label>
				<input id="expirationdate" placeholder="ex:01/23" type="text" pattern="[0-9]*" inputmode="numeric" name="payuni_card_expiry">
			</div>
			<div class="payuni-field-container">
				<label for="securitycode">安全碼</label>
				<input id="securitycode" placeholder="ex:123" type="text" maxlength=3 pattern="[0-9]*" inputmode="numeric" name="payuni_card_cvc">
			</div>
			<div>
				<input id="remember" type="checkbox" name="payuni_card_remember" style="width:auto; margin:0">
				<label for="remember" style="padding:0; position:relative; top:-2px; cursor:pointer">記憶卡號</label>
			</div>
		</div>
		<?php
		do_action( 'woocommerce_credit_card_form_end', $this->id );
	}

	public function validate_fields() {

		if ( empty( $_POST['payuni_card_number'] ) ) {
			wc_add_notice( __( 'Credit card number is required', 'woomp' ), 'error' );
		}

		if ( empty( $_POST['payuni_card_expiry'] ) ) {
			wc_add_notice( __( 'Credit card expired date is required', 'woomp' ), 'error' );
		}

		if ( empty( $_POST['payuni_card_cvc'] ) ) {
			wc_add_notice( __( 'Credit card security code is required', 'woomp' ), 'error' );
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

		if ( isset( $_POST['payuni_card_number'] ) && ! empty( $_POST['payuni_card_number'] ) ) {
			$card_number = str_replace( ' ', '', sanitize_text_field( $_POST['payuni_card_number'] ) );
			$order->update_meta_data( '_payuni_card_number', $card_number );
		}

		if ( isset( $_POST['payuni_card_expiry'] ) && ! empty( $_POST['payuni_card_expiry'] ) ) {
			$card_expiry = str_replace( '/', '', sanitize_text_field( $_POST['payuni_card_expiry'] ) );
			$order->update_meta_data( '_payuni_card_expiry', $card_expiry );
		}

		if ( isset( $_POST['payuni_card_cvc'] ) && ! empty( $_POST['payuni_card_cvc'] ) ) {
			$order->update_meta_data( '_payuni_card_cvc', sanitize_text_field( $_POST['payuni_card_cvc'] ) );
		}

		if ( isset( $_POST['payuni_card_remember'] ) && ! empty( $_POST['payuni_card_remember'] ) ) {
			$order->update_meta_data( '_payuni_card_remember', sanitize_text_field( $_POST['payuni_card_remember'] ) );
		}
		$order->save();

		$request = new Request( new self() );
		$resp    = $request->build_request( $order );

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
	public function add_args( $args, $order ) {
		return array_merge(
			$args,
			array(
				'CardNo'      => $order->get_meta( 'payuni_card_number' ),
				'CardExpired' => $order->get_meta( 'payuni_card_expiry' ),
				'CardCVC'     => $order->get_meta( 'payuni_card_cvc' ),
			)
		);
	}

	/**
	 * Redirect to paynow payment page
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function receipt_page( $order ) {
		$request = new Request( $this );
		$request->build_request_form( $order );
	}

	/**
	 * Order meta fields for payment
	 *
	 * @return array
	 */
	public static function order_metas() {
		return array(
			'_payuni_tran_id'     => __( 'Transaction No', 'wan-pay' ),
			'_payuni_pan_no4'     => __( 'Card Last 4 Num', 'wan-pay' ),
			'_payuni_tran_status' => __( 'Tran Status', 'wan-pay' ),
		);
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function get_detail_after_order_table( $order ) {
		if ( get_post_meta( $order->get_id(), '_payment_method', true ) === $this->id ) {
			echo '<h3 style="display:block">' . esc_html( __( '交易明細', 'wan-pay' ) ) . '</h3><table class="shop_table wanpya_payment_details"><tbody>';
			echo '<tr><td><strong>' . esc_html( __( '交易結果：', 'wan-pay' ) ) . esc_html( get_post_meta( $order->get_id(), '_payuni_result', true ) ) . '</strong></td>';
			echo '</tbody></table>';
		}
	}


}
