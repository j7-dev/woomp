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
class CreditInstallment extends AbstractGateway {


	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->plugin_name        = 'payuni-payment-credit-installment';
		$this->version            = '1.0.0';
		$this->has_fields         = true;
		$this->id                 = 'payuni-credit-installment';
		$this->method_title       = __( '統一金流 PAYUNi 信用卡分期', 'woomp' );
		$this->method_description = __( '透過統一金流 PAYUNi 信用卡分期進行站內付款', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->api_endpoint_url  = 'api/credit';
		$this->supports          = array( 'products', 'refunds', 'tokenization' );
		$this->number_of_periods = $this->get_option( 'number_of_periods', array() );

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
			'enabled'           => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				/* translators: %s: Gateway method title */
				'label'   => sprintf( __( 'Enable %s', 'woomp' ), $this->method_title ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'default'     => $this->method_title,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'       => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'css'         => 'width: 400px;',
				'default'     => $this->order_button_text,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'number_of_periods' => array(
				'title'             => __( 'Enable number of periods', 'woomp' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => '',
				'options'           => array(
					/* translators: %d number of periods */
					3  => sprintf( __( '%d periods', 'woomp' ), 3 ),
					/* translators: %d number of periods */
					6  => sprintf( __( '%d periods', 'woomp' ), 6 ),
					/* translators: %d number of periods */
					9  => sprintf( __( '%d periods', 'woomp' ), 9 ),
					/* translators: %d number of periods */
					12 => sprintf( __( '%d periods', 'woomp' ), 12 ),
					/* translators: %d number of periods */
					18 => sprintf( __( '%d periods', 'woomp' ), 18 ),
					/* translators: %d number of periods */
					24 => sprintf( __( '%d periods', 'woomp' ), 24 ),
					/* translators: %d number of periods */
					30 => sprintf( __( '%d periods', 'woomp' ), 30 ),
				),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => _x( 'Number of periods', 'Gateway setting', 'woomp' ),
				),
			),
		);
	}

	/**
	 * Payment fields
	 */
	public function form() {
		parent::form();?>
		<?php if ( is_checkout() ) : ?>
			<div>
				<div style="margin: 10px 0">
					<p style="margin-bottom:5px">選擇分期期數</p>
					<div class="payuni_radio">
						<select name="<?php echo esc_html( $this->id ); ?>-period"
								style="
							background: #fff url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjwhRE9DVFlQRSBzdmcgIFBVQkxJQyAnLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4nICAnaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkJz48c3ZnIGhlaWdodD0iNTEycHgiIGlkPSJMYXllcl8xIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MTIgNTEyOyIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgd2lkdGg9IjUxMnB4IiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48cGF0aCBkPSJNOTguOSwxODQuN2wxLjgsMi4xbDEzNiwxNTYuNWM0LjYsNS4zLDExLjUsOC42LDE5LjIsOC42YzcuNywwLDE0LjYtMy40LDE5LjItOC42TDQxMSwxODcuMWwyLjMtMi42ICBjMS43LTIuNSwyLjctNS41LDIuNy04LjdjMC04LjctNy40LTE1LjgtMTYuNi0xNS44djBIMTEyLjZ2MGMtOS4yLDAtMTYuNiw3LjEtMTYuNiwxNS44Qzk2LDE3OS4xLDk3LjEsMTgyLjIsOTguOSwxODQuN3oiLz48L3N2Zz4=) no-repeat 99% 50% !important;
							background-size: 16px 12px!important;
							-moz-appearance: none;
							-webkit-appearance: none;
							appearance: none;
							padding-left: 13px;display:block;width:100%;cursor:pointer
						">
							<?php
							if ( $this->get_option( 'number_of_periods' ) ) {
								foreach ( $this->get_option( 'number_of_periods' ) as $key => $value ) {
									echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '期</option>';
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<?php
endif;
	}

	public function validate_fields() {

		parent::validate_fields();

		if ( empty( $_POST[ $this->id . '-period' ] ) ) {
			wc_add_notice( __( 'Credit card installment period is required', 'woomp' ), 'error' );
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
        $number   = (isset($_POST[ $this->id . '-card-number' ])) ? wc_clean(wp_unslash($_POST[ $this->id . '-card-number' ])) : '';
        $expiry   = (isset($_POST[ $this->id . '-card-expiry' ])) ? wc_clean(wp_unslash(str_replace(' ', '', $_POST[ $this->id . '-card-expiry' ]))) : '';
        $cvc      = (isset($_POST[ $this->id . '-card-cvc' ])) ? wc_clean(wp_unslash($_POST[ $this->id . '-card-cvc' ])) : '';
        $period   = (isset($_POST[ $this->id . '-period' ])) ? wc_clean(wp_unslash($_POST[ $this->id . '-period' ])) : '';
        $token_id = (isset($_POST[ 'wc-' . $this->id . '-payment-token' ])) ? wc_clean(wp_unslash($_POST[ 'wc-' . $this->id . '-payment-token' ])) : '';
        $new      = (isset($_POST[ 'wc-' . $this->id . '-new-payment-method' ])) ? wc_clean(wp_unslash($_POST[ 'wc-' . $this->id . '-new-payment-method' ])) : '';
        //@codingStandardsIgnoreEnd

		$card_data = array(
			'number'   => str_replace( ' ', '', $number ),
			'expiry'   => str_replace( '/', '', $expiry ),
			'cvc'      => $cvc,
			'token_id' => $token_id,
			'new'      => $new,
			'period'   => $period,
		);

		$request = new Request( new self() );

		return $request->build_request( $order, $card_data );
	}

	/**
	 * Filter payment api arguments for virtual account payment
	 *
	 * @param array    $args  The payment api arguments.
	 * @param WC_Order $order The order object.
	 *
	 * @return array
	 */
	public function add_args( $args, $order ) {
		$data = array();
		if ( wc_string_to_bool( get_option( 'payuni_3d_auth', 'yes' ) ) ) {
			$data['API3D'] = 1;
			// $data[ 'NotifyURL' ] = home_url('wc-api/payuni_notify_card');
			$data['ReturnURL'] = home_url( 'wc-api/payuni_notify_card' );
		}

		return array_merge(
			$args,
			$data
		);
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

			$status    = $order->get_meta( '_payuni_resp_status', true );
			$message   = $order->get_meta( '_payuni_resp_message', true );
			$trade_no  = $order->get_meta( '_payuni_resp_trade_no', true );
			$card_inst = $order->get_meta( '_payuni_resp_card_inst', true );
			$first_amt = $order->get_meta( '_payuni_resp_first_amt', true );
			$each_amt  = $order->get_meta( '_payuni_resp_each_amt', true );
			$card_4no  = $order->get_meta( '_payuni_card_number', true );

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
							<tr>
								<th>分期期數：</th>
								<td>' . esc_html( $card_inst ) . '期</td>
							</tr>
							<tr>
								<th>首期金額：</th>
								<td>' . wc_price( esc_html( $first_amt ) ) . '</td>
							</tr>
							<tr>
								<th>每期金額：</th>
								<td>' . wc_price( esc_html( $each_amt ) ) . '</td>
							</tr>
						</tbody>
					</table>
				</div>
			';
			echo $html;
		}
	}
}
