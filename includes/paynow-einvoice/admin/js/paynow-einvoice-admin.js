(function( $ ) {
	'use strict';

	$(document).on('click', 'a.button.cancel_einvoice', function (e) {
		e.preventDefault();

		$('#paynow-ei-meta-boxes').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var order_id = $(this).data('id');
		var ajaxurl = jQuery(this).attr('href');
		$.ajax({
			url: ajaxurl,
			type: 'get',
			success: function (response) {
				console.log(response);
				if (response.success) {
					alert('電子發票作廢成功');
					$('#paynow-ei-meta-boxes').unblock();
					window.location.reload();
				} else {

					if (response.data.message) {
						alert(response.data.message);
					} else {
						alert('電子發票作廢失敗');
					}
					$('#paynow-ei-meta-boxes').unblock();
					window.location.reload();


				}
			},
			always: function () {
				$('#paynow-ei-meta-boxes').unblock();
			}
		});
	});

	$(document).on('click', 'a.button.issue_einvoice', function (e) {
		e.preventDefault();
		console.log('paynow issue einvoice');

		$('#paynow-ei-meta-boxes').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var order_id = $(this).data('id');
		var ajaxurl = jQuery(this).attr('href');
		$.ajax({
			url: ajaxurl,
			type: 'get',
			success: function (response) {
				console.log(response);
				if (response.success) {
					alert('開立電子發票成功');
					$('#paynow-ei-meta-boxes').unblock();
					window.location.reload();
				} else {

					if (response.data.message) {
						alert(response.data.message);
					} else {
						alert('開立電子發票失敗');
					}
					$('#paynow-ei-meta-boxes').unblock();


				}
			},
			always: function() {
				$('#paynow-ei-meta-boxes').unblock();
			}
		});
	});

})( jQuery );
