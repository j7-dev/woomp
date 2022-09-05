<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-cvs-store.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.2.9
 */
defined( 'ABSPATH' ) || exit;

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php esc_html_e( '您的未付款訂單即將取消，請記得前往繳費！', 'ry-woocommerce-tools' ); ?>
</p>

<div>
	<h3><?php esc_html_e( 'Payment details', 'ry-woocommerce-tools' ); ?></h3>
	<table style="border: 1px solid #e5e5e5; width: 100%; margin-bottom: 20px;">
		<tr>
			<td style="padding:5px 10px;"><?php esc_html_e( 'Bank', 'ry-woocommerce-tools' ); ?>
			</td>
			<td style="padding:5px 10px;"><?php echo esc_html( _x( $order->get_meta( '_ecpay_atm_BankCode' ), 'Bank code', 'ry-woocommerce-tools' ) ); ?> (<?php echo esc_html( $order->get_meta( '_ecpay_atm_BankCode' ) ); ?>)</td>
		</tr>
		<tr>
			<td style="padding:5px 10px;"><?php esc_html_e( 'ATM Bank account', 'ry-woocommerce-tools' ); ?>
			</td>
			<td style="padding:5px 10px;"><?php echo esc_html( $order->get_meta( '_ecpay_atm_vAccount' ) ); ?>
			</td>
		</tr>
		<tr>
			<td style="padding:5px 10px;"><?php esc_html_e( 'Payment deadline', 'ry-woocommerce-tools' ); ?>
			</td>
			<td style="padding:5px 10px;"><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', $order->get_meta( '_ecpay_atm_ExpireDate' ) ) ); ?>
			</td>
		</tr>
	</table>
</div>

<?php

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additonal content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
