<?php
class RY_NewebPay_Shipping_Meta_Box {

	public static function add_meta_box( $post_type, $post ) {
		if ( $post_type == 'shop_order' ) {
			global $theorder;
			if ( ! is_object( $theorder ) ) {
				$theorder = wc_get_order( $post->ID );
			}

			foreach ( $theorder->get_items( 'shipping' ) as $item_id => $item ) {
				if ( RY_NewebPay_Shipping::get_order_support_shipping( $item ) !== false ) {
					add_meta_box( 'ry-newebpay-shipping-info', __( 'NewebPay shipping info', 'ry-woocommerce-tools' ), [ __CLASS__, 'output' ], 'shop_order', 'normal', 'high' );
					break;
				}
			}
		}
	}

	public static function output( $post ) {
		global $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		$shipping_list = $theorder->get_meta( '_newebpay_shipping_info', true );
		if ( ! is_array( $shipping_list ) ) {
			$shipping_list = [];
		} ?>
<table cellpadding="0" cellspacing="0" class="widefat">
	<thead>
		<tr>
			<th><?php echo __( 'NewebPay shipping ID', 'ry-woocommerce-tools' ); ?>
			</th>
			<th><?php echo __( 'Store ID', 'ry-woocommerce-tools' ); ?>
			</th>
			<th><?php echo __( 'declare amount', 'ry-woocommerce-tools' ); ?>
			</th>
			<th><?php echo __( 'Collection of money', 'ry-woocommerce-tools' ); ?>
			</th>
			<th><?php echo __( 'Shipping status last change time', 'ry-woocommerce-tools' ); ?>
			</th>
			<th><?php echo __( 'Shipping create time', 'ry-woocommerce-tools' ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $shipping_list as $item ) {
			$item['edit']   = wc_string_to_datetime( $item['edit'] );
			$item['create'] = wc_string_to_datetime( $item['create'] );
			?>
		<tr>
			<td><?php echo $item['ID']; ?>
			</td>
			<td><?php echo $item['store_ID']; ?>
			</td>
			<td><?php echo $item['amount']; ?>
			</td>
			<td><?php echo ( $item['IsCollection'] == '1' ) ? __( 'Yes' ) : __( 'No' ); ?>
			</td>
			<?php /* translators: %1$s: date %2$s: time */ ?>
			<td><?php printf( _x( '%1$s %2$s', 'Datetime', 'ry-woocommerce-tools' ), $item['edit']->date_i18n( wc_date_format() ), $item['edit']->date_i18n( wc_time_format() ) ); ?>
			</td>
			<td><?php printf( _x( '%1$s %2$s', 'Datetime', 'ry-woocommerce-tools' ), $item['create']->date_i18n( wc_date_format() ), $item['create']->date_i18n( wc_time_format() ) ); ?>
			</td>
		</tr>
			<?php
		}
		?>
	</tbody>
</table>
		<?php
	}
}
