jQuery(
	function ($) {

		$(document).ready(
			function () {

				$('.btnPayuniSubscriptionPayManual').click(
					function () {

						$.blockUI({ message: '<p>處理中...</p>' });

						const data = {
							action: "payuni_subscription_pay_manual",
							nonce: woomp_payuni_subscription_params.ajax_nonce,
							orderId: $(this).val(),
						};

						$.post(
							ajaxurl,
							data,
							function (response) {
								if (response?.success) {
									alert(response.data);
									location.reload(true);
								} else {
									alert('扣款失敗，請查看訂單備註，調整後再試一次');
									location.reload(true);
								}
							}
						);
					}
				)

			}
		)
	}
)