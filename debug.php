<?php
/**
 * Log class
 * 方便印出 Error Log
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if (!class_exists('J7\WpUtils\Classes\ErrorLog')) {
	/**
	 * Log class
	 */
	abstract class ErrorLog {

		/**
		 * Log a message.
		 *
		 * @param mixed  $message The message to log.
		 * @param string $level The log level.
		 * @param string $context The context of the message.
		 *
		 * @return void
		 */
		protected static function log( $message, string $level = 'info', ?string $context = '' ): void {
			$emoji = match ( $level ) {
				'info' => '📘 ',
				'error' => '❌ ',
				'debug' => '🐛 ',
				default => ' ',
			};

			$formatted_message = sprintf(
			'[%1$s%2$s] %3$s %4$s',
			$emoji,
			strtoupper( $level ),
			$context,
			self::stringify( $message )
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
		 * Stringify the message.
		 *
		 * @param mixed $message The message to stringify.
		 *
		 * @return string The stringified message.
		 */
		public static function stringify( $message ): string {
			ob_start();
			var_dump($message);
			return ob_get_clean();
		}

		/**
		 * Log a info message.
		 *
		 * @param mixed  $message The message to log.
		 * @param string $context The context of the message.
		 *
		 * @return void
		 */
		public static function info( $message, ?string $context = '' ): void {
			self::log( $message, 'info', $context );
		}

		/**
		 * Log a error message.
		 *
		 * @param mixed  $message The message to log.
		 * @param string $context The context of the message.
		 *
		 * @return void
		 */
		public static function error( $message, ?string $context = '' ): void {
			self::log( $message, 'error', $context );
		}

		/**
		 * Log a debug message.
		 *
		 * @param mixed  $message The message to log.
		 * @param string $context The context of the message.
		 *
		 * @return void
		 */
		public static function debug( $message, ?string $context = '' ): void {
			self::log( $message, 'debug', $context );
		}
	}
}
