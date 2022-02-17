jQuery(function ($) {
	$(document).ready(function () {

		$('.paynow-choose-cvs').click(function () {
			var html = '<form id="paynow-choose-cvs-form" action="' + PayNowInfo.postData.ajax_url + '" method="post">';

			for (const [key, value] of Object.entries(PayNowInfo.postData)) {
				html += '<input type="hidden" name="' + key + '" value="' + value + '">';
			}
			html += '</form>';

			document.body.innerHTML += html;
			document.getElementById('paynow-choose-cvs-form').submit();
		});

	})
})