<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class WC_Settings_Tab_PayNow_EInvoice extends WC_Settings_Page {


    public function __construct() {
        $this->id    = 'paynow';
        // $this->label = __( 'PayNow 電子發票', 'paynow-einvoice' );


		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

		parent::__construct();

    }

	/**
	 * Get setting sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'einvoice'     => __( '電子發票設定', 'paynow' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

    private static function ww_get_order_status() {
        $order_statuses = array();

        foreach ( wc_get_order_statuses() as $slug => $name ) {
            if ( $slug == 'wc-cancelled' || $slug == 'wc-refunded' || $slug == 'wc-failed' )
                continue;
            $order_statuses[ str_replace( 'wc-', '', $slug ) ] = $name;
        }

        return $order_statuses;
    }

    public function get_settings( $current_section = '' ) {

		if ( 'einvoice' === $current_section ) {
			$settings = apply_filters(
				'paynow_einvoice_settings',
				array(
				'section_title' => array(
					'name'     => __( 'E-Invoice Settings', 'paynow-einvoice' ),
					'type'     => 'title',
					'desc'     => '',
					'id'       => 'wc_settings_tab_demo_section_title'
				),
				'active_paynow_einvoice' => array(
					'name' => __( 'Enable', 'paynow-einvoice' ),
					'type' => 'checkbox',
					'desc' => '',
					'id'   => 'wc_settings_tab_active_paynow_einvoice'
				),
				'paynow_einvoice_sandbox' => array(
					'name' => __( 'Test Mode', 'paynow-einvoice' ),
					'type' => 'checkbox',
					'desc' => '',
					'id'   => 'wc_settings_tab_paynow_einvoice_sandbox'
				),
				'paynow_debug_log' => array(
					'name'       => __('Debug Log', 'paynow-einvoice'),
					'type'        => 'checkbox',
					'label'       => __('Enable Logging', 'paynow-einvoice'),
					'default'     => 'no',
					'desc' => sprintf(__('Log PayNow E-Invoice message, inside <code>%s</code>', 'paynow-einvoice'), wc_get_log_file_path('paynow-einvoice')),
					'id'          => 'paynow_einvoice_debug_log_enabled'
				),
				'mem_cid' => array(
					'name' => __( 'Merchant ID', 'paynow-einvoice' ),
					'type' => 'text',
					'desc' => '',
					'id'   => 'wc_settings_tab_mem_cid'
				),
				'mem_password' => array(
					'name' => __( 'Merchant Password', 'paynow-einvoice' ),
					'type' => 'text',
					'desc' => '',
					'id'   => 'wc_settings_tab_mem_password'
				),
				'issue_mode' => array(
					'name' => __( 'Issue Mode', 'paynow-einvoice' ),
					'type' => 'radio',
					'desc' => __('You can issue the e-invoice manually even if you choose Automatic mode'),
					'desc_tip' => true,
					'id'   => 'wc_settings_tab_issue_mode',
					'options' => array(
						'auto'=> __( 'Automatic', 'paynow-einvoice'),
						'manual' => __( 'Manual', 'paynow-einvoice'),
					),
					'default' => 'auto'
				),
				'issue_at' => array(
					'name' => __( 'Allowed Order Status', 'paynow-einvoice' ),
					'type' => 'select',
					'class'=> 'wc-enhanced-select',
					'desc' => __('When order status changes to the status, the e-invoice will be issued automatically.'),
					'id'   => 'wc_settings_tab_issue_at',
					'desc_tip'=> true,
					'options' => self::ww_get_order_status()
				),
				'tax_type' => array(
					'name' => __( 'Tax Type', 'paynow-einvoice' ),
					'type' => 'select',
					'desc' => __('When input the product price, please input the price with tax-included.'),
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options' => array(
						'1' => '應稅(5%)',
						'2'   => '零稅率(0%)',
						'3'   => '免稅(0%)'
					),
					'id'   => 'wc_settings_tab_tax_type',
				),
				'carrier_type' => array(
					'name' => __( 'Carrier Type', 'paynow-einvoice' ),
					'type' => 'checkbox',
					'desc'    => __( 'Mobile Code', 'paynow-einvoice' ),
					'default' => 'yes',
					'id'   => 'wc_settings_tab_carrier_type_mobile_code',
					'checkboxgroup' => 'start',
				),
				array(
					'desc'            => __( 'Citizen Digital Certificate', 'paynow-einvoice' ),
					'id'              => 'wc_settings_tab_carrier_type_cdc_code',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => '',
				),
				array(
					'desc'            => __( 'Easy Card', 'paynow-einvoice' ),
					'id'              => 'wc_settings_tab_carrier_type_easycard_code',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => '',
				),
				// array(
				// 	'desc'            => __( '捐贈發票', 'woocommerce' ),
				// 	'id'              => 'wc_settings_tab_carrier_type_donate',
				// 	'default'         => 'yes',
				// 	'type'            => 'checkbox',
				// 	'checkboxgroup'   => '',
				// ),
				'donate_org' => array(
					'name' => __( 'Donated Organization', 'paynow-einvoice' ),
					'type' => 'textarea',
					'desc' => '輸入捐增機構(每行一筆)，格式為：愛心碼|社福團體名稱',
					'desc_tip' => true,
					'id'   => 'wc_settings_tab_donate_org',
				),
				'section_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_tab_demo_section_end'
				),
        	)
			);
		}

        return apply_filters( 'wc_settings_tab_paynow_einvoice_settings', $settings );
    }

	public function output() {

		global $current_section;

		if ($current_section !== 'einvoice') return;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::output_fields( $settings );
	}

	function output_order_section( $order_status ) {
		echo $order_status;
	}

    public function save() {

		global $current_section;

		if ($current_section !== 'einvoice') return;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}

}
