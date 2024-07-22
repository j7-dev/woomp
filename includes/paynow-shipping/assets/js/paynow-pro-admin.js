(function ($) {
	'use strict';

	$( document ).on(
		'click',
		'.wp-admin.post-type-shop_order .tablenav .button.action',
		function (event) {

			var action = $( this ).closest( '.actions' ).find( 'select' ).val();
			if ( ! action.includes( 'paynow_bulk_print' )) {
				return;
			}

			event.preventDefault();

			var selected_posts = [];
			if ( $( 'input[name="post[]"]:checked:enabled' ).length > 1 ) {
				$( 'input[name="post[]"]:checked:enabled' ).each(
					function () {
						selected_posts.push( $( this ).val() )
					}
				);
			} else if ($( 'input[name="post[]"]:checked:enabled' ).length == 1 ) {
				selected_posts.push( $( 'input[name="post[]"]:checked:enabled' ).val() );
			}

			if ( selected_posts.length == 0 ) {
				alert( '請先選擇訂單' );
				return;
			}

			var orderids = selected_posts.toString();
			var data     = {
				action: "paynow_pre_print_label",
				orderIds: orderids
			};

			$.ajax(
				{
					url: paynow_shipping_object.ajax_url,
					data: data,
					type: 'POST',
					dataType: "json",
					success: function (data) {
						console.log( data );

						$( document ).ready(
							function () {
								var $html = $( '<div id="paynow-print-label-modal" title="列印託運單"></div>' );
								if ( data['01'].length > 0 ) {
									$html.append( '<div class="shipping-orders"><div class="shipping-service">統一超商託運單(PayNow) - <span data-tip="' + data['01'].join( ',' ) + '">共' + data['01'].length + '筆</span></div><div class="shipping-action"><a href="#" data-id="' + data['01'].join( ',' ) + '" data-service="01" class="button button-primary">列印</a></div></div>' )
								}

								if (data['03'].length > 0) {
									$html.append( '<div class="shipping-orders"><div class="shipping-service">全家超商託運單(PayNow) - <span data-tip="' + data['03'].join( ',' ) + '">共' + data['03'].length + '筆</span></div><div class="shipping-action"><a href="#" data-id="' + data['03'].join( ',' ) + '" data-service="03" class="button button-primary">列印</a></div></div>' );
								}

								if (data['05'].length > 0) {
									$html.append( '<div class="shipping-orders"><div class="shipping-service">萊爾富超商託運單(PayNow) - <span data-tip="' + data['05'].join( ',' ) + '">共' + data['05'].length + '筆</span></div><div class="shipping-action"><a href="#" data-id="' + data['05'].join( ',' ) + '" data-service="05" class="button button-primary">列印</a></div></div>' );
								}

								if (data['06'].length > 0) {
									$html.append( '<div class="shipping-orders"><div class="shipping-service">黑貓宅配託運單(PayNow) - <span data-tip="' + data['06'].join( ',' ) + '">共' + data['06'].length + '筆</span></div><div class="shipping-action"><a href="#" data-id="' + data['06'].join( ',' ) + '" data-service="06" class="button button-primary">列印</a></div></div>' );
								}
								$( 'body' ).append( $html );

								var option = {
									autoOpen: false,
									modal: true,
									width: 400,
									height: 250,
									closeText: "",
									modal: true,
									open: function () {
										$( '#paynow-print-label-modal' ).focus();
										$( '.shipping-service span' ).hover(
											function () {
												$( this ).tipTip(
													{
														'attribute': 'data-tip', 'keepAlive': true
													}
												);
											}
										);
									},
									close: function () {
										$( '#paynow-print-label-modal' ).remove();
									},
								};
								$( '#paynow-print-label-modal' ).dialog( option ).dialog( 'open' );

							}
						);
					}
				}
			);// end ajax

		}
	);

	$( document ).on(
		'click',
		'.shipping-action a',
		function () {
			window.open(
				paynow_shipping_object.ajax_url + "?" + $.param(
					{
						action: "paynow_shipping_print_label",
						orderids: $( this ).data( 'id' ),
						service: $( this ).data( 'service' ),
					}
				),
				"_blank",
				"toolbar=yes,scrollbars=yes,resizable=yes"
			);
		}
	);

})( jQuery );
