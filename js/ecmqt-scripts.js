function ecmqtDelete(id, title) {

	if(confirm("Are you sure you want to delete '" + title + "'? If the item has children, they will also be deleted."))
	{
		var data = {
			'action': 'ecmqt_delete_custom_link',
			'ecmqt_id': id
		};

		jQuery.post(ajaxurl, data, function(response) {
			location.reload();
		});
	}
	else
	{
		e.preventDefault();
	}
}


jQuery(document).ready(function($){

	var custom_uploader;

	$('#_ecmqt_upload_image_button').click(function(e) {
		e.preventDefault();

		//If the uploader object has already been created, reopen the dialog
		if (custom_uploader) {
			custom_uploader.open();
			return;
		}

		//Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});

		//When a file is selected, grab the URL and set it as the text field's value
		custom_uploader.on('select', function() {
			attachment = custom_uploader.state().get('selection').first().toJSON();
			$('#_ecmqt_upload_image').val(attachment.url);
			$('#_ecmqt_upload_image_label').val(attachment.url);
		});

		//Open the uploader dialog
		custom_uploader.open();
	});

});