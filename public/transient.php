<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 處理 transient 讀取
 */
function ajax_load_transient() {
	if ( is_checkout() ) {
		wp_register_script( 'my_ajax_load', plugin_dir_url( __DIR__ ) . 'public/js/transient-load.js', array( 'jquery' ), null, true );
		wp_localize_script(
			'my_ajax_load',
			'ajax_params_load',
			array(
				'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php',
				'nonce'   => wp_create_nonce( 'ajax-my-nonce' ),
				'login'   => is_user_logged_in(),
			)
		);
		wp_enqueue_script( 'my_ajax_load' );
	}
}
add_action( 'wp_enqueue_scripts', 'ajax_load_transient' );

function myajax_ajax_handler_2() {
	// TOKEN 驗證
	$nonce   = $_POST['nonce'];
	$user_id = $_POST['user_id'];
	if ( ! wp_verify_nonce( $nonce, 'ajax-my-nonce' ) ) {
		wp_send_json_error(
			array(
				'code' => 500,
				'data' => '',
				'msg'  => '錯誤的請求',
			)
		);
	}
	echo json_encode(
		array(
			'temp' => get_transient( 'woomp_temp_' . $user_id ),
		)
	);
	die;
}

add_action( 'wp_ajax_nopriv_checkout_load', 'myajax_ajax_handler_2', 99 );


/**
 * 處理 transient 寫入
 */
function ajax_scripts() {
	if ( is_checkout() ) {
		wp_register_script( 'my_ajax', plugin_dir_url( __DIR__ ) . 'public/js/transient-save.js', array( 'jquery' ), null, true );
		wp_localize_script(
			'my_ajax',
			'ajax_params',
			array(
				'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php', // WordPress AJAX
				'nonce'   => wp_create_nonce( 'ajax-my-nonce' ), // TOKEN 驗證
				'login'   => is_user_logged_in(),
			)
		);
		wp_enqueue_script( 'my_ajax' );
	}
}
add_action( 'wp_enqueue_scripts', 'ajax_scripts' ); // 掛載要引入的 js 與參數

function myajax_ajax_handler() {
	// TOKEN 驗證
	$nonce = $_POST['nonce'];
	if ( ! wp_verify_nonce( $nonce, 'ajax-my-nonce' ) ) { // 第二個參數要跟一開始 php 裡面的一樣
		wp_send_json_error(
			array(
				'code' => 500,
				'data' => '',
				'msg'  => '錯誤的請求',
			)
		);
	}

	$user_data = $_POST['user_data'];
	$user_id   = $_POST['user_id'];
	set_transient( 'woomp_temp_' . $user_id, $user_data, 12 * HOUR_IN_SECONDS );

	$temp = $billing_first_name;
	echo json_encode(
		array(
			'temp' => get_transient( 'woomp_temp_' . $user_id ),
		)
	);
	die;
}

add_action( 'wp_ajax_nopriv_checkout_autosave', 'myajax_ajax_handler' );

// /**
// * 處理 transient 刪除
// */
// function delete_checkout_transient( $order_get_id ){
// delete_transient('woomp_temp_'.get_current_user_id());
// }
// add_action( 'woocommerce_thankyou', 'delete_checkout_transient', 10, 1 );
