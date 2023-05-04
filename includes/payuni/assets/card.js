jQuery(function ($) {
	$(document.body).on('updated_checkout', function () {

		const cardnumber = document.querySelectorAll('.cardnumber');
		const expirationdate = document.querySelectorAll('.expirationdate');
		const securitycode = document.querySelectorAll('.securitycode');

		//Mask the Credit Card Number Input
		if (cardnumber) {
			cardnumber.forEach(function (e) {
				new IMask(e, {
					mask: [
						{
							mask: '0000 000000 00000',
							regex: '^3[47]\\d{0,13}',
							cardtype: 'american express'
						},
						{
							mask: '0000 0000 0000 0000',
							regex: '^(?:6011|65\\d{0,2}|64[4-9]\\d?)\\d{0,12}',
							cardtype: 'discover'
						},
						{
							mask: '0000 000000 0000',
							regex: '^3(?:0([0-5]|9)|[689]\\d?)\\d{0,11}',
							cardtype: 'diners'
						},
						{
							mask: '0000 0000 0000 0000',
							regex: '^(5[1-5]\\d{0,2}|22[2-9]\\d{0,1}|2[3-7]\\d{0,2})\\d{0,12}',
							cardtype: 'mastercard'
						},
						// {
						//     mask: '0000-0000-0000-0000',
						//     regex: '^(5019|4175|4571)\\d{0,12}',
						//     cardtype: 'dankort'
						// },
						// {
						//     mask: '0000-0000-0000-0000',
						//     regex: '^63[7-9]\\d{0,13}',
						//     cardtype: 'instapayment'
						// },
						{
							mask: '0000 000000 00000',
							regex: '^(?:2131|1800)\\d{0,11}',
							cardtype: 'jcb15'
						},
						{
							mask: '0000 0000 0000 0000',
							regex: '^(?:35\\d{0,2})\\d{0,12}',
							cardtype: 'jcb'
						},
						{
							mask: '0000 0000 0000 0000',
							regex: '^(?:5[0678]\\d{0,2}|6304|67\\d{0,2})\\d{0,12}',
							cardtype: 'maestro'
						},
						// {
						//     mask: '0000-0000-0000-0000',
						//     regex: '^220[0-4]\\d{0,12}',
						//     cardtype: 'mir'
						// },
						{
							mask: '0000 0000 0000 0000',
							regex: '^4\\d{0,15}',
							cardtype: 'visa'
						},
						{
							mask: '0000 0000 0000 0000',
							regex: '^62\\d{0,14}',
							cardtype: 'unionpay'
						},
						{
							mask: '0000 0000 0000 0000',
							cardtype: 'Unknown'
						}
					],
					dispatch: function (appended, dynamicMasked) {
						var number = (dynamicMasked.value + appended).replace(/\D/g, '');

						for (var i = 0; i < dynamicMasked.compiledMasks.length; i++) {
							let re = new RegExp(dynamicMasked.compiledMasks[i].regex);
							if (number.match(re) != null) {
								return dynamicMasked.compiledMasks[i];
							}
						}
					}
				});
			})
		}

		//Mask the Expiration Date
		if (expirationdate) {
			expirationdate.forEach(function (e) {
				new IMask(e, {
					mask: 'MM{/}YY',
					groups: {
						YY: new IMask.MaskedPattern.Group.Range([0, 99]),
						MM: new IMask.MaskedPattern.Group.Range([1, 12]),
					}
				});
			})
		}

		//Mask the security code
		if (securitycode) {
			securitycode.forEach(function (e) {
				new IMask(e, {
					mask: '000',
				});
			})
		}

		$('.card-change').on('click', function () {
			
			var data = {
				action: "payuni_card_change",
				nonce: card_params.ajax_nonce,
				user_id: card_params.user_id
			};

			if(confirm('確定要更換信用卡嗎？')){
				$('body').trigger( 'update_checkout' )
				$.ajax({
					url: card_params.ajax_url,
					data: data,
					type: 'POST',
					dataType: "json",
					success: function (data) {
						if(data){
							$('body').trigger( 'update_checkout' )
						} else {
							alert('發生錯誤，請稍候再試')
						}
					}
				})
			}

		})

	});
})