(function() {
	tinymce.create('tinymce.plugins.ar_buttons', {
		init: function(ed, url) {
			// Register commands
			ed.addCommand('mp-mc-form', function() {
				ed.windowManager.open({
					url: an_mc_dialog_url, // file that contains HTML for our modal window
					width: 500 + parseInt(ed.getLang('button.delta_width', 0)), // size of our window
					height: 400 + parseInt(ed.getLang('button.delta_height', 0)), // size of our window
					inline: 1,
				}, {
					plugin_url: url
				});
			});

			// Register buttons
			ed.addButton('mp-mc-form', {
				title: 'Insert MailChimp Form',
				cmd: 'mp-mc-form',
				icon: 'mce-ico dashicons-before dashicons-email-alt'
			});
		}
	});

	// Register plugin
	// first parameter is the button ID and must match ID elsewhere
	// second parameter must match the first parameter of the tinymce.create() function above
	tinymce.PluginManager.add('ar_buttons', tinymce.plugins.ar_buttons);

})();