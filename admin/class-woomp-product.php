<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'wc_woomp_setting_product_variations_ui', 1 ) === 'no' ) {
	return;
}

if ( ! class_exists( 'WooMP_Product' ) ) {
	class WooMP_Product {
		/**
		 * 初始化
		 */
		public static function init() {
			add_action( 'admin_head', __CLASS__ . '::set_woomp_ui' );
			add_action( 'admin_head', __CLASS__ . '::add_active_woomp_ui_button' );
			add_action( 'admin_head', __CLASS__ . '::is_active_woomp_ui' );
			add_filter( 'admin_head', __CLASS__ . '::enqueue_proudcts_style', 99 );
			add_filter( 'admin_footer', __CLASS__ . '::enqueue_proudcts_script', 99 );

			// 一頁式商品設定&儲存
			//add_filter( 'product_type_options', __CLASS__ . '::add_onepage_checkout_setting', 99, 1 );
			//add_action( 'woocommerce_admin_process_product_object', __CLASS__ . '::save_onepage_checkout_setting', 99, 1 );

			add_action( 'wp_ajax_woocommerce_save_attributes', __CLASS__ . '::wcb_ajax_woocommerce_save_attributes', 1 );
			add_action( 'save_post', __CLASS__ . '::save_post_attribute_type', 10, 3 );
			add_filter( 'woocommerce_variation_is_active', __CLASS__ . '::variation_check', 10, 2 );
			if ( get_option( 'wc_woomp_setting_product_variations_frontend_ui', 1 ) === 'yes' ) {
				add_action( 'woocommerce_after_product_attribute_settings', __CLASS__ . '::wcb_add_product_attribute_is_highlighted', 10, 2 );
				add_filter( 'woocommerce_dropdown_variation_attribute_options_html', __CLASS__ . '::variation_radio_buttons', 20, 2 );
			}
		}

		/**
		 * 掛載商品頁自訂 CSS
		 */
		public static function enqueue_proudcts_style() {
			global $pagenow, $typenow;
			if ( 'product' === $typenow && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && self::is_active_woomp_ui() === 'yes' ) { ?>
			<style>
				select.attribute_taxonomy,
				.woocommerce_attribute textarea {
				display: none!important;
				}
				.attributeValuesList.tagchecklist {
				margin-top: 5px;
				}
				.attributeValuesList.tagchecklist li {
				font-size: 14px;
				}
				.hint-variable-update {
					margin-left: 0!important;
					padding: 3px 5px;
					color: #ff4748;
					font-size: 12px;
				}
				.hint-variable-price-empty {
					margin-left: 0!important;
					padding: 3px 5px;
					background-color: #ff4748;
					font-size: 12px;
					color: #fff;
					border-radius: 10px;
				}
			</style>
				<?php
			}
		}

		/**
		 * 掛載商品頁自訂 JS
		 */
		public static function enqueue_proudcts_script() {
			global $pagenow, $typenow;
			if ( 'product' === $typenow && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && self::is_active_woomp_ui() === 'yes' ) {
				?>
				<script>
					var $ = jQuery.noConflict();
					$(document).ready(function($){
						/**
						 * Feature - tab label 屬性改為商品屬性
						 */
						$('.attribute_options.attribute_tab a span').html('<?php _e( '商品屬性', 'woomp' ); ?>')

						/**
						 * Feature 
						 * 1.如果是可變商品預設顯示商品屬性 Tab
						 * 2.商品屬性與變化類型 tab 移到最上面
						 */
						function set_variable_tab_to_default(){
							if($('#product-type').val() === 'variable'){
								$('.product_data_tabs li').removeClass('active');
								$('.product_data_tabs li.attribute_options').addClass('active');
								$('.panel-wrap.product_data > div').hide();
								$('#product_attributes').show();
								$('li.attribute_options,li.variations_options').insertBefore($('li.inventory_options'));
							}
						}
						set_variable_tab_to_default()
						
						$('#product-type').on('change',function(){
							set_variable_tab_to_default()
						})

						/**
						 * Feature - 儲存屬性按鈕移至新增按鈕右邊
						 */
						var toolbar = $('#product_attributes .toolbar.toolbar-top .button.add_attribute').after($('#product_attributes .save_attributes'))

						
						var toolbarHeader = $('#woocommerce-product-data .postbox-header h2 > span')
						toolbarHeader.append(`
						<form action="${window.location.href}" method="post" style="display: inline-block">
							<input type="hidden" name="woomp_ui" value="no">
							<button type="submit" class="button"><?php _e( '切換原版變化類型介面', 'woomp' ); ?></button>
						</form>
						`)

						/**
						 * Feature - 增加自製的輸入欄位介面，風格比照文章標籤
						 */
						var inputUI = `
						<div class="hide-if-no-js attributeValuesWrap" style="display:flex; justify-content: space-between;">
							<input type="text" class="ui-autocomplete-input attributeValue" size="16" autocomplete="off" placeholder="<?php _e( '請輸入變化類型規格，一次輸入一個', 'woomp' ); ?>" style="width: 100%; margin-right: 5px;">
							<input type="button" class="button attrListAdd" value="新增" style="width: 80px; min-width: auto;">
						</div>
						<ul class="attributeValuesList tagchecklist" role="list"></ul>`;
						
						function generateAttrListLi( text ){
							return `
							<li>
								<button type="button" class="ntdelbutton attrListRemove">
								<span class="remove-tag-icon" aria-hidden="true"></span>
								<span class="screen-reader-text">移除分類法詞彙: ${text}</span>
								</button>
								<span>&nbsp;${text}</span>
							</li>` 
						}

						// 新增 List
						function addAttrbute(btn){
							var attrText = btn.parent().find('.attributeValue').val();
							var currentAttr = btn.parent().next().next().val();

							if( attrText != '' && currentAttr.indexOf(attrText) < 0 ){
								btn.parent().next().append(generateAttrListLi(attrText))
								btn.parent().next().next().val( currentAttr + ' | '+attrText )
								btn.parent().find('.attributeValue').val('')
							}
						}

						// 移除 List
						function removeAttribute( btnRemove ){
							var currentAttr = btnRemove.parent().parent().next().val();
							var deleteAttr = btnRemove.next().text().trim();
							var updateAttr = currentAttr.replace(deleteAttr,'')
							btnRemove.parent().parent().next().val(updateAttr);
							btnRemove.parent().remove();
						}

						// 讀取既有的規格
						function loadExistingAttr( textarea ){
							var existAttr = textarea.val().split(' | ')
							for (let i = 0; i < existAttr.length; i++) {
								textarea.parent().find('ul.attributeValuesList').append(generateAttrListLi(existAttr[i]))
							}
						}

						// 封裝
						function loadAttributeFeature( wooAttr, isNew = null ){
							wooAttr.find('.woocommerce_attribute_data table tr').each(function(){
								$(this).find('td.attribute_name + td label').after(inputUI)
							})

							wooAttr.find('.attributeValuesWrap').on('click','.attrListAdd',function(){
								addAttrbute($(this))
							})

							wooAttr.find('.attributeValuesWrap').on('keypress','.attributeValue',function(e){
								var code = e.key;
								if(code === "Enter"){
									e.preventDefault()
									addAttrbute($(this))
									setTimeout(() => {
										$('button.save_attributes').removeAttr('disabled')
									}, 100);
								}
							})

							wooAttr.find('.attributeValuesList').on('click', '.attrListRemove', function(){
								removeAttribute($(this))
							})

							if(!isNew){
								wooAttr.find('.attributeValuesList.tagchecklist + textarea').each(function(){
								loadExistingAttr($(this));
								})
							}

						}

						loadAttributeFeature($('.woocommerce_attribute'))

						/**
						 * Feature - 變化類型資料更新
						 */
						function save_variations(){
							$('#field_to_edit option[value="link_all_variations"]').attr('selected','selected');
							// $('#field_to_edit + a.do_variation_action').trigger('click');
							// wc_meta_boxes_product_variations_ajax.block();

							var dataLink = {
								action: 'woocommerce_link_all_variations',
								post_id: woocommerce_admin_meta_boxes_variations.post_id,
								security: woocommerce_admin_meta_boxes_variations.link_variation_nonce
							};

							$.post( woocommerce_admin_meta_boxes_variations.ajax_url, dataLink, function( response ) {
								var count = parseInt( response, 10 );

								window.alert('<?php _e( '變化類型已更新', 'woomp' ); ?>')

								if ( count > 0 ) {
									$( '#variable_product_options' ).trigger( 'woocommerce_variations_added', count );
								}
							});

							var do_variation_action = $( 'select.variation_actions' ).val(),
								data       = {},
								changes    = 0,
								value;

							$.ajax({
								url: woocommerce_admin_meta_boxes_variations.ajax_url,
								data: {
									action:       'woocommerce_bulk_edit_variations',
									security:     woocommerce_admin_meta_boxes_variations.bulk_edit_variations_nonce,
									product_id:   woocommerce_admin_meta_boxes_variations.post_id,
									product_type: $( '#product-type' ).val(),
									bulk_action:  do_variation_action,
									data:         data
								},
								type: 'POST',
								success: function() {
									// wc_meta_boxes_product_variations_pagenav.go_to_page( 1, changes );
								}
							});

							if( $('.hint-variable-update').length == 0 ){
								$('.product_data_tabs li.variations_options a').append('<span class="hint-variable-update">已更新</span>')
							}
						}

						/**
						 * Feature - 移除沒帶值的變化類型
						 */
						function remove_empty_variations(){
							setTimeout(() => {
								$('.woocommerce_variations .woocommerce_variation h3 select').each(function(){
									if($(this).val() == ''){
										var variation     = $(this).parent().find('a.remove_variation').attr( 'rel' ),
											variation_ids = [],
											data          = {
												action: 'woocommerce_remove_variations'
											};

										if ( 0 < variation ) {
											variation_ids.push( variation );

											data.variation_ids = variation_ids;
											data.security      = woocommerce_admin_meta_boxes_variations.delete_variations_nonce;

											$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
												var wrapper      = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
													current_page = parseInt( wrapper.attr( 'data-page' ), 10 ),
													total_pages  = Math.ceil( (
														parseInt( wrapper.attr( 'data-total' ), 10 ) - 1
													) / woocommerce_admin_meta_boxes_variations.variations_per_page ),
													page         = 1;

												$( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_removed' );

												if ( current_page === total_pages || current_page <= total_pages ) {
													page = current_page;
												} else if ( current_page > total_pages && 0 !== total_pages ) {
													page = total_pages;
												}

												// wc_meta_boxes_product_variations_pagenav.go_to_page( page, -1 );
											});
										}
										$(this).parent().parent().remove()
									}
								})
							}, 1500);
						}

						/**
						 * Feature - 變化類型增加價格輸入欄位
						 */
						function add_variations_price_input_field(){
							$('.woocommerce_variation h3').each(function(){
								if($(this).find('.wc_input_price_clone').length == 0){
									$(this).find('select').last().after(`
									<label><?php echo sprintf( __( 'Regular price (%s)', 'woocommerce' ), get_woocommerce_currency_symbol() ); ?></label>
									<input class="wc_input_price_clone" type="text" placeholder="<?php _e( '必填', 'woomp' ); ?>" style="width: 80px;" required >
									<label style="margin-left: 5px;"><?php echo sprintf( __( 'Sale price (%s)', 'woocommerce' ), get_woocommerce_currency_symbol() ); ?></label>
									<input class="wc_input_sale_price_clone" type="text" placeholder="<?php _e( '須小於定價', 'woomp' ); ?>" style="width: 90px;">
									`);
								}
							})
							// 同步定價欄位
							$('.woocommerce_variation h3 .wc_input_price_clone').each(function(){
								$(this).val($(this).parent().parent().find('.variable_pricing > p:first-child input').val())
							})
							$('.wc_input_price_clone').on('keyup', function(){
								$(this).parent().parent().find('.variable_pricing > p:first-child input').val($(this).val())
								$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
							})
							// 同步折扣價欄位
							$('.woocommerce_variation h3 .wc_input_sale_price_clone').each(function(){
								$(this).val($(this).parent().parent().find('.variable_pricing > p:nth-child(2) input').val())
							})
							$('.wc_input_sale_price_clone').on('keyup', function(){
								$(this).parent().parent().find('.variable_pricing > p:nth-child(2) input').val($(this).val())
								$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
							})
						}

						/**
						 * Feature - 檢查是否有未輸入價格的變化類型
						 */
						function add_variations_price_empty_hint(){
							$('.wc_input_price_clone').each(function(){
								$('.hint-variable-price-empty').remove()
								if( $(this).val() == ''){
									$('.product_data_tabs li.variations_options a').append('<span class="hint-variable-price-empty"><?php _e( '定價未填', 'woomp' ); ?></span>')
									return false;
								} else {
									$('.hint-variable-price-empty').remove()
								}
							})
						}
						add_variations_price_empty_hint();

						// Event - 按下變化類型 Tab
						$('.product_data_tabs li.variations_options').on('click',function(){
							$('.hint-variable-update').fadeOut(300,function(){$(this).remove()})
							remove_empty_variations()
							if($('#product-type').val()!=='variable-subscription'){
								add_variations_price_input_field();
							}
							add_variations_price_empty_hint();
							
							// 觸發資料更新
							page     = 1;
							per_page = 10;

							var wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' );
							$.ajax({
								url: woocommerce_admin_meta_boxes_variations.ajax_url,
								data: {
									action:     'woocommerce_load_variations',
									security:   woocommerce_admin_meta_boxes_variations.load_variations_nonce,
									product_id: woocommerce_admin_meta_boxes_variations.post_id,
									attributes: wrapper.data( 'attributes' ),
									page:       page,
									per_page:   per_page
								},
								type: 'POST',
								success: function( response ) {
									wrapper.empty().append( response ).attr( 'data-page', page );
									$( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_loaded' );
								}
							});
						})

						// Event - 按下儲存屬性後
						$('#variable_product_options').on('reload', function(){
							loadAttributeFeature($('.woocommerce_attribute'))
							save_variations()
						})

						// Event - 在變化類型資料載入完成
						$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
							if($('#product-type').val()!=='variable-subscription'){
								add_variations_price_input_field();
							}
							add_variations_price_empty_hint();
							remove_empty_variations()
						});

						// Event - 新增商品屬性
						$( document.body ).on( 'woocommerce_added_attribute', function() {
							loadAttributeFeature($('.product_attributes .woocommerce_attribute:last-child'),true)
							//預設就勾選 「用於變化類型」
							$('.product_attributes .woocommerce_attribute:last-child .enable_variation.show_if_variable input.checkbox').attr('checked', 'checked');
						})

					})
				</script>
				<?php
			}
		}

		/**
		 * 掛載商品頁切換好用版按鈕
		 */
		public static function add_active_woomp_ui_button() {
			global $post, $pagenow, $typenow;
			if ( 'product' === $typenow && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && self::is_active_woomp_ui() === 'no' ) {
				?>
				<script>
					var $ = jQuery.noConflict();
					$(document).ready(function($){
						/**
						 * Feature - 商品資料下拉選單右側增加切回新版的選項
						 */
						var toolbarHeader = $('#woocommerce-product-data .postbox-header h2 > span')
						toolbarHeader.append(`
						<form action="${window.location.href}" method="post" style="display: inline-block">
							<input type="hidden" name="woomp_ui" value="yes">
						<button type="submit" class="button"><?php _e( '切換好用版 Woo 變化類型介面', 'woomp' ); ?></button>
						</form>
						`)
					})
				</script>	
				<?php
			}
		}

		/**
		 * 取得目前 attribute_type 值
		 */
		public static function get_attribute_highlighted( $attribute_name ) {
			global $post;

			$post_id        = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : $post->ID;
			$attribute_name = strtolower( sanitize_title( $attribute_name ) );
			$val            = get_post_meta( $post_id, 'attribute_' . $attribute_name . '_type', true );

			return ! empty( $val ) ? $val : false;
		}

		/**
		 * 加入 attribute_type 選擇介面
		 */
		public static function wcb_add_product_attribute_is_highlighted( $attribute, $i = 0 ) {
			$value = ( self::get_attribute_highlighted( $attribute->get_name() ) ) ? self::get_attribute_highlighted( $attribute->get_name() ) : 'default';
			?>
			<tr>
				<td>
					<div class="enable_highlighted">
						<label><?php _e( '設定前台變化類型介面:', 'woomp' ); ?></label>
						<select name="attribute_type[<?php echo esc_attr( $i ); ?>]">
							<?php if ( wc_string_to_bool( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿default' ) ) ) : ?>
								<option value="tag" <?php echo ( 'tag' === $value || 'default' === $value ) ? 'selected' : ''; ?>><?php _e( '標籤式選項', 'woomp' ); ?></option>
							<?php endif; ?>
							<option value="select" <?php echo ( 'select' === $value ) ? 'selected' : ''; ?>><?php _e( '下拉選單', 'woomp' ); ?></option>
							<option value="radio" <?php echo ( 'radio' === $value ) ? 'selected' : ''; ?>><?php _e( '單選方塊(不斷行)', 'woomp' ); ?></option>
							<option value="radio-one" <?php echo ( 'radio-one' === $value ) ? 'selected' : ''; ?>><?php _e( '單選方塊(每行放1個選項)', 'woomp' ); ?></option>
							<option value="radio-two" <?php echo ( 'radio-two' === $value ) ? 'selected' : ''; ?>><?php _e( '單選方塊(每行放2個選項) ', 'woomp' ); ?></option>
							<?php if ( ! wc_string_to_bool( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿default' ) ) ) : ?>
							<option value="tag" <?php echo ( 'tag' === $value ) ? 'selected' : ''; ?>><?php _e( '標籤式選項', 'woomp' ); ?></option>
							<?php endif; ?>
						</select>
					</div>
					<a href='#' class="button save_attributes_after button-primary" style="margin-top: 1rem">儲存屬性-xxx</a>
				</td>
			</tr>
			<?php
		}

		/**
		 * 修改 attribute_type 值
		 */
		public static function wcb_ajax_woocommerce_save_attributes() {
			check_ajax_referer( 'save-attributes', 'security' );
			parse_str( $_POST['data'], $data );
			$post_id = absint( $_POST['post_id'] );
			if ( array_key_exists( 'attribute_type', $data ) && is_array( $data['attribute_type'] ) ) {
				foreach ( $data['attribute_type'] as $i => $val ) {
					$attr_name = sanitize_title( $data['attribute_names'][ $i ] );
					$attr_name = strtolower( $attr_name );
					update_post_meta( $post_id, 'attribute_' . $attr_name . '_type', $val );
				}
			}
		}

		/**
		 * 點擊更新按鈕修改 attribute_type 值
		 */
		public static function save_post_attribute_type( $post_id, $post, $update ) {
			if ( 'product' === $post->post_type && isset( $_POST['attribute_type'] ) ) {
				if ( is_array( $_POST['attribute_type'] ) ) {
					foreach ( $_POST['attribute_type'] as $i => $val ) {
						$attr_name = sanitize_title( $_POST['attribute_names'][ $i ] );
						$attr_name = strtolower( $attr_name );
						update_post_meta( $post_id, 'attribute_' . $attr_name . '_type', $val );
					}
				}

				if ( $_POST['_onepagecheckout'] ) {
					update_post_meta( $post_id, '_onepagecheckout', $_POST['_onepagecheckout'] );
				}
			}
		}

		/**
		 * 修改前台變化商品介面的 html 結構
		 *
		 * @param $html 原始的結構
		 * @param $args 相關參數
		 * 原始碼在 woocommerce/inluces/wc-template-functions.php Line 2988
		 */
		public static function variation_radio_buttons( $html, $args ) {

			global $post;

			// 判斷為 WPC Product Bundle 外掛的商品則直接回傳原始的 html 結構
			if ( wc_get_product( $post->ID )->get_type() === 'woosg' || wc_get_product( $post->ID )->get_type() === 'woosb' ) {
				return $html;
			}

			$args = wp_parse_args(
				apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ),
				array(
					'options'          => false,
					'attribute'        => false,
					'product'          => false,
					'selected'         => false,
					'name'             => '',
					'id'               => '',
					'class'            => '',
					'show_option_none' => __( 'Choose an option', 'woocommerce' ),
				)
			);

			if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
				$selected_key     = 'attribute_' . sanitize_title( $args['attribute'] );
				$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
			}

			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = (bool) $args['show_option_none'];
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' );

			if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
				$attributes = $product->get_variation_attributes();
				$options    = $attributes[ $attribute ];
			}

			$attribute_type = self::get_attribute_highlighted( $attribute );

			if ( ! empty( $options ) && strpos( $attribute_type, 'radio' ) !== false ) {

				$radios = '<style>.variation-radios + select,.variation-radios + select + *{display:none!important;}.variation-radios > * {cursor: pointer;}.radio-one,.radio-two{display:flex;flex-wrap:wrap;}.radio > div{display:inline-block;}.radio-one>div{width:100%}.radio-two>div{width:50%;}</style><div class="variation-radios ' . $attribute_type . '">';

				if ( $product && taxonomy_exists( $attribute ) ) {

					$terms = wc_get_product_terms(
						$product->get_id(),
						$attribute,
						array(
							'fields' => 'all',
						)
					);

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$id      = $name . '-' . $term->slug;
							$radios .= '<div class="radio-list"><input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( sanitize_title( $args['selected'] ), $term->slug, false ) . '><label style="cursor:pointer;padding-left: 5px; margin-right: 10px;" for="' . esc_attr( $id ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</label></div>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						$id      = $name . '-' . $option;
						$checked = sanitize_title( $args['selected'] ) === $args['selected'] ? checked( $args['selected'], sanitize_title( $option ), false ) : checked( $args['selected'], $option, false );
						$radios .= '<div class="radio-list"><input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option ) . '" id="' . sanitize_title( $option ) . '" ' . $checked . '><label for="' . esc_attr( $id ) . '" style="padding-left: 5px;margin-right: 10px;cursor:pointer;">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</label></div>';
					}
				}
				$radios .= '</div>';
				return $radios . $html;
			} elseif ( ! empty( $options ) && $attribute_type === 'select' ) {
				if ( $product && taxonomy_exists( $attribute ) ) {
					$terms = wc_get_product_terms(
						$product->get_id(),
						$attribute,
						array(
							'fields' => 'all',
						)
					);
					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				}
				return $html;
			} elseif ( ! empty( $options ) && $attribute_type === 'tag' ) {
				$radios = '
				<style>
				.variation-radios + select,.variation-radios + select + * {
					display:none!important;
				}
				.variation-radios + select + a.reset_variations {
					display: block!important;
				}
				.variation-radios > * {
					cursor: pointer;
				}
				.variation-radios.tag {
					display:flex;
					flex-wrap: wrap;
					margin-bottom: 1rem;
				}
				.tag-list label {
					display: inline-block;
					background:#efefef;
					padding: .1rem .8rem;
					cursor: pointer;
					margin: .8rem .8rem 0 0;
					border-radius: 3px;
				}
				.tag-list input:checked + label {
					 color: #fff;
				}
				.tag-list input:disabled + label {
					text-decoration: line-through;
					text-decoration-thickness: 3px;
					opacity: 0.7;
					cursor: default;
				}
				</style>
				<script>
				jQuery(function($){
				   $(document).ready(function(){
					   var mainColor = $(".woocommerce-variation-add-to-cart button").css("background-color");';
				if ( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿bg_color' ) ) {
					$radios .= '$(this).find("input:checked + label").css("background-color", "' . get_option( 'wc_woomp_setting_product_variations_frontend_ui＿bg_color' ) . '");';
				} else {
					 $radios .= '$(this).find("input:checked + label").css("background-color",mainColor);';
				}

				if ( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿text_color' ) ) {
					 $radios .= '$(this).find("input:checked + label").css("color", "' . get_option( 'wc_woomp_setting_product_variations_frontend_ui＿text_color' ) . '");';
				} else {
					 $radios .= '$(this).find("input:checked + label").css("color","#fff");';
				}

					   $radios .= '
				       $(".variation-radios.tag").click(function(){
				           $(this).find("input + label").css("background-color","#efefef");
						   $(this).find("input + label").css("color","#000");';

				if ( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿bg_color' ) ) {
					$radios .= '$(this).find("input:checked + label").css("background-color", "' . get_option( 'wc_woomp_setting_product_variations_frontend_ui＿bg_color' ) . '");';
				} else {
					 $radios .= '$(this).find("input:checked + label").css("background-color",mainColor);';
				}

				if ( get_option( 'wc_woomp_setting_product_variations_frontend_ui＿text_color' ) ) {
					$radios .= '$(this).find("input:checked + label").css("color", "' . get_option( 'wc_woomp_setting_product_variations_frontend_ui＿text_color' ) . '");';
				} else {
					 $radios .= '$(this).find("input:checked + label").css("color","#fff");';
				}
						  $radios .= '
				       })
				   }) 
				})
				</script>
				<div class="variation-radios ' . $attribute_type . '">';

				if ( $product && taxonomy_exists( $attribute ) ) {

					$terms = wc_get_product_terms(
						$product->get_id(),
						$attribute,
						array(
							'fields' => 'all',
						)
					);

					foreach ( $terms as $term ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$id      = $name . '-' . $term->slug;
							$radios .= '<div class="tag-list"><input type="radio" style="display:none" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( sanitize_title( $args['selected'] ), $term->slug, false ) . '><label for="' . esc_attr( $id ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</label></div>';
						}
					}
				} else {
					foreach ( $options as $option ) {
						$id      = $name . '-' . $option;
						$checked = sanitize_title( $args['selected'] ) === $args['selected'] ? checked( $args['selected'], sanitize_title( $option ), false ) : checked( $args['selected'], $option, false );
						$radios .= '<div class="tag-list"><input type="radio" style="display:none" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option ) . '" id="' . sanitize_title( $option ) . '" ' . $checked . '><label for="' . esc_attr( $id ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</label></div>';
					}
				}
				$radios .= '</div>';
				return $radios . $html;
			}

		}
		public static function variation_check( $active, $variation ) {
			if ( ! $variation->is_in_stock() && ! $variation->backorders_allowed() ) {
				return false;
			}
			return $active;
		}

		/**
		 * 檢查該商品是否有啟用好用介面
		 */
		public static function is_active_woomp_ui() {
			global $post, $pagenow, $typenow;
			if ( 'product' === $typenow && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) ) {
				if ( get_post_meta( $post->ID, '_is_active_woomp_ui' ) ) {
					return get_post_meta( $post->ID, '_is_active_woomp_ui', 'yes' );
				} else {
					update_post_meta( $post->ID, '_is_active_woomp_ui', get_option( 'wc_woomp_setting_product_variations_ui' ) );
					return get_option( 'wc_woomp_setting_product_variations_ui' );
				}
			}
		}

		/**
		 * 變更該商品的好用介面啟用狀態
		 */
		public static function set_woomp_ui() {
			global $post, $pagenow, $typenow;
			if ( 'product' === $typenow && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && isset( $_POST['woomp_ui'] ) && ! empty( $_POST['woomp_ui'] ) ) {
				update_post_meta( $post->ID, '_is_active_woomp_ui', $_POST['woomp_ui'] );
			}
		}

		/**
		 * 增加一頁式設定
		 */
		public static function add_onepage_checkout_setting( $options ) {
			$options['onepagecheckout'] = array(
				'id'            => '_onepagecheckout',
				'wrapper_class' => '',
				'label'         => __( '一頁式結帳', 'woocommerce' ),
				'description'   => __( '讓顧客可以在商品頁直接完成結帳', 'woocommerce' ),
				'default'       => 'no',
			);
			return $options;
		}

		/**
		 * 儲存一頁式商品設定
		 */
		public static function save_onepage_checkout_setting( $product ) {
			$product->update_meta_data( '_onepagecheckout', ! empty( $_POST['_onepagecheckout'] ) ? 'yes' : 'no' );
		}
	}
	WooMP_Product::init();
}
