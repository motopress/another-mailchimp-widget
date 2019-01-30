(function($) {
	'use strict';

	$.fn.an_mc_subscribe_to_mail_chimp = function(options) {
		var defaults, eL, opts;

		defaults = {
			'url': '/'
		};

		opts = jQuery.extend(defaults, options);

		eL = $(this);

		var valid_form = eL[0].checkValidity();

		if (valid_form) {
			eL.submit(function() {
				var ajax_loader = eL.find('.mpam-submit');
				ajax_loader.addClass('mp-am-preloader');

				$.getJSON(opts.url, eL.serialize(), function(data, textStatus) {
					var error_container;

					if ('success' === textStatus) {
						if (true === data.success) {
							ajax_loader.removeClass('mp-am-preloader');
							eL.html('<p class="notification success">' + data.success_message + '</p>');
						} else {
							ajax_loader.removeClass('mp-am-preloader');

							error_container = jQuery('.error', eL);

							if (0 === error_container.length) {
								error_container = jQuery('<p class="notification error"></p>');
								error_container.prependTo(eL);
							}

							error_container.html(data.error);
						}
					}
					return false;
				});
				return false;
			});
		}
	};
}(jQuery));

(function($) {
	"use strict";
	$(document).ready(function() {

		$(document).on('click.another_mp_am', 'form input[type="submit"].mpam-submit', function(e) {

			var $form = $(this).closest("form");
			var id = $form.data('id');
			var url = $form.data('url');

			$form.an_mc_subscribe_to_mail_chimp({
				id: id,
				url: url
			});

		});

	});
}(jQuery));