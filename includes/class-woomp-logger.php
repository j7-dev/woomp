<?php declare(strict_types=1);

/**
 * https://github.com/martingcao/mgc-logger
 */

namespace MGC\Logger;

class Logger {

	private $log_dir;
	private $log_file;
	private $options = [
		'time_format' => 'Y-m-d H:i',
	];

	public function __construct( string $sub_dir_name, string $log_file_name, array $opts = [] ) {
		$uploads_dir    = \wp_get_upload_dir();
		$this->log_dir  = \trailingslashit( $uploads_dir['basedir'] ) . $sub_dir_name;
		$this->log_file = \trailingslashit( $this->log_dir ) . $log_file_name;

		if ( ! is_dir( $this->log_dir ) ) {
			mkdir( $this->log_dir, 0755, true );
		}

		if ( ! empty( $opts ) ) {
			$this->options = array_replace_recursive( $this->options, $opts );
		}
	}

	public function log( string $base_message, array $tags = [] ): void {
		$message = $this->transform_message( $base_message, $tags );
		file_put_contents( $this->log_file, $message, FILE_APPEND );
	}

	public function get_test_log( string $base_message = 'Test Message', array $tags = [] ): array {
		$info = [
			'log_directory'        => $this->log_dir,
			'log_directory_exists' => is_dir( $this->log_dir ),
			'log_file'             => $this->log_file,
			'log_file_exists'      => file_exists( $this->log_file ),
			'base_message'         => $base_message,
			'tags'                 => $tags,
			'final_message'        => $this->transform_message( $base_message, $tags ),
		];
		return $info;
	}

	private function transform_message( string $original, $tags = [] ): string {
		$date        = date( $this->options['time_format'] );
		$prefix      = $tags ? '[' . implode( '][', $tags ) . ']' : '';
		$transformed = "$date: $prefix $original" . PHP_EOL;
		return $transformed;
	}
}
