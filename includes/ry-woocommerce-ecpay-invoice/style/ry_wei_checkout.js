jQuery(function ($) {

    $(document.body).on('change', '#invoice_type', function () {
        switch ($(this).val()) {
            case 'personal':
                $('#invoice_carruer_type_field').show();
                $('#invoice_no_field').hide();
                $('#invoice_donate_no_field').hide();
                $('#invoice_company_name_field').hide();
                $('#invoice_carruer_type').trigger('change');
                break;
            case 'company':
                $('#invoice_carruer_type_field').hide();
                $('#invoice_carruer_no_field').hide();
                $('#invoice_no_field').show();
                $('#invoice_company_name_field').show();
                $('#invoice_donate_no_field').hide();
                break;
            case 'donate':
                $('#invoice_carruer_type_field').hide();
                $('#invoice_carruer_no_field').hide();
                $('#invoice_no_field').hide();
                $('#invoice_company_name_field').hide();
                $('#invoice_donate_no_field').show();
                break;
        }
    });
    $(document.body).on('change', '#invoice_carruer_type', function () {
        switch ($(this).val()) {
            case 'none':
                $('#invoice_carruer_no_field').hide();
                break;
            case 'ecpay_host':
                $('#invoice_carruer_no_field').hide();
                break;
            case 'MOICA':
                $('#invoice_carruer_no_field').show();
                break;
            case 'phone_barcode':
                $('#invoice_carruer_no_field').show();
                break;
        }
    });

    $(document.body).on('change', '#invoice_carruer_no', function () {
        $(this).val($(this).val().toUpperCase());
    });

    $(document.body).on('updated_checkout', function () {
        $('#invoice_type').trigger('change');
    });

});
