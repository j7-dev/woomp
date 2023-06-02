jQuery(document).ready(function ($) {
	var prefix = 'ecpay_invoice';

	$(document.body).on('updated_checkout',function(){
		var total = woomp_ecpay_invoice_params.cart_total;
		var productType = woomp_ecpay_invoice_params.product_type;
		
        if( total === '0' && !productType.includes('subscription') ){
			$('#invoice-type_field,#individual-invoice_field').hide()
		} else {
			$('#invoice-type_field,#individual-invoice_field').show()
		}
	})


	$("#donate-number,#invoice-type,#individual-invoice").selectWoo();

	// 當頁面載入，藏起所有表格額外欄位
	$('#carrier-number_field').hide();
	$('#taxid-number_field').hide();
	$('#donate-number_field').hide();
	$('#company-name_field').hide();
	$('#billing_company_field').hide();

	
	// 當發票類型改變，顯示對應的數值
	$("#invoice-type").change(function () {
		var selection = $('#invoice-type').val();

		// 發票類型為個人，顯示載具類別區塊
		if (selection == 'individual') {
			$('#individual-invoice_field').show();
			// 預設為雲端發票，避免載具區域顯示
			$('#individual-invoice').val('雲端發票');
			$('#taxid-number_field').hide();
			$('#donate-number_field').hide();
			$('#company-name_field').hide();
		}

		//發票類型為公司，顯示統編區塊
		else if (selection == 'company') {
			$('#individual-invoice_field').hide();
			$('#individual-invoice').val(''); // 個人發票選項數值清空
			$('#carrier-number_field').hide();
			$('#taxid-number_field').show();
			$('#donate-number_field').hide();
			$('#company-name_field').show();

		}

		//發票類型為捐贈，顯示捐贈碼區塊
		else {
			$('#individual-invoice_field').hide();
			$('#individual-invoice').val(''); // 個人發票選項數值清空
			$('#carrier-number_field').hide();
			$('#taxid-number_field').hide();
			$('#donate-number_field').show();
			$('#company-name_field').hide();
		}
	});
	
	// 發票類型為個人，且選擇為自然人憑證或手機條碼
	$("#individual-invoice").change(function () {
		var individualOption = $('#individual-invoice option:selected').text();
		if (individualOption == '自然人憑證' || individualOption == '手機條碼') {
			$('#carrier-number_field').show();
		}
		else {
			$('#carrier-number_field').hide();
		}
	});

	// 紙本發票移到最後
	var options = $('#individual-invoice option');
	//$(options[0]).insertAfter($(options[3]));
	//$(options[3]).insertAfter($(options[1]));
	//$('#individual-invoice option').eq(0).prop('selected',true)

	$('#invoice-type,#individual-invoice').select2({
		minimumResultsForSearch: Infinity, // 禁用搜尋框
	});

});