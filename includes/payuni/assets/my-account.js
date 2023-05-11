jQuery(function ($) {
	$(document).ready(function () {
		$('a.payuni-refund').click(function (e) {
			$(this).text('退款中...')
			$(this).css('pointer-events', 'none')
			$(this).css('background-color', '#ccc')
			e.preventDefault();
			urlParams = new URLSearchParams($(this).attr('href'));
			orderId = urlParams.get('order_id');
			amount = urlParams.get('amount');

			if(!orderId||!amount){
				return;
			}

			var data = {
				action: "payuni_refund",
				nonce: payuni_my_account_script_params.ajax_nonce,
				user_id: payuni_my_account_script_params.user_id,
				order_id: orderId,
				amount: amount,
			};

			console.log(data)

			$.ajax({
				url: payuni_my_account_script_params.ajax_url,
				data: data,
				type: 'POST',
				dataType: "json",
				success: function (data) {
					alert(data)
					window.location.reload();
				},
			})
		})
	})
})