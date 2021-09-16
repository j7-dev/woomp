<?php

/**
 *Define Logger to be used in LINEPay Gateway.
 *
 *-Logger used WC_Logger.
 *-Whether to output is determined according to the log level registered in the settings.
 *-Since there is a high possibility of performance degradation by writing directly to a file
 * Record the result in error level only when the required linepay-api request fails.
 *
 * @class 		WC_Gateway_LINEPay_Logger
 * @version		1.0.0
 * @author 		LINEPay
 */
class WC_Gateway_LINEPay_Logger {

	// Log LeveL
	const LOG_LEVEL_NONE	= 'none';
	const LOG_LEVEL_ERROR	= 'error';
	const LOG_LEVEL_DEBUG	= 'debug';
	const LOG_LEVEL_INFO	= 'info';

	// Priority registration of Log Level
	private static $log_levels = array(
			self::LOG_LEVEL_ERROR,
			self::LOG_LEVEL_DEBUG,
			self::LOG_LEVEL_INFO
	);

	/**
	 * @var WC_Gateway_LINEPay_Logger
	 */
	private static $LINEPAY_LOGGER;

	private $wc_logger		= null;
	private $log_enabled	= false;
	private $log_level		= self::LOG_LEVEL_NONE;

	/**
	 * Initialize LINEPay Logger.
	 * Create a WC_Logger instance only when log is used.
	 *
	 * For singleton patterns, restrict access to prevent instantiation.
	 *
	 * @param array $log_info
	 */
	protected function __construct( $log_info ) {

		if ( is_array( $log_info ) ) {
			$this->log_enabled	= $log_info[ 'enabled' ];
			$this->log_level	= $log_info[ 'level' ];

			if ( $this->log_enabled ) {
				$this->wc_logger = new WC_Logger();
			}
		}

	}

	/**
	 * Log info level.
	 *
	 * @param string $title
	 * @param stdClass|array|WP_Error|string $message
	 */
	public function info( $title, $message ) {
		$this->log( self::LOG_LEVEL_INFO, $title, $message );
	}

	/**
	 * Log debug level.
	 *
	 * @param string $title
	 * @param stdClass|array|WP_Error|string $message
	 */
	public function debug( $title, $message ) {
		$this->log( self::LOG_LEVEL_DEBUG, $title, $message );
	}

	/**
	 * Log the error level.
	 *
	 * @param string $title
	 * @param stdClass|array|WP_Error|string $message
	 */
	public function error( $title, $message ) {
		$this->log( self::LOG_LEVEL_ERROR, $title, $message );
	}

	/**
	 * Returns the WC_Gateway_LINEPay_Logger instance.
	 *
	 * @param array $log_info
	 */
	public static function get_instance( $log_info ) {

		if ( static::$LINEPAY_LOGGER === null ) {
			static::$LINEPAY_LOGGER = new WC_Gateway_LINEPay_Logger( $log_info );
		}

		return static::$LINEPAY_LOGGER;
	}


	/**
	 * Record the log.
	 *
	 * @param string $level	=> WC_Gateway_LINEPay_Logger::LOG_LEVEL_NONE|ERROR|DEBUG|INFO
	 * @param string $title
	 * @param stdClass|array|WP_Error|string $message
	 */
	private function log( $level, $title, $message ) {

		if ( ! $this->log_enabled ) {
			return;
		}

		if ( $this->log_level == self::LOG_LEVEL_NONE || $level == self::LOG_LEVEL_NONE ) {
			return;
		}

		$required_level_idx	= array_search( $this->log_level, static::$log_levels );
		$level_idx			= array_search( $level, static::$log_levels );

		// When the log of the selected log level is not requested
		if ( $level_idx > $required_level_idx ) {

			return;
		}

		// When the message is WP_Error
		if ( is_wp_error( $message ) ) {
			$code			= $message->get_error_code();
			$msg			= $message->get_error_message( $code );
			$data			= $message->get_error_data( $code );
			$data_format	= ( empty ( $data ) ) ? '[%s]' : '';

			$message = sprintf('[%s][%s]' . $data_format, $code, $msg, $this->to_string( $data ) );

		// When the message is stdClass, array, string
		} else {
			$message = $this->to_string( $message );
		}

		// log record
		$this->wc_logger->add( 'linepay', sprintf( '[%s][%s] - %s', $level, $title, $message ) );

	}

	/**
	 * The message is changed and returned by character.
	 *
	 * @param	stdClass|array|string $message
	 * @return	string
	 */
	private function to_string( $message ) {

		// stdClass -> array
		if ( is_a( $message, 'stdClass' ) ) {
			$message = json_decode( json_encode( $message ), true );
		}

		// array -> string
		if ( is_array( $message ) ) {
			$message = json_encode( $message );
		}

		return $message;

	}

	/**
	 * Do not clone instances when using singleton patterns.
	 */
	private function __clone() { }

	/**
	 * Do not deserialize instances when using a singleton pattern.
	 */
	private function __wakeup() { }


}