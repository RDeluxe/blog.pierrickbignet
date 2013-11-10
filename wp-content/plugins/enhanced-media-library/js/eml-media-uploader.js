(function($){
	
	if ( typeof wp.media !== 'undefined' )
	{	
		var media = wp.media,
		    l10n  = media.view.l10n;
		
		// Taxonomy AttachmentFilters
		media.view.AttachmentFilters.Taxonomy = media.view.AttachmentFilters.extend({	
			tagName:   'select',
			
			createFilters: function() {
				var filters = {};
				var that = this;
	
				_.each( that.options.termList || {}, function( term, key ) {
					var term_name = $("<div/>").html(term['term_name']).text();
					filters[ key ] = {
						text: term_name,
					};
					filters[key]['props'] = {};
					filters[key]['props'][that.options.taxonomy] = term['term_id'];
				});
				
				filters.all = {
					text:  that.options.termListTitle,
					priority: 10
				};
				filters['all']['props'] = {};
				filters['all']['props'][that.options.taxonomy] = 0;
	
				this.filters = filters;
			}
		});
		
		// Enhanced AttachmentBrowser
		media.view.AttachmentsBrowser = media.view.AttachmentsBrowser.extend({
			createToolbar: function() {
				var filters, FiltersConstructor;
	
				this.toolbar = new media.view.Toolbar({
					controller: this.controller
				});
	
				this.views.add( this.toolbar );
	
				filters = this.options.filters;
				if ( 'uploaded' === filters )
					FiltersConstructor = media.view.AttachmentFilters.Uploaded;
				else if ( 'all' === filters )
					FiltersConstructor = media.view.AttachmentFilters.All;
	
				if ( FiltersConstructor ) {
					this.toolbar.set( 'filters', new FiltersConstructor({
						controller: this.controller,
						model:      this.collection.props,
						priority:   -80
					}).render() );
				}
				
				var that = this;
				i = 1;
				$.each(wpuxss_eml_taxonomies, function(taxonomy, values) 
				{
					if ( filters && values.term_list ) 
					{				
						that.toolbar.set( taxonomy+'-filters', new media.view.AttachmentFilters.Taxonomy({
							controller: that.controller,
							model: that.collection.props,
							priority: -80 + 10*i++,
							taxonomy: taxonomy, 
							termList: values.term_list,
							termListTitle: values.list_title,
							className: 'attachment-'+taxonomy+'-filters'
						}).render() );
						
						
					}
				});
	
	
				if ( this.options.search ) {
					this.toolbar.set( 'search', new media.view.Search({
						controller: this.controller,
						model:      this.collection.props,
						priority:   60
					}).render() );
				}
	
				if ( this.options.dragInfo ) {
					this.toolbar.set( 'dragInfo', new media.View({
						el: $( '<div class="instructions">' + l10n.dragInfo + '</div>' )[0],
						priority: -40
					}) );
				}
			}
		});
	
	}
	
	
	$(document).on('change', 'input[name^="tax_input"]', function() 
	{
		var tax_list = [];
		var parent = $(this).closest('.term-list');
		
		parent.find('input[name^="tax_input"]:checked').each(function(i)
		{
			tax_list[i] = $(this).val();
		});
		
		var tax_string = tax_list.join(', ');
     
     		parent.next('input[name^="attachments"]').val(tax_string).change();
	});
	
})( jQuery );