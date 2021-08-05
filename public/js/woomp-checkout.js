jQuery(function($){

	// 台灣鄉鎮下拉選單
	function setTwAddress(){
		function updateValue( field ){
			$("#"+field+"_state").val($(".woocommerce-"+field+"-fields select[name=\'county\']").val());
			$("#"+field+"_city").val($(".woocommerce-"+field+"-fields select[name=\'district\']").val());
			$("#"+field+"_postcode").val($(".woocommerce-"+field+"-fields input[name=\'zipcode\']").val());
		}
		function updateField(field){
			$(".woocommerce-"+field+"-fields select[name=\'county\']").appendTo($("#"+field+"_state_field"));
			$(".woocommerce-"+field+"-fields select[name=\'district\']").appendTo($("#"+field+"_city_field"));
			$(".woocommerce-"+field+"-fields input[name=\'zipcode\']").appendTo($("#"+field+"_postcode_field"));
		}
		if( $('#billing_country').val() === 'TW' && $('#shipping_country').val() === 'TW' ){
							
			$(".woocommerce-billing-fields,.woocommerce-shipping-fields").twzipcode();
			
			updateField("billing");
			updateField("shipping");
			
			$("select[name=\'county\'],select[name=\'district\']").change(function(){
				updateValue("billing");
				updateValue("shipping");
			})

			$("input[name=\'zipcode\']").keyup(function(){
				updateValue("billing");
				updateValue("shipping");
			})

			$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").hide();
		}

		$('#billing_country, #shipping_country').on('change',function(){
			if($(this).val() === 'TW'){
				$(".woocommerce-billing-fields,.woocommerce-shipping-fields").twzipcode();
				updateField("billing");
				updateField("shipping");
				$("select[name=\'county\'],select[name=\'district\'],input[name=\'zipcode\']").show();
				$("select[name=\'county\'],select[name=\'district\']").change(function(){
					updateValue("billing");
					updateValue("shipping");
				})
				$("input[name=\'zipcode\']").keyup(function(){updateValue();})
				$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").hide();
				$("select#billing_state + span, select#shipping_state + span").hide();
			} else {
				$("form.woocommerce-checkout").twzipcode('destory');
				$("select[name=\'county\'],select[name=\'district\'],input[name=\'zipcode\']").hide();
				$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").show();
				$("select#billing_state + span, select#shipping_state + span").show();
			}
		})
	}

	// 同步 Billing 與 Shipping 欄位
	function setBillingShippingFieldsSync(){
		let syncFields = ['first_name', 'last_name', 'phone'];
		for (let i = 0; i < syncFields.length; i++) {
			$('#billing_' + syncFields[i] ).on('input',function (e) {
				$('.woocommerce-shipping-fields').find('#shipping_' + syncFields[i]).val($(this).val())
			});	
		}
	}

	// 將國家欄位置頂
	function setCountryToTop(){
		$('#billing_country_field').prependTo('#order_review');
		$('#billing_country_field').css('margin-bottom','25px');
		$(document.body).on('updated_checkout', function (e, data) {
			if( $('#ship-to-different-address-checkbox').val() ){
				$('#shipping_country_field').prependTo('#order_review');
				$('#shipping_country_field').css('margin-bottom','25px');
				$('#billing_country_field').hide()
				$('#billing_country').val( $( '#shipping_country' ).val() )
			} else {
				$('#billing_country_field').show();
				$('#shipping_country_field').show();
			}
		})
		// 同步 billing & shipping 國家欄位
		$( '#shipping_country' ).change(function(){
			$( '#billing_country' ).val( $(this).val() )
			$('#select2-billing_country-container').text($( '#shipping_country option:selected').text())
			$('#select2-billing_country-container').attr('title',$( '#shipping_country option:selected').text())
		})
	}

	// 更新購物車
	function setUpdateCart(){
		let updateCart = false;
		$('.woocommerce-cart-form').attr('action',woomp_checkout_params.wcGetCheckoutUrl)
		$('.woocommerce').on('change', 'input.qty', function(){
			setTimeout(() => {
				$("[name='update_cart']").trigger("click");
				updateCart = true
			}, 1000);
		});
		// 處理購物車更新後，會把登入跟折價卷給吃掉
		$(document.body).on('updated_cart_totals', function () {
			if( updateCart ){
				if( woomp_checkout_params.isUserLoggedIn && woomp_checkout_params.enableCheckoutLoginReminder === 'yes' ){
					$('.woocommerce-form-login-toggle').append('<div class="woocommerce-message" role="alert">'+woomp_checkout_params.textReturnCustomer+'<a href="#" class="showlogin">'+woomp_checkout_params.textClickLogin+'</a></div>');
				}
				if( woomp_checkout_params.enableCoupons === 'yes' ){
					$('.woocommerce-form-coupon-toggle').append('<div class="woocommerce-info">'+ woomp_checkout_params.textHaveCoupon +'<a href="#" class="showcoupon">'+ woomp_checkout_params.textClickCoupon +'</a></div>')
				}
			}
		})
	}

	// 移動結帳按鈕&金流位置
	function setCheckoutButtonToBottom(){
		function placeCheckoutButton(){
			if(jQuery('#paymentWrap').length === 0){
				jQuery('table.shop_table.woocommerce-checkout-review-order-table').after('<table id="paymentWrap" style="margin-top: -24px; margin-bottom: -20px;"><tr><th style="width: 159px; font-weight: normal; padding: 0; font-size: 14px; text-align: left;">付款方式</th><td></td></tr></table>');
				jQuery('#payment').appendTo(jQuery('#paymentWrap td'))
			} else {
				jQuery( '#payment .woocommerce-terms-and-conditions-wrapper' ).remove(); // 移除重複的隱私權政策
			}
			if(jQuery('#placeOrderWrap').length === 0){
				jQuery('form.woocommerce-checkout #customer_details').append('<div id="placeOrderWrap"></div>')
				jQuery('.woocommerce-terms-and-conditions-wrapper,#place_order').appendTo(jQuery('#placeOrderWrap'));
			}
			jQuery('#paymentWrap #payment button').remove()
		}
		jQuery(document.body).on('updated_checkout', function (e, data) {
			placeCheckoutButton();
		})
	}

	// 根據運送方式顯示資訊
	function changeFieldsDisplayByShippingMethod(){
		if( $("#CVSStoreName_field strong").text() === '' ){
			$('#ship-to-different-address-checkbox').prop('checked', false);
			$('.shipping_address').hide();
		}
		// 處理運送到不同地址取消勾選
		$('body').on('click', '#shipping_method li input', function(){
			if( 
				$('#shipping_method li input:checked').val() !== "ecpay_shipping" || 
				!$('#shipping_method li input:checked').val().includes('ry_ecpay_shipping_cvs') ||
				!$('#shipping_method li input:checked').val().includes('ry_newebpay_shipping_cvs') 
			){
				$('#ship-to-different-address-checkbox').prop('checked', false);
				$('.shipping_address').hide();
			}
		})
		function toggleBillingAddressField( status ){
			if( status === 'hide' ){
				$('.woocommerce-shipping-fields,h3#ship-to-different-address input,#billing_address_1_field,#billing_address_2_field,#billing_city_field,#billing_state_field,#billing_postcode_field').hide()
			} else {
				$('.woocommerce-shipping-fields,h3#ship-to-different-address input,#billing_address_1_field,#billing_address_2_field,#billing_city_field,#billing_state_field,#billing_postcode_field').show()
			}
		}	
		$(document.body).on('updated_checkout', function (e, data) {
			/**
			 * 針對物流方式顯示帳單與運送地址欄位
			 */
			let shippingMethodNum = $('#shipping_method li').length;
			let shippingMethodSelector = ( shippingMethodNum === 1 ) ? '' : ':checked';
			if( shippingMethodNum >= 1 ){
				if( $('#shipping_method li input' + shippingMethodSelector ).val() === "ecpay_shipping" || 
					$('#shipping_method li input' + shippingMethodSelector ).val().includes('ry_ecpay_shipping_cvs') ||
					$('#shipping_method li input' + shippingMethodSelector ).val().includes('ry_newebpay_shipping_cvs') 
				){
					toggleBillingAddressField('hide')
				} else {
					toggleBillingAddressField('show')	
				}
			}
		});
	}

	$(document).ready(function(){
        if( woomp_checkout_params.enableWoomp === 'yes' ){
			setBillingShippingFieldsSync();
			setUpdateCart();
			setCheckoutButtonToBottom();
			changeFieldsDisplayByShippingMethod();
		}
        if( woomp_checkout_params.enableCountryToTop === 'yes' ){
			setCountryToTop();
		}
        if( woomp_checkout_params.enableCountryToTop === 'yes' ){
			setCountryToTop();
		}
	})
})