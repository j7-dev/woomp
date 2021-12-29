(function ($) {
	'use strict';

	$(document).on('click', '.wp-admin.post-type-shop_order .tablenav .button.action', function (event) {

		var action = $(this).closest('.actions').find('select').val();
		if (!action.includes('paynow_bulk_print')) {
			return;
		}

		event.preventDefault();

		var selected_posts = [];
		if ($('input[name="post[]"]:checked:enabled').length > 1) {
			$('input[name="post[]"]:checked:enabled').each(function () {
				selected_posts.push($(this).val())
			});
		} else {
			selected_posts.push($('input[name="post[]"]:checked:enabled').val());
		}

		if (selected_posts.length == 0) {
			alert('Please select at least one order.');
		}

		var service = '';
		if (action == 'paynow_bulk_print_711') {
			service = '01';
		} else if (action == 'paynow_bulk_print_family') {
			service = '03';
		} else if (action == 'paynow_bulk_print_hilife') {
			service = '05';
		} else if (action == 'paynow_bulk_print_tcat') {
			service = '06';
		}

		var orderids = selected_posts.toString();
		window.open(ajaxurl + "?" + $.param({
			action: "paynow_shipping_print_label",
			orderids: orderids,
			service: service,
		}), "_blank", "toolbar=yes,scrollbars=yes,resizable=yes");

	});


})(jQuery);
