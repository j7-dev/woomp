<?php
/**
 * PayNow_Payment_Request class file
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generates payment form and redirect to PayNow
 */
class PayNow_Payment_Request {

	/**
	 * The gateway instance
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param  WC_Payment_Gateway $gateway The payment gateway instance.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Build transaction args.
	 *
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public function get_transaction_args( $order ) {

		$args = apply_filters(
			'paynow_transaction_args_' . $this->gateway->id,
			array(
				'WebNo'         => $this->gateway->get_web_no(),
				'PassCode'      => $this->gateway->build_pass_code( $order->get_order_number(), $order->get_total() ),
				'TotalPrice'    => $order->get_total(),
				'OrderInfo'     => $this->gateway->get_items_infos( $order ),
				'ReceiverName'  => $order->get_billing_last_name() . $order->get_billing_first_name(),
				'ReceiverID'    => $order->get_billing_email(),
				'ReceiverTel'   => $order->get_billing_phone(),
				'ReceiverEmail' => $order->get_billing_email(),
				'OrderNo'       => $order->get_order_number(),
				'ECPlatform'    => 'paynowwoocommerce',
				'PayType'       => $this->gateway->get_pay_type(),
				'Note1'         => $order->get_id(),
				'EPT'           => '1',
			),
			$order
		);

		return $args;
	}

	/**
	 * Generate the form and redirect to PayNow
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function build_request_form( $order ) {

		$order = wc_get_order( $order );

		try {
			?>
			<div>請稍候重新導向中...</div>
			<form method="post" id="paynow-form" action="<?php echo esc_url( $this->gateway->get_api_url() ); ?>" accept="UTF-8" accept-charset="UTF-8">
				<?php
				$fields = $this->get_transaction_args( $order );

				Paynow_Payment::log( 'request transaction args:' . wc_print_r( $fields, true ) );

				foreach ( $fields as $key => $value ) {
					echo '<input type="hidden" name="' . esc_html( $key ) . '" value="' . esc_html( $value ) . '">';
				}
				?>
			</form>
			<script type="text/javascript">
				document.getElementById('paynow-form').submit();
			</script>
			<?php

		} catch ( Exception $e ) {
			self::log( $e->getMessage() . ' ' . $e->getTraceAsString() );
		}
	}
}
