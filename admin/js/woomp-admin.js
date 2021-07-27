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
		<table class="zone_tw_island" style="display:none;">
			<tr>
				<th>金門縣</th>
				<th>澎湖縣</th>
				<th>連江縣</th>
			</tr>
			<tr>
				<td><label for="group1"><input id="group1" class="group" type="checkbox">全選</label></td>
				<td><label for="group2"><input id="group2" class="group" type="checkbox">全選</label></td>
				<td><label for="group3"><input id="group3" class="group" type="checkbox">全選</label></td>
			</tr>
			<tr>
	  			<td><label for="island1"><input id="island1" class="group1 island" name="island[]" type="checkbox" value="890">金沙鎮</label></td>
	  			<td><label for="island3"><input id="island3" class="group2 island" name="island[]" type="checkbox" value="880">馬公市</label></td>
	  			<td><label for="island2"><input id="island2" class="group3 island" name="island[]" type="checkbox" value="209">南竿鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island4"><input id="island4" class="group1 island" name="island[]" type="checkbox" value="891">金湖鎮</label></td>
	  			<td><label for="island6"><input id="island6" class="group2 island" name="island[]" type="checkbox" value="881">西嶼鄉</label></td>
	  			<td><label for="island5"><input id="island5" class="group3 island" name="island[]" type="checkbox" value="210">北竿鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island7"><input id="island7" class="group1 island" name="island[]" type="checkbox" value="892">金寧鄉</label></td>
	  			<td><label for="island9"><input id="island9" class="group2 island" name="island[]" type="checkbox" value="882">望安鄉</label></td>
	  			<td><label for="island8"><input id="island8" class="group3 island" name="island[]" type="checkbox" value="211">莒光鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island10"><input id="island10" class="group1 island" name="island[]" type="checkbox" value="893">金城鎮</label></td>
	  			<td><label for="island12"><input id="island12" class="group2 island" name="island[]" type="checkbox" value="883">七美鄉</label></td>
	  			<td><label for="island11"><input id="island11" class="group3 island" name="island[]" type="checkbox" value="212">東引鄉</label></td>
			</tr>
			<tr>
	  			<td><label for="island13"><input id="island13" class="group1 island" name="island[]" type="checkbox" value="894">烈嶼鄉</label></td>
	  			<td><label for="island14"><input id="island14" class="group2 island" name="island[]" type="checkbox" value="884">白沙鄉</label></td>
	  			<td>&nbsp;</td>
			</tr>
			<tr>
	  			<td><label for="island15"><input id="island15" class="group1 island" name="island[]" type="checkbox" value="896">烏坵鄉</label></td>
				<td><label for="island16"><input id="island16" class="group2 island" name="island[]" type="checkbox" value="885">湖西鄉</label></td>
				<td>&nbsp;</td>
			</tr>
		</table>
	`)
	
	// 取得有勾選的地區
	function updatePostCode(){
		var checkedCode = $(".zone_tw_island input.island:checkbox:checked").map(function(){
			return $(this).val();
		  }).get();

		$('#zone_postcodes').text( checkedCode.sort().join("\n") );
		$('#zone_postcodes').trigger('input');
	}

	// 寫入郵遞區號
	$('#TW_Island').change(function(){
		if( $(this).is(':checked') ){
			$('.zone_tw_island').show();
			$('.zone_tw_island input').prop('checked', true);
			updatePostCode()
		} else {
			$('.zone_tw_island').hide();
			$('.zone_tw_island input').prop('checked', false);
			updatePostCode()
		}
	})
	

	// 判斷是否有輸入郵遞區號
	if( $('#zone_postcodes').val() != '' ){
		$('#TW_Island').prop('checked',true);
		$('.zone_tw_island').show();
	} else {
		$('#TW_Island').prop('checked',false);
		$('.zone_tw_island').hide();
	}

	// 讀取既有的郵遞區號
	var current_code = $('#zone_postcodes').val().split('\n');
	for (let index = 0; index < current_code.length; index++) {
		$(".zone_tw_island input.island[value='"+ current_code[index] +"']").prop('checked', true);
	}

	

	// 郵遞區號寫入與刪除
	$('.zone_tw_island input[type="checkbox"]').change(function(){
		updatePostCode()
	})

	// 全選寫入與刪除
	for (let index = 1; index < 4 ; index++) {
		$('#group' + index ).change(function(){
			if( $(this).is(':checked') ){
				$('.group' + index).prop('checked', true);
				updatePostCode()
			} else {
				$('.group' + index ).prop('checked', false);
				updatePostCode()
			}
		})
	}

})( jQuery );
