jQuery(document).ready(function($) {
	// Language Switcher for Guild Page
	const langButtons = $('.sgoplus-fs-lang-btn');
	const langPanes = $('.sgoplus-fs-guild-pane');

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
	$('#sgoplus_fs_upload_btn').on('click', function(e) {
		e.preventDefault();
		if (typeof wp.media === 'undefined') return;

		var frame = wp.media({ 
			title: 'Select or Upload File',
			button: { text: 'Use this file' },
			multiple: false
		});

		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			
			$('#sgoplus_fs_file_url').val(attachment.url);
			$('#sgoplus_fs_attachment_id').val(attachment.id);
			
			$('#sgoplus-fs-filename').text(attachment.filename);
			$('#sgoplus-fs-selected-file-info').show();
			$('#sgoplus_fs_upload_btn').text('Change File');
		});

		frame.open();
	});

	// Clear Selection
	$('#sgoplus_fs_clear_btn').on('click', function(e) {
		e.preventDefault();
		$('#sgoplus_fs_file_url').val('');
		$('#sgoplus_fs_attachment_id').val('');
		$('#sgoplus-fs-selected-file-info').hide();
		$('#sgoplus_fs_upload_btn').text('Select or Upload File');
	});
});
