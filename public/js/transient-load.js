jQuery(
	function ($) {
		if ( ! jQuery( 'body' ).hasClass( 'logged-in' ) && localStorage.hasOwnProperty( 'woomp' )) {
			var dataLoad = {
				action: "checkout_load",
				nonce: ajax_params_load.nonce,
				user_id: localStorage.getItem( "woomp" ),
			};
			setTimeout(
				() => {
					$.ajax(
						{
							url: ajax_params_load.ajaxurl,
							data: dataLoad,
							type: "POST",
							dataType: "json",
							success: function (data) {
								if (data.temp) {
									var saveData = JSON.parse( data.temp.replaceAll( "\\", "" ) );
									for (var [key, value] of Object.entries( saveData )) {
										$( '[name="' + key + '"]' ).val( value )
									}
								}
							},
						}
					);
				},
				1000
			);
		}
	}
)