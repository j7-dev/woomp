<?php
/**
 * 訂閱發票管理 Metabox
 */

declare (strict_types = 1);

namespace J7\Woomp\Admin\InoviceManagement;

// 如果沒有啟用訂閱，就不初始化訂閱發票管理
if (!class_exists('WC_Subscriptions')) {
	return;
}

/**
 * Class InoviceManagement
 */
final class InoviceManagement {

	const METABOX_ID = 'woomp_invoice_management';

	/**
	 * 是否啟用立吉富電子發票
	 *
	 * @var bool
	 */
	private $is_paynow_einvoice_active;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->is_paynow_einvoice_active = \get_option( 'wc_settings_tab_active_paynow_einvoice' ) === 'yes';

		// 目前只支援立吉富電子發票 其他的未來再慢慢支援
		if (!$this->is_paynow_einvoice_active) {
			return;
		}

		\add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		\add_action( 'save_post', [ $this, 'save_meta_box' ] );

		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Adds the meta box container.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function add_meta_box( string $post_type ): void {
		// Limit meta box to certain post types.
		$post_types = [ 'shop_subscription' ];

		if ( in_array( $post_type, $post_types, true ) ) {
			\add_meta_box( self::METABOX_ID, __( '訂閱發票資訊(下期開始以下方資訊開立發票)', 'woomp' ), [ $this, 'render_meta_box_content' ], $post_types, 'normal', 'default' );
		}
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param \WP_Post $post    The current post.
	 * @return void
	 */
	public function render_meta_box_content( $post ): void {

		echo '<div class="woomp">';

		echo '<div class="grid grid-cols-4 gap-4 [&_label]:block">';
		\woocommerce_wp_select(
			[
				'id'      => 'woomp_invoice_management_select',
				'label'   => '請選擇電子發票廠商',
				'class'   => ' w-full ',
				'options' => [
					'paynow' => '立吉富 PAYNOW',
				],
			]
			);
		echo '</div>';

		$fields = \Paynow_Einvoice::get_einvoice_fields();
		echo '<div class="grid grid-cols-4 gap-4 [&_label]:block">';
		foreach ($fields as $field => $args) {
			$input_type = match ($args['type']) {
				'text' => 'woocommerce_wp_text_input',
				'select' => 'woocommerce_wp_select',
				default => 'woocommerce_wp_text_input',
			};

			$value = \get_post_meta( $post->ID, "_{$field}", true );

			$input_type(
				[
					'id'            => $field,
					'label'         => $args['label'] ?? '',
					'placeholder'   => $args['placeholder'] ?? '',
					'value'         => $value,
					'class'         => ' w-full ',
					'wrapper_class' => ( !$value && !\in_array($field, [ 'paynow_ei_carrier_type', 'paynow_ei_issue_type' ], true) ? ' tw-hidden' : '' ),
					'options'       => $args['options'] ?? [],
				]
			);
		}
		echo '</div>';

		echo '</div>';
	}


	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts(): void {
		\wp_enqueue_script( 'paynow-einvoice', WOOMP_PLUGIN_URL . 'includes/paynow-einvoice/public/js/paynow-einvoice-public.js', [ 'jquery' ], '1.0.0', false );
	}

	/**
	 * 把發票資訊存入訂閱
	 *
	 * @param int $post_id Post ID
	 */
	public function save_meta_box( $post_id ) {

		$fields = \Paynow_Einvoice::get_einvoice_fields();
		foreach ($fields as $field => $args) {
			$value = \sanitize_text_field( $_POST[ $field ] ?? '' ); // phpcs:ignore
			\update_post_meta( $post_id, "_{$field}", $value );
		}
	}
}

new InoviceManagement();
