(function ($) {
	$(document).on('click', '.s3ds-media-pick', function (event) {
		event.preventDefault();
		var button = $(this);
		var target = button.closest('p').find('input.s3ds-media-url');
		var frame = wp.media({ title: 'Select media', button: { text: 'Use this file' }, multiple: false });
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			target.val(attachment.url).trigger('change');
		});
		frame.open();
	});
})(jQuery);
