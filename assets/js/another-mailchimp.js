(function($) {
	'use strict';

	// Retain the public jQuery helper without registering another handler per click.
	$.fn.an_mc_subscribe_to_mail_chimp = function(options) {
		return this.each(function() {
			$(this).data('anMcOptions', $.extend({}, options));
		});
	};

	$(document).on('submit.another_mp_am', 'form[data-id][data-url]', function(event) {
		var form = this;
		var $form = $(form);
		if (!$form.find('input[name="an_mc_submit"]').length) {
			return;
		}
		event.preventDefault();
		if ($form.data('anMcBusy')) {
			return;
		}
		if (!form.checkValidity()) {
			form.reportValidity();
			return;
		}

		var options = $form.data('anMcOptions') || {};
		var $buttons = $form.find('.mpam-submit');
		var fallback = $form.attr('data-error');
		var fields = $form.serialize();
		$form.data('anMcBusy', true);
		$buttons.addClass('mp-am-preloader').prop('disabled', true);

		function showError(message) {
			var $error = $form.find('.notification.error');
			if (!$error.length) {
				$error = $('<p class="notification error" role="alert"></p>').prependTo($form);
			}
			// Messages originate from the server-sanitized, signed configuration.
			$error.html(typeof message === 'string' ? message : fallback);
		}

		$.ajax({
			url: options.url || $form.attr('data-url'),
			method: 'POST',
			dataType: 'json',
			data: fields,
			timeout: 60000
		}).done(function(data) {
			if (data && data.success === true && typeof data.success_message === 'string') {
				$form.empty().append($('<p class="notification success" role="status"></p>').html(data.success_message));
			} else {
				showError(data && data.error);
			}
		}).fail(function() {
			showError(fallback);
		}).always(function() {
			$form.data('anMcBusy', false);
			$buttons.removeClass('mp-am-preloader').prop('disabled', false);
		});
	});
}(jQuery));
