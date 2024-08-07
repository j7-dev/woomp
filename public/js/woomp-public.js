(function ($) {
	'use strict';
	$( document ).ready(
		function () {

		}
	)
	$( document ).on(
		"change",
		".variation-radios input",
		function () {
			$( ".variation-radios input:checked" ).each(
				function (index, element) {
					var $el      = $( element );
					var thisName = $el.attr( "name" );
					var thisVal  = $el.attr( "value" );
					$( '.variation-radios input + label' ).removeClass( 'variation-selected' );
					$( this ).next( 'label' ).addClass( 'variation-selected' )
					$( 'select[name="' + thisName + '"]' )
					.val( thisVal )
					.trigger( "change" );
				}
			);
		}
	);
	$( document ).on(
		"woocommerce_update_variation_values",
		function () {
			$( ".variation-radios input" ).each(
				function (index, element) {
					var $el      = $( element );
					var thisName = $el.attr( "name" );
					var thisVal  = $el.attr( "value" );
					$el.removeAttr( "disabled" );
					if ( $( 'select[name="' + thisName + '"] option[value="' + thisVal + '"]' ).is( ":disabled" )
					) {
						$el.prop( "disabled", true );
						$el.next( 'label' ).addClass( 'variation-out-of-stock' );
					}
				}
			);
		}
	);
})( jQuery );
