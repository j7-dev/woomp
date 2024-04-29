<?php

/**
 * @wordpress-plugin
 * Plugin Name:       好用版擴充 MorePower Addon for WooCommerce
 * Plugin URI:        https://morepower.club/morepower-addon/
 * Description:       WooCommerce 好用版擴充，改善結帳流程與可變商品等區塊，並整合多項金流，讓 WooCommerce 更符合亞洲人使用習慣。
 * Version:           3.3.5
 * Author:            MorePower
 * Author URI:        https://morepower.club
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woomp
 * Domain Path:       /languages
 * WC requires at least: 5
 * WC tested up to: 6.4.1
 */
require_once 'init.php';
require_once 'licenser/class-woomp-base.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class woomp_elite {

	public $plugin_file = __FILE__;
	public $response_obj;
	public $license_message;
	public $show_message   = false;
	public $slug           = 'woomp';
	public $plugin_version = '';
	public $text_domain    = '';

	public static $github_repo      = 'https://github.com/j7-dev/woomp';
	public static $app_slug         = 'woomp';
	public static $buy_license_link = 'https://cloud.luke.cafe/product/woomp';
	public static $support_email    = 'cloud@luke.cafe';

	public function __construct() {
		/**
		 * wp plugin 更新檢查 update checker
		 */

		$updateChecker = PucFactory::buildUpdateChecker(
			self::$github_repo,
			__FILE__,
			self::$app_slug
		);

		$updateChecker->getVcsApi()->enableReleaseAssets();

		// ---

		add_action( 'admin_print_styles', array( $this, 'set_admin_style' ) );
		$this->set_plugin_data();
		$main_lic_key = 'woomp_lic_Key';
		$lic_key_name = woomp_Base::get_lic_key_param( $main_lic_key );
		$license_key  = get_option( $lic_key_name, '' );
		if ( empty( $license_key ) ) {
			$license_key = get_option( $main_lic_key, '' );
			if ( ! empty( $license_key ) ) {
				update_option( $lic_key_name, $license_key ) || add_option( $lic_key_name, $license_key );
			}
		}
		$lice_email = get_option( 'woomp_lic_email', '' );
		woomp_Base::add_on_delete(
			function () {
				update_option( 'woomp_lic_Key', '' );
			}
		);
		if ( woomp_Base::check_wp_plugin( $license_key, $lice_email, $this->license_message, $this->response_obj, __FILE__ ) ) {
			// add_action('admin_menu', [$this, 'active_admin_menu'], 60);
			add_action( 'admin_post_woomp_el_deactivate_license', array( $this, 'action_deactivate_license' ) );
			// $this->licenselMessage=$this->mess;
			// ***Write you plugin's code here***

		} else {
			if ( ! empty( $license_key ) && ! empty( $this->license_message ) ) {
				$this->show_message = true;
			}
			update_option( $license_key, '' ) || add_option( $license_key, '' );
			add_action( 'admin_post_woomp_el_activate_license', array( $this, 'action_activate_license' ) );
			// add_action('admin_menu', [$this, 'inactive_menu'], 60);
		}
	}
	public function set_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( function_exists( 'get_plugin_data' ) ) {
			$data = get_plugin_data( $this->plugin_file );
			if ( isset( $data['Version'] ) ) {
				$this->plugin_version = $data['Version'];
			}
			if ( isset( $data['TextDomain'] ) ) {
				$this->text_domain = $data['TextDomain'];
			}
		}
	}
	private static function &get_server_array() {
		return $_SERVER;
	}
	private static function get_raw_domain() {
		if ( function_exists( 'site_url' ) ) {
			return site_url();
		}
		if ( defined( 'WPINC' ) && function_exists( 'get_bloginfo' ) ) {
			return get_bloginfo( 'url' );
		} else {
			$server = self::get_server_array();
			if ( ! empty( $server['HTTP_HOST'] ) && ! empty( $server['SCRIPT_NAME'] ) ) {
				$base_url  = ( ( isset( $server['HTTPS'] ) && $server['HTTPS'] == 'on' ) ? 'https' : 'http' );
				$base_url .= '://' . $server['HTTP_HOST'];
				$base_url .= str_replace( basename( $server['SCRIPT_NAME'] ), '', $server['SCRIPT_NAME'] );

				return $base_url;
			}
		}
		return '';
	}
	private static function get_raw_wp() {
		$domain = self::get_raw_domain();
		return preg_replace( '(^https?://)', '', $domain );
	}
	public static function get_lic_key_param( $key ) {
		$raw_url = self::get_raw_wp();
		return $key . '_s' . hash( 'crc32b', $raw_url . 'vtpbdapps' );
	}
	public function set_admin_style() {
		wp_register_style( 'woompLic', plugins_url( 'licenser/_lic_style.css', __FILE__ ), 10, time() );
		wp_enqueue_style( 'woompLic' );
	}
	public function active_admin_menu() {

		add_submenu_page( 'woocommerce', 'woomp-main', '- 好用版授權管理', 'activate_plugins', $this->slug, array( $this, 'activated' ), 150 );

		// add_submenu_page(  $this->slug, "woomp License", "License Info", "activate_plugins",  $this->slug."_license", [$this,"activated"] );
	}
	public function inactive_menu() {
		add_submenu_page( 'woocommerce', 'woomp-main', '- 好用版授權管理', 'activate_plugins', $this->slug, array( $this, 'license_form' ), 150 );
	}
	public function action_activate_license() {
		check_admin_referer( 'el-license' );
		$license_key   = ! empty( $_POST['el_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['el_license_key'] ) ) : '';
		$license_email = ! empty( $_POST['el_license_email'] ) ? sanitize_email( wp_unslash( $_POST['el_license_email'] ) ) : '';
		update_option( 'woomp_lic_Key', $license_key ) || add_option( 'woomp_lic_Key', $license_key );
		update_option( 'woomp_lic_email', $license_email ) || add_option( 'woomp_lic_email', $license_email );
		update_option( '_site_transient_update_plugins', '' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
	}
	public function action_deactivate_license() {
		check_admin_referer( 'el-license' );
		$message      = '';
		$main_lic_key = 'woomp_lic_Key';
		$lic_key_name = woomp_Base::get_lic_key_param( $main_lic_key );
		if ( woomp_Base::remove_license_key( __FILE__, $message ) ) {
			update_option( $lic_key_name, '' ) || add_option( $lic_key_name, '' );
			update_option( '_site_transient_update_plugins', '' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
	}
	public function activated() {

		?>
<form class="w-fit mx-auto" method="post"
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<div class="mt-24 flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 z-50 relative">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm">
			<input type="hidden" name="action" value="woomp_el_deactivate_license" />


			<!-- <img class="h-16 mx-auto w-auto" src="https://morepower.club/wp-content/uploads/2020/10/powerlogo-y.png"> -->
			<h2 class="text-gray-700 text-center text-4xl font-black leading-9 tracking-tight">站長路可</h2>
			<h2 class="text-gray-700 mt-10 mb-4 text-center text-2xl font-bold leading-9 tracking-tight">
				<?php esc_html_e( 'WooCommerce 好用版授權', 'woomp' ); ?>
			</h2>
			<?php
			if ( ! empty( $this->show_message ) && ! empty( $this->license_message ) ) {
				?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php echo esc_html( $this->license_message, 'woomp' ); ?>
				</p>
			</div>
				<?php
			}
			?>
			<p class='text-gray-500'>好用版擴充是款免費外掛，但請先到 <a
					class="font-semibold leading-6 text-primary hover:text-primary-400" target="_blank"
					href="<?php echo self::$buy_license_link; ?>">站長路可網站</a>
				註冊帳號，即可索取授權碼。有任何客服問題，請私訊站長路可網站右下方對話框，或是來信 <a
					class="font-semibold leading-6 text-primary hover:text-primary-400"
					href="mailto:<?php echo self::$support_email; ?>" target="_blank">
					<?php echo self::$support_email; ?>
				</a></p>
		</div>

		<div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
			<table class="table table-fixed table-small th-left">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e( '狀態', 'woomp' ); ?>
						</th>
						<td>
							<?php if ( $this->response_obj->is_valid ) : ?>
							<span class="text-white bg-teal-400 rounded-md px-2 py-1">
								<?php esc_html_e( '啟用', 'woomp' ); ?>
							</span>
							<?php else : ?>
							<span class="text-white bg-crimson-400 rounded-md px-2 py-1">
								<?php esc_html_e( '尚未啟用', 'woomp' ); ?>
							</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( '授權種類', 'woomp' ); ?>
						</th>
						<td>
							<?php echo esc_html( $this->response_obj->license_title, 'woomp' ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( '到期日', 'woomp' ); ?>
						</th>
						<td>
							<?php
							echo esc_html( $this->response_obj->expire_date, 'woomp' );
							if ( ! empty( $this->response_obj->expire_renew_link ) ) {
								?>
							<a target="_blank" class="el-blue-btn"
								href="<?php echo esc_url( $this->response_obj->expire_renew_link ); ?>">購買授權</a>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( '支援更新時間', 'woomp' ); ?>
						</th>
						<td>
							<?php
							echo esc_html( $this->response_obj->support_end, 'woomp' );

							if ( ! empty( $this->response_obj->support_renew_link ) ) {
								?>
							<a target="_blank" class="el-blue-btn"
								href="<?php echo esc_url( $this->response_obj->support_renew_link ); ?>">購買授權</a>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( '授權碼', 'woomp' ); ?>
						</th>
						<td>
							<?php echo esc_attr( substr( $this->response_obj->license_key, 0, 9 ) . 'XXXXXXXX-XXXXXXXX' . substr( $this->response_obj->license_key, -9 ) ); ?>
						</td>
					</tr>
				</tbody>
			</table>


			<div class="mt-8">
				<button type="submit"
					class="flex w-full justify-center rounded-md bg-primary px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">棄用授權</button>
			</div>

			<p class="mt-10 text-center text-sm text-gray-400">
				網站速度不夠快？
				<a target="_blank" href="https://cloud.luke.cafe/"
					class="font-semibold leading-6 text-primary hover:text-primary-400">我們的主機代管服務</a>
				提供30天免費試用
			</p>
			<?php wp_nonce_field( 'el-license' ); ?>

		</div>
	</div>
</form>

		<?php
		$this->get_background_html();
	}

	public function license_form() {
		?>
<form class="w-fit mx-auto" method="post"
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<div class="mt-24 flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 z-50 relative">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm">
			<input type="hidden" name="action" value="woomp_el_activate_license" />

			<!-- <img class="h-16 mx-auto w-auto" src="https://morepower.club/wp-content/uploads/2020/10/powerlogo-y.png"> -->
			<h2 class="text-gray-700 text-center text-4xl font-black leading-9 tracking-tight">站長路可</h2>
			<h2 class="text-gray-700 mt-10 mb-4 text-center text-2xl font-bold leading-9 tracking-tight">
				<?php esc_html_e( 'WooCommerce 好用版授權', 'woomp' ); ?>
			</h2>
			<?php
			if ( ! empty( $this->show_message ) && ! empty( $this->license_message ) ) {
				?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php echo esc_html( $this->license_message, 'woomp' ); ?>
				</p>
			</div>
				<?php
			}
			?>
			<p class='text-gray-500'>好用版擴充是款免費外掛，但請先到 <a
					class="font-semibold leading-6 text-primary hover:text-primary-400" target="_blank"
					href="<?php echo self::$buy_license_link; ?>">站長路可網站</a>
				註冊帳號，即可索取授權碼。有任何客服問題，請私訊站長路可網站右下方對話框，或是來信 <a
					class="font-semibold leading-6 text-primary hover:text-primary-400"
					href="mailto:<?php echo self::$support_email; ?>" target="_blank">
					<?php echo self::$support_email; ?>
				</a></p>
			<input id="el_license_key" type="text"
				class="h-[36px] block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
				name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx"
				required="required">
		</div>
	</div>

	<div class="mb-4">
		<label for="el_license_email" class="block text-sm font-medium leading-6 text-gray-500">
			<?php echo esc_html( 'Email', 'woomp' ); ?>
		</label>
		<div class="mt-2">
			<?php
			$purchase_email = get_option( 'woomp_lic_email', get_bloginfo( 'admin_email' ) );
			?>
			<input id="el_license_email" type="email"
				class="h-[36px] block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
				name="el_license_email" size="50" value="<?php echo esc_html( $purchase_email ); ?>"
				placeholder="" required="required">
		</div>
	</div>

	<div class="mt-8">
		<button type="submit"
			class="flex w-full justify-center rounded-md bg-primary px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">啟用授權</button>
	</div>
</form>

<p class="mt-10 text-center text-sm text-gray-400">
	網站速度不夠快？
	<a target="_blank" href="https://cloud.luke.cafe/"
		class="font-semibold leading-6 text-primary hover:text-primary-400">我們的主機代管服務</a> 提供30天免費試用
</p>
		<?php wp_nonce_field( 'el-license' ); ?>

</div>
</div>
</form>
		<?php
		$this->get_background_html();
	}

	public function get_background_html() {
		?>
<style>
table.table {
	color: #334155;
	width: 100%;
	border-collapse: collapse;
	table-layout: auto;
	/* 让列宽自动分配 */
}

table.table.table-fixed {
	table-layout: fixed;
	/* 让列宽平均分配 */
}

table.table tr {
	background-color: transparent;
	transition: 0.3s ease-in-out;
}

table.table tr:hover {
	color: #4096ff;
}

table.table td,
table.table th {
	width: auto;
	border: 0px solid #ddd;
	padding: 0.75rem 0.5rem;
	line-height: 1;
}

table.table th {
	width: 90px;
}

table.table.table-small td,
table.table.table-small th {
	padding: 0.5rem 0rem;
	font-size: 0.75rem;
}

table.table.table-nowrap td,
table.table.table-nowrap th {
	white-space: nowrap;
}

table.table td {
	text-align: right;
}

table.table.th-left th {
	text-align: left;
}

table.table th {
	text-align: center;
	font-weight: 700;
}

table.table.table-vertical {
	table-layout: fixed;
}

table.table.table-vertical tr {
	display: flex;
	border-bottom: 1px solid #ddd;
}

table.table.table-vertical th {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	background-color: #f8f8f8;
	border: none;
	width: 15rem;
}

table.table.table-vertical th * {
	text-align: left;
}

table.table.table-vertical td {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	flex: 1;
	border: none;
}
</style>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
	theme: {
		extend: {
			colors: {
				primary: '#1677ff',
				'primary-400': '#4096ff',
			}
		}
	}
}
</script>

		<?php
	}
}

new woomp_elite();
