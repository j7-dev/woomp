(function( $ ) {
	var addr0 = $('#_billing_postcode').val()
	var addr1 = $('#_billing_country option:selected').text()
	var addr2 = $("#_billing_state").val();
	var addr3 = $("#_billing_city").val();
	var addr4 = $("#_billing_address_1").val();
	var addr5 = $("#_billing_address_2").val();
	$(".full-address input").val( addr0 + " " + addr1 + addr2 + addr3 + addr4 + addr5 );
	$("#fullAddress span").html( addr0 + " " + addr1 + addr2 + addr3 + addr4 + addr5 );
	$("#billingName").insertAfter(".order_data_column:nth-child(2) .address p:first-child");
	$('#fullAddress').insertAfter('p#billingName');
	
	$(".full-address input").on('paste change', function () {
		setTimeout(() => {
			var addrf1 = $(".full-address input").val().replaceAll("市", "市 ");
			var addrf2 = addrf1.replaceAll("區", "區 ");
			var addr = addrf2.split(' ');
			$("#_billing_state").val(addr[1]);
			$("#_billing_city").val(addr[2]);
			$("#_billing_postcode").val(addr[0]);
			$("#_billing_address_1").val(addr[3]);
		}, 300);
  	});

	// 運送方式預設離島郵遞區號
	$('.wc-shipping-zone-settings tr:nth-child(2)').after(`
		<tr>
			<th>
				<select id="selectZonePostcodes" style="width: 200px; margin-top: -8px;">
					<option value="zone_postcodes">限制為特定郵遞區號</option>
					<option value="zone_postcodes_tw_local">設定台灣本島郵遞區號</option>
					<option value="zone_postcodes_tw_island">設定台灣離島郵遞區號</option>
				</select>
			</th>
			<td id="zonePostcodesTextarea"></td>
		</tr>
	`)
	$('.wc-shipping-zone-postcodes').appendTo($('#zonePostcodesTextarea'))
	$('#selectZonePostcodes').change(function(){
		if( $(this).val() ===  'zone_postcodes'){
			$('#zone_postcodes').text('');
		}
		if( $(this).val() ===  'zone_postcodes_tw_local'){
			$('#zone_postcodes').text('100...116\n200...208\n220...253\n260...290\n300\n302...315\n320...338\n350...369\n400...439\n500...530\n540...558\n600\n602...625\n630...655\n700...745\n800...852\n900...947\n950...966\n970...983')
		}
		if( $(this).val() ===  'zone_postcodes_tw_island'){
			$('#zone_postcodes').text('209...212\n880...885\n890...896')
		}
		$('#zone_postcodes').trigger('input');
	})

	if( $('#zone_postcodes').val().includes('100...116') ){
		$('#selectZonePostcodes option[value="zone_postcodes_tw_local"]').prop('selected',true);
	}

	if( $('#zone_postcodes').val().includes('890...896') ){
		$('#selectZonePostcodes option[value="zone_postcodes_tw_island"]').prop('selected',true);
	}

})( jQuery );
