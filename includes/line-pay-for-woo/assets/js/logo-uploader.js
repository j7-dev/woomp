jQuery(document).ready(function($) {
	
	var textEl = $('#woocommerce_linepay_custom_logo');
	
	textEl.addClass('wc-linepay-logo-location');
	textEl.after('<a href="#" class="wc-linepay-reset-btn button">Reset</a><a href="#" class="wc-linepay-upload-btn button">Upload</a>');
	textEl.parent().after('<div class="image-preview no-image"><img src=""></div>');
	
	var imgPreviewEl = textEl.closest('td').find('.image-preview');
	var imgUrl = textEl.val();
	if (imgUrl) {
		imgPreviewEl.removeClass('no-image');
		imgPreviewEl.find('img').prop('src', imgUrl);
	}
	
	// reset
	$('.wc-linepay-reset-btn').click(function(e) {
		e.preventDefault();
		imgPreviewEl.addClass('no-image');
		
		$('.wc-linepay-logo-location').val('');
	});

	// upload
	$('.wc-linepay-upload-btn').click(function(e) {
		e.preventDefault();
		
		var image = wp.media({
			title : 'Upload Image',
			multiple : false
		}).open().on('select', function(e) {
			var uploaded_image = image.state().get('selection').first();
			var image_url = uploaded_image.toJSON().url;

			$('.wc-linepay-logo-location').val(image_url);
		});
	});
});