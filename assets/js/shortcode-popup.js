function an_mc_init() {
	tinyMCEPopup.resizeToInnerSize();
}

function an_mc_shortcode_attribute(value) {
	return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
		.replace(/</g, '&lt;').replace(/>/g, '&gt;')
		.replace(/\[/g, '&#91;').replace(/\]/g, '&#93;').replace(/[\r\n]+/g, ' ');
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

	shortcode = ' [mp-mc-form list="' + an_mc_shortcode_attribute(current_mailing_list.join(',')) + '" button="' + an_mc_shortcode_attribute(signup_text) + '" email_text="' + an_mc_shortcode_attribute(email_text) + '" first_name_text="' + an_mc_shortcode_attribute(first_name_text) + '" last_name_text="' + an_mc_shortcode_attribute(last_name_text) + '" placeholder="' + showplaceholder + '" firstname="' + collect_first + '" lastname="' + collect_last + '" success="' + an_mc_shortcode_attribute(success_message) + '" failure="' + an_mc_shortcode_attribute(failure_message) + '" ]';

	if (window.tinyMCE) {
		var id = 'content';

		if (typeof tinyMCE.activeEditor.editorId != 'undefined') {
			id = tinyMCE.activeEditor.editorId;
		}

		// Insert as HTML text so TinyMCE cannot decode attribute delimiters prematurely.
		var shortcode_html = shortcode.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		window.tinyMCE.execCommand('mceInsertContent', false, shortcode_html);
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}

	return false;
}
