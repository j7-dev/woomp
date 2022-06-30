jQuery(function ($) {

	function fieldDisplay(fieldControlName, fieldControlNameValue, fieldDisplay) {
		function condition(fieldControlName) {
			if (fieldControlName.val() === fieldControlNameValue) {
				fieldDisplay.show()
			} else {
				fieldDisplay.hide()
			}
		}
		condition(fieldControlName);
		fieldControlName.on('change', function () {
			condition(fieldControlName);
		})
	}

	$(document).ready(function () {

		$('.btnGenerateInvoice').click(function () {

			$.blockUI({ message: '<p>處理中...</p>' });

			var data = {
				action: "gen_invoice",
				nonce: woomp_ecpay_invoice_params.ajax_nonce,
				orderId: $(this).val(),
			};

			$.post(ajaxurl, data, function (response) {
				$.unblockUI();
				alert(response);
				location.reload(true);
			});
		})

		$('.btnInvalidInvoice').click(function () {

			if (confirm("確定要刪除此筆發票")) {

				$.blockUI({ message: '<p>處理中...</p>' });

				var data = {
					action: "invalid_invoice",
					nonce: woomp_ecpay_invoice_params.ajax_nonce,
					orderId: $(this).val(),
				};

				$.post(ajaxurl, data, function (response) {
					$.unblockUI();
					alert(response);
					location.reload(true);
				});
			}

		})

		fieldDisplay($('select[name="wc_woomp_ecpay_invoice_issue_mode"'), 'auto', $('#wc_woomp_ecpay_invoice_issue_at').parent().parent());

		fieldDisplay($('select[name="wc_woomp_ecpay_invoice_invalid_mode"'), 'auto', $('#wc_woomp_ecpay_invoice_invalid_at').parent().parent());

		// 訂單電子發票欄位顯示判斷
		fieldDisplay($('select[name="_invoice_type"'), 'individual', $('select[name="_invoice_individual"]'));
		fieldDisplay($('select[name="_invoice_type"'), 'individual', $('#invoiceIndividual'));
		fieldDisplay($('select[name="_invoice_type"'), 'individual', $('#invoiceCarrier'));
		fieldDisplay($('select[name="_invoice_type"'), 'company', $('#invoiceCompanyName'));
		fieldDisplay($('select[name="_invoice_type"'), 'company', $('#invoiceTaxId'));
		fieldDisplay($('select[name="_invoice_type"'), 'donate', $('#invoiceDonate'));

		$('select[name="_invoice_individual"]').on('change', function () {
			if ($(this).val() === '2' || $(this).val() === '1') {
				$('#invoiceCarrier').show()
			} else {
				$('#invoiceCarrier').hide()
			}
		})
		
		if ($('select[name="_invoice_individual"]').val() === '1' || $('select[name="_invoice_individual"]').val() === '2' ) {
			$('#invoiceCarrier').show()
		} else {
			$('#invoiceCarrier').hide()
		}


		// 觸發變更發票資料按鈕
		$('#ecpay_invoice select,#ecpay_invoice input[type="text"]').on('click', function () {
			$('#btnUpdateInvoiceData').prop('disabled', false)
			$('.btnGenerateInvoice').prop('disabled', true);
		})

		$('select[name="_invoice_type"]').on('change', function () {
			$('#ecpay_invoice input').val('');
		})

		// 紙本發票移到最後
		var options = $('select[name="_invoice_individual"] option');
		$(options[0]).insertAfter($(options[3]));
	})
})