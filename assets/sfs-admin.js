jQuery(document).ready(function($) {
	// Language Switcher for Guild Page
	const langButtons = $('.sfs-lang-btn');
	const langPanes = $('.sfs-guild-pane');

	if (langButtons.length) {
		langButtons.on('click', function() {
			const lang = $(this).data('lang');
			
			langButtons.removeClass('active');
			$(this).addClass('active');

			langPanes.removeClass('active');
			$('#pane-' + lang).addClass('active');
		});
	}

	// Media Uploader for CPT
	$('#sfs_upload_btn').on('click', function(e) {
		e.preventDefault();
		if (typeof wp.media === 'undefined') return;

		var image = wp.media({ 
			title: 'Upload File',
			multiple: false
		}).open()
		.on('select', function(e){
			var uploaded_image = image.state().get('selection').first();
			var file_url = uploaded_image.toJSON().url;
			$('#sfs_file_url').val(file_url);
		});
	});
});
