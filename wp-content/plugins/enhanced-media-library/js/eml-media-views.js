window.wp = window.wp || {};

(function($){
	
	var media = wp.media;
	

	
	media.view.AttachmentFilters.Taxonomy = media.view.AttachmentFilters.extend({	
	
		tagName:   'select',
		
		createFilters: function() {
			var filters = {};
			var that = this;

			_.each( that.options.termList || {}, function( term, key ) {
				var term_id = term['term_id'];
				var term_name = $("<div/>").html(term['term_name']).text();
				filters[ term_id ] = {
					text: term_name,
					priority: key+2
				};
				filters[term_id]['props'] = {};
				filters[term_id]['props'][that.options.taxonomy] = term_id;
			});
			
			filters.all = {
				text: that.options.termListTitle,
				priority: 1
			};
			filters['all']['props'] = {};
			filters['all']['props'][that.options.taxonomy] = null;

			this.filters = filters;
		}
	});
	
	
	
	var curAttachmentsBrowser = media.view.AttachmentsBrowser;
	
	media.view.AttachmentsBrowser = media.view.AttachmentsBrowser.extend({
		
		createToolbar: function() {
			
			var filters = this.options.filters;
			
			curAttachmentsBrowser.prototype.createToolbar.apply(this,arguments);

			var that = this,				
			i = 1;
			
			$.each(wpuxss_eml_taxonomies, function(taxonomy, values) 
			{
				if ( values.term_list && filters )
				{				
					that.toolbar.set( taxonomy+'-filter', new media.view.AttachmentFilters.Taxonomy({
						controller: that.controller,
						model: that.collection.props,
						priority: -80 + 10*i++,
						taxonomy: taxonomy, 
						termList: values.term_list,
						termListTitle: values.list_title,
						className: 'attachment-'+taxonomy+'-filter'
					}).render() );
				}
			});
		}
	});
	
	
	
	function correctAttachmentsListCSS()
	{		
		if ( 'absolute' == $('.attachments-browser .attachments').css('position') && $('.attachments-browser').height() > $('.attachments-browser .media-toolbar').height()+20 )
		{
			$('.attachments-browser .attachments').css('top',$('.attachments-browser .media-toolbar').height()+20);
			
		}
		else if ( 'absolute' == $('.attachments-browser .attachments').css('position') )
		{
			$('.attachments-browser .attachments').css('top','50px');
		}
		else if ( 'relative' == $('.attachments-browser .attachments').css('position') )
		{
			$('.attachments-browser .attachments').css('top','0');
		}
		
		return false;
	};
	
	$(document).on('change', '.media-toolbar-secondary select', function(e)
	{
    	correctAttachmentsListCSS();
	});
	
	wp.media.view.Modal.prototype.on('open', function()
	{
		correctAttachmentsListCSS();
	});	
	
	$(window).on('resize',function(e)
	{
		if ( typeof wp !== 'undefined' && wp.media && wp.media.editor )
		{	
			correctAttachmentsListCSS();
		}
	})
	
})( jQuery );