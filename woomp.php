<?php

/**
 * @wordpress-plugin
 * Plugin Name:       好用版擴充 MorePower Addon for WooCommerce
 * Plugin URI:        https://morepower.club/morepower-addon/
 * Description:       WooCommerce 好用版擴充，改善結帳流程與可變商品等區塊，讓 WooCommerce 更符合亞洲人使用習慣。
 * Version:           3.0.13
 * Author:            MorePower
 * Author URI:        https://morepower.club
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woomp
 * Domain Path:       /languages
 * WC requires at least: 5
 * WC tested up to: 5.6.0
 */
require_once "init.php";
require_once "licenser/class-woomp-base.php";

class woomp_elite
{
	public $plugin_file = __FILE__;
	public $response_obj;
	public $license_message;
	public $show_message = false;
	public $slug = "woomp";
	public $plugin_version = '';
	public $text_domain = '';
	function __construct()
	{
		add_action('admin_print_styles', [$this, 'set_admin_style']);
		$this->set_plugin_data();
		$main_lic_key = "woomp_lic_Key";
		$lic_key_name = woomp_Base::get_lic_key_param($main_lic_key);
		$license_key = get_option($lic_key_name, "");
		if (empty($license_key)) {
			$license_key = get_option($main_lic_key, "");
			if (!empty($license_key)) {
				update_option($lic_key_name, $license_key) || add_option($lic_key_name, $license_key);
			}
		}
		$lice_email = get_option("woomp_lic_email", "");
		woomp_Base::add_on_delete(function () {
			update_option("woomp_lic_Key", "");
		});
		if (woomp_Base::check_wp_plugin($license_key, $lice_email, $this->license_message, $this->response_obj, __FILE__)) {
			add_action('admin_menu', [$this, 'active_admin_menu'], 60);
			add_action('admin_post_woomp_el_deactivate_license', [$this, 'action_deactivate_license']);
			//$this->licenselMessage=$this->mess;
			//***Write you plugin's code here***

		} else {
			if (!empty($license_key) && !empty($this->license_message)) {
				$this->show_message = true;
			}
			update_option($license_key, "") || add_option($license_key, "");
			add_action('admin_post_woomp_el_activate_license', [$this, 'action_activate_license']);
			add_action('admin_menu', [$this, 'inactive_menu'], 60);
		}
	}
	public function set_plugin_data()
	{
		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if (function_exists('get_plugin_data')) {
			$data = get_plugin_data($this->plugin_file);
			if (isset($data['Version'])) {
				$this->plugin_version = $data['Version'];
			}
			if (isset($data['TextDomain'])) {
				$this->text_domain = $data['TextDomain'];
			}
		}
	}
	private static function &get_server_array()
	{
		return $_SERVER;
	}
	private static function get_raw_domain()
	{
		if (function_exists("site_url")) {
			return site_url();
		}
		if (defined("WPINC") && function_exists("get_bloginfo")) {
			return get_bloginfo('url');
		} else {
			$server = self::get_server_array();
			if (!empty($server['HTTP_HOST']) && !empty($server['SCRIPT_NAME'])) {
				$base_url  = ((isset($server['HTTPS']) && $server['HTTPS'] == 'on') ? 'https' : 'http');
				$base_url .= '://' . $server['HTTP_HOST'];
				$base_url .= str_replace(basename($server['SCRIPT_NAME']), '', $server['SCRIPT_NAME']);

				return $base_url;
			}
		}
		return '';
	}
	private static function get_raw_wp()
	{
		$domain = self::get_raw_domain();
		return preg_replace("(^https?://)", "", $domain);
	}
	public static function get_lic_key_param($key)
	{
		$raw_url = self::get_raw_wp();
		return $key . "_s" . hash('crc32b', $raw_url . "vtpbdapps");
	}
	public function set_admin_style()
	{
		wp_register_style("woompLic", plugins_url("_lic_style.css", $this->plugin_file), 10, time());
		wp_enqueue_style("woompLic");
	}
	public function active_admin_menu()
	{


		add_submenu_page('woocommerce', 'woomp-main', '- 好用版授權管理', 'activate_plugins', $this->slug, [$this, "activated"], 150);

		//add_submenu_page(  $this->slug, "woomp License", "License Info", "activate_plugins",  $this->slug."_license", [$this,"activated"] );

	}
	public function inactive_menu()
	{
		add_submenu_page('woocommerce', 'woomp-main', '- 好用版授權管理', 'activate_plugins', $this->slug, [$this, "license_form"], 150);
	}
	function action_activate_license()
	{
		check_admin_referer('el-license');
		$license_key = !empty($_POST['el_license_key']) ? sanitize_text_field(wp_unslash($_POST['el_license_key'])) : "";
		$license_email = !empty($_POST['el_license_email']) ? sanitize_email(wp_unslash($_POST['el_license_email'])) : "";
		update_option("woomp_lic_Key", $license_key) || add_option("woomp_lic_Key", $license_key);
		update_option("woomp_lic_email", $license_email) || add_option("woomp_lic_email", $license_email);
		update_option('_site_transient_update_plugins', '');
		wp_safe_redirect(admin_url('admin.php?page=' . $this->slug));
	}
	function action_deactivate_license()
	{
		check_admin_referer('el-license');
		$message = "";
		$main_lic_key = "woomp_lic_Key";
		$lic_key_name = woomp_Base::get_lic_key_param($main_lic_key);
		if (woomp_Base::remove_license_key(__FILE__, $message)) {
			update_option($lic_key_name, "") || add_option($lic_key_name, "");
			update_option('_site_transient_update_plugins', '');
		}
		wp_safe_redirect(admin_url('admin.php?page=' . $this->slug));
	}
	function activated()
	{
?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="woomp_el_deactivate_license" />
			<div class="el-license-container">
				<h3 class="el-license-title"><i class="dashicons-before dashicons-star-filled"></i> <?php esc_html_e("woomp License Info", "woomp"); ?> </h3>
				<hr>
				<ul class="el-license-info">
					<li>
						<div>
							<span class="el-license-info-title"><?php esc_html_e("Status", "woomp"); ?></span>

							<?php if ($this->response_obj->is_valid) : ?>
								<span class="el-license-valid"><?php esc_html_e("Valid", "woomp"); ?></span>
							<?php else : ?>
								<span class="el-license-valid"><?php esc_html_e("Invalid", "woomp"); ?></span>
							<?php endif; ?>
						</div>
					</li>

					<li>
						<div>
							<span class="el-license-info-title"><?php esc_html_e("License Type", "woomp"); ?></span>
							<?php echo esc_html($this->response_obj->license_title, "woomp"); ?>
						</div>
					</li>

					<li>
						<div>
							<span class="el-license-info-title"><?php esc_html_e("License Expired on", "woomp"); ?></span>
							<?php echo esc_html($this->response_obj->expire_date, "woomp");
							if (!empty($this->response_obj->expire_renew_link)) {
							?>
								<a target="_blank" class="el-blue-btn" href="<?php echo esc_url($this->response_obj->expire_renew_link); ?>">Renew</a>
							<?php
							}
							?>
						</div>
					</li>

					<li>
						<div>
							<span class="el-license-info-title"><?php esc_html_e("Support Expired on", "woomp"); ?></span>
							<?php
							echo esc_html($this->response_obj->support_end, "woomp");;
							if (!empty($this->response_obj->support_renew_link)) {
							?>
								<a target="_blank" class="el-blue-btn" href="<?php echo esc_url($this->response_obj->support_renew_link); ?>">Renew</a>
							<?php
							}
							?>
						</div>
					</li>
					<li>
						<div>
							<span class="el-license-info-title"><?php esc_html_e("Your License Key", "woomp"); ?></span>
							<span class="el-license-key"><?php echo esc_attr(substr($this->response_obj->license_key, 0, 9) . "XXXXXXXX-XXXXXXXX" . substr($this->response_obj->license_key, -9)); ?></span>
						</div>
					</li>
				</ul>
				<div class="el-license-active-btn">
					<?php wp_nonce_field('el-license'); ?>
					<?php submit_button('Deactivate'); ?>
				</div>
			</div>
		</form>
	<?php
	}

	function license_form()
	{
	?>
		<style>
			#wpbody-content {
				position: relative;
				background-color: #f00;
				height: 1127px;
				width: 100%;
				background-color: #000;
				background-image: radial-gradient(circle at top right,
						rgba(121, 68, 154, 0.13),
						transparent),
					radial-gradient(circle at 20% 80%, rgba(41, 196, 255, 0.13), transparent);
			}

			canvas {
				position: absolute;
				top: 0px;
				left: 0px;
				width: 100%;
				height: 100%;
				z-index: 10;
			}
		</style>
		<script src="https://cdn.tailwindcss.com"></script>
		<script>
			tailwind.config = {
				theme: {
					extend: {
						colors: {
							primary: '#f6d456',
							'primary-400': '#ffe47f',
						}
					}
				}
			}
		</script>


		<form class="w-fit mx-auto" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<div class="mt-24 flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 z-50 relative">
				<div class="sm:mx-auto sm:w-full sm:max-w-sm">
					<input type="hidden" name="action" value="woomp_el_activate_license" />

					<img class="h-16 mx-auto w-auto" src="https://morepower.club/wp-content/uploads/2020/10/powerlogo-y.png">
					<h2 class="text-white mt-10 mb-4 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900"><?php esc_html_e("Woocommerce 好用版授權", "woomp"); ?></h2>
					<?php
					if (!empty($this->show_message) && !empty($this->license_message)) {
					?>
						<div class="notice notice-error is-dismissible">
							<p><?php echo esc_html($this->license_message, "woomp"); ?></p>
						</div>
					<?php
					}
					?>
					<p class='text-gray-200'>請輸入授權碼以開通進階功能，購買授權請到<a target="_blank" href="<?= $_ENV['BUY_LICENSE_LINK']; ?>">站長路可網站</a>購買
						有任何客服問題，請私訊站長路可網站右下方對話框，或是來信 <a href="mailto:<?= $_ENV['SUPPORT_EMAIL']; ?>" target="_blank"><?= $_ENV['SUPPORT_EMAIL']; ?></a></p>
				</div>

				<div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
					<form class="space-y-6" action="#" method="POST">
						<div class="mb-4">
							<label for="el_license_key" class="block text-sm font-medium leading-6 text-gray-200"><?php echo esc_html("License code", "woomp"); ?></label>
							<div class="mt-2">
								<input id="el_license_key" type="text" class="h-[36px] block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
							</div>
						</div>

						<div class="mb-4">
							<label for="el_license_email" class="block text-sm font-medium leading-6 text-gray-200"><?php echo esc_html("Email Address", "woomp"); ?></label>
							<div class="mt-2">
								<?php
								$purchase_email   = get_option("woomp_lic_email", get_bloginfo('admin_email'));
								?>
								<input id="el_license_email" type="email" class="h-[36px] block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6" name="el_license_email" size="50" value="<?php echo esc_html($purchase_email); ?>" placeholder="" required="required">
							</div>
						</div>

						<div>
							<button type="submit" class="flex w-full justify-center rounded-md bg-primary px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">啟用授權</button>
						</div>
					</form>

					<p class="mt-10 text-center text-sm text-gray-400">
						Not a member?
						<a href="#" class="font-semibold leading-6 text--600 hover:text-primary-400">Start a 14 day free trial</a>
					</p>
					<?php wp_nonce_field('el-license'); ?>

				</div>
			</div>
		</form>
		<canvas />

		<script>
			const STAR_COUNT = (window.innerWidth + window.innerHeight) / 8,
				STAR_SIZE = 3,
				STAR_MIN_SCALE = 0.2,
				OVERFLOW_THRESHOLD = 50;

			const canvas = document.querySelector("canvas"),
				context = canvas.getContext("2d");

			let scale = 1, // device pixel ratio
				width,
				height;

			let stars = [];

			let pointerX, pointerY;

			let velocity = {
				x: 0,
				y: 0,
				tx: 0,
				ty: 0,
				z: 0.0005
			};

			let touchInput = false;

			generate();
			resize();
			step();

			window.onresize = resize;
			canvas.onmousemove = onMouseMove;
			canvas.ontouchmove = onTouchMove;
			canvas.ontouchend = onMouseLeave;
			document.onmouseleave = onMouseLeave;

			function generate() {
				for (let i = 0; i < STAR_COUNT; i++) {
					stars.push({
						x: 0,
						y: 0,
						z: STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE)
					});

				}
			}

			function placeStar(star) {
				star.x = Math.random() * width;
				star.y = Math.random() * height;
			}

			function recycleStar(star) {
				let direction = "z";

				let vx = Math.abs(velocity.tx),
					vy = Math.abs(velocity.ty);

				if (vx > 1 && vy > 1) {
					let axis;

					if (vx > vy) {
						axis = Math.random() < Math.abs(velocity.x) / (vx + vy) ? "h" : "v";
					} else {
						axis = Math.random() < Math.abs(velocity.y) / (vx + vy) ? "v" : "h";
					}

					if (axis === "h") {
						direction = velocity.x > 0 ? "l" : "r";
					} else {
						direction = velocity.y > 0 ? "t" : "b";
					}
				}

				star.z = STAR_MIN_SCALE + Math.random() * (1 - STAR_MIN_SCALE);

				if (direction === "z") {
					star.z = 0.1;
					star.x = Math.random() * width;
					star.y = Math.random() * height;
				} else if (direction === "l") {
					star.x = -STAR_SIZE;
					star.y = height * Math.random();
				} else if (direction === "r") {
					star.x = width + STAR_SIZE;
					star.y = height * Math.random();
				} else if (direction === "t") {
					star.x = width * Math.random();
					star.y = -STAR_SIZE;
				} else if (direction === "b") {
					star.x = width * Math.random();
					star.y = height + STAR_SIZE;
				}
			}

			function resize() {
				scale = window.devicePixelRatio || 1;

				width = window.innerWidth * scale;
				height = window.innerHeight * scale;

				canvas.width = width;
				canvas.height = height;

				stars.forEach(placeStar);
			}

			function step() {
				context.clearRect(0, 0, width, height);

				update();
				render();

				requestAnimationFrame(step);
			}

			function update() {
				velocity.tx *= 0.95;
				velocity.ty *= 0.95;

				velocity.x += (velocity.tx - velocity.x) * 0.7;
				velocity.y += (velocity.ty - velocity.y) * 0.7;

				stars.forEach(star => {
					star.x += velocity.x * star.z;
					star.y += velocity.y * star.z;

					star.x += (star.x - width / 2) * velocity.z * star.z;
					star.y += (star.y - height / 2) * velocity.z * star.z;
					star.z += velocity.z;

					// recycle when out of bounds
					if (
						star.x < -OVERFLOW_THRESHOLD ||
						star.x > width + OVERFLOW_THRESHOLD ||
						star.y < -OVERFLOW_THRESHOLD ||
						star.y > height + OVERFLOW_THRESHOLD) {
						recycleStar(star);
					}
				});
			}

			function render() {
				stars.forEach(star => {
					context.beginPath();
					context.lineCap = "round";
					context.lineWidth = STAR_SIZE * star.z * scale;
					context.strokeStyle =
						"rgba(255,255,255," + (0.5 + 0.5 * Math.random()) + ")";

					context.beginPath();
					context.moveTo(star.x, star.y);

					var tailX = velocity.x * 2,
						tailY = velocity.y * 2;

					// stroke() wont work on an invisible line
					if (Math.abs(tailX) < 0.1) tailX = 0.5;
					if (Math.abs(tailY) < 0.1) tailY = 0.5;

					context.lineTo(star.x + tailX, star.y + tailY);

					context.stroke();
				});
			}

			function movePointer(x, y) {
				if (typeof pointerX === "number" && typeof pointerY === "number") {
					let ox = x - pointerX,
						oy = y - pointerY;

					velocity.tx = velocity.x + ox / 8 * scale * (touchInput ? -1 : 1);
					velocity.ty = velocity.y + oy / 8 * scale * (touchInput ? -1 : 1);
				}

				pointerX = x;
				pointerY = y;
			}

			function onMouseMove(event) {
				touchInput = false;

				movePointer(event.clientX, event.clientY);
			}

			function onTouchMove(event) {
				touchInput = true;

				movePointer(event.touches[0].clientX, event.touches[0].clientY, true);

				event.preventDefault();
			}

			function onMouseLeave() {
				pointerX = null;
				pointerY = null;
			}
		</script>

<?php
	}
}

new woomp_elite();
