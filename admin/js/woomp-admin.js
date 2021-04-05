(function( $ ) {
	var addr0 = $('#_billing_postcode').val()
	var addr1 = $('#_billing_country option:selected').text()
	var addr2 = $("#_billing_state").val();
	var addr3 = $("#_billing_city").val();
	var addr4 = $("#_billing_address_1").val();
	var addr5 = $("#_billing_address_2").val();
	$(".full-address input").val( addr0 + " " + addr1 + addr2 + addr3 + addr4 + addr5 );
	$("#fullAddress span").html( addr0 + " " + addr1 + addr2 + addr3 + addr4 + addr5 );
	$("#billingName").insertAfter(".order_data_column:nth-child(2) .address p:first-child"
  );
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
})( jQuery );
