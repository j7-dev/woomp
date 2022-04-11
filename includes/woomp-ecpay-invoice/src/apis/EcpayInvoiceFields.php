<?php

namespace WOOMPECPAYINVOICE\APIs;

defined( 'ABSPATH' ) || exit;

class EcpayInvoiceFields {

	/**
	 * Get Ecpay invoice order data
	 *
	 * @param int    $order_id Order Id.
	 * @param string $type optional type, tax_id, company_name, donate, individual, and carrier.
	 */
	public static function get_meta( $order_id, $type ) {
		$order = wc_get_order( $order_id );

		// Compability with old Ecpay invoice
		if ( ! $order->get_meta( '_ecpay_invoice_data' ) && $order->get_meta( '_invoice_type' ) ) {

			switch ( $type ) {
				case 'individual':
					$type = 'carruer_type';
					break;
				case 'donate':
					$type = 'donate_no';
					break;
				case 'tax_id':
					$type = 'no';
					break;
				case 'company_name':
					$type = $order->get_billing_company();
					break;
				case 'carrier':
					$type = 'carruer_no';
					break;
				default:
					// code...
					break;
			}

			return $order->get_meta( '_invoice_' . $type );
		}

		return $order->get_meta( '_ecpay_invoice_data' )[ '_invoice_' . $type ];
	}

}
