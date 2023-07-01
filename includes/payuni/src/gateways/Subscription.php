<?php

namespace PAYUNI\Gateways;

defined( 'ABSPATH' ) || exit;

class Subscription {
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = new self();
		//add_filter( 'upload_mimes', array( $class, 'add_upload_mimes' ) );
	}
	public function pay(){

	}
}

//Subscription::init();
