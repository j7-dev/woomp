<?php
/**
 * 信用卡管理 Metabox
 */

declare (strict_types = 1);

namespace J7\Woomp\Admin\CardManagement;

/**
 * Class ShopSubscription
 */
final class CardManagement {

	const METABOX_ID = 'woomp_card_management';

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		\add_action( 'wp_ajax_woomp_set_default', array( $this, 'woomp_set_default_callback' ) );
		// \add_action( 'wp_ajax_nopriv_woomp_set_default', array( $this, 'woomp_set_default_callback' ) );

		\add_action( 'wp_ajax_woomp_remove', array( $this, 'woomp_remove_callback' ) );
		// \add_action( 'wp_ajax_nopriv_woomp_remove', array( $this, 'woomp_remove_callback' ) );
	}

	/**
	 * Adds the meta box container.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function add_meta_box( string $post_type ): void {
		// Limit meta box to certain post types.
		$post_types = array( 'shop_order', 'shop_subscription' );

		if ( in_array( $post_type, $post_types, true ) ) {
			\add_meta_box( self::METABOX_ID, __( '信用卡儲存資訊', 'woomp' ), array( $this, 'render_meta_box_content' ), $post_types, 'normal', 'default' );
		}
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param \WP_Post $post    The current post.
	 * @return void
	 */
	public function render_meta_box_content( $post ): void {
		// TODO
		?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
	tailwind.config = {
			blocklist: ['hidden', 'columns-1', 'columns-2', 'fixed'],
			plugins: [
	function ({ addUtilities }) {
		const newUtilities = {
		'.rtl': {
			direction: 'rtl',
		},

		// 與 WordPress 衝突的 class
		'.tw-hidden': {
			display: 'none',
		},
		'.tw-columns-1': {
			columnCount: 1,
		},
		'.tw-columns-2': {
			columnCount: 2,
		},
		'.tw-fixed': {
			position: 'fixed',
		},
		}
		addUtilities(newUtilities, ['responsive', 'hover'])
	},
	],
	}
	</script>
		<?php
		$post_type = \get_post_type( $post );
		$is_order  = 'shop_order' === $post_type;
		$order     = $this->get_order_by_post( $post );
		if ( ! $order ) {
			echo $is_order ? '找不到訂單資訊' : '找不到此訂閱的上層訂單資訊';
			return;
		}

		$customer_id    = $order->get_customer_id();
		$payment_tokens = \WC_Payment_Tokens::get_customer_tokens( $customer_id, '' ); // payuni-credit-subscription
		// TODO 之後再整理
		$nonce    = \wp_create_nonce( 'woomp' );
		$ajax_url = \admin_url( 'admin-ajax.php' );

		echo '<div class="grid grid-cols-6 text-center [&_div]:py-3">';
		include __DIR__ . '/templates/row-head.php';
		foreach ( $payment_tokens as $payment_token ) {
			$args = $this->format_payment_token( $payment_token );
			include __DIR__ . '/templates/row.php';

		}
		echo '</div>';

		?>
<script>
	(function($){
		const ajaxurl = '<?php echo $ajax_url; ?>';
		const nonce = '<?php echo $nonce; ?>';
		$('.woomp_ajax').on('click', function(){
			$.blockUI();
			const data = $(this).data();
			data.nonce = nonce;

			const action_label_mapper = {
				woomp_set_default: '設為主要卡號',
				woomp_remove: '移除 token',
			}

			const action_label = action_label_mapper?.[data?.action] || '未知';


			$.post(ajaxurl, data, function(res){

				if(200 === res.code){
					alert(`${action_label} 操作成功，即將重新載入頁面`);
					location.reload();
				}else{
					alert(`${action_label} 操作失敗，請再試一次或連繫管理員`);
					console.log(res);
				}
				$.unblockUI();
			});
		});
	})(jQuery)
</script>
		<?php
	}


	public function admin_enqueue_scripts() {
		$screen         = \get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$allowed_screen = array(
			'shop_subscription',
			'shop_order',
		);
		if ( ! in_array( $screen_id, $allowed_screen, true ) ) {
			return;
		}
		\wp_enqueue_script( 'jquery-blockui' );

		// \wp_enqueue_script( 'woomp-admin', \WOOMP_URL . 'admin/assets/js/admin.js', array( 'jquery' ), \WOOMP_VERSION, true );
	}

	/**
	 * 根據 post 獲取訂單或訂閱的父訂單
	 *
	 * @param \WP_Post $post 文章物件.
	 * @return \WC_Order|null 訂單或訂閱的父訂單
	 */
	private function get_order_by_post( $post ) {
		$post_type = \get_post_type( $post );
		$is_order  = 'shop_order' === $post_type;

		if ( $is_order ) {
			$order_id = (int) $post->ID;
			$order    = \wc_get_order( $order_id );
			return $order;
		}

		$subscription_id = (int) $post->ID;
		$subscription    = \wcs_get_subscription( $subscription_id );
		return $subscription->get_parent();
	}

	/**
	 * 格式化支付令牌資料
	 *
	 * @param \WC_Payment_Token $payment_token 支付令牌物件.
	 * @return array 格式化後的支付令牌資料
	 */
	private function format_payment_token( \WC_Payment_Token $payment_token ): array {
		$payment_token_data = $payment_token->get_data();

		$formatted_data                 = array();
		$formatted_data['user_id']      = $payment_token->get_user_id();
		$formatted_data['token_id']     = $payment_token->get_id();
		$formatted_data['token']        = $payment_token_data['token'];
		$formatted_data['is_default']   = $payment_token_data['is_default'];
		$formatted_data['type']         = $payment_token_data['type'];
		$formatted_data['last4']        = $payment_token_data['last4'];
		$formatted_data['expiry_year']  = $payment_token_data['expiry_year'];
		$formatted_data['expiry_month'] = $payment_token_data['expiry_month'];
		$formatted_data['card_type']    = $payment_token_data['card_type'];
		$formatted_data['card_name']    = match ( $payment_token_data['card_type'] ) {
			'visa'                          =>'VISA',
			'mastercard'                =>'MasterCard',
			'jcb'                               =>'JCB',
			'union pay'                 =>'Union Pay',
			'american express'  =>'American Express',
		};

		return $formatted_data;
	}

	/**
	 * 設置支付令牌為默認的回調函數
	 *
	 * @return void
	 */
	public function woomp_set_default_callback(): void {
		$token_id = (int) $_POST['token_id'] ?? 0;
		$user_id  = (int) $_POST['user_id'] ?? 0;
		$nonce    = $_POST['nonce'] ?? '';
		if ( ! $token_id || ! $user_id ) {
			\wp_send_json(
				array(
					'code'    => 500,
					'message' => '缺少 token_id 或 user_id',
					'data'    => $_POST,
				)
			);
			\wp_die();
		}

		if ( ! \wp_verify_nonce( $nonce, 'woomp' ) ) {
			\wp_send_json(
				array(
					'code'    => 500,
					'message' => 'nonce 錯誤',
					'data'    => $_POST,
				)
			);
			\wp_die();
		}

		\WC_Payment_Tokens::set_users_default( $user_id, $token_id );

		// Make your array as json
		\wp_send_json(
			array(
				'code'    => 200,
				'message' => '設定為主要卡號成功',
				'data'    => $_POST,
			)
		);

		// Don't forget to stop execution afterward.
		\wp_die();
	}

	/**
	 * 移除支付令牌的回調函數
	 *
	 * @return void
	 */
	public function woomp_remove_callback(): void {
		$token_id = (int) $_POST['token_id'] ?? 0;
		$nonce    = $_POST['nonce'] ?? '';

		if ( ! \wp_verify_nonce( $nonce, 'woomp' ) ) {
			\wp_send_json(
				array(
					'code'    => 500,
					'message' => 'nonce 錯誤',
					'data'    => $_POST,
				)
			);
			\wp_die();
		}

		if ( ! $token_id ) {
			\wp_send_json(
				array(
					'code'    => 500,
					'message' => '缺少 token_id',
					'data'    => $_POST,
				)
			);
			\wp_die();
		}
		\WC_Payment_Tokens::delete( $token_id );
		// Make your array as json
		\wp_send_json(
			array(
				'code'    => 200,
				'message' => '移除成功',
				'data'    => $_POST,
			)
		);

		// Don't forget to stop execution afterward.
		\wp_die();
	}
}

new CardManagement();
