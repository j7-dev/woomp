jQuery(function ($) {

    if ($('#RY_WEI_get_mode').length) {
        function toggleDelayBox() {
            var $item = $('#RY_WEI_get_delay_days').closest('tr');

            if ($('#RY_WEI_get_mode').val() == 'manual') {
                $item.hide();
            } else {
                $item.show();
            }
        }

        toggleDelayBox();
        $('#RY_WEI_get_mode').change(toggleDelayBox);
    }

    if ($('#_invoice_type').length) {
        $(document.body).on('change', '#_invoice_type', function () {
            switch ($(this).val()) {
                case 'personal':
                    $('._invoice_carruer_type_field').show();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').hide();
                    $('#_invoice_carruer_type').trigger('change');
                    break;
                case 'company':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').show();
                    $('._invoice_donate_no_field').hide();
                    break;
                case 'donate':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').show();
                    break;
            }
        });
        $(document.body).on('change', '#_invoice_carruer_type', function () {
            switch ($(this).val()) {
                case 'none':
                    $('._invoice_carruer_no_field').hide();
                    break;
                case 'ecpay_host':
                    $('._invoice_carruer_no_field').hide();
                    break;
                case 'MOICA':
                    $('._invoice_carruer_no_field').show();
                    break;
                case 'phone_barcode':
                    $('._invoice_carruer_no_field').show();
                    break;
            }
        });
        $('#_invoice_type').trigger('change');
    }

    $('#get_ecpay_invoice').click(function () {
        $.blockUI({ message: ry_wei_script.get_loading_text });
        $.post(ajaxurl, {
            action: 'RY_WEI_get',
            id: $(this).data('orderid'),
        }, function () {
            location.reload();
        });
    });

    $('#invalid_ecpay_invoice').click(function () {
        $.blockUI({ message: ry_wei_script.invalid_loading_text });
        $.post(ajaxurl, {
            action: 'RY_WEI_invalid',
            id: $(this).data('orderid'),
        }, function () {
            location.reload();
        });
    });

    $('#clean_delay_ecpay_invoice').click(function () {
        $.blockUI({ message: ry_wei_script.clean_delay_loading_text });
        $.post(ajaxurl, {
            action: 'RY_WEI_clean_delay',
            id: $(this).data('orderid'),
        }, function () {
            location.reload();
        });
    });
});
