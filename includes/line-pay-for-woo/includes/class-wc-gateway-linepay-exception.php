<?php

/**
 * Define the exception to be used in LINEPay Gateway.
 *
 * PHP default exception cannot save object
 * Extends to define Object by inheriting Exception
 *
 * @class WC_Gateway_LINEPay_Exception
 * @version 1.0.0
 * @author LINEPay
 */
class WC_Gateway_LINEPay_Exception extends Exception {

	private $return_code;
	private $info;

	/**
	 * constructor
	 *
	 * @param string $message
	 * @param mixed $info
	 * @param [type] $code
	 * @param [type] $previous
	 */
	public function __construct( $message = null, $info = null, $code = null, $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->info = $info;
	}

	public function getInfo() {

		return $this->info;
	}

	public function setInfo( $info = null ) {
		$this->info = $info;
	}

}