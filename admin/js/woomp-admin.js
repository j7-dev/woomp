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

	// 增加離島 toggle 按鈕
	$('#zone_locations').after(`<fieldset class="toggle" id="zoneTwIsland"><label for="TW_Island"><input name="TW_Island" id="TW_Island" type="checkbox" class="toggle"><label for="TW_Island">Toggle</label><span class="description">啟用 / 停用 台灣離島運送區域</span></label></fieldset>`);

	// 增加離島複選方塊
	$('#zoneTwIsland').after(`
		<table class="zone_tw_island">
			<tr>
				<th>金門縣</th>
				<th>澎湖縣</th>
				<th>連江縣</th>
			</tr>
			<tr>
				<td><label for="group1"><input id="group1" type="checkbox" checked="checked">全選</label></td>
				<td><label for="group2"><input id="group2" type="checkbox" checked="checked">全選</label></td>
				<td><label for="group3"><input id="group3" type="checkbox" checked="checked">全選</label></td>
			</tr>
			<tr>
	  			<td><label for="island1"><input id="island1" name="island[]" type="checkbox" checked="checked" value="890">金沙鎮</label></td>
	  			<td><label for="island3"><input id="island3" name="island[]" type="checkbox" checked="checked" value="880">馬公市</label></td>
	  			<td><label for="island2"><input id="island2" name="island[]" type="checkbox" checked="checked" value="209">南竿鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island4"><input id="island4" name="island[]" type="checkbox" checked="checked" value="891">金湖鎮</label></td>
	  			<td><label for="island6"><input id="island6" name="island[]" type="checkbox" checked="checked" value="881">西嶼鄉</label></td>
	  			<td><label for="island5"><input id="island5" name="island[]" type="checkbox" checked="checked" value="210">北竿鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island7"><input id="island7" name="island[]" type="checkbox" checked="checked" value="892">金寧鄉</label></td>
	  			<td><label for="island9"><input id="island9" name="island[]" type="checkbox" checked="checked" value="882">望安鄉</label></td>
	  			<td><label for="island8"><input id="island8" name="island[]" type="checkbox" checked="checked" value="211">莒光鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island10"><input id="island10" name="island[]" type="checkbox" checked="checked" value="893">金城鎮</label></td>
	  			<td><label for="island12"><input id="island12" name="island[]" type="checkbox" checked="checked" value="883">七美鄉</label></td>
	  			<td><label for="island11"><input id="island11" name="island[]" type="checkbox" checked="checked" value="212">東引鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island13"><input id="island13" name="island[]" type="checkbox" checked="checked" value="894">烈嶼鄉</label></td>
	  			<td><label for="island14"><input id="island14" name="island[]" type="checkbox" checked="checked" value="884">白沙鄉</label></td>
	  			<td>&nbsp;</td>
			</tr>
			<tr>
	  			<td><label for="island15"><input id="island15" name="island[]" type="checkbox" checked="checked" value="896">烏坵鄉</label></td>
				<td><label for="island16"><input id="island16" name="island[]" type="checkbox" checked="checked" value="111">湖西鄉</label></td>
				<td>&nbsp;</td>
			</tr>
		</table>
	`)


	// 運送方式預設離島郵遞區號
	//$('.wc-shipping-zone-settings tr:nth-child(2)').after(`
	//	<tr>
	//		<th>
	//			<select id="selectZonePostcodes" style="width: 200px; margin-top: -8px;">
	//				<option value="zone_postcodes">限制為特定郵遞區號</option>
	//				<option value="zone_postcodes_tw_local">設定台灣本島郵遞區號</option>
	//				<option value="zone_postcodes_tw_island">設定台灣離島郵遞區號</option>
	//			</select>
	//		</th>
	//		<td id="zonePostcodesTextarea"></td>
	//	</tr>
	//`)
	//$('.wc-shipping-zone-postcodes').appendTo($('#zonePostcodesTextarea'))
	//$('#selectZonePostcodes').change(function(){
	//	if( $(this).val() ===  'zone_postcodes'){
	//		$('#zone_postcodes').text('');
	//	}
	//	if( $(this).val() ===  'zone_postcodes_tw_local'){
	//		$('#zone_postcodes').text('100...116\n200...208\n220...253\n260...290\n300\n302...315\n320...338\n350...369\n400...439\n500...530\n540...558\n600\n602...625\n630...655\n700...745\n800...852\n900...947\n950...966\n970...983')
	//	}
	//	if( $(this).val() ===  'zone_postcodes_tw_island'){
	//		$('#zone_postcodes').text('209...212\n880...885\n890...896')
	//	}
	//	$('#zone_postcodes').trigger('input');
	//})

	//if( $('#zone_postcodes').val().includes('100...116') ){
	//	$('#selectZonePostcodes option[value="zone_postcodes_tw_local"]').prop('selected',true);
	//}

	//if( $('#zone_postcodes').val().includes('890...896') ){
	//	$('#selectZonePostcodes option[value="zone_postcodes_tw_island"]').prop('selected',true);
	//}

})( jQuery );
