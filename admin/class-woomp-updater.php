<?php

/**
 * Updater plugin class
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WooMP_Updater' ) ) {
	return;
}

class WooMP_Updater {

	public $plugin_slug;
	public $version;
	public $cache_key;
	public $cache_allowed;

	public function __construct() {

		$this->plugin_slug   = 'woomp';
		$this->version       = WOOMP_VERSION;
		$this->cache_key     = 'woomp_updater';
		$this->cache_allowed = false;

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

	}

	public function info( $response, $action, $args ) {

		// do nothing if you're not getting plugin information right now
		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		// do nothing if it is not our plugin
		if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $response;
		}

		// get updates
		$remote = $this->request();

		if ( ! $remote ) {
			return $response;
		}

		$response = new \stdClass();

		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->donate_link    = $remote->donate_link;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;

		$response->sections = array(
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog,
		);

		if ( ! empty( $remote->banners ) ) {
			$response->banners = array(
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}

		return $response;

	}

	public function request() {

		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {

			$remote = wp_remote_get(
				'https://wmp.oberonlai.blog/woomp-update.json',
				array(
					'timeout' => 60,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

	}

	public function update( $transient ) {

		if ( empty( $transient->last_checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if ( $remote && version_compare( $this->version, $remote->version, '<' ) ) {
			$response              = new \stdClass();
			$response->slug        = $this->plugin_slug;
			$response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
			$response->new_version = $remote->version;
			$response->tested      = $remote->tested;
			$response->package     = $remote->download_url;

			$transient->response[ $response->plugin ] = $response;

		}

		return $transient;

	}

	public function purge( $upgrader, $options ) {

		if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			// just clean the cache when new plugin version is installed
			delete_transient( $this->cache_key );
		}

	}

}
