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
		// Buttons index page (TODO : we could probably avoid those duplicates ...)
		var oButtonLeftIndex = $('#slider-index #content_slider a.left');
		var oButtonRightIndex = $('#slider-index #content_slider a.right');
		// Buttons single page
		var oButtonLeftSingle = $('#slider-single #content_slider a.left');
		var oButtonRightSingle = $('#slider-single #content_slider a.right');
		// Tiles size.
		var iTileSize = 250;
		iTileSize = $('.inner_slider a').width();

		var iMarginLeft = 0;
		// Number of tiles/articles
		var nbTiles = $('.inner_slider a').length;
		// Maximum number of tiles displayed (screen size dependant)
		var nbMaxTilesDisplayed = 6;

		if( $( window ).width() < 1500)
		{
			nbMaxTilesDisplayed = $(window).width()/iTileSize;
		}
		else {
			nbMaxTilesDisplayed = 1500/iTileSize;
		}

		// Do we display the buttons. (todo: change 6 by the number of tile in the slider, it depends on the screen size)
		if(nbTiles < nbMaxTilesDisplayed)
		{
			oButtonLeftIndex.css('display', 'none');
			oButtonRightIndex.css('display', 'none');
			oButtonLeftSingle.css('display', 'none');
			oButtonRightSingle.css('display', 'none');
		}

		/**
		 * Handler for onClick on the slider's left and right buttons
		 * @param  {string} direction left or right
		 * @param  {int} 		sizePict  Slider's pictures size on this page
		 * @return {void}
		 */
		 var buttonNextPrev = function(direction, sizePict) {
		 	if(direction != undefined)
		 	{
		 		if(direction == "left") {
		 			if(iMarginLeft > 0)
		 			{
		 				iMarginLeft -= sizePict
		 				oSlider.animate({
		 					marginLeft: -(iMarginLeft)
		 				}, sizePict);
		 			}
		 		}

		 		if(direction == "right") {
		 			if(iMarginLeft < sizePict*(nbTiles-nbMaxTilesDisplayed))
		 			{
		 				iMarginLeft += sizePict;
		 				oSlider.animate({
		 					marginLeft: -(iMarginLeft)
		 				}, sizePict);
		 			}
		 		}
		 	}
		 }

	/**
 	* Next/previous articles onClick event handlers
 	*/
		// click button right index
		oButtonRightIndex.click(function(){
			buttonNextPrev("right", iTileSize);
		});

		//click button left index
		oButtonLeftIndex.click(function(){
			buttonNextPrev("left", iTileSize);
		});

		// click button right single page
		oButtonRightSingle.click(function(){
			buttonNextPrev("right", iTileSize);
		});

		//click button left single page
		oButtonLeftSingle.click(function(){
			buttonNextPrev("left", iTileSize);
		});

		/**
		 * [Filter the slider pictures by category]
		 * @param  {[string]} div    		All the pictures to be filtered
		 * @param  {int}		  sizePict  The picture size on this page
		 * @return {void}
		 */
		 var filterCat = function(div, sizePict) {
		 	var sClassBtn = div.attr('class');
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
		 					width: sizePict,
		 					opacity: 1
		 				}, 300);
		 			}
		 		});
		 	}
		 }

		// Index slider filterCat
		$('#content_menu_index li a').click(function(){
			filterCat($(this),iTileSize);
		});

		// Single page slider filterCat
		$('#content_menu_single li a').click(function(){
			filterCat($(this),iTileSize);
		});

		// Show all button
		/**
		 * Show all pictures in the slider
		 * @param  {int} 		sizePict 		The size of the pictures
		 * @return {void}
		 */
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
			showAllCat(iTileSize);
		});

		// Single page showAll Cat
		$('#content_menu_single .show_all a').click(function(){
			showAllCat(iTileSize);
		});


		/*
		* Changing the 'note' div background
		 */
		var score = $('.note span').attr('score');
		$('#scoreBar').css({
    background: "-webkit-linear-gradient(left, #2782d7 "+score+"%, white 50%)"
		});

		if(score < 90) {
			$('.note span').css({
				color: "#2782d7"
			});
		}

	});
});