window.wp = window.wp || {};

(function($){
	
	wp.customize.HeaderControl = wp.customize.HeaderControl.extend({
		
		openMedia: function(event) {
			
			var l10n = _wpMediaViewsL10n;

			event.preventDefault();

			this.frame = wp.media({
				button: {
					text: l10n.selectAndCrop,
					close: false
				},
				states: [
					new wp.media.controller.Library({
						title:     l10n.chooseImage,
						library:   wp.media.query({ type: 'image' }),
						multiple:  false,
						priority:  20,
						suggestedWidth: _wpCustomizeHeader.data.width,
						suggestedHeight: _wpCustomizeHeader.data.height,
						filterable: 'eml' // turn on filters
					}),
					new wp.media.controller.Cropper({
						imgSelectOptions: this.calculateImageSelectOptions
					})
				]
			});

			this.frame.on('select', this.onSelect, this);
			this.frame.on('cropped', this.onCropped, this);
			this.frame.on('skippedcrop', this.onSkippedCrop, this);

			this.frame.open();
		}
	});
	
	$.extend( wp.customize.controlConstructor, {
		header: wp.customize.HeaderControl
	});
	
}(jQuery));