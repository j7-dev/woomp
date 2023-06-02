jQuery(document).ready(function ($) {
	var prefix = 'ezpay_invoice';

	$(document.body).on('updated_checkout',function(){
		var total = woomp_ezpay_invoice_params.cart_total;
		var productType = woomp_ezpay_invoice_params.product_type;
		
        if( total === '0' && !productType.includes('subscription') ){
			$('#ezpay-invoice-type_field,#ezpay-individual-invoice_field').hide()
		} else {
			$('#ezpay-invoice-type_field,#ezpay-individual-invoice_field').show()
		}
	})


	$("#ezpay-donate-number,#ezpay-invoice-type,#ezpay-individual-invoice").selectWoo();

	// 當頁面載入，藏起所有表格額外欄位
	$('#ezpay-carrier-number_field').hide();
	$('#ezpay-taxid-number_field').hide();
	$('#ezpay-donate-number_field').hide();
	$('#ezpay-company-name_field').hide();
	$('#ezpay-billing_company_field').hide();

	
	// 當發票類型改變，顯示對應的數值
	$("#ezpay-invoice-type").change(function () {
		var selection = $('#ezpay-invoice-type').val();

		// 發票類型為個人，顯示載具類別區塊
		if (selection == 'individual') {
			$('#ezpay-individual-invoice_field').show();
			// 預設為雲端發票，避免載具區域顯示
			$('#ezpay-individual-invoice').val('雲端發票');
			$('#ezpay-taxid-number_field').hide();
			$('#ezpay-donate-number_field').hide();
			$('#ezpay-company-name_field').hide();
		}

		//發票類型為公司，顯示統編區塊
		else if (selection == 'company') {
			$('#ezpay-individual-invoice_field').hide();
			$('#ezpay-individual-invoice').val(''); // 個人發票選項數值清空
			$('#ezpay-carrier-number_field').hide();
			$('#ezpay-taxid-number_field').show();
			$('#ezpay-donate-number_field').hide();
			$('#ezpay-company-name_field').show();

		}

		//發票類型為捐贈，顯示捐贈碼區塊
		else {
			$('#ezpay-individual-invoice_field').hide();
			$('#ezpay-individual-invoice').val(''); // 個人發票選項數值清空
			$('#ezpay-carrier-number_field').hide();
			$('#ezpay-taxid-number_field').hide();
			$('#ezpay-donate-number_field').show();
			$('#ezpay-company-name_field').hide();
		}
	});
	
	// 發票類型為個人，且選擇為自然人憑證或手機條碼
	$("#ezpay-individual-invoice").change(function () {
		var individualOption = $('#ezpay-individual-invoice option:selected').text();
		if (individualOption == '自然人憑證' || individualOption == '手機條碼') {
			$('#ezpay-carrier-number_field').show();
		}
		else {
			$('#ezpay-carrier-number_field').hide();
		}
	});

	// 自動帶入公司抬頭
	function get_company(taxId){
		$.ajax({
			url: 'https://company.g0v.ronny.tw/api/show/' + taxId,
			type: 'GET',
			dataType: "json",
			success: function(data){
				if(data.data.公司名稱){
					$('#ezpay-company-name').val(data.data.公司名稱);
				}
			}
		})
	}
	$('#ezpay-taxid-number').on('focusout',function(){
		get_company($(this).val())
	})

	$('#ezpay-invoice-type,#ezpay-individual-invoice').selectWoo({
		minimumResultsForSearch: Infinity,
	});

});