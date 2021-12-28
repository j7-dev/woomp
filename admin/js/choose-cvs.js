jQuery(function($){
	$(document).ready(function(){
		// 訂單編輯頁重新選擇超商
		if ($('#_shipping_cvs_store_ID').length) {
			$('.choose-cvs').click(function () {
				var html = '<form id="RYECPaySendCvsForm" action="' + ECPayInfo.postUrl + '" method="post">';
				for (var idx in ECPayInfo.postData) {
					html += '<input type="hidden" name="' + idx + '" value="' + ECPayInfo.postData[idx] + '">';
				}
				html += '</form>';
				document.body.innerHTML += html;
				document.getElementById('RYECPaySendCvsForm').submit();
			});

			if (typeof ECPayInfo.newStore == 'object') {
				$('#_shipping_cvs_store_ID').val(ECPayInfo.newStore.CVSStoreID);
				$('#_shipping_cvs_store_name').val(ECPayInfo.newStore.CVSStoreName);
				$('#_shipping_cvs_store_address').val(ECPayInfo.newStore.CVSAddress);
				$('#_shipping_cvs_store_telephone').val(ECPayInfo.newStore.CVSTelephone);
				$('.choose-cvs').parents('.order_data_column').find('a.edit_address').click();
			}
		}
	}) 
})