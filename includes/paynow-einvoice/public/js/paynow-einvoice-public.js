(function( $ ) {
	'use strict';

	 $(document).ready(function(){
		var issue_type   = $('#paynow_ei_issue_type').val();
        var carrier_type = $('#paynow_ei_carrier_type').val();
        change_fields(issue_type, carrier_type);
    });

	$(document).on('change', '#paynow_ei_issue_type', function () {

		var issue_type = $('#paynow_ei_issue_type').val();
		if (issue_type == 'b2b') {

			$('#paynow-ei-carrier-type').hide();
			change_fields(issue_type,'ei_carrier_type_ubn');

		} else if (issue_type == 'b2c') {

			$('#paynow-ei-carrier-type').show();
			change_fields(issue_type, 'ei_carrier_type_mobile_code');

		} else {
			$('#paynow-ei-carrier-type').hide();
			change_fields(issue_type, 'ei_carrier_type_donate');
		}


	});

    $(document).on('change','#paynow_ei_carrier_type', function(){

		var issue_type = $('#paynow_ei_issue_type').val();
        change_fields(issue_type, $(this).val());

    });

    function change_fields( issue_type, carrier_type ) {
		console.log(issue_type, carrier_type);
        var $c_title     = $('#paynow-ei-company-title');
        var $ubn         = $('#paynow-ei-ubn');
        var $carrier_num = $('#paynow-ei-carrier-num');
        var $donate_org  = $('#paynow-ei-org');


        if( carrier_type == 'ei_carrier_type_ubn' ) {
            $c_title.show();
            $ubn.show();
            $carrier_num.hide();
            $donate_org.hide();
        }

        if ( carrier_type == 'ei_carrier_type_mobile_code' ) {
            $c_title.hide();
            $ubn.hide();
            $carrier_num.show();
            $donate_org.hide();
        }

        if ( carrier_type == 'ei_carrier_type_cdc_code' ) {
            $c_title.hide();
            $ubn.hide();
            $carrier_num.show();
            $donate_org.hide();
        }

        if ( carrier_type == 'ei_carrier_type_donate' ) {
            $c_title.hide();
            $ubn.hide();
            $carrier_num.hide();
            $donate_org.show();
        }

    }

})( jQuery );
