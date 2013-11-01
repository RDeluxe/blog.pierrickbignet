jQuery(document).ready(function($) {
    // Inside of this function, $() will work as an alias for jQuery()
    // and other libraries also using $ will not be accessible under this shortcut

    $(document).ready(function() {
		// tiles  
		$('.inner_slider > a ').each( function(){
			$(this).hoverdir();
		});

		$('a.lightbox').zoombox();

		// slider
		var oSlider = $('.inner_slider');
		var oButtonLeft = $('#content_slider a.left');
		var oButtonRight = $('#content_slider a.right');
		
		var iTileSize = 251;
		var iMarginLeft = 0;
		var iNbTiles = $('.inner_slider a').length;

		if(iNbTiles < 6)
		{
			oButtonLeft.css('display', 'none');
			oButtonRight.css('display', 'none');
		}

		// click button right (todo: change 6 by the number of tile in the slider, it depends on the screen size)
		oButtonRight.click(function(){
			if(iMarginLeft < iTileSize*(iNbTiles-iNbtiles))
			{
				iMarginLeft += iTileSize;
				oSlider.animate({
					marginLeft: -(iMarginLeft)
				}, 250);
			}
		});

		//click button left
		oButtonLeft.click(function(){
			if(iMarginLeft > 0)
			{
				iMarginLeft -= iTileSize
				oSlider.animate({
					marginLeft: -(iMarginLeft)
				}, 250);
			}
		});	

		// menu 
		$('#content_menu li a').click(function(){
			var sClassBtn = $(this).attr('class');
			if(sClassBtn != undefined)
			{
				$('.inner_slider a').each(function(i, elem){
					var oElem = $(elem);
					if(!oElem.hasClass(sClassBtn))
					{
						oElem.animate({
							width: 0,
							opacity: 0
						}, 300, function(){
							oElem.addClass('hidden');
						});
					}
					else if(oElem.hasClass('hidden'))
					{
						oElem.removeClass('hidden');
						oElem.animate({
							width: 250,
							opacity: 1
						}, 300);					
					}
				});
			}
		});

		// show all button
		$('.show_all a').click(function(){
			$('.inner_slider a').each(function(i, elem){
				var oElem = $(elem);
				if(oElem.hasClass('hidden'))
				{
					oElem.removeClass('hidden');
					oElem.animate({
						width: 250,
						opacity: 1
					}, 300);					
				}
			});
		});
	});
});