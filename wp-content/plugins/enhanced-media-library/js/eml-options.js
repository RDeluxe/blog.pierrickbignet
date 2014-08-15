(function($){
	
	// remove taxonomy
	$(document).on('click', 'li .wpuxss-eml-button-remove', function()
	{
		target = $(this).parent();
		
		if ( target.hasClass('wpuxss-eml-clone-taxonomy') )
		{
			target.hide( 300, function() {
				$(this).remove();
			});
		}
		else
		{
			if ( confirm(wpuxss_eml_i18n_data.tax_deletion_confirm) )
			{
				target.hide( 300, function() {
					$(this).remove();
				});
			}
		}
		
		return false;
	});

	// create new taxonomy
	$(document).on('click', '.wpuxss-eml-button-create-taxonomy', function()
	{
		$('.wpuxss-eml-media-taxonomy-list').find('.wpuxss-eml-clone').clone().attr('class','wpuxss-eml-clone-taxonomy').appendTo('.wpuxss-eml-media-taxonomy-list').show(300);
		
		return false;
	});

	// edit taxonomy parameters
	$(document).on('click', '.wpuxss-eml-button-edit', function()
	{
		$(this).parent().find('.wpuxss-eml-taxonomy-edit').toggle(300);
		
		$(this).html(function(i, html)
		{
			return html == wpuxss_eml_i18n_data.edit+' \u2193' ? wpuxss_eml_i18n_data.close+' \u2191' : wpuxss_eml_i18n_data.edit+' \u2193';
		});
		
		return false;
	});
	
	// on change of a singular taxonomy name during creation
	$(document).on('blur', '.wpuxss-eml-clone-taxonomy .wpuxss-eml-singular_name', function()
	{
		var dictionary,
		taxonomy_name = $(this).val().toLowerCase(),
		taxonomy_edit_box = $(this).parents('.wpuxss-eml-taxonomy-edit');
		
		taxonomy_name = taxonomy_name.replace(/(<([^>]+)>)/g,'');
		
		if ( taxonomy_name != '' )
		{
			// thanks to 
			// https://github.com/borodean/jquery-translit 
			// https://gist.github.com/richardsweeney/5317392
			// http://www.advancedcustomfields.com/
			// for the 'dictionary' code!
			dictionary = {
				'а': 'a',
				'б': 'b',
				'в': 'v',
				'г': 'g',
				'д': 'd',
				'е': 'e',
				'ё': 'e',
				'ж': 'zh',
				'з': 'z',
				'и': 'i',
				'й': 'i',
				'к': 'k',
				'л': 'l',
				'м': 'm',
				'н': 'n',
				'о': 'o',
				'п': 'p',
				'р': 'r',
				'с': 's',
				'т': 't',
				'у': 'u',
				'ф': 'f',
				'х': 'kh',
				'ц': 'tc',
				'ч': 'ch',
				'ш': 'sh',
				'щ': 'shch',
				'ъ': '',
				'ы': 'y',
				'ь': '',
				'э': 'e',
				'ю': 'iu',
				'я': 'ia',
				'ä': 'a',
				'æ': 'a',
				'å': 'a',
				'ö': 'o',
				'ø': 'o',
				'é': 'e',
				'ë': 'e',
				'ü': 'u',
				'ó': 'o',
				'ő': 'o',
				'ú': 'u',
				'é': 'e',
				'á': 'a',
				'ű': 'u',
				'í': 'i',
				' ' : '_',
				'-' : '_',
				'\'' : '',
				'&' : '_'		
			};
			
			$.each( dictionary, function(k, v)
			{
				var regex = new RegExp( k, 'g' );
				taxonomy_name = taxonomy_name.replace( regex, v );
			});
			
			taxonomy_name = taxonomy_name.replace(/[^a-z0-9_\s]/g, '');
			
			$(this).closest('.wpuxss-eml-clone-taxonomy').attr('id',taxonomy_name);
			
			if ( $('.wpuxss-eml-clone-taxonomy[id='+taxonomy_name+'], .wpuxss-eml-taxonomy[id='+taxonomy_name+'], .wpuxss-non-eml-taxonomy[id='+taxonomy_name+']').length > 1 )
			{
				alert(wpuxss_eml_i18n_data.tax_error_duplicate);
			}
			
			$(this).attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][singular_name]');
			taxonomy_edit_box.find('.wpuxss-eml-name').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][name]');
			taxonomy_edit_box.find('.wpuxss-eml-menu_name').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][menu_name]');
			taxonomy_edit_box.find('.wpuxss-eml-all_items').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][all_items]');
			taxonomy_edit_box.find('.wpuxss-eml-edit_item').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][edit_item]');
			taxonomy_edit_box.find('.wpuxss-eml-view_item').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][view_item]');
			taxonomy_edit_box.find('.wpuxss-eml-update_item').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][update_item]');
			taxonomy_edit_box.find('.wpuxss-eml-add_new_item').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][add_new_item]');
			taxonomy_edit_box.find('.wpuxss-eml-new_item_name').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][new_item_name]');
			taxonomy_edit_box.find('.wpuxss-eml-parent_item').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][parent_item]');
			taxonomy_edit_box.find('.wpuxss-eml-search_items').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][labels][search_items]');
			
			if( taxonomy_edit_box.find('.wpuxss-eml-edit_item').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-edit_item').val(wpuxss_eml_i18n_data.edit+' '+$(this).val());
			
			if( taxonomy_edit_box.find('.wpuxss-eml-view_item').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-view_item').val(wpuxss_eml_i18n_data.view+' '+$(this).val());
			
			if( taxonomy_edit_box.find('.wpuxss-eml-update_item').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-update_item').val(wpuxss_eml_i18n_data.update+' '+$(this).val());
			
			if( taxonomy_edit_box.find('.wpuxss-eml-add_new_item').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-add_new_item').val(wpuxss_eml_i18n_data.add_new+' '+$(this).val());
			
			if( taxonomy_edit_box.find('.wpuxss-eml-new_item_name').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-new_item_name').val(wpuxss_eml_i18n_data.new+' '+$(this).val()+' '+wpuxss_eml_i18n_data.name);
			
			if( taxonomy_edit_box.find('.wpuxss-eml-parent_item').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-parent_item').val(wpuxss_eml_i18n_data.parent+' '+$(this).val());
			
			taxonomy_edit_box.find('.wpuxss-eml-hierarchical').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][hierarchical]');
			taxonomy_edit_box.find('.wpuxss-eml-public').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][public]');
			taxonomy_edit_box.find('.wpuxss-eml-show_admin_column').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][show_admin_column]');
			taxonomy_edit_box.find('.wpuxss-eml-show_in_nav_menus').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][show_in_nav_menus]');
			taxonomy_edit_box.find('.wpuxss-eml-sort').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][sort]');
			taxonomy_edit_box.find('.wpuxss-eml-slug').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][rewrite][slug]').val(taxonomy_name);
			
			taxonomy_edit_box.find('.wpuxss-eml-admin_filter').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][admin_filter]');	
			taxonomy_edit_box.find('.wpuxss-eml-media_uploader_filter').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][media_uploader_filter]');
			
			$(this).closest('.wpuxss-eml-clone-taxonomy').find('.wpuxss-eml-assigned').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][assigned]');
			$(this).closest('.wpuxss-eml-clone-taxonomy').find('.wpuxss-eml-eml_media').attr('name','wpuxss_eml_taxonomies['+taxonomy_name+'][eml_media]');		
		}
		else
		{
			$(this).val('');
			taxonomy_edit_box.find('.wpuxss-eml-edit_item').val('');
			taxonomy_edit_box.find('.wpuxss-eml-view_item').val('');
			taxonomy_edit_box.find('.wpuxss-eml-update_item').val('');
			taxonomy_edit_box.find('.wpuxss-eml-add_new_item').val('');
			taxonomy_edit_box.find('.wpuxss-eml-new_item_name').val('');
			taxonomy_edit_box.find('.wpuxss-eml-parent_item').val('');
			taxonomy_edit_box.find('.wpuxss-eml-slug').val('');
		}
	});

	// on change of a plural taxonomy name during creation
	$(document).on('blur', '.wpuxss-eml-clone-taxonomy .wpuxss-eml-name', function()
	{
		var taxonomy_plural_name =  $(this).val();
		taxonomy_edit_box = $(this).parents('.wpuxss-eml-taxonomy-edit');
		
		taxonomy_plural_name = taxonomy_plural_name.replace(/(<([^>]+)>)/g,'');
		
		if ( taxonomy_plural_name != '' )
		{
			if( taxonomy_edit_box.find('.wpuxss-eml-menu_name').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-menu_name').val($(this).val());
	
			if( taxonomy_edit_box.find('.wpuxss-eml-all_items').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-all_items').val(wpuxss_eml_i18n_data.all+' '+$(this).val());
				
			if( taxonomy_edit_box.find('.wpuxss-eml-search_items').val() == '' )
				taxonomy_edit_box.find('.wpuxss-eml-search_items').val(wpuxss_eml_i18n_data.search+' '+$(this).val());
			
			$(this).closest('.wpuxss-eml-clone-taxonomy').find('.wpuxss-eml-taxonomy-label').text($(this).val());
		}
		else
		{
			$(this).val('');
			taxonomy_edit_box.find('.wpuxss-eml-menu_name').val('');
			taxonomy_edit_box.find('.wpuxss-eml-all_items').val('');
			taxonomy_edit_box.find('.wpuxss-eml-search_items').val('');
			$(this).closest('.wpuxss-eml-clone-taxonomy').find('.wpuxss-eml-taxonomy-label').text(wpuxss_eml_i18n_data.tax_new);
		}
	});

	// on taxonomy form submit
	$('#wpuxss-eml-form-taxonomies').submit(function( event )
	{
		submit_it = true;
		alert_text = '';
		
		$('.wpuxss-eml-clone-taxonomy, .wpuxss-eml-taxonomy').each(function( index )
		{
			current_taxonomy = $(this).attr('id');
			
			if ( !$('.wpuxss-eml-singular_name',this).val() && !$('.wpuxss-eml-name',this).val() )
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.tax_error_empty_both;
			}
			else if ( !$('.wpuxss-eml-singular_name',this).val() ) 
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.tax_error_empty_singular;
			}
			else if ( !$('.wpuxss-eml-name',this).val() )
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.tax_error_empty_plural;
			}
			else if ( $('.wpuxss-eml-clone-taxonomy[id='+current_taxonomy+'], .wpuxss-eml-taxonomy[id='+current_taxonomy+'], .wpuxss-non-eml-taxonomy[id='+current_taxonomy+']').length > 1 )
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.tax_error_duplicate;
			}
		});
		
		if ( !submit_it ) alert(alert_text);
		
		return submit_it;
	});
	
	
	
	// create new mime type
	$(document).on('click', '.wpuxss-eml-button-create-mime', function()
	{
		$('.wpuxss-eml-mime-type-list').find('.wpuxss-eml-clone').clone().attr('class','wpuxss-eml-clone-mime').prependTo('.wpuxss-eml-mime-type-list tbody').show(300);
		
		return false;
	});
	
	// remove mime type
	$(document).on('click', 'tr .wpuxss-eml-button-remove', function()
	{
		$(this).closest('tr').hide( 300, function() {
			$(this).remove();
		});
		
		return false;
	});

	// on change of an extension during creation
	$(document).on('blur', '.wpuxss-eml-clone-mime .wpuxss-eml-type', function()
	{
		var extension = $(this).val().toLowerCase(),
		mime_type_tr = $(this).closest('tr');
		
		$(this).val(extension);
		
		mime_type_tr.find('.wpuxss-eml-mime').attr('name','wpuxss_eml_mimes['+extension+'][mime]');
		mime_type_tr.find('.wpuxss-eml-singular').attr('name','wpuxss_eml_mimes['+extension+'][singular]');
		mime_type_tr.find('.wpuxss-eml-plural').attr('name','wpuxss_eml_mimes['+extension+'][plural]');
		mime_type_tr.find('.wpuxss-eml-filter').attr('name','wpuxss_eml_mimes['+extension+'][filter]');
		mime_type_tr.find('.wpuxss-eml-upload').attr('name','wpuxss_eml_mimes['+extension+'][upload]');
	});
	
	
	// on change of a mime type during creation
	$(document).on('blur', '.wpuxss-eml-clone-mime .wpuxss-eml-mime', function()
	{
		var mime_type = $(this).val().toLowerCase(),
		mime_type_tr = $(this).closest('tr');
		
		$(this).val(mime_type);
	});
	
	// mime types restoration warning
	$(document).on('click', '#wpuxss_eml_restore_mimes_backup', function()
	{
		if ( confirm(wpuxss_eml_i18n_data.mime_deletion_confirm) )
		{
			return true;
		}
		
		return false;
	});

	// on mime types form submit
	$('#wpuxss-eml-form-mimetypes').submit(function( event )
	{
		submit_it = true;
		alert_text = '';
		
		$('.wpuxss-eml-clone-mime').each(function( index )
		{
			if ( !$('.wpuxss-eml-type',this).val() || $('.wpuxss-eml-type',this).val() == '' || !$('.wpuxss-eml-mime',this).val() || $('.wpuxss-eml-mime',this).val() == '' )
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.mime_error_empty_fields;
			} 
			else if ( $('[id="'+$('.wpuxss-eml-type',this).val()+'"]').length > 0 || $('.wpuxss-eml-mime[value="'+$('.wpuxss-eml-mime',this).val()+'"]').length > 0 )
			{
				submit_it = false;
				alert_text = wpuxss_eml_i18n_data.mime_error_duplicate;
			}
			
			if ( !$('.wpuxss-eml-singular',this).val() || $('.wpuxss-eml-singular',this).val() == '' || !$('.wpuxss-eml-plural',this).val() || $('.wpuxss-eml-plural',this).val() == '' )
			{
				$('.wpuxss-eml-singular',this).val($('.wpuxss-eml-mime',this).val());
				$('.wpuxss-eml-plural',this).val($('.wpuxss-eml-mime',this).val());
			}
		});
		
		if ( !submit_it && alert_text != '' ) alert(alert_text);
		
		return submit_it;
	});
	
})( jQuery );