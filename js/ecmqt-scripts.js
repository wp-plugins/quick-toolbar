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