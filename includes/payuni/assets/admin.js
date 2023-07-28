jQuery(function ($) {


    $(document).ready(function () {

        $('.btnPayuniSubscriptionPayManual').click(function () {

            $.blockUI({message: '<p>處理中...</p>'});

            var data = {
                action: "payuni_subscription_pay_manual",
                nonce: woomp_payuni_subscription_params.ajax_nonce,
                orderId: $(this).val(),
            };

            $.post(ajaxurl, data, function (response) {
                location.reload(true);
            });
        })

    })
})