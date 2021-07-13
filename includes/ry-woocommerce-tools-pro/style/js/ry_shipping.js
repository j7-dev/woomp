jQuery(function ($) {

	$(document.body).on('updated_checkout', function (e, data) {
		if (data !== undefined) {
			if (data.fragments.hide_billing_address !== undefined && data.fragments.hide_billing_address) {
				$('.woocommerce-billing-fields__field-wrapper p#billing_country_field').hide();
				$('.woocommerce-billing-fields__field-wrapper p#billing_address_1_field').hide();
				$('.woocommerce-billing-fields__field-wrapper p#billing_address_2_field').hide();
				$('.woocommerce-billing-fields__field-wrapper p#billing_city_field').hide();
				$('.woocommerce-billing-fields__field-wrapper p#billing_state_field').hide();
				$('.woocommerce-billing-fields__field-wrapper p#billing_postcode_field').hide();
			} else {
				$('.woocommerce-billing-fields__field-wrapper p#billing_country_field').show();
				$('.woocommerce-billing-fields__field-wrapper p#billing_address_1_field').show();
				$('.woocommerce-billing-fields__field-wrapper p#billing_address_2_field').show();
				$('.woocommerce-billing-fields__field-wrapper p#billing_city_field').show();
				$('.woocommerce-billing-fields__field-wrapper p#billing_state_field').show();
				$('.woocommerce-billing-fields__field-wrapper p#billing_postcode_field').show();
			}
		}
	});

});
