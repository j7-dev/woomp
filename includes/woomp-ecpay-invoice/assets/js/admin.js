jQuery(function ($) {
	function fieldDisplay(fieldControlName, fieldControlNameValue, fieldDisplay) {
		function condition(fieldControlName) {
			if (fieldControlName.val() === fieldControlNameValue) {
				fieldDisplay.show();
			} else {
				fieldDisplay.hide();
			}
		}
		condition(fieldControlName);
		fieldControlName.on("change", function () {
			condition(fieldControlName);
		});
	}

	$(document).ready(function () {
		$(".btnGenerateInvoice").click(function () {
			$.blockUI({ message: "<p>處理中...</p>" });
			$btn = $(this);

			const getInvoicePayload = () => {
				const _invoice_type = $('select[name="_invoice_type"]').val() || $btn.data("invoice_type");

				const defaultPayload = {
					action: "gen_invoice",
					nonce: woomp_ecpay_invoice_params.ajax_nonce,
					orderId: $btn.val(),
					_invoice_type,
				};

				switch (_invoice_type) {
					case "individual":
						return {
							...defaultPayload,
							_invoice_individual: $(
								'select[name="_invoice_individual"]'
							).val(),
							_invoice_carrier: $('input[name="_invoice_carrier"]').val(),
						};
					case "company":
						return {
							...defaultPayload,
							_invoice_company_name: $(
								'input[name="_invoice_company_name"]'
							).val() || $btn.data("invoice_company_name"),
							_invoice_tax_id: $('input[name="_invoice_tax_id"]').val() || $btn.data("invoice_tax_id"),
						};
					case "donate":
						return {
							...defaultPayload,
							_invoice_donate: $('input[name="_invoice_donate"]').val(),
						};
					default:
						return defaultPayload;
				}
			};

			const data = getInvoicePayload();

			$.post(ajaxurl, data, function (response) {
				$.unblockUI();
				alert(response?.data);
				location.reload();
			}).fail(function () {
				$.unblockUI();
				alert("發票開立錯誤");
			});
		});

		$(".btnInvalidInvoice").click(function () {
			if (confirm("確定要刪除此筆發票")) {
				$.blockUI({ message: "<p>處理中...</p>" });

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
		});

		fieldDisplay(
			$('select[name="wc_woomp_ecpay_invoice_issue_mode"'),
			"auto",
			$("#wc_woomp_ecpay_invoice_issue_at").parent().parent()
		);

		fieldDisplay(
			$('select[name="wc_woomp_ecpay_invoice_invalid_mode"'),
			"auto",
			$("#wc_woomp_ecpay_invoice_invalid_at").parent().parent()
		);

		// 訂單電子發票欄位顯示判斷
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"individual",
			$('select[name="_invoice_individual"]')
		);
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"individual",
			$("#invoiceIndividual")
		);
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"individual",
			$("#invoiceCarrier")
		);
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"company",
			$("#invoiceCompanyName")
		);
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"company",
			$("#invoiceTaxId")
		);
		fieldDisplay(
			$('select[name="_invoice_type"'),
			"donate",
			$("#invoiceDonate")
		);

		$('select[name="_invoice_individual"]').on("change", function () {
			if (
				$('select[name="_invoice_individual"] option:selected').text() ===
				"自然人憑證" ||
				$('select[name="_invoice_individual"] option:selected').text() ===
				"手機條碼"
			) {
				$("#invoiceCarrier").show();
			} else {
				$("#invoiceCarrier").hide();
			}
		});

		if (
			$('select[name="_invoice_individual"] option:selected').text() ===
			"自然人憑證" ||
			$('select[name="_invoice_individual"] option:selected').text() ===
			"手機條碼"
		) {
			$("#invoiceCarrier").show();
		} else {
			$("#invoiceCarrier").hide();
		}

		// 觸發變更發票資料按鈕
		$('#ecpay_invoice select,#ecpay_invoice input[type="text"]').on(
			"click",
			function () {
				$("#btnUpdateInvoiceData").prop("disabled", false);
				$(".btnGenerateInvoice").prop("disabled", true);
			}
		);

		$('select[name="_invoice_type"]').on("change", function () {
			$("#ecpay_invoice input").val("");
		});
	});
});
