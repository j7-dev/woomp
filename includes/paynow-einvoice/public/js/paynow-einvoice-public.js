(function ($) {
	'use strict';

	class PaynowEinvoiceHandler {

		fieldsEnum = {
			issueType: 'paynow_ei_issue_type',
			carrierType: 'paynow_ei_carrier_type'
		};

		_paynow_ei_issue_type = 'b2c';
		_paynow_ei_carrier_type = '';

		constructor() {
			this.attachEvents();
			this.paynow_ei_issue_type = 'b2c';
		}

		set paynow_ei_issue_type(value) {
			this._paynow_ei_issue_type = value;
			this.paynow_ei_carrier_type = ''; // 每次改變時，將載具類型改為預設的雲端載具

			if ('b2b' === value) {
				// 如果是開公司發票，則自動帶入公司名稱
				$('#paynow_ei_buyer_name').val($('#billing_company').val());
			} else {
				// 如果是開個人發票，則自動帶入購買者姓名
				$('#paynow_ei_buyer_name').val(`${$('#billing_last_name').val()}${$('#billing_first_name').val()}`);
			}
		}

		set paynow_ei_carrier_type(value) {
			this._paynow_ei_carrier_type = value;
			this.changeFields();
		}

		changeFields() {
			const issue_type = this._paynow_ei_issue_type;
			const carrier_type = this._paynow_ei_carrier_type;

			const displayCarrierType = issue_type === 'b2c' ? 'show' : 'hide';
			$('#paynow-ei-carrier-type')?.[displayCarrierType](); // 隱藏/顯示整個載具類型 show() / hide()

			// 只有開公司發票時才需要顯示填寫，其他時候 buyer name 自動帶入購買者姓名
			const displayCompanyInfo = issue_type === 'b2b' ? 'show' : 'hide';
			$('#paynow-ei-buyer-name')?.[displayCompanyInfo]();
			$('#paynow-ei-ubn')?.[displayCompanyInfo]();

			// 個人發票  且  非雲端載具才需要填寫"載具"欄位
			const displayCarrierNum = (issue_type === 'b2c' && carrier_type !== '') ? 'show' : 'hide';
			$('#paynow-ei-carrier-num')?.[displayCarrierNum]();


			// 只有發票類型為捐贈時才需要顯示填寫"捐贈給"欄位
			const displayDonateOrg = (issue_type === 'donate') ? 'show' : 'hide';
			$('#paynow-ei-org')?.[displayDonateOrg]();
		}

		attachEvents() {
			Object.values(this.fieldsEnum).forEach(
				(field) => {
					$(`#${field}`).on(
						'change',
						() => {
							this[`${field}`] = $(`#${field}`).val();
						}
					);
				}
			);
		}
	}

	$(document).ready(
		function () {
			new PaynowEinvoiceHandler();
		}
	);

})(jQuery);
