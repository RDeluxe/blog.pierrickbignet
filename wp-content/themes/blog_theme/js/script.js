jQuery(document).ready(function($) {
    // Inside of this function, $() will work as an alias for jQuery()
    // and other libraries also using $ will not be accessible under this shortcut

    $(document).ready(function() {
		// tiles
		$('.inner_slider > a ').each( function(){
			$(this).hoverdir();
		});

		$('a.lightbox').zoombox();

		// background management (not useful for now)
		$( window ).scroll(function() {
			var iScrollTop = $( window ).scrollTop();
			if(iScrollTop >= 347 && !$('#screen').hasClass('fixed'))
			{
				$('#screen').addClass('fixed');
			}
			else if(iScrollTop < 347 && $('#screen').hasClass('fixed'))
			{
				$('#screen').removeClass('fixed');
			}
		});

		// slider
		var oSlider = $('.inner_slider');
		var oButtonLeft = $('#content_slider a.left');
		var oButtonRight = $('#content_slider a.right');
		var iTileSizeIndex = 250;
		var iTileSizeSingle = 150;
		var iMarginLeft = 0;
		var iNbTiles = $('.inner_slider a').length;

		if(iNbTiles < 6)
		{
			oButtonLeft.css('display', 'none');
			oButtonRight.css('display', 'none');
		}

		// click button right (todo: change 6 by the number of tile in the slider, it depends on the screen size)
		oButtonRight.click(function(){
			if(iMarginLeft < iTileSizeIndex*(iNbTiles-iNbtiles))
			{
				iMarginLeft += iTileSizeIndex;
				oSlider.animate({
					marginLeft: -(iMarginLeft)
				}, 250);
			}
		});

		//click button left
		oButtonLeft.click(function(){
			if(iMarginLeft > 0)
			{
				iMarginLeft -= iTileSizeIndex
				oSlider.animate({
					marginLeft: -(iMarginLeft)
				}, 250);
			}
		});

		// Filter the slider pictures by category. Size needs to be set.
		var filterCat = function(div, sizePict) {
			var sClassBtn = div.attr('class');
			if(sClassBtn != undefined)
			{
				console.log("LOL");
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
							width: sizePict,
							opacity: 1
						}, 300);
					}
				});
			}
		}

		// Index slider filterCat
		$('#content_menu_index li a').click(function(){
			filterCat($(this),iTileSizeIndex);
		});

		// Single page slider filterCat
		$('#content_menu_single li a').click(function(){
			filterCat($(this),iTileSizeSingle);
		});

		// Show all button
		var showAllCat = function (sizePict) {
			$('.inner_slider a').each(function(i, elem){
				var oElem = $(elem);
				if(oElem.hasClass('hidden'))
				{
					oElem.removeClass('hidden');
					oElem.animate({
						width: sizePict,
						opacity: 1
					}, 300);
				}
			});
		}

		// Index showAll Cat
		$('#content_menu_index .show_all a').click(function(){
			showAllCat(iTileSizeIndex);
		});

		// Single page showAll Cat
		$('#content_menu_single .show_all a').click(function(){
			showAllCat(iTileSizeSingle);
		});

	});
});