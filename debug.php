<?php
namespace J7\WpUtils\Classes;

if (!class_exists('J7\WpUtils\Classes\ErrorLog')) {
	class ErrorLog {
		/**
		 * Log a message.
		 *
		 * @param string $message The message to log.
		 * @param string $level The log level.
		 *
		 * @return void
		 */
		protected static function log( $message, $level = 'info' ): void {

			switch ( $level ) {
				case 'info':
					$emoji = '📘 ';
					break;
				case 'error':
					$emoji = '❌ ';
					break;
				case 'debug':
					$emoji = '🐛 ';
					break;
				default:
					$emoji = '';
					break;
			}

			$formatted_message = sprintf(
			'[%1$s%2$s] %3$s',
			$emoji,
			strtoupper( $level ),
			$message
			);

			if ( defined( 'ABSPATH' ) ) {
				$default_path      = \ABSPATH . 'wp-content';
				$default_file_name = 'debug.log';
				$log_in_file       = file_put_contents( "{$default_path}/{$default_file_name}", '[' . gmdate( 'Y-m-d H:i:s' ) . ' UTC]' . $formatted_message . PHP_EOL, FILE_APPEND );
			} else {
				// Write the log message using error_log()
				error_log( $formatted_message );
			}
		}
		/**
		 * Log a info message.
		 *
		 * @param string $message The message to log.
		 *
		 * @return void
		 */
		public static function info( $message ): void {
			self::log( $message, 'info' );
		}
	}
}
