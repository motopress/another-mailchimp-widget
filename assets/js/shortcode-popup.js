function an_mc_init() {
	tinyMCEPopup.resizeToInnerSize();
}

function an_mc_submit() {

	var shortcode;
	var current_mailing_list = [];

	var box_wrapper = jQuery('#mpmc-box-wrapper');

	// Get enable lists
	var lists = box_wrapper.find('#mpam_list_ids :selected');
	jQuery.each(lists, function(index, element) {
		var list = jQuery(this);
		current_mailing_list[index] = list.val();
	});

	var showplaceholder = jQuery('#showplaceholder').is(':checked');
	var collect_first = jQuery('#collect_first').is(':checked');
	var collect_last = jQuery('#collect_last').is(':checked');

	// Messages fail/success
	var success_message = jQuery('#success_message').val();
	var failure_message = jQuery('#failure_message').val();

	// Get text for label/placeholder
	var signup_text = jQuery('#signup_text').val();
	var email_text = jQuery('#email_text').val();
	var first_name_text = jQuery('#first_name_text').val();
	var last_name_text = jQuery('#last_name_text').val();

	shortcode = ' [mp-mc-form list="' + current_mailing_list + '" button="' + signup_text + '" email_text="' + email_text + '" first_name_text="' + first_name_text + '" last_name_text="' + last_name_text + '" placeholder="' + showplaceholder + '" firstname="' + collect_first + '" lastname="' + collect_last + '" success="' + success_message + '" failure="' + failure_message + '" ]';

	if (window.tinyMCE) {
		var id = 'content';

		if (typeof tinyMCE.activeEditor.editorId != 'undefined') {
			id = tinyMCE.activeEditor.editorId;
		}

		window.tinyMCE.execCommand('mceInsertContent', false, shortcode);
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}

	return;
}
