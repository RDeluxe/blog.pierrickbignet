(function($) {
	
	var frame;
	
	$( document ).ready(function() {
		
		$("#choose-from-library-link").unbind('click').click( function( event ) {
			
			var $el = $(this);
			
			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return;
			}

			// Create the media frame.
			frame = wp.media.frames.customHeader = wp.media({

				// Customize the submit button.
				button: {
					// Set the text of the button.
					text: $el.data('update'),
					// Tell the button not to close the modal, since we're
					// going to refresh the page when the image is selected.
					close: false
				},
				
				states: [
					new wp.media.controller.Library({
						title:     $el.data('choose'),
						library:   wp.media.query({ type: 'image' }),
						multiple:  false,
						priority:  20,
						filterable: 'eml' // turn on filters
					})
				]
			});

			// When an image is selected, run a callback.
			frame.on( 'select', function() {
				// Grab the selected attachment.
				var attachment = frame.state().get('selection').first(),
					link = $el.data('updateLink');

				// Tell the browser to navigate to the crop step.
				window.location = link + '&file=' + attachment.id;
			});

			frame.open();
		});
	});
	
}(jQuery));