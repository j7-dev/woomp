<?php

class PayNow_EInvoice_Issue_Type {

	const B2B    = 'b2b';
	const B2C    = 'b2c';
	const DONATE = 'donate';

    const UBN = 'ei_carrier_type_ubn';
    const MOBILE_CODE = 'ei_carrier_type_mobile_code';
    const CDC_CODE = 'ei_carrier_type_cdc_code';
    // const DONATE = 'ei_carrier_type_donate';

	public static function getType( $issue_type ) {
		switch ( $issue_type ) {
            case 'b2b':
                return '公司用統一發票';
                break;
            case 'b2c':
                return '個人用統一發票';
                break;
            case 'donate':
                return '捐贈發票';
                break;
            default:
                return '無法判斷的索取類型：' . $issue_type;
                break;
        }
	}

    public static function getName( $carrier_type ) {
        switch ( $carrier_type ) {
            case 'ei_carrier_type_ubn':
                return '統一編號';
                break;
            case 'ei_carrier_type_mobile_code':
                return '手機條碼';
                break;
            case 'ei_carrier_type_cdc_code':
                return '自然人憑證條碼';
                break;
            case 'ei_carrier_type_donate':
                return '捐贈發票';
                break;
            default:
                return '無法判斷的索取類型：' . $carrier_type;
                break;
        }
    }

}