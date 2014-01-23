<?php
/**
 * Media Library Assistant Shortcode handler(s)
 *
 * @package Media Library Assistant
 * @since 0.1
 */

/**
 * Class MLA (Media Library Assistant) Shortcodes defines the shortcodes available to MLA users
 *
 * @package Media Library Assistant
 * @since 0.20
 */
class MLAShortcodes {
	/**
	 * Initialization function, similar to __construct()
	 *
	 * @since 0.20
	 *
	 * @return	void
	 */
	public static function initialize() {
		add_shortcode( 'mla_attachment_list', 'MLAShortcodes::mla_attachment_list_shortcode' );
		add_shortcode( 'mla_gallery', 'MLAShortcodes::mla_gallery_shortcode' );
		add_shortcode( 'mla_tag_cloud', 'MLAShortcodes::mla_tag_cloud_shortcode' );
	}

	/**
	 * WordPress Shortcode; renders a complete list of all attachments and references to them
	 *
	 * @since 0.1
	 *
	 * @return	void	echoes HTML markup for the attachment list
	 */
	public static function mla_attachment_list_shortcode( /* $atts */ ) {
		global $wpdb;
		
		/*	extract(shortcode_atts(array(
		'item_type'=>'attachment',
		'organize_by'=>'title',
		), $atts)); */
		
		/*
		 * Process the where-used settings option
		 */
		if ('checked' == MLAOptions::mla_get_option( MLAOptions::MLA_EXCLUDE_REVISIONS ) )
			$exclude_revisions = "(post_type <> 'revision') AND ";
		else
			$exclude_revisions = '';
				
		$attachments = $wpdb->get_results(
				"
				SELECT ID, post_title, post_name, post_parent
				FROM {$wpdb->posts}
				WHERE {$exclude_revisions}post_type = 'attachment' 
				"
		);
		
		foreach ( $attachments as $attachment ) {
			$references = MLAData::mla_fetch_attachment_references( $attachment->ID, $attachment->post_parent );
			
			echo '&nbsp;<br><h3>' . $attachment->ID . ', ' . esc_attr( $attachment->post_title ) . ', Parent: ' . $attachment->post_parent . '<br>' . esc_attr( $attachment->post_name ) . '<br>' . esc_html( $references['base_file'] ) . "</h3>\r\n";
			
			/*
			 * Look for the "Featured Image(s)"
			 */
			if ( empty( $references['features'] ) ) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;not featured in any posts.<br>\r\n";
			} else {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;Featured in<br>\r\n";
				foreach ( $references['features'] as $feature_id => $feature ) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					
					if ( $feature_id == $attachment->post_parent ) {
						echo 'PARENT ';
						$found_parent = true;
					}
					
					echo $feature_id . ' (' . $feature->post_type . '), ' . esc_attr( $feature->post_title ) . "<br>\r\n";
				}
			}
			
			/*
			 * Look for item(s) inserted in post_content
			 */
			if ( empty( $references['inserts'] ) ) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;no inserts in any post_content.<br>\r\n";
			} else {
				foreach ( $references['inserts'] as $file => $inserts ) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $file . " inserted in<br>\r\n";
					foreach ( $inserts as $insert ) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						
						if ( $insert->ID == $attachment->post_parent ) {
							echo 'PARENT ';
							$found_parent = true;
						}
						
						echo $insert->ID . ' (' . $insert->post_type . '), ' . esc_attr( $insert->post_title ) . "<br>\r\n";
					} // foreach $insert
				} // foreach $file
			}
			
			$errors = '';
			
			if ( !$references['found_reference'] )
				$errors .= '(ORPHAN) ';
			
			if ( $references['is_unattached'] )
				$errors .= '(UNATTACHED) ';
			else {
				if ( !$references['found_parent'] ) {
					if ( isset( $references['parent_title'] ) )
						$errors .= '(BAD PARENT) ';
					else
						$errors .= '(INVALID PARENT) ';
				}
			}
			
			if ( !empty( $errors ) )
				echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $errors . "<br>\r\n";
		} // foreach attachment
		
		echo "<br>----- End of Report -----\r\n";
	}
	
	/**
	 * Accumulates debug messages
	 *
	 * @since 0.60
	 *
	 * @var	string
	 */
	public static $mla_debug_messages = '';
	
	/**
	 * Turn debug collection and display on or off
	 *
	 * @since 0.70
	 *
	 * @var	boolean
	 */
	private static $mla_debug = false;
	
	/**
	 * The MLA Gallery shortcode.
	 *
	 * This is a superset of the WordPress Gallery shortcode for displaying images on a post,
	 * page or custom post type. It is adapted from /wp-includes/media.php gallery_shortcode.
	 * Enhancements include many additional selection parameters and full taxonomy support.
	 *
	 * @since .50
	 *
	 * @param array Attributes of the shortcode
	 * @param string Optional content for enclosing shortcodes; used with mla_alt_shortcode
	 *
	 * @return string HTML content to display gallery.
	 */
	public static function mla_gallery_shortcode( $attr, $content = NULL ) {
		global $post;

		/*
		 * Some do_shortcode callers may not have a specific post in mind
		 */
		if ( ! is_object( $post ) )
			$post = (object) array( 'ID' => 0 );
		
		/*
		 * Make sure $attr is an array, even if it's empty
		 */
		if ( empty( $attr ) )
			$attr = array();
		elseif ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );

		/*
		 * The mla_paginate_current parameter can be changed to support multiple galleries per page.
		 */
		if ( ! isset( $attr['mla_page_parameter'] ) )
			$attr['mla_page_parameter'] = self::$mla_get_shortcode_attachments_parameters['mla_page_parameter'];
			
		$mla_page_parameter = $attr['mla_page_parameter'];
		 
		/*
		 * Special handling of the mla_paginate_current parameter to make
		 * "MLA pagination" easier. Look for this parameter in $_REQUEST
		 * if it's not present in the shortcode itself.
		 */
		if ( ! isset( $attr[ $mla_page_parameter ] ) )
			if ( isset( $_REQUEST[ $mla_page_parameter ] ) )
				$attr[ $mla_page_parameter ] = $_REQUEST[ $mla_page_parameter ];
		 
		/*
		 * These are the parameters for gallery display
		 */
		$mla_item_specific_arguments = array(
			'mla_link_attributes' => '',
			'mla_link_class' => '',
			'mla_link_href' => '',
			'mla_link_text' => '',
			'mla_nolink_text' => '',
			'mla_rollover_text' => '',
			'mla_image_class' => '',
			'mla_image_alt' => '',
			'mla_image_attributes' => '',
			'mla_caption' => ''
		);
		
		$mla_arguments = array_merge( array(
			'mla_output' => 'gallery',
			'mla_style' => MLAOptions::mla_get_option('default_style'),
			'mla_markup' => MLAOptions::mla_get_option('default_markup'),
			'mla_float' => is_rtl() ? 'right' : 'left',
			'mla_itemwidth' => MLAOptions::mla_get_option('mla_gallery_itemwidth'),
			'mla_margin' => MLAOptions::mla_get_option('mla_gallery_margin'),
			'mla_target' => '',
			'mla_debug' => false,
			'mla_viewer' => false,
			'mla_viewer_extensions' => 'doc,xls,ppt,pdf,txt',
			'mla_viewer_page' => '1',
			'mla_viewer_width' => '150',
			'mla_alt_shortcode' => NULL,
			'mla_alt_ids_name' => 'ids',
			
			// paginatation arguments defined in $mla_get_shortcode_attachments_parameters
			// 'mla_page_parameter' => 'mla_paginate_current', handled in code with $mla_page_parameter
			// 'mla_paginate_current' => NULL,
			// 'mla_paginate_total' => NULL,
			// 'id' => NULL,

			'mla_end_size'=> 1,
			'mla_mid_size' => 2,
			'mla_prev_text' => '&laquo; Previous',
			'mla_next_text' => 'Next &raquo;',
			'mla_paginate_type' => 'plain'),
			$mla_item_specific_arguments
		);
		
		$default_arguments = array_merge( array(
			'size' => 'thumbnail', // or 'medium', 'large', 'full' or registered size
			'itemtag' => 'dl',
			'icontag' => 'dt',
			'captiontag' => 'dd',
			'columns' => MLAOptions::mla_get_option('mla_gallery_columns'),
			'link' => 'permalink', // or 'post' or file' or a registered size
			// Photonic-specific
			'id' => NULL,
			'style' => NULL,
			'type' => 'default', // also used by WordPress.com Jetpack!
			'thumb_width' => 75,
			'thumb_height' => 75,
			'thumbnail_size' => 'thumbnail',
			'slide_size' => 'large',
			'slideshow_height' => 500,
			'fx' => 'fade',
			'timeout' => 4000,
			'speed' => 1000,
			'pause' => NULL),
			$mla_arguments
		);
			
		/*
		 * Look for 'request' substitution parameters,
		 * which can be added to any input parameter
		 */
		foreach ( $attr as $attr_key => $attr_value ) {
			/*
			 * attachment-specific Gallery Display Content parameters must be evaluated
			 * later, when all of the information is available.
			 */
			if ( array_key_exists( $attr_key, $mla_item_specific_arguments ) )
				continue;
				
			$attr_value = str_replace( '{+', '[+', str_replace( '+}', '+]', $attr_value ) );
			$replacement_values = MLAData::mla_expand_field_level_parameters( $attr_value );

			if ( ! empty( $replacement_values ) )
				$attr[ $attr_key ] = MLAData::mla_parse_template( $attr_value, $replacement_values );
		}
		
		/*
		 * Merge gallery arguments with defaults, pass the query arguments on to mla_get_shortcode_attachments.
		 */
		 
		$attr = apply_filters( 'mla_gallery_attributes', $attr );
		$content = apply_filters( 'mla_gallery_initial_content', $content, $attr );
		$arguments = shortcode_atts( $default_arguments, $attr );
		$arguments = apply_filters( 'mla_gallery_arguments', $arguments );
		
		self::$mla_debug = !empty( $arguments['mla_debug'] ) && ( 'true' == strtolower( $arguments['mla_debug'] ) );

		/*
		 * Determine output type
		 */
		$output_parameters = array_map( 'strtolower', array_map( 'trim', explode( ',', $arguments['mla_output'] ) ) );
		$is_gallery = 'gallery' == $output_parameters[0];
		$is_pagination = in_array( $output_parameters[0], array( 'previous_page', 'next_page', 'paginate_links' ) ); 
		
		$attachments = self::mla_get_shortcode_attachments( $post->ID, $attr, $is_pagination );
			
		if ( is_string( $attachments ) )
			return $attachments;
			
		if ( empty($attachments) ) {
			if ( self::$mla_debug ) {
				$output = '<p><strong>mla_debug empty gallery</strong>, query = ' . var_export( $attr, true ) . '</p>';
				$output .= self::$mla_debug_messages;
				self::$mla_debug_messages = '';
			}
			else {
				$output =  '';
			}
			
			$output .= $arguments['mla_nolink_text'];
			return $output;
		} // empty $attachments
	
		/*
		 * Look for user-specified alternate gallery shortcode
		 */
		if ( is_string( $arguments['mla_alt_shortcode'] ) ) {
			/*
			 * Replace data-selection parameters with the "ids" list
			 */
			$blacklist = array_merge( $mla_arguments, self::$mla_get_shortcode_attachments_parameters );
			$new_args = '';
			foreach ( $attr as $key => $value ) {
				if ( array_key_exists( $key, $blacklist ) ) {
					continue;
				}
				
				$slashed = addcslashes( $value, chr(0).chr(7).chr(8)."\f\n\r\t\v\"\\\$" );
				if ( ( false !== strpos( $value, ' ' ) ) || ( false !== strpos( $value, '\'' ) ) || ( $slashed != $value ) ) {
					$value = '"' . $slashed . '"';
				}
				
				$new_args .= empty( $new_args ) ? $key . '=' . $value : ' ' . $key . '=' . $value;
			} // foreach $attr
			
			$new_ids = '';
			foreach ( $attachments as $value ) {
				$new_ids .= empty( $new_ids ) ? (string) $value->ID : ',' . $value->ID;
			} // foreach $attachments

			$new_ids = $arguments['mla_alt_ids_name'] . '="' . $new_ids . '"';
			
			if ( self::$mla_debug ) {
				$output = self::$mla_debug_messages;
				self::$mla_debug_messages = '';
			}
			else
				$output = '';
			/*
			 * Execute the alternate gallery shortcode with the new parameters
			 */
			$content = apply_filters( 'mla_gallery_final_content', $content );
			if ( ! empty( $content ) ) {
				return $output . do_shortcode( sprintf( '[%1$s %2$s %3$s]%4$s[/%5$s]', $arguments['mla_alt_shortcode'], $new_ids, $new_args, $content, $arguments['mla_alt_shortcode'] ) );
			} else {
				return $output . do_shortcode( sprintf( '[%1$s %2$s %3$s]', $arguments['mla_alt_shortcode'], $new_ids, $new_args ) );
			}
		} // mla_alt_shortcode

		/*
		 * Look for Photonic-enhanced gallery
		 */
		global $photonic;
		
		if ( is_object( $photonic ) && ! empty( $arguments['style'] ) ) {
			if ( 'default' != strtolower( $arguments['type'] ) ) 
				return '<p><strong>Photonic-enhanced [mla_gallery]</strong> type must be <strong>default</strong>, query = ' . var_export( $attr, true ) . '</p>';

			$images = array();
			foreach ($attachments as $key => $val) {
				$images[$val->ID] = $attachments[$key];
			}
			
			if ( isset( $arguments['pause'] ) && ( 'false' == $arguments['pause'] ) )
				$arguments['pause'] = NULL;

			$output = $photonic->build_gallery( $images, $arguments['style'], $arguments );
			return $output;
		}
		
		$size = $size_class = $arguments['size'];
		if ( 'icon' == strtolower( $size) ) {
			if ( 'checked' == MLAOptions::mla_get_option( MLAOptions::MLA_ENABLE_MLA_ICONS ) )
				$size = array( 64, 64 );
			else
				$size = array( 60, 60 );
				
			$show_icon = true;
		}
		else
			$show_icon = false;
		
		/*
		 * Feeds such as RSS, Atom or RDF do not require styled and formatted output
		 */
		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment )
				$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
			return $output;
		}

		/*
		 * Check for Google File Viewer arguments
		 */
		$arguments['mla_viewer'] = !empty( $arguments['mla_viewer'] ) && ( 'true' == strtolower( $arguments['mla_viewer'] ) );
		if ( $arguments['mla_viewer'] ) {
			$arguments['mla_viewer_extensions'] = array_filter( array_map( 'trim', explode( ',', $arguments['mla_viewer_extensions'] ) ) );
			$arguments['mla_viewer_page'] = absint( $arguments['mla_viewer_page'] );
			$arguments['mla_viewer_width'] = absint( $arguments['mla_viewer_width'] );
		}
			
		// $instance supports multiple galleries in one page/post	
		static $instance = 0;
		$instance++;

		/*
		 * The default MLA style template includes "margin: 1.5%" to put a bit of
		 * minimum space between the columns. "mla_margin" can be used to change
		 * this. "mla_itemwidth" can be used with "columns=0" to achieve a "responsive"
		 * layout.
		 */
		 
		$columns = absint( $arguments['columns'] );
		$margin_string = strtolower( trim( $arguments['mla_margin'] ) );
		
		if ( is_numeric( $margin_string ) && ( 0 != $margin_string) )
			$margin_string .= '%'; // Legacy values are always in percent
		
		if ( '%' == substr( $margin_string, -1 ) )
			$margin_percent = (float) substr( $margin_string, 0, strlen( $margin_string ) - 1 );
		else
			$margin_percent = 0;
		
		$width_string = strtolower( trim( $arguments['mla_itemwidth'] ) );
		if ( 'none' != $width_string ) {
			switch ( $width_string ) {
				case 'exact':
					$margin_percent = 0;
					/* fallthru */
				case 'calculate':
					$width_string = $columns > 0 ? (floor(1000/$columns)/10) - ( 2.0 * $margin_percent ) : 100 - ( 2.0 * $margin_percent );
					/* fallthru */
				default:
					if ( is_numeric( $width_string ) && ( 0 != $width_string) )
						$width_string .= '%'; // Legacy values are always in percent
			}
		} // $use_width
		
		$float = strtolower( $arguments['mla_float'] );
		if ( ! in_array( $float, array( 'left', 'none', 'right' ) ) )
			$float = is_rtl() ? 'right' : 'left';
		
		$style_values = array(
			'mla_style' => $arguments['mla_style'],
			'mla_markup' => $arguments['mla_markup'],
			'instance' => $instance,
			'id' => $post->ID,
			'itemtag' => tag_escape( $arguments['itemtag'] ),
			'icontag' => tag_escape( $arguments['icontag'] ),
			'captiontag' => tag_escape( $arguments['captiontag'] ),
			'columns' => $columns,
			'itemwidth' => $width_string,
			'margin' => $margin_string,
			'float' => $float,
			'selector' => "mla_gallery-{$instance}",
			'size_class' => sanitize_html_class( $size_class )
		);

		$style_template = $gallery_style = '';
		$use_mla_gallery_style = ( 'none' != strtolower( $style_values['mla_style'] ) );
		if ( apply_filters( 'use_mla_gallery_style', $use_mla_gallery_style, $style_values['mla_style'] ) ) {
			$style_template = MLAOptions::mla_fetch_gallery_template( $style_values['mla_style'], 'style' );
			if ( empty( $style_template ) ) {
				$style_values['mla_style'] = $default_arguments['mla_style'];
				$style_template = MLAOptions::mla_fetch_gallery_template( $style_values['mla_style'], 'style' );
				if ( empty( $style_template ) ) {
					$style_values['mla_style'] = 'default';
					$style_template = MLAOptions::mla_fetch_gallery_template( 'default', 'style' );
				}
			}
				
			if ( ! empty ( $style_template ) ) {
				/*
				 * Look for 'query' and 'request' substitution parameters
				 */
				$style_values = MLAData::mla_expand_field_level_parameters( $style_template, $attr, $style_values );

				/*
				 * Clean up the template to resolve width or margin == 'none'
				 */
				if ( 'none' == $margin_string ) {
					$style_values['margin'] = '0';
					$style_template = preg_replace( '/margin:[\s]*\[\+margin\+\][\%]*[\;]*/', '', $style_template );
				}

				if ( 'none' == $width_string ) {
					$style_values['itemwidth'] = 'auto';
					$style_template = preg_replace( '/width:[\s]*\[\+itemwidth\+\][\%]*[\;]*/', '', $style_template );
				}
				
				$style_values = apply_filters( 'mla_gallery_style_values', $style_values );
				$style_template = apply_filters( 'mla_gallery_style_template', $style_template );
				$gallery_style = MLAData::mla_parse_template( $style_template, $style_values );
				$gallery_style = apply_filters( 'mla_gallery_style_parse', $gallery_style, $style_template, $style_values );
				
				/*
				 * Clean up the styles to resolve extra "%" suffixes on width or margin (pre v1.42 values)
				 */
				$preg_pattern = array( '/([margin|width]:[^\%]*)\%\%/', '/([margin|width]:.*)auto\%/', '/([margin|width]:.*)inherit\%/' );
				$preg_replacement = array( '${1}%', '${1}auto', '${1}inherit',  );
				$gallery_style = preg_replace( $preg_pattern, $preg_replacement, $gallery_style );
			} // !empty template
		} // use_mla_gallery_style
		
		$upload_dir = wp_upload_dir();
		$markup_values = $style_values;
		$markup_values['site_url'] = site_url();
		$markup_values['base_url'] = $upload_dir['baseurl'];
		$markup_values['base_dir'] = $upload_dir['basedir'];
		$markup_values['page_ID'] = get_the_ID();
		$markup_values['page_url'] = ( 0 < $markup_values['page_ID'] ) ? get_page_link() : '';

		$open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-open', 'markup' );
		if ( false === $open_template ) {
			$markup_values['mla_markup'] = $default_arguments['mla_markup'];
			$open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-open', 'markup' );
			if ( false === $open_template ) {
				$markup_values['mla_markup'] = 'default';
				$open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-open', 'markup' );
			}
		}
		if ( empty( $open_template ) )
			$open_template = '';

		$row_open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-open', 'markup' );
		if ( empty( $row_open_template ) )
			$row_open_template = '';
				
		$item_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-item', 'markup' );
		if ( empty( $item_template ) )
			$item_template = '';

		$row_close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-close', 'markup' );
		if ( empty( $row_close_template ) )
			$row_close_template = '';
			
		$close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-close', 'markup' );
		if ( empty( $close_template ) )
			$close_template = '';

		/*
		 * Look for gallery-level markup substitution parameters
		 */
		$new_text = $open_template . $row_open_template . $row_close_template . $close_template;
		
		$markup_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $markup_values );

		if ( self::$mla_debug ) {
			$output = self::$mla_debug_messages;
			self::$mla_debug_messages = '';
		}
		else
			$output = '';

		if ($is_gallery ) {
			$markup_values = apply_filters( 'mla_gallery_open_values', $markup_values );

			$open_template = apply_filters( 'mla_gallery_open_template', $open_template );
			if ( empty( $open_template ) )
				$gallery_open = '';
			else
				$gallery_open = MLAData::mla_parse_template( $open_template, $markup_values );

			$gallery_open = apply_filters( 'mla_gallery_open_parse', $gallery_open, $open_template, $markup_values );
			$output .= apply_filters( 'mla_gallery_style', $gallery_style . $gallery_open, $style_values, $markup_values, $style_template, $open_template );
		}
		else {
			if ( ! isset( $attachments['found_rows'] ) )
				$attachments['found_rows'] = 0;
	
			/*
			 * Handle 'previous_page', 'next_page', and 'paginate_links'
			 */
			$pagination_result = self::_process_pagination_output_types( $output_parameters, $markup_values, $arguments, $attr, $attachments['found_rows'], $output );
			if ( false !== $pagination_result )
				return $pagination_result;
				
			unset( $attachments['found_rows'] );
		}
		
		/*
		 * For "previous_link", "current_link" and "next_link", discard all of the $attachments except the appropriate choice
		 */
		if ( ! $is_gallery ) {
			$link_type = $output_parameters[0];
			
			if ( ! in_array( $link_type, array ( 'previous_link', 'current_link', 'next_link' ) ) )
				return ''; // unknown outtput type
			
			$is_wrap = isset( $output_parameters[1] ) && 'wrap' == $output_parameters[1];
			$current_id = empty( $arguments['id'] ) ? $markup_values['id'] : $arguments['id'];
				
			foreach ( $attachments as $id => $attachment ) {
				if ( $attachment->ID == $current_id )
					break;
			}
		
			switch ( $link_type ) {
				case 'previous_link':
					$target_id = $id - 1;
					break;
				case 'next_link':
					$target_id = $id + 1;
					break;
				case 'current_link':
				default:
					$target_id = $id;
			} // link_type
			
			$target = NULL;
			if ( isset( $attachments[ $target_id ] ) ) {
				$target = $attachments[ $target_id ];
			}
			elseif ( $is_wrap ) {
				switch ( $link_type ) {
					case 'previous_link':
						$target = array_pop( $attachments );
						break;
					case 'next_link':
						$target = array_shift( $attachments );
				} // link_type
			} // is_wrap

			if ( isset( $target ) )
				$attachments = array( $target );			
			elseif ( ! empty( $arguments['mla_nolink_text'] ) )
				return self::_process_shortcode_parameter( $arguments['mla_nolink_text'], $markup_values ) . '</a>';
			else
				return '';
		} // ! is_gallery
		else
			$link_type= '';
		
		$column_index = 0;
		foreach ( $attachments as $id => $attachment ) {
			$item_values = $markup_values;
			
			/*
			 * fill in item-specific elements
			 */
			$item_values['index'] = (string) 1 + $column_index;

			$item_values['excerpt'] = wptexturize( $attachment->post_excerpt );
			$item_values['attachment_ID'] = $attachment->ID;
			$item_values['mime_type'] = $attachment->post_mime_type;
			$item_values['menu_order'] = $attachment->menu_order;
			$item_values['date'] = $attachment->post_date;
			$item_values['modified'] = $attachment->post_modified;
			$item_values['parent'] = $attachment->post_parent;
			$item_values['parent_title'] = '(unattached)';
			$item_values['parent_type'] = '';
			$item_values['parent_date'] = '';
			$item_values['title'] = wptexturize( $attachment->post_title );
			$item_values['slug'] = wptexturize( $attachment->post_name );
			$item_values['width'] = '';
			$item_values['height'] = '';
			$item_values['orientation'] = '';
			$item_values['image_meta'] = '';
			$item_values['image_alt'] = '';
			$item_values['base_file'] = '';
			$item_values['path'] = '';
			$item_values['file'] = '';
			$item_values['description'] = wptexturize( $attachment->post_content );
			$item_values['file_url'] = wptexturize( $attachment->guid );
			$item_values['author_id'] = $attachment->post_author;
		
			$user = get_user_by( 'id', $attachment->post_author );
			if ( isset( $user->data->display_name ) )
				$item_values['author'] = wptexturize( $user->data->display_name );
			else
				$item_values['author'] = 'unknown';

			$post_meta = MLAData::mla_fetch_attachment_metadata( $attachment->ID );
			$base_file = $post_meta['mla_wp_attached_file'];
			$sizes = isset( $post_meta['mla_wp_attachment_metadata']['sizes'] ) ? $post_meta['mla_wp_attachment_metadata']['sizes'] : array();

			if ( !empty( $post_meta['mla_wp_attachment_metadata']['width'] ) ) {
				$item_values['width'] = $post_meta['mla_wp_attachment_metadata']['width'];
				$width = absint( $item_values['width'] );
			}
			else
				$width = 0;
				
			if ( !empty( $post_meta['mla_wp_attachment_metadata']['height'] ) ) {
				$item_values['height'] = $post_meta['mla_wp_attachment_metadata']['height'];
				$height = absint( $item_values['height'] );
			}
			else
				$height = 0;
				
			if ( $width && $height )
				$item_values['orientation'] = ( $height > $width ) ? 'portrait' : 'landscape';

			if ( !empty( $post_meta['mla_wp_attachment_metadata']['image_meta'] ) )
				$item_values['image_meta'] = wptexturize( var_export( $post_meta['mla_wp_attachment_metadata']['image_meta'], true ) );
			if ( !empty( $post_meta['mla_wp_attachment_image_alt'] ) )
				$item_values['image_alt'] = wptexturize( $post_meta['mla_wp_attachment_image_alt'] );

			if ( ! empty( $base_file ) ) {
				$last_slash = strrpos( $base_file, '/' );
				if ( false === $last_slash ) {
					$file_name = $base_file;
					$item_values['base_file'] = wptexturize( $base_file );
					$item_values['file'] = wptexturize( $base_file );
				}
				else {
					$file_name = substr( $base_file, $last_slash + 1 );
					$item_values['base_file'] = wptexturize( $base_file );
					$item_values['path'] = wptexturize( substr( $base_file, 0, $last_slash + 1 ) );
					$item_values['file'] = wptexturize( $file_name );
				}
			}
			else
				$file_name = '';

			$parent_info = MLAData::mla_fetch_attachment_parent_data( $attachment->post_parent );
			if ( isset( $parent_info['parent_title'] ) )
				$item_values['parent_title'] = wptexturize( $parent_info['parent_title'] );
				
			if ( isset( $parent_info['parent_date'] ) )
				$item_values['parent_date'] = wptexturize( $parent_info['parent_date'] );
				
			if ( isset( $parent_info['parent_type'] ) )
				$item_values['parent_type'] = wptexturize( $parent_info['parent_type'] );
				
			/*
			 * Add attachment-specific field-level substitution parameters
			 */
			$new_text = isset( $item_template ) ? $item_template : '';
			foreach( $mla_item_specific_arguments as $index => $value ) {
				$new_text .= str_replace( '{+', '[+', str_replace( '+}', '+]', $arguments[ $index ] ) );
			}
			
			$item_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $item_values, $attachment->ID );

			if ( $item_values['captiontag'] ) {
				$item_values['caption'] = wptexturize( $attachment->post_excerpt );
				if ( ! empty( $arguments['mla_caption'] ) )
					$item_values['caption'] = wptexturize( self::_process_shortcode_parameter( $arguments['mla_caption'], $item_values ) );
			}
			else
				$item_values['caption'] = '';
			
			if ( ! empty( $arguments['mla_link_text'] ) )
				$link_text = self::_process_shortcode_parameter( $arguments['mla_link_text'], $item_values );
			else
				$link_text = false;

			$item_values['pagelink'] = wp_get_attachment_link($attachment->ID, $size, true, $show_icon, $link_text);
			$item_values['filelink'] = wp_get_attachment_link($attachment->ID, $size, false, $show_icon, $link_text);

			/*
			 * Apply the Gallery Display Content parameters.
			 * Note that $link_attributes and $rollover_text
			 * are used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_target'] ) )
				$link_attributes = 'target="' . $arguments['mla_target'] . '" ';
			else
				$link_attributes = '';
				
			if ( ! empty( $arguments['mla_link_attributes'] ) )
				$link_attributes .= self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $item_values ) . ' ';

			if ( ! empty( $arguments['mla_link_class'] ) )
				$link_attributes .= 'class="' . self::_process_shortcode_parameter( $arguments['mla_link_class'], $item_values ) . '" ';

			if ( ! empty( $link_attributes ) ) {
				$item_values['pagelink'] = str_replace( '<a href=', '<a ' . $link_attributes . 'href=', $item_values['pagelink'] );
				$item_values['filelink'] = str_replace( '<a href=', '<a ' . $link_attributes . 'href=', $item_values['filelink'] );
			}
			
			if ( ! empty( $arguments['mla_rollover_text'] ) ) {
				$rollover_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $item_values ) );
				
				/*
				 * Replace single- and double-quote delimited values
				 */
				$item_values['pagelink'] = preg_replace('# title=\'([^\']*)\'#', " title='{$rollover_text}'", $item_values['pagelink'] );
				$item_values['pagelink'] = preg_replace('# title=\"([^\"]*)\"#', " title=\"{$rollover_text}\"", $item_values['pagelink'] );
				$item_values['filelink'] = preg_replace('# title=\'([^\']*)\'#', " title='{$rollover_text}'", $item_values['filelink'] );
				$item_values['filelink'] = preg_replace('# title=\"([^\"]*)\"#', " title=\"{$rollover_text}\"", $item_values['filelink'] );
			}
			else
				$rollover_text = $item_values['title'];

			/*
			 * Process the <img> tag, if present
			 * Note that $image_attributes, $image_class and $image_alt
			 * are used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_image_attributes'] ) )
				$image_attributes = self::_process_shortcode_parameter( $arguments['mla_image_attributes'], $item_values ) . ' ';
			else
				$image_attributes = '';
				
			if ( ! empty( $arguments['mla_image_class'] ) )
				$image_class = esc_attr( self::_process_shortcode_parameter( $arguments['mla_image_class'], $item_values ) );
			else
				$image_class = '';

				if ( ! empty( $arguments['mla_image_alt'] ) )
					$image_alt = esc_attr( self::_process_shortcode_parameter( $arguments['mla_image_alt'], $item_values ) );
				else
					$image_alt = '';

			if ( false !== strpos( $item_values['pagelink'], '<img ' ) ) {
				if ( ! empty( $image_attributes ) ) {
					$item_values['pagelink'] = str_replace( '<img ', '<img ' . $image_attributes, $item_values['pagelink'] );
					$item_values['filelink'] = str_replace( '<img ', '<img ' . $image_attributes, $item_values['filelink'] );
				}
				
				/*
				 * Extract existing class values and add to them
				 */
				if ( ! empty( $image_class ) ) {
					$match_count = preg_match_all( '# class=\"([^\"]+)\" #', $item_values['pagelink'], $matches, PREG_OFFSET_CAPTURE );
					if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
						$class = $matches[1][0][0] . ' ' . $image_class;
					}
					else
						$class = $image_class;
					
					$item_values['pagelink'] = preg_replace('# class=\"([^\"]*)\"#', " class=\"{$class}\"", $item_values['pagelink'] );
					$item_values['filelink'] = preg_replace('# class=\"([^\"]*)\"#', " class=\"{$class}\"", $item_values['filelink'] );
				}
				
				if ( ! empty( $image_alt ) ) {
					$item_values['pagelink'] = preg_replace('# alt=\"([^\"]*)\"#', " alt=\"{$image_alt}\"", $item_values['pagelink'] );
					$item_values['filelink'] = preg_replace('# alt=\"([^\"]*)\"#', " alt=\"{$image_alt}\"", $item_values['filelink'] );
				}
			} // process <img> tag
			
			switch ( $arguments['link'] ) {
				case 'permalink':
				case 'post':
					$item_values['link'] = $item_values['pagelink'];
					break;
				case 'file':
				case 'full':
					$item_values['link'] = $item_values['filelink'];
					break;
				default:
					$item_values['link'] = $item_values['filelink'];

					/*
					 * Check for link to specific (registered) file size
					 */
					if ( array_key_exists( $arguments['link'], $sizes ) ) {
						$target_file = $sizes[ $arguments['link'] ]['file'];
						$item_values['link'] = str_replace( $file_name, $target_file, $item_values['filelink'] );
					}
			} // switch 'link'
			
			/*
			 * Extract target and thumbnail fields
			 */
			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['pagelink'], $matches, PREG_OFFSET_CAPTURE );
 			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['pagelink_url'] = $matches[1][0][0];
			}
			else
				$item_values['pagelink_url'] = '';

			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['filelink'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['filelink_url'] = $matches[1][0][0];
			}
			else
				$item_values['filelink_url'] = '';

			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['link_url'] = $matches[1][0][0];
			}
			else
				$item_values['link_url'] = '';

			/*
			 * Override the link value; leave filelink and pagelink unchanged
			 * Note that $link_href is used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_link_href'] ) ) {
				$link_href = self::_process_shortcode_parameter( $arguments['mla_link_href'], $item_values );

				/*
				 * Replace single- and double-quote delimited values
				 */
				$item_values['link'] = preg_replace('# href=\'([^\']*)\'#', " href='{$link_href}'", $item_values['link'] );
				$item_values['link'] = preg_replace('# href=\"([^\"]*)\"#', " href=\"{$link_href}\"", $item_values['link'] );
			}
			else
				$link_href = '';
			
			$match_count = preg_match_all( '#\<a [^\>]+\>(.*)\</a\>#', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['thumbnail_content'] = $matches[1][0][0];
			}
			else
				$item_values['thumbnail_content'] = '';

			$match_count = preg_match_all( '# width=\"([^\"]+)\" height=\"([^\"]+)\" src=\"([^\"]+)\" #', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['thumbnail_width'] = $matches[1][0][0];
				$item_values['thumbnail_height'] = $matches[2][0][0];
				$item_values['thumbnail_url'] = $matches[3][0][0];
			}
			else {
				$item_values['thumbnail_width'] = '';
				$item_values['thumbnail_height'] = '';
				$item_values['thumbnail_url'] = '';
			}

			/*
			 * Now that we have thumbnail_content we can check for 'span' and 'none'
			 */
			if ( 'none' == $arguments['link'] )
				$item_values['link'] = $item_values['thumbnail_content'];
			elseif ( 'span' == $arguments['link'] )
				$item_values['link'] = sprintf( '<span %1$s>%2$s</span>', $link_attributes, $item_values['thumbnail_content'] );
				
			/*
			 * Check for Google file viewer substitution, uses above-defined
			 * $link_attributes (includes target), $rollover_text, $link_href (link only),
			 * $image_attributes, $image_class, $image_alt
			 */
			if ( $arguments['mla_viewer'] && empty( $item_values['thumbnail_url'] ) ) {
				$last_dot = strrpos( $item_values['file'], '.' );
				if ( !( false === $last_dot) ) {
					$extension = substr( $item_values['file'], $last_dot + 1 );
					if ( in_array( $extension, $arguments['mla_viewer_extensions'] ) ) {
						/*
						 * <img> tag (thumbnail_text)
						 */
						if ( ! empty( $image_class ) )
							$image_class = ' class="' . $image_class . '"';
							
						if ( ! empty( $image_alt ) )
							$image_alt = ' alt="' . $image_alt . '"';
						elseif ( ! empty( $item_values['caption'] ) )
							$image_alt = ' alt="' . $item_values['caption'] . '"';

						$item_values['thumbnail_content'] = sprintf( '<img %1$ssrc="http://docs.google.com/viewer?url=%2$s&a=bi&pagenumber=%3$d&w=%4$d"%5$s%6$s>', $image_attributes, $item_values['filelink_url'], $arguments['mla_viewer_page'], $arguments['mla_viewer_width'], $image_class, $image_alt );
						
						/*
						 * Filelink, pagelink and link
						 */
						$item_values['pagelink'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $item_values['pagelink_url'], $rollover_text, $item_values['thumbnail_content'] );
						$item_values['filelink'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $item_values['filelink_url'], $rollover_text, $item_values['thumbnail_content'] );

						if ( ! empty( $link_href ) )
							$item_values['link'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $link_href, $rollover_text, $item_values['thumbnail_content'] );
						elseif ( 'permalink' == $arguments['link'] )
							$item_values['link'] = $item_values['pagelink'];
						elseif ( 'file' == $arguments['link'] )
							$item_values['link'] = $item_values['filelink'];
						elseif ( 'span' == $arguments['link'] )
							$item_values['link'] = sprintf( '<a %1$s>%2$s</a>', $link_attributes, $item_values['thumbnail_content'] );
						else
							$item_values['link'] = $item_values['thumbnail_content'];
					} // viewer extension
				} // has extension
			} // mla_viewer
			
			if ($is_gallery ) {
				/*
				 * Start of row markup
				 */
				if ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) {
					$markup_values = apply_filters( 'mla_gallery_row_open_values', $markup_values );
					$row_open_template = apply_filters( 'mla_gallery_row_open_template', $row_open_template );
					$parse_value = MLAData::mla_parse_template( $row_open_template, $markup_values );
					$output .= apply_filters( 'mla_gallery_row_open_parse', $parse_value, $row_open_template, $markup_values );
				}
				
				/*
				 * item markup
				 */
				$column_index++;
				if ( $item_values['columns'] > 0 && $column_index % $item_values['columns'] == 0 )
					$item_values['last_in_row'] = 'last_in_row';
				else
					$item_values['last_in_row'] = '';
				
				$item_values = apply_filters( 'mla_gallery_item_values', $item_values );
				$item_template = apply_filters( 'mla_gallery_item_template', $item_template );
				$parse_value = MLAData::mla_parse_template( $item_template, $item_values );
				$output .= apply_filters( 'mla_gallery_item_parse', $parse_value, $item_template, $item_values );
	
				/*
				 * End of row markup
				 */
				if ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) {
					$markup_values = apply_filters( 'mla_gallery_row_close_values', $markup_values );
					$row_close_template = apply_filters( 'mla_gallery_row_close_template', $row_close_template );
					$parse_value = MLAData::mla_parse_template( $row_close_template, $markup_values );
					$output .= apply_filters( 'mla_gallery_row_close_parse', $parse_value, $row_close_template, $markup_values );
				}
			} // is_gallery
			elseif ( ! empty( $link_type ) )
				return $item_values['link'];
		} // foreach attachment
	
		if ($is_gallery ) {
			/*
			 * Close out partial row
			 */
			if ( ! ($markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) ) {
				$markup_values = apply_filters( 'mla_gallery_row_close_values', $markup_values );
				$row_close_template = apply_filters( 'mla_gallery_row_close_template', $row_close_template );
				$parse_value = MLAData::mla_parse_template( $row_close_template, $markup_values );
				$output .= apply_filters( 'mla_gallery_row_close_parse', $parse_value, $row_close_template, $markup_values );
			}
				
			$markup_values = apply_filters( 'mla_gallery_close_values', $markup_values );
			$close_template = apply_filters( 'mla_gallery_close_template', $close_template );
			$parse_value = MLAData::mla_parse_template( $close_template, $markup_values );
			$output .= apply_filters( 'mla_gallery_close_parse', $parse_value, $close_template, $markup_values );
		} // is_gallery
	
		return $output;
	}

	/**
	 * The MLA Tag Cloud support function.
	 *
	 * This is an alternative to the WordPress wp_tag_cloud function, with additional
	 * options to customize the hyperlink behind each term.
	 *
	 * @since 1.60
	 *
	 * @param array $attr Attributes of the shortcode.
	 *
	 * @return string HTML content to display the tag cloud.
	 */
	public static function mla_tag_cloud( $attr ) {
		/*
		 * These are the default parameters for tag cloud display
		 */
		$mla_item_specific_arguments = array(
			'mla_link_attributes' => '',
			'mla_link_class' => '',
			'mla_link_style' => '',
			'mla_link_href' => '',
			'mla_link_text' => '',
			'mla_nolink_text' => '',
			'mla_rollover_text' => '',
			'mla_caption' => ''
		);
		
		$mla_arguments = array_merge( array(
			'mla_output' => 'flat',
			'mla_style' => NULL,
			'mla_markup' => NULL,
			'mla_float' => is_rtl() ? 'right' : 'left',
			'mla_itemwidth' => MLAOptions::mla_get_option('mla_tag_cloud_itemwidth'),
			'mla_margin' => MLAOptions::mla_get_option('mla_tag_cloud_margin'),
			'mla_target' => '',
			'mla_debug' => false,

			// Pagination parameters
			'term_id' => NULL,
			'mla_end_size'=> 1,
			'mla_mid_size' => 2,
			'mla_prev_text' => '&laquo; Previous',
			'mla_next_text' => 'Next &raquo;',
			'mla_page_parameter' => 'mla_cloud_current',
			'mla_cloud_current' => NULL,
			'mla_paginate_total' => NULL,
			'mla_paginate_type' => 'plain'),
			$mla_item_specific_arguments
		);
		
		$defaults = array_merge(
			self::$mla_get_terms_parameters,
			array(
			'smallest' => 8,
			'largest' => 22,
			'unit' => 'pt',
			'separator' => "\n",
			'single_text' => '%d item',
			'multiple_text' => '%d items',

			'echo' => false,
			'link' => 'view',
			
			'itemtag' => 'ul',
			'termtag' => 'li',
			'captiontag' => '',
			'columns' => MLAOptions::mla_get_option('mla_tag_cloud_columns')
			),
			$mla_arguments
		);
		
		/*
		 * The mla_paginate_current parameter can be changed to support multiple galleries per page.
		 */
		if ( ! isset( $attr['mla_page_parameter'] ) )
			$attr['mla_page_parameter'] = $defaults['mla_page_parameter'];
			
		$mla_page_parameter = $attr['mla_page_parameter'];
		 
		/*
		 * Special handling of mla_page_parameter to make
		 * "MLA pagination" easier. Look for this parameter in $_REQUEST
		 * if it's not present in the shortcode itself.
		 */
		if ( ! isset( $attr[ $mla_page_parameter ] ) )
			if ( isset( $_REQUEST[ $mla_page_parameter ] ) )
				$attr[ $mla_page_parameter ] = $_REQUEST[ $mla_page_parameter ];
		 
		/*
		 * Look for 'request' substitution parameters,
		 * which can be added to any input parameter
		 */
		foreach ( $attr as $attr_key => $attr_value ) {
			/*
			 * item-specific Display Content parameters must be evaluated
			 * later, when all of the information is available.
			 */
			if ( array_key_exists( $attr_key, $mla_item_specific_arguments ) )
				continue;
				
			$attr_value = str_replace( '{+', '[+', str_replace( '+}', '+]', $attr_value ) );
			$replacement_values = MLAData::mla_expand_field_level_parameters( $attr_value );

			if ( ! empty( $replacement_values ) )
				$attr[ $attr_key ] = MLAData::mla_parse_template( $attr_value, $replacement_values );
		}
		
		$attr = apply_filters( 'mla_tag_cloud_attributes', $attr );
		$arguments = shortcode_atts( $defaults, $attr );

		/*
		 * $mla_page_parameter, if non-default, doesn't make it through the shortcode_atts filter,
		 * so we handle it separately
		 */
		if ( ! isset( $arguments[ $mla_page_parameter ] ) )
			if ( isset( $attr[ $mla_page_parameter ] ) )
				$arguments[ $mla_page_parameter ] = $attr[ $mla_page_parameter ];
			else
				$arguments[ $mla_page_parameter ] = $defaults['mla_cloud_current'];

		/*
		 * Process the pagination parameter, if present
		 */
		if ( isset( $arguments[ $mla_page_parameter ] ) ) {
			$arguments['offset'] = $arguments['limit'] * ( $arguments[ $mla_page_parameter ] - 1);
		}

		$arguments = apply_filters( 'mla_tag_cloud_arguments', $arguments );

		self::$mla_debug = !empty( $arguments['mla_debug'] ) && ( 'true' == strtolower( $arguments['mla_debug'] ) );
		if ( self::$mla_debug ) {
			self::$mla_debug_messages .= '<p><strong>mla_debug attributes</strong> = ' . var_export( $attr, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug arguments</strong> = ' . var_export( $arguments, true ) . '</p>';
		}

		/*
		 * Determine output type and templates
		 */
		$output_parameters = array_map( 'strtolower', array_map( 'trim', explode( ',', $arguments['mla_output'] ) ) );
		
		if ( $is_grid = 'grid' == $output_parameters[0] ) {
			$default_style = MLAOptions::mla_get_option('default_tag_cloud_style');
			$default_markup = MLAOptions::mla_get_option('default_tag_cloud_markup');
			
			if ( NULL == $arguments['mla_style'] )
				$arguments['mla_style'] = $default_style;
				
			if ( NULL == $arguments['mla_markup'] ) {
				$arguments['mla_markup'] = $default_markup;
				$arguments['itemtag'] = 'dl';
				$arguments['termtag'] = 'dt';
				$arguments['captiontag'] = 'dd';
			}
		}
	
		if ( $is_list = 'list' == $output_parameters[0] ) {
			$default_style = 'none';
			if ( empty( $arguments['captiontag'] ) ) {
				$default_markup = 'tag-cloud-ul';
			} else {
				$default_markup = 'tag-cloud-dl';
				
				if ( 'dd' == $arguments['captiontag'] ) {
					$arguments['itemtag'] = 'dl';
					$arguments['termtag'] = 'dt';
				}
			}
			
			if ( NULL == $arguments['mla_style'] )
				$arguments['mla_style'] = $default_style;
				
			if ( NULL == $arguments['mla_markup'] )
				$arguments['mla_markup'] = $default_markup;
		}
		
		$is_pagination = in_array( $output_parameters[0], array( 'previous_link', 'current_link', 'next_link', 'previous_page', 'next_page', 'paginate_links' ) ); 
		
		/*
		 * Convert taxonomy list to an array
		 */
		if ( is_string( $arguments['taxonomy'] ) )
			$arguments['taxonomy'] = explode( ',', $arguments['taxonomy'] );

		$tags = self::mla_get_terms( $arguments );
		
		if ( self::$mla_debug ) {
			$cloud = self::$mla_debug_messages;
			self::$mla_debug_messages = '';
		}
		else
			$cloud = '';

		/*
		 * Invalid taxonomy names return WP_Error
		 */
		if ( is_wp_error( $tags ) ) {
			$cloud .=  '<strong>ERROR: ' . $tags->get_error_message() . '</strong>, ' . $tags->get_error_data( $tags->get_error_code() );

			if ( 'array' == $arguments['mla_output'] )
				return array( $cloud );
	
			if ( empty($arguments['echo']) )
				return $cloud;
	
			echo $cloud;
			return;
		}
	
		if ( empty( $tags ) ) {
			if ( self::$mla_debug ) {
				$cloud .= '<p><strong>mla_debug empty cloud</strong>, query = ' . var_export( $arguments, true ) . '</p>';
			}
			
			$cloud .= $arguments['mla_nolink_text'];
			if ( 'array' == $arguments['mla_output'] )
				return array( $cloud );
	
			if ( empty($arguments['echo']) )
				return $cloud;
	
			echo $cloud;
			return;
		}
	
		/*
		 * Fill in the item_specific link properties, calculate cloud parameters
		 */
		if( isset( $tags['found_rows'] ) ) {
			$found_rows = $tags['found_rows'];
			unset( $tags['found_rows'] );
		} else
			$found_rows = count( $tags );
			
		$min_count = 0x7FFFFFFF;
		$max_count = 0;
		$min_scaled_count = 0x7FFFFFFF;
		$max_scaled_count = 0;
		foreach ( $tags as $key => $tag ) {
			$tag->scaled_count = apply_filters( 'mla_tag_cloud_scale', round(log10($tag->count + 1) * 100), $attr, $arguments, $tag );
			
			if ( $tag->count < $min_count )
				$min_count = $tag->count;
			if ( $tag->count > $max_count )
				$max_count = $tag->count;
			
			if ( $tag->scaled_count < $min_scaled_count )
				$min_scaled_count = $tag->scaled_count;
			if ( $tag->scaled_count > $max_scaled_count )
				$max_scaled_count = $tag->scaled_count;
			
			$link = get_edit_tag_link( $tag->term_id, $tag->taxonomy );
			if ( ! is_wp_error( $link ) ) {
				$tags[ $key ]->edit_link = $link;
				$link = get_term_link( intval($tag->term_id), $tag->taxonomy );
				$tags[ $key ]->term_link = $link;
			}

			if ( is_wp_error( $link ) ) {
				$cloud =  '<strong>ERROR: ' . $link->get_error_message() . '</strong>, ' . $link->get_error_data( $link->get_error_code() );
	
			if ( 'array' == $arguments['mla_output'] )
				return array( $cloud );
	
			if ( empty($arguments['echo']) )
				return $cloud;
	
			echo $cloud;
			return;
			}

			if ( 'edit' == $arguments['link'] )
				$tags[ $key ]->link = $tags[ $key ]->edit_link;
			else
				$tags[ $key ]->link = $tags[ $key ]->term_link;
		} // foreach tag

		// $instance supports multiple clouds in one page/post	
		static $instance = 0;
		$instance++;

		/*
		 * The default MLA style template includes "margin: 1.5%" to put a bit of
		 * minimum space between the columns. "mla_margin" can be used to change
		 * this. "mla_itemwidth" can be used with "columns=0" to achieve a
		 * "responsive" layout.
		 */
		 
		$columns = absint( $arguments['columns'] );
		$margin_string = strtolower( trim( $arguments['mla_margin'] ) );
		
		if ( is_numeric( $margin_string ) && ( 0 != $margin_string) )
			$margin_string .= '%'; // Legacy values are always in percent
		
		if ( '%' == substr( $margin_string, -1 ) )
			$margin_percent = (float) substr( $margin_string, 0, strlen( $margin_string ) - 1 );
		else
			$margin_percent = 0;
		
		$width_string = strtolower( trim( $arguments['mla_itemwidth'] ) );
		if ( 'none' != $width_string ) {
			switch ( $width_string ) {
				case 'exact':
					$margin_percent = 0;
					/* fallthru */
				case 'calculate':
					$width_string = $columns > 0 ? (floor(1000/$columns)/10) - ( 2.0 * $margin_percent ) : 100 - ( 2.0 * $margin_percent );
					/* fallthru */
				default:
					if ( is_numeric( $width_string ) && ( 0 != $width_string) )
						$width_string .= '%'; // Legacy values are always in percent
			}
		} // $use_width
		
		$float = strtolower( $arguments['mla_float'] );
		if ( ! in_array( $float, array( 'left', 'none', 'right' ) ) )
			$float = is_rtl() ? 'right' : 'left';

		/*
		 * Calculate cloud parameters
		 */
		$spread = $max_scaled_count - $min_scaled_count;
		if ( $spread <= 0 )
			$spread = 1;
		$font_spread = $arguments['largest'] - $arguments['smallest'];
		if ( $font_spread < 0 )
			$font_spread = 1;
		$font_step = $font_spread / $spread;

		$style_values = array(
			'mla_output' => $arguments['mla_output'],
			'mla_style' => $arguments['mla_style'],
			'mla_markup' => $arguments['mla_markup'],
			'instance' => $instance,
			'taxonomy' => implode( '-', $arguments['taxonomy'] ),
			'itemtag' => tag_escape( $arguments['itemtag'] ),
			'termtag' => tag_escape( $arguments['termtag'] ),
			'captiontag' => tag_escape( $arguments['captiontag'] ),
			'columns' => $columns,
			'itemwidth' => $width_string,
			'margin' => $margin_string,
			'float' => $float,
			'selector' => "mla_tag_cloud-{$instance}",
			'found_rows' => $found_rows,
			'min_count' => $min_count,
			'max_count' => $max_count,
			'min_scaled_count' => $min_scaled_count,
			'max_scaled_count' => $max_scaled_count,
			'spread' => $spread,
			'smallest' => $arguments['smallest'],
			'largest' => $arguments['largest'],
			'unit' => $arguments['unit'],
			'font_spread' => $font_spread,
			'font_step' => $font_step,
			'separator' => $arguments['separator'],
			'single_text' => $arguments['single_text'],
			'multiple_text' => $arguments['multiple_text'],
			'echo' => $arguments['echo'],
			'link' => $arguments['link']
		);

		$style_template = $gallery_style = '';
		$use_mla_tag_cloud_style = ( $is_grid || $is_list ) && ( 'none' != strtolower( $style_values['mla_style'] ) );
		if ( apply_filters( 'use_mla_tag_cloud_style', $use_mla_tag_cloud_style, $style_values['mla_style'] ) ) {
			$style_template = MLAOptions::mla_fetch_gallery_template( $style_values['mla_style'], 'style' );
			if ( empty( $style_template ) ) {
				$style_values['mla_style'] = $default_style;
				$style_template = MLAOptions::mla_fetch_gallery_template( $default_style, 'style' );
			}
				
			if ( ! empty ( $style_template ) ) {
				/*
				 * Look for 'query' and 'request' substitution parameters
				 */
				$style_values = MLAData::mla_expand_field_level_parameters( $style_template, $attr, $style_values );

				/*
				 * Clean up the template to resolve width or margin == 'none'
				 */
				if ( 'none' == $margin_string ) {
					$style_values['margin'] = '0';
					$style_template = preg_replace( '/margin:[\s]*\[\+margin\+\][\%]*[\;]*/', '', $style_template );
				}

				if ( 'none' == $width_string ) {
					$style_values['itemwidth'] = 'auto';
					$style_template = preg_replace( '/width:[\s]*\[\+itemwidth\+\][\%]*[\;]*/', '', $style_template );
				}
				
				$style_values = apply_filters( 'mla_tag_cloud_style_values', $style_values );
				$style_template = apply_filters( 'mla_tag_cloud_style_template', $style_template );
				$gallery_style = MLAData::mla_parse_template( $style_template, $style_values );
				$gallery_style = apply_filters( 'mla_tag_cloud_style_parse', $gallery_style, $style_template, $style_values );
			} // !empty template
		} // use_mla_tag_cloud_style
		
		$upload_dir = wp_upload_dir();
		$markup_values = $style_values;
		$markup_values['site_url'] = site_url();
		$markup_values['page_ID'] = get_the_ID();
		$markup_values['page_url'] = ( 0 < $markup_values['page_ID'] ) ? get_page_link() : '';

		if ( $is_grid || $is_list ) {
			$open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-open', 'markup' );
			if ( false === $open_template ) {
				$markup_values['mla_markup'] = $default_markup;
				$open_template = MLAOptions::mla_fetch_gallery_template( $default_markup, 'markup' );
			}
			if ( empty( $open_template ) )
				$open_template = '';
	
			if ( $is_grid ) {
				$row_open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-open', 'markup' );
				if ( empty( $row_open_template ) )
					$row_open_template = '';
			}
			else
				$row_open_template = '';
					
			$item_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-item', 'markup' );
			if ( empty( $item_template ) )
				$item_template = '';
	
			if ( $is_grid ) {
				$row_close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-close', 'markup' );
				if ( empty( $row_close_template ) )
					$row_close_template = '';
			}
			else
				$row_close_template = '';
				
			$close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-close', 'markup' );
			if ( empty( $close_template ) )
				$close_template = '';
	
			/*
			 * Look for gallery-level markup substitution parameters
			 */
			$new_text = $open_template . $row_open_template . $row_close_template . $close_template;
			$markup_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $markup_values );

			$markup_values = apply_filters( 'mla_tag_cloud_open_values', $markup_values );
			$open_template = apply_filters( 'mla_tag_cloud_open_template', $open_template );
			if ( empty( $open_template ) )
				$gallery_open = '';
			else
				$gallery_open = MLAData::mla_parse_template( $open_template, $markup_values );

			$gallery_open = apply_filters( 'mla_tag_cloud_open_parse', $gallery_open, $open_template, $markup_values );
			$cloud .= $gallery_style . $gallery_open;
		} // is_grid || is_list
		elseif ( $is_pagination ) {
			/*
			 * Handle 'previous_page', 'next_page', and 'paginate_links'
			 */
			if ( isset( $attr['limit'] ) )
				$attr['posts_per_page'] = $attr['limit'];
				
			$pagination_result = self::_process_pagination_output_types( $output_parameters, $markup_values, $arguments, $attr, $found_rows, $output );
			if ( false !== $pagination_result )
				return $pagination_result;

			/*
			 * For "previous_link", "current_link" and "next_link", discard all of the $tags except the appropriate choice
			 */
			$link_type = $output_parameters[0];
			
			if ( ! in_array( $link_type, array ( 'previous_link', 'current_link', 'next_link' ) ) )
				return ''; // unknown outtput type
			
			$is_wrap = isset( $output_parameters[1] ) && 'wrap' == $output_parameters[1];
			if ( empty( $arguments['term_id'] ) ) {
				$target_id = -2; // won't match anything
			}
			else {
				$current_id = $arguments['term_id'];
					
				foreach ( $tags as $id => $tag ) {
					if ( $tag->term_id == $current_id )
						break;
				}
			
				switch ( $link_type ) {
					case 'previous_link':
						$target_id = $id - 1;
						break;
					case 'next_link':
						$target_id = $id + 1;
						break;
					case 'current_link':
					default:
						$target_id = $id;
				} // link_type
			}
			
			$target = NULL;
			if ( isset( $tags[ $target_id ] ) ) {
				$target = $tags[ $target_id ];
			}
			elseif ( $is_wrap ) {
				switch ( $link_type ) {
					case 'previous_link':
						$target = array_pop( $tags );
						break;
					case 'next_link':
						$target = array_shift( $tags );
				} // link_type
			} // is_wrap
			
			if ( isset( $target ) ) 
				$tags = array( $target );
			elseif ( ! empty( $arguments['mla_nolink_text'] ) )
				return self::_process_shortcode_parameter( $arguments['mla_nolink_text'], $markup_values ) . '</a>';
			else
				return '';
		} // is_pagination
		
		/*
		 * Accumulate links for flat and array output
		 */
		$tag_links = array();

		$column_index = 0;
		foreach ( $tags as $key => $tag ) {
			$item_values = $markup_values;
			
			/*
			 * fill in item-specific elements
			 */
			$item_values['index'] = (string) 1 + $column_index;
			if ( $item_values['columns'] > 0 && ( 1 + $column_index ) % $item_values['columns'] == 0 )
				$item_values['last_in_row'] = 'last_in_row';
			else
				$item_values['last_in_row'] = '';

			$item_values['key'] = $key;
			$item_values['term_id'] = $tag->term_id;
			$item_values['name'] = wptexturize( $tag->name );
			$item_values['slug'] = wptexturize( $tag->slug );
			$item_values['term_group'] = $tag->term_group;
			$item_values['term_taxonomy_id'] = $tag->term_taxonomy_id;
			$item_values['taxonomy'] = wptexturize( $tag->taxonomy );
			$item_values['description'] = wptexturize( $tag->description );
			$item_values['parent'] = $tag->parent;
			$item_values['count'] = $tag->count;
			$item_values['scaled_count'] = $tag->scaled_count;
			$item_values['font_size'] = str_replace( ',', '.', ( $item_values['smallest'] + ( ( $item_values['scaled_count'] - $item_values['min_scaled_count'] ) * $item_values['font_step'] ) ) );
			$item_values['link_url'] = $tag->link;
			$item_values['editlink_url'] = $tag->edit_link;
			$item_values['termlink_url'] = $tag->term_link;
			// Added in the code below:
			// 'caption', 'link_attributes', 'rollover_text', 'link_style', 'link_text', 'editlink', 'termlink', 'thelink'
			
			/*
			 * Add item_specific field-level substitution parameters
			 */
			$new_text = isset( $item_template ) ? $item_template : '';
			foreach( $mla_item_specific_arguments as $index => $value ) {
				$new_text .= str_replace( '{+', '[+', str_replace( '+}', '+]', $arguments[ $index ] ) );
			}
			
			$item_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $item_values );

			if ( $item_values['captiontag'] ) {
				$item_values['caption'] = wptexturize( $tag->description );
				if ( ! empty( $arguments['mla_caption'] ) )
					$item_values['caption'] = wptexturize( self::_process_shortcode_parameter( $arguments['mla_caption'], $item_values ) );
			}
			else
				$item_values['caption'] = '';
			
			if ( ! empty( $arguments['mla_link_text'] ) )
				$link_text = self::_process_shortcode_parameter( $arguments['mla_link_text'], $item_values );
			else
				$link_text = false;

			/*
			 * Apply the Display Content parameters.
			 */
			if ( ! empty( $arguments['mla_target'] ) )
				$link_attributes = 'target="' . $arguments['mla_target'] . '" ';
			else
				$link_attributes = '';
				
			if ( ! empty( $arguments['mla_link_attributes'] ) )
				$link_attributes .= self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $item_values ) . ' ';

			if ( ! empty( $arguments['mla_link_class'] ) )
				$link_attributes .= 'class="' . self::_process_shortcode_parameter( $arguments['mla_link_class'], $item_values ) . '" ';

			$item_values['link_attributes'] = $link_attributes;
			
			$item_values['rollover_text'] = sprintf( _n( $item_values['single_text'], $item_values['multiple_text'], $item_values['count'] ), number_format_i18n( $item_values['count'] ) );
			if ( ! empty( $arguments['mla_rollover_text'] ) ) {
				$item_values['rollover_text'] = esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $item_values ) );
			}

			if ( ! empty( $arguments['mla_link_href'] ) ) {
				$link_href = self::_process_shortcode_parameter( $arguments['mla_link_href'], $item_values );
				$item_values['link_url'] = $link_href;
			}
			else
				$link_href = '';

			if ( ! empty( $arguments['mla_link_style'] ) ) {
				$item_values['link_style'] = esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_style'], $item_values ) );
			}
			else
				$item_values['link_style'] = 'font-size: ' . $item_values['font_size'] . $item_values['unit'];

			if ( ! empty( $arguments['mla_link_text'] ) ) {
				$item_values['link_text'] = esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_text'], $item_values ) );
			}
			else
				$item_values['link_text'] = $item_values['name'];

			/*
			 * Editlink, termlink and thelink
			 */
			$item_values['editlink'] = sprintf( '<a %1$shref="%2$s" title="%3$s" style="%4$s">%5$s</a>', $link_attributes, $item_values['editlink_url'], $item_values['rollover_text'], $item_values['link_style'], $item_values['link_text'] );
			$item_values['termlink'] = sprintf( '<a %1$shref="%2$s" title="%3$s" style="%4$s">%5$s</a>', $link_attributes, $item_values['termlink_url'], $item_values['rollover_text'], $item_values['link_style'], $item_values['link_text'] );

			if ( ! empty( $link_href ) )
				$item_values['thelink'] = sprintf( '<a %1$shref="%2$s" title="%3$s" style="%4$s">%5$s</a>', $link_attributes, $link_href, $item_values['rollover_text'], $item_values['link_style'], $item_values['link_text'] );
			elseif ( 'edit' == $arguments['link'] )
				$item_values['thelink'] = $item_values['editlink'];
			elseif ( 'view' == $arguments['link'] )
				$item_values['thelink'] = $item_values['termlink'];
			elseif ( 'span' == $arguments['link'] )
				$item_values['thelink'] = sprintf( '<span %1$sstyle="%2$s">%3$s</a>', $link_attributes, $item_values['link_style'], $item_values['link_text'] );
			else
				$item_values['thelink'] = $item_values['link_text'];

			if ( $is_grid || $is_list ) {
				/*
				 * Start of row markup
				 */
				if ( $is_grid && ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) ) {
					$markup_values = apply_filters( 'mla_tag_cloud_row_open_values', $markup_values );
					$row_open_template = apply_filters( 'mla_tag_cloud_row_open_template', $row_open_template );
					$parse_value = MLAData::mla_parse_template( $row_open_template, $markup_values );
					$cloud .= apply_filters( 'mla_tag_cloud_row_open_parse', $parse_value, $row_open_template, $markup_values );
				}
				
				/*
				 * item markup
				 */
				$column_index++;
				$item_values = apply_filters( 'mla_tag_cloud_item_values', $item_values );
				$item_template = apply_filters( 'mla_tag_cloud_item_template', $item_template );
				$parse_value = MLAData::mla_parse_template( $item_template, $item_values );
				$cloud .= apply_filters( 'mla_tag_cloud_item_parse', $parse_value, $item_template, $item_values );
	
				/*
				 * End of row markup
				 */
				if ( $is_grid && ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) ) {
					$markup_values = apply_filters( 'mla_tag_cloud_row_close_values', $markup_values );
					$row_close_template = apply_filters( 'mla_tag_cloud_row_close_template', $row_close_template );
					$parse_value = MLAData::mla_parse_template( $row_close_template, $markup_values );
					$cloud .= apply_filters( 'mla_tag_cloud_row_close_parse', $parse_value, $row_close_template, $markup_values );
				}
			} // is_grid || is_list
			elseif ( $is_pagination )
				return $item_values['thelink'];
			else {
				$column_index++;
				$item_values = apply_filters( 'mla_tag_cloud_item_values', $item_values );
				$tag_links[] = apply_filters( 'mla_tag_cloud_item_parse', $item_values['thelink'], NULL, $item_values );
			} 
		} // foreach tag
		
		if ($is_grid || $is_list ) {
			/*
			 * Close out partial row
			 */
			if ( $is_grid && ( ! ($markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) ) ) {
				$markup_values = apply_filters( 'mla_tag_cloud_row_close_values', $markup_values );
				$row_close_template = apply_filters( 'mla_tag_cloud_row_close_template', $row_close_template );
				$parse_value = MLAData::mla_parse_template( $row_close_template, $markup_values );
				$cloud .= apply_filters( 'mla_tag_cloud_row_close_parse', $parse_value, $row_close_template, $markup_values );
			}
				
			$markup_values = apply_filters( 'mla_tag_cloud_close_values', $markup_values );
			$close_template = apply_filters( 'mla_tag_cloud_close_template', $close_template );
			$parse_value = MLAData::mla_parse_template( $close_template, $markup_values );
			$cloud .= apply_filters( 'mla_tag_cloud_close_parse', $parse_value, $close_template, $markup_values );
		} // is_grid || is_list
		else {
			switch ( $markup_values['mla_output'] ) {
			case 'array' :
				$cloud =& $tag_links;
				break;
			case 'flat' :
			default :
				$cloud .= join( $markup_values['separator'], $tag_links );
				break;
			} // switch format
		}
	
		//$cloud = wp_generate_tag_cloud( $tags, $arguments );
		
		if ( 'array' == $arguments['mla_output'] || empty($arguments['echo']) )
			return $cloud;
	
		echo $cloud;
	}
	
	/**
	 * The MLA Tag Cloud shortcode.
	 *
	 * This is an interface to the mla_tag_cloud function.
	 *
	 * @since 1.60
	 *
	 * @param array $attr Attributes of the shortcode.
	 *
	 * @return string HTML content to display the tag cloud.
	 */
	public static function mla_tag_cloud_shortcode( $attr ) {
		/*
		 * Make sure $attr is an array, even if it's empty
		 */
		if ( empty( $attr ) )
			$attr = array();
		elseif ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );

		/*
		 * The 'array' format makes no sense in a shortcode
		 */
		if ( isset( $attr['mla_output'] ) && 'array' == $attr['mla_output'] )
			$attr['mla_output'] = 'flat';
			 
		/*
		 * A shortcode must return its content to the caller, so "echo" makes no sense
		 */
		$attr['echo'] = false;
			 
		return self::mla_tag_cloud( $attr );
	}
	
	/**
	 * Handles brace/bracket escaping and parses template for a shortcode parameter
	 *
	 * @since 1.14
	 *
	 * @param string raw shortcode parameter, e.g., "text {+field+} {brackets} \\{braces\\}"
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 *
	 * @return string query specification with HTML escape sequences and line breaks removed
	 */
	private static function _process_shortcode_parameter( $text, $markup_values ) {
		$new_text = str_replace( '{', '[', str_replace( '}', ']', $text ) );
		$new_text = str_replace( '\[', '{', str_replace( '\]', '}', $new_text ) );
		return MLAData::mla_parse_template( $new_text, $markup_values );
	}
	
	/**
	 * Handles pagnation output types 'previous_page', 'next_page', and 'paginate_links'
	 *
	 * @since 1.42
	 *
	 * @param array	value(s) for mla_output_type parameter
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 * @param string merged default and passed shortcode parameter values
	 * @param integer number of attachments in the gallery, without pagination
	 * @param string output text so far, may include debug values
	 *
	 * @return mixed	false or string with HTML for pagination output types
	 */
	private static function _paginate_links( $output_parameters, $markup_values, $arguments, $found_rows, $output = '' ) {
		if ( 2 > $markup_values['last_page'] )
			return '';
			
		$show_all = $prev_next = false;
		
		if ( isset ( $output_parameters[1] ) )
				switch ( $output_parameters[1] ) {
				case 'show_all':
					$show_all = true;
					break;
				case 'prev_next':
					$prev_next = true;
			}

		$mla_page_parameter = $arguments['mla_page_parameter'];
		$current_page = $markup_values['current_page'];
		$last_page = $markup_values['last_page'];
		$end_size = absint( $arguments['mla_end_size'] );
		$mid_size = absint( $arguments['mla_mid_size'] );
		$posts_per_page = $markup_values['posts_per_page'];
		
		$new_target = ( ! empty( $arguments['mla_target'] ) ) ? 'target="' . $arguments['mla_target'] . '" ' : '';
		
		/*
		 * these will add to the default classes
		 */
		$new_class = ( ! empty( $arguments['mla_link_class'] ) ) ? ' ' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_class'], $markup_values ) ) : '';

		$new_attributes = ( ! empty( $arguments['mla_link_attributes'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $markup_values ) ) . ' ' : '';

		$new_base =  ( ! empty( $arguments['mla_link_href'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_href'], $markup_values ) ) : $markup_values['new_url'];

		/*
		 * Build the array of page links
		 */
		$page_links = array();
		$dots = false;
		
		if ( $prev_next && $current_page && 1 < $current_page ) {
			$markup_values['new_page'] = $current_page - 1;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			$new_url = add_query_arg( array(  $mla_page_parameter  => $current_page - 1 ), $new_base );
			$prev_text = ( ! empty( $arguments['mla_prev_text'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_prev_text'], $markup_values ) ) : '&laquo; Previous';
			$page_links[] = sprintf( '<a %1$sclass="prev page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
				/* %1$s */ $new_target,
				/* %2$s */ $new_class,
				/* %3$s */ $new_attributes,
				/* %4$s */ $new_title,
				/* %5$s */ $new_url,
				/* %6$s */ $prev_text );
		}
		
		for ( $new_page = 1; $new_page <= $last_page; $new_page++ ) {
			$new_page_display = number_format_i18n( $new_page );
			$markup_values['new_page'] = $new_page;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			
			if ( $new_page == $current_page ) {
				// build current page span
				$page_links[] = sprintf( '<span class="page-numbers current%1$s">%2$s</span>',
					/* %1$s */ $new_class,
					/* %2$s */ $new_page_display );
				$dots = true;
			}
			else {
				if ( $show_all || ( $new_page <= $end_size || ( $current_page && $new_page >= $current_page - $mid_size && $new_page <= $current_page + $mid_size ) || $new_page > $last_page - $end_size ) ) {
					// build link
					$new_url = add_query_arg( array(  $mla_page_parameter  => $new_page ), $new_base );
					$page_links[] = sprintf( '<a %1$sclass="page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
						/* %1$s */ $new_target,
						/* %2$s */ $new_class,
						/* %3$s */ $new_attributes,
						/* %4$s */ $new_title,
						/* %5$s */ $new_url,
						/* %6$s */ $new_page_display );
					$dots = true;
				}
				elseif ( $dots && ! $show_all ) {
					// build link
					$page_links[] = sprintf( '<span class="page-numbers dots%1$s">&hellip;</span>',
						/* %1$s */ $new_class );
					$dots = false;
				}
			} // ! current
		} // for $new_page
		
		if ( $prev_next && $current_page && ( $current_page < $last_page || -1 == $last_page ) ) {
			// build next link
			$markup_values['new_page'] = $current_page + 1;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			$new_url = add_query_arg( array(  $mla_page_parameter  => $current_page + 1 ), $new_base );
			$next_text = ( ! empty( $arguments['mla_next_text'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_next_text'], $markup_values ) ) : 'Next &raquo;';
			$page_links[] = sprintf( '<a %1$sclass="next page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
				/* %1$s */ $new_target,
				/* %2$s */ $new_class,
				/* %3$s */ $new_attributes,
				/* %4$s */ $new_title,
				/* %5$s */ $new_url,
				/* %6$s */ $next_text );
		}

		switch ( strtolower( trim( $arguments['mla_paginate_type'] ) ) ) {
			case 'list':
				$results = "<ul class='page-numbers'>\n\t<li>";
				$results .= join("</li>\n\t<li>", $page_links);
				$results .= "</li>\n</ul>\n";
				break;
			case 'plain':
			default:
				$results = join("\n", $page_links);
		} // mla_paginate_type
	
		return $output . $results;
	}
	
	/**
	 * Handles pagnation output types 'previous_page', 'next_page', and 'paginate_links'
	 *
	 * @since 1.42
	 *
	 * @param array	value(s) for mla_output_type parameter
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 * @param string merged default and passed shortcode parameter values
	 * @param string raw passed shortcode parameter values
	 * @param integer number of attachments in the gallery, without pagination
	 * @param string output text so far, may include debug values
	 *
	 * @return mixed	false or string with HTML for pagination output types
	 */
	private static function _process_pagination_output_types( $output_parameters, $markup_values, $arguments, $attr, $found_rows, $output = '' ) {
		if ( ! in_array( $output_parameters[0], array( 'previous_page', 'next_page', 'paginate_links' ) ) )
			return false;
			
		/*
		 * Add data selection parameters to gallery-specific and mla_gallery-specific parameters
		 */
		$arguments = array_merge( $arguments, shortcode_atts( self::$mla_get_shortcode_attachments_parameters, $attr ) );
		$posts_per_page = absint( $arguments['posts_per_page'] );
		$mla_page_parameter = $arguments['mla_page_parameter'];
		
		/*
		 * $mla_page_parameter, if set, doesn't make it through the shortcode_atts filter,
		 * so we handle it separately
		 */
		if ( ! isset( $arguments[ $mla_page_parameter ] ) )
			if ( isset( $attr[ $mla_page_parameter ] ) )
				$arguments[ $mla_page_parameter ] = $attr[ $mla_page_parameter ];
			else
				$arguments[ $mla_page_parameter ] = '';
			
		if ( 0 == $posts_per_page )
			$posts_per_page = absint( $arguments['numberposts'] );
			
		if ( 0 == $posts_per_page )
			$posts_per_page = absint( get_option('posts_per_page') );

			if ( 0 < $posts_per_page ) {
				$max_page = floor( $found_rows / $posts_per_page );
				if ( $max_page < ( $found_rows / $posts_per_page ) )
					$max_page++;
			}
			else
				$max_page = 1;

		if ( isset( $arguments['mla_paginate_total'] )  && $max_page > absint( $arguments['mla_paginate_total'] ) )
			$max_page = absint( $arguments['mla_paginate_total'] );
			
		if ( isset( $arguments[ $mla_page_parameter ] ) )
			$paged = absint( $arguments[ $mla_page_parameter ] );
		else
			$paged = absint( $arguments['paged'] );
		
		if ( 0 == $paged )
			$paged = 1;

		if ( $max_page < $paged )
			$paged = $max_page;

		switch ( $output_parameters[0] ) {
			case 'previous_page':
				if ( 1 < $paged )
					$new_page = $paged - 1;
				else {
					$new_page = 0;

					if ( isset ( $output_parameters[1] ) )
						switch ( $output_parameters[1] ) {
							case 'wrap':
								$new_page = $max_page;
								break;
							case 'first':
								$new_page = 1;
						}
				}
				
				break;
			case 'next_page':
				if ( $paged < $max_page )
					$new_page = $paged + 1;
				else {
					$new_page = 0;

					if ( isset ( $output_parameters[1] ) )
						switch ( $output_parameters[1] ) {
							case 'last':
								$new_page = $max_page;
								break;
							case 'wrap':
								$new_page = 1;
						}
				}
					
				break;
			case 'paginate_links':
				$new_page = 0;
				$new_text = '';
		}
		
		$markup_values['current_page'] = $paged;
		$markup_values['new_page'] = $new_page;
		$markup_values['last_page'] = $max_page;
		$markup_values['posts_per_page'] = $posts_per_page;
		$markup_values['found_rows'] = $found_rows;

		if ( $paged )
			$markup_values['current_offset'] = ( $paged - 1 ) * $posts_per_page;
		else
			$markup_values['current_offset'] = 0;
			
		if ( $new_page )
			$markup_values['new_offset'] = ( $new_page - 1 ) * $posts_per_page;
		else
			$markup_values['new_offset'] = 0;
			
		$markup_values['current_page_text'] = 'mla_paginate_current="[+current_page+]"';
		$markup_values['new_page_text'] = 'mla_paginate_current="[+new_page+]"';
		$markup_values['last_page_text'] = 'mla_paginate_total="[+last_page+]"';
		$markup_values['posts_per_page_text'] = 'posts_per_page="[+posts_per_page+]"';
		
		if ( 'HTTPS' == substr( $_SERVER["SERVER_PROTOCOL"], 0, 5 ) )
			$markup_values['scheme'] = 'https://';
		else
			$markup_values['scheme'] = 'http://';
			
		$markup_values['http_host'] = $_SERVER['HTTP_HOST'];
		$markup_values['request_uri'] = add_query_arg( array(  $mla_page_parameter  => $new_page ), $_SERVER['REQUEST_URI'] );	
		$markup_values['new_url'] = set_url_scheme( $markup_values['scheme'] . $markup_values['http_host'] . $markup_values['request_uri'] );

		/*
		 * Build the new link, applying Gallery Display Content parameters
		 */
		if ( 'paginate_links' == $output_parameters[0] )
			return self::_paginate_links( $output_parameters, $markup_values, $arguments, $found_rows, $output );
			
		if ( 0 == $new_page ) {
			if ( ! empty( $arguments['mla_nolink_text'] ) )
				return self::_process_shortcode_parameter( $arguments['mla_nolink_text'], $markup_values );
			else
				return '';
		}
		
		$new_link = '<a ';
		
		if ( ! empty( $arguments['mla_target'] ) )
			$new_link .= 'target="' . $arguments['mla_target'] . '" ';
		
		if ( ! empty( $arguments['mla_link_class'] ) )
			$new_link .= 'class="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_class'], $markup_values ) ) . '" ';

		if ( ! empty( $arguments['mla_rollover_text'] ) )
			$new_link .= 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ';

		if ( ! empty( $arguments['mla_link_attributes'] ) )
			$new_link .= esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $markup_values ) ) . ' ';

		if ( ! empty( $arguments['mla_link_href'] ) )
			$new_link .= 'href="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_href'], $markup_values ) ) . '" >';
		else
			$new_link .= 'href="' . $markup_values['new_url'] . '" >';
		
		if ( ! empty( $arguments['mla_link_text'] ) )
			$new_link .= self::_process_shortcode_parameter( $arguments['mla_link_text'], $markup_values ) . '</a>';
		else {
			if ( 'previous_page' == $output_parameters[0] ) {
				if ( isset( $arguments['mla_prev_text'] ) )
					$new_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_prev_text'], $markup_values ) );
				else
					$new_text = '&laquo; Previous';
			}
			else {
				if ( isset( $arguments['mla_next_text'] ) )
					$new_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_next_text'], $markup_values ) );
				else
					$new_text = 'Next &raquo;';
			}
		
			$new_link .= $new_text . '</a>';
		}
		
		return $new_link;
	}
	
	/**
	 * WP_Query filter "parameters"
	 *
	 * This array defines parameters for the query's where and orderby filters,
	 * mla_shortcode_query_posts_where_filter and mla_shortcode_query_posts_orderby_filter.
	 * The parameters are set up in the mla_get_shortcode_attachments function, and
	 * any further logic required to translate those values is contained in the filter.
	 *
	 * Array index values are: orderby, post_parent
	 *
	 * @since 1.13
	 *
	 * @var	array
	 */
	private static $query_parameters = array();

	/**
	 * Cleans up damage caused by the Visual Editor to the tax_query and meta_query specifications
	 *
	 * @since 1.14
	 *
	 * @param string query specification; PHP nested arrays
	 *
	 * @return string query specification with HTML escape sequences and line breaks removed
	 */
	private static function _sanitize_query_specification( $specification ) {
		$specification = wp_specialchars_decode( $specification );
		$specification = str_replace( array( '<br />', '<p>', '</p>', "\r", "\n" ), ' ', $specification );
		return $specification;
	}
	
	/**
	 * Translates query parameters to a valid SQL order by clause.
	 *
	 * Accepts one or more valid columns, with or without ASC/DESC.
	 * Enhanced version of /wp-includes/formatting.php function sanitize_sql_orderby().
	 *
	 * @since 1.20
	 *
	 * @param array Validated query parameters
	 * @return string|bool Returns the orderby clause if present, false otherwise.
	 */
	private static function _validate_sql_orderby( $query_parameters ){
		global $wpdb;

		$results = array ();
		$order = isset( $query_parameters['order'] ) ? ' ' . $query_parameters['order'] : '';
		$orderby = isset( $query_parameters['orderby'] ) ? $query_parameters['orderby'] : '';
		$meta_key = isset( $query_parameters['meta_key'] ) ? $query_parameters['meta_key'] : '';
		$post__in = isset( $query_parameters['post__in'] ) ? implode(',', array_map( 'absint', $query_parameters['post__in'] )) : '';

		if ( empty( $orderby ) ) {
			$orderby = "$wpdb->posts.post_date " . $order;
		} elseif ( 'none' == $orderby ) {
			return '';
		} elseif ( $orderby == 'post__in' && ! empty( $post__in ) ) {
			$orderby = "FIELD( {$wpdb->posts}.ID, {$post__in} )";
		} else {
			$allowed_keys = array('ID', 'author', 'date', 'description', 'content', 'title', 'caption', 'excerpt', 'slug', 'name', 'modified', 'parent', 'menu_order', 'mime_type', 'comment_count', 'rand');
			if ( ! empty( $meta_key ) ) {
				$allowed_keys[] = $meta_key;
				$allowed_keys[] = 'meta_value';
				$allowed_keys[] = 'meta_value_num';
			}
		
			$obmatches = preg_split('/\s*,\s*/', trim($query_parameters['orderby']));
			foreach ( $obmatches as $index => $value ) {
				$count = preg_match('/([a-z0-9_]+)(\s+(ASC|DESC))?/i', $value, $matches);

				if ( $count && ( $value == $matches[0] ) && in_array( $matches[1], $allowed_keys ) ) {
					if ( 'rand' == $matches[1] )
							$results[] = 'RAND()';
					else {
						switch ( $matches[1] ) {
							case 'ID':
								$matches[1] = "$wpdb->posts.ID";
								break;
							case 'description':
								$matches[1] = "$wpdb->posts.post_content";
								break;
							case 'caption':
								$matches[1] = "$wpdb->posts.post_excerpt";
								break;
							case 'slug':
								$matches[1] = "$wpdb->posts.post_name";
								break;
							case 'menu_order':
								$matches[1] = "$wpdb->posts.menu_order";
								break;
							case 'comment_count':
								$matches[1] = "$wpdb->posts.comment_count";
								break;
							case $meta_key:
							case 'meta_value':
								$matches[1] = "$wpdb->postmeta.meta_value";
								break;
							case 'meta_value_num':
								$matches[1] = "$wpdb->postmeta.meta_value+0";
								break;
							default:
								$matches[1] = "$wpdb->posts.post_" . $matches[1];
						} // switch $matches[1]
	
						$results[] = isset( $matches[2] ) ? $matches[1] . $matches[2] : $matches[1] . $order;
					} // not 'rand'
				} // valid column specification
			} // foreach $obmatches

			$orderby = implode( ', ', $results );
			if ( empty( $orderby ) )
				return false;
		} // else filter by allowed keys, etc.

		return $orderby;
	}

	/**
	 * Data selection parameters for the WP_Query in [mla_gallery]
	 *
	 * @since 1.30
	 *
	 * @var	array
	 */
	private static $mla_get_shortcode_attachments_parameters = array(
			'order' => 'ASC', // or 'DESC' or 'RAND'
			'orderby' => 'menu_order,ID',
			'id' => NULL,
			'ids' => array(),
			'include' => array(),
			'exclude' => array(),
			// MLA extensions, from WP_Query
			// Force 'get_children' style query
			'post_parent' => NULL, // post/page ID or 'current' or 'all'
			// Author
			'author' => NULL,
			'author_name' => '',
			// Category
			'cat' => 0,
			'category_name' => '',
			'category__and' => array(),
			'category__in' => array(),
			'category__not_in' => array(),
			// Tag
			'tag' => '',
			'tag_id' => 0,
			'tag__and' => array(),
			'tag__in' => array(),
			'tag__not_in' => array(),
			'tag_slug__and' => array(),
			'tag_slug__in' => array(),
			// Taxonomy parameters are handled separately
			// {tax_slug} => 'term' | array ( 'term, 'term, ... )
			// 'tax_query' => ''
			'tax_operator' => '',
			'tax_include_children' => true,
			// Post 
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_mime_type' => 'image',
			// Pagination - no default for most of these
			'nopaging' => true,
			'numberposts' => 0,
			'posts_per_page' => 0,
			'posts_per_archive_page' => 0,
			'paged' => NULL, // page number or 'current'
			'offset' => NULL,
			'mla_page_parameter' => 'mla_paginate_current',
			'mla_paginate_current' => NULL,
			'mla_paginate_total' => NULL,
			// TBD Time
			// Custom Field
			'meta_key' => '',
			'meta_value' => '',
			'meta_value_num' => NULL,
			'meta_compare' => '',
			'meta_query' => '',
			// Search
			's' => ''
		);

	/**
	 * The WP_Query object used to select items for the gallery.
	 *
	 * Defined as a public, static variable so it can be inspected from the
	 * "mla_gallery_wp_query_object" action. Set to NULL at all other times.
	 *
	 * @since 1.51
	 *
	 * @var	object
	 */
	public static $mla_gallery_wp_query_object = NULL;
	
	/**
	 * Parses shortcode parameters and returns the gallery objects
	 *
	 * @since .50
	 *
	 * @param int Post ID of the parent
	 * @param array Attributes of the shortcode
	 * @param boolean true to calculate and return ['found_posts'] as an array element
	 *
	 * @return array List of attachments returned from WP_Query
	 */
	public static function mla_get_shortcode_attachments( $post_parent, $attr, $return_found_rows = NULL ) {
		global $wp_query;

		/*
		 * Parameters passed to the where and orderby filter functions
		 */
		self::$query_parameters = array();

		/*
		 * Make sure $attr is an array, even if it's empty
		 */
		if ( empty( $attr ) )
			$attr = array();
		elseif ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );

		/*
		 * The "where used" queries have no $_REQUEST context available to them,
		 * so tax_ and meta_query evaluation will fail if they contain "{+request:"
		 * parameters. Ignore these errors.
		 */
		if ( isset( $attr['where_used_query'] ) && ( 'this-is-a-where-used-query' == $attr['where_used_query'] ) ) {
			$where_used_query = true;
			unset( $attr['where_used_query'] );
		}
		else
			$where_used_query = false;

		/*
		 * Merge input arguments with defaults, then extract the query arguments.
		 *
		 * $return_found_rows is used to indicate that the call comes from gallery_shortcode(),
		 * which is the only call that supplies it.
		 */
		if ( ! is_null( $return_found_rows ) )
			$attr = apply_filters( 'mla_gallery_query_attributes', $attr );

		$arguments = shortcode_atts( self::$mla_get_shortcode_attachments_parameters, $attr );
		$mla_page_parameter = $arguments['mla_page_parameter'];
		unset( $arguments['mla_page_parameter'] );
		
		/*
		 * $mla_page_parameter, if set, doesn't make it through the shortcode_atts filter,
		 * so we handle it separately
		 */
		if ( ! isset( $arguments[ $mla_page_parameter ] ) )
			if ( isset( $attr[ $mla_page_parameter ] ) )
				$arguments[ $mla_page_parameter ] = $attr[ $mla_page_parameter ];
			else
				$arguments[ $mla_page_parameter ] = NULL;

		/*
		 * 'RAND' is not documented in the codex, but is present in the code.
		 */
		if ( 'RAND' == strtoupper( $arguments['order'] ) ) {
			$arguments['orderby'] = 'none';
			unset( $arguments['order'] );
		}

		if ( !empty( $arguments['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) )
				$arguments['orderby'] = 'post__in';

			$arguments['include'] = $arguments['ids'];
		}
		unset( $arguments['ids'] );

		if ( ! is_null( $return_found_rows ) )
			$arguments = apply_filters( 'mla_gallery_query_arguments', $arguments );
	
		/*
		 * Extract taxonomy arguments
		 */
		$taxonomies = get_taxonomies( array ( 'show_ui' => true ), 'names' ); // 'objects'
		$query_arguments = array();
		if ( ! empty( $attr ) ) {
			foreach ( $attr as $key => $value ) {
				if ( 'tax_query' == $key ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else {
						$tax_query = NULL;
						$value = self::_sanitize_query_specification( $value );

						/*
						 * Replace invalid queries from "where-used" callers with a harmless equivalent
						 */
						if ( $where_used_query && ( false !== strpos( $value, '{+' ) ) )
							$value = "array( array( 'taxonomy' => 'none', 'field' => 'slug', 'terms' => 'none' ) )";

						$function = @create_function('', 'return ' . $value . ';' );
						if ( is_callable( $function ) )
							$tax_query = $function();

						if ( is_array( $tax_query ) )						
							$query_arguments[ $key ] = $tax_query;
						else {
							return '<p>ERROR: invalid mla_gallery tax_query = ' . var_export( $value, true ) . '</p>';
						}
					} // not array
				}  // tax_query
				elseif ( array_key_exists( $key, $taxonomies ) ) {
					$query_arguments[ $key ] = implode(',', array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
					
					if ( 'false' == strtolower( trim( $arguments['tax_include_children'] ) ) ) {
						$arguments['tax_include_children'] = false;
						
						if ( '' == $arguments['tax_operator'] )
						 $arguments['tax_operator'] = 'OR';
					}
					else
						$arguments['tax_include_children'] = true;
					
					if ( in_array( strtoupper( $arguments['tax_operator'] ), array( 'OR', 'IN', 'NOT IN', 'AND' ) ) ) {
						$query_arguments['tax_query'] =	array( array( 'taxonomy' => $key, 'field' => 'slug', 'terms' => explode( ',', $query_arguments[ $key ] ), 'operator' => strtoupper( $arguments['tax_operator'] ), 'include_children' => $arguments['tax_include_children'] ) );
						unset( $query_arguments[ $key ] );
					}
				} // array_key_exists
			} //foreach $attr
		} // ! empty
		unset( $arguments['tax_operator'] );
		unset( $arguments['tax_include_children'] );
		
		/*
		 * $query_arguments has been initialized in the taxonomy code above.
		 */
		$use_children = empty( $query_arguments );
		foreach ($arguments as $key => $value ) {
			/*
			 * There are several "fallthru" cases in this switch statement that decide 
			 * whether or not to limit the query to children of a specific post.
			 */
			$children_ok = true;
			switch ( $key ) {
			case 'post_parent':
				switch ( strtolower( $value ) ) {
				case 'all':
					$value = NULL;
					$use_children = false;
					break;
				case 'any':
					self::$query_parameters['post_parent'] = 'any';
					$value = NULL;
					$use_children = false;
					break;
				case 'current':
					$value = $post_parent;
					break;
				case 'none':
					self::$query_parameters['post_parent'] = 'none';
					$value = NULL;
					$use_children = false;
					break;
				}
				// fallthru
			case 'id':
				if ( is_numeric( $value ) ) {
					$query_arguments[ $key ] = intval( $value );
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'numberposts':
			case 'posts_per_page':
			case 'posts_per_archive_page':
				if ( is_numeric( $value ) ) {
					$value =  intval( $value );
					if ( ! empty( $value ) ) {
						$query_arguments[ $key ] = $value;
					}
				}
				unset( $arguments[ $key ] );
				break;
			case 'meta_value_num':
				$children_ok = false;
				// fallthru
			case 'offset':
				if ( is_numeric( $value ) ) {
					$query_arguments[ $key ] = intval( $value );
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'paged':
				if ( 'current' == strtolower( $value ) ) {
					/*
					 * Note: The query variable 'page' holds the pagenumber for a single paginated
					 * Post or Page that includes the <!--nextpage--> Quicktag in the post content. 
					 */
					if ( get_query_var('page') )
						$query_arguments[ $key ] = get_query_var('page');
					else
						$query_arguments[ $key ] = (get_query_var('paged')) ? get_query_var('paged') : 1;
				}
				elseif ( is_numeric( $value ) )
					$query_arguments[ $key ] = intval( $value );
				elseif ( '' === $value )
					$query_arguments[ $key ] = 1;
				unset( $arguments[ $key ] );
				break;
			case  $mla_page_parameter :
			case 'mla_paginate_total':
				if ( is_numeric( $value ) )
					$query_arguments[ $key ] = intval( $value );
				elseif ( '' === $value )
					$query_arguments[ $key ] = 1;
				unset( $arguments[ $key ] );
				break;
			case 'author':
			case 'cat':
			case 'tag_id':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = array_filter( $value );
					else
						$query_arguments[ $key ] = array_filter( array_map( 'intval', explode( ",", $value ) ) );
						
					if ( 1 == count( $query_arguments[ $key ] ) )
						$query_arguments[ $key ] = $query_arguments[ $key ][0];
					else
						$query_arguments[ $key ] = implode(',', $query_arguments[ $key ] );

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'category__and':
			case 'category__in':
			case 'category__not_in':
			case 'tag__and':
			case 'tag__in':
			case 'tag__not_in':
			case 'include':
				$children_ok = false;
				// fallthru
			case 'exclude':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = array_filter( $value );
					else
						$query_arguments[ $key ] = array_filter( array_map( 'intval', explode( ",", $value ) ) );
						
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'tag_slug__and':
			case 'tag_slug__in':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else
						$query_arguments[ $key ] = array_filter( array_map( 'trim', explode( ",", $value ) ) );

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'nopaging': // boolean
				if ( ! empty( $value ) && ( 'false' != strtolower( $value ) ) )
					$query_arguments[ $key ] = true;
				unset( $arguments[ $key ] );
				break;
			case 'author_name':
			case 'category_name':
			case 'tag':
			case 'meta_key':
			case 'meta_value':
			case 'meta_compare':
			case 's':
				$children_ok = false;
				// fallthru
			case 'post_type':
			case 'post_status':
			case 'post_mime_type':
			case 'orderby':
				if ( ! empty( $value ) ) {
					$query_arguments[ $key ] = $value;
					
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'order':
				if ( ! empty( $value ) ) {
					$value = strtoupper( $value );
					if ( in_array( $value, array( 'ASC', 'DESC' ) ) )
						$query_arguments[ $key ] = $value;
				}
				unset( $arguments[ $key ] );
				break;
			case 'meta_query':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else {
						$meta_query = NULL;
						$value = self::_sanitize_query_specification( $value );

						/*
						 * Replace invalid queries from "where-used" callers with a harmless equivalent
						 */
						if ( $where_used_query && ( false !== strpos( $value, '{+' ) ) )
							$value = "array( array( 'key' => 'unlikely', 'value' => 'none or otherwise unlikely' ) )";

						$function = @create_function('', 'return ' . $value . ';' );
						if ( is_callable( $function ) )
							$meta_query = $function();
						
						if ( is_array( $meta_query ) )
							$query_arguments[ $key ] = $meta_query;
						else
							return '<p>ERROR: invalid mla_gallery meta_query = ' . var_export( $value, true ) . '</p>';
					} // not array

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			default:
				// ignore anything else
			} // switch $key
		} // foreach $arguments 

		/*
		 * Decide whether to use a "get_children" style query
		 */
		if ( $use_children && ! isset( $query_arguments['post_parent'] ) ) {
			if ( ! isset( $query_arguments['id'] ) )
				$query_arguments['post_parent'] = $post_parent;
			else				
				$query_arguments['post_parent'] = $query_arguments['id'];

			unset( $query_arguments['id'] );
		}

		if ( isset( $query_arguments['numberposts'] ) && ! isset( $query_arguments['posts_per_page'] )) {
			$query_arguments['posts_per_page'] = $query_arguments['numberposts'];
		}
		unset( $query_arguments['numberposts'] );

		/*
		 * MLA pagination will override WordPress pagination
		 */
		if ( isset( $query_arguments[ $mla_page_parameter ] ) ) {
			unset( $query_arguments['nopaging'] );
			unset( $query_arguments['offset'] );
			unset( $query_arguments['paged'] );
			
			if ( isset( $query_arguments['mla_paginate_total'] ) && ( $query_arguments[ $mla_page_parameter ] > $query_arguments['mla_paginate_total'] ) )
				$query_arguments['offset'] = 0x7FFFFFFF; // suppress further output
			else
				$query_arguments['paged'] = $query_arguments[ $mla_page_parameter ];
		}
		else {
			if ( isset( $query_arguments['posts_per_page'] ) || isset( $query_arguments['posts_per_archive_page'] ) ||
				isset( $query_arguments['paged'] ) || isset( $query_arguments['offset'] ) ) {
				unset( $query_arguments['nopaging'] );
			}
		}
		unset( $query_arguments[ $mla_page_parameter ] );
		unset( $query_arguments['mla_paginate_total'] );

		if ( isset( $query_arguments['post_mime_type'] ) && ('all' == strtolower( $query_arguments['post_mime_type'] ) ) )
			unset( $query_arguments['post_mime_type'] );

		if ( ! empty($query_arguments['include']) ) {
			$incposts = wp_parse_id_list( $query_arguments['include'] );
			$query_arguments['posts_per_page'] = count($incposts);  // only the number of posts included
			$query_arguments['post__in'] = $incposts;
		} elseif ( ! empty($query_arguments['exclude']) )
			$query_arguments['post__not_in'] = wp_parse_id_list( $query_arguments['exclude'] );
	
		$query_arguments['ignore_sticky_posts'] = true;
		$query_arguments['no_found_rows'] = is_null( $return_found_rows ) ? true : ! $return_found_rows;
	
		/*
		 * We will always handle "orderby" in our filter
		 */ 
		self::$query_parameters['orderby'] = self::_validate_sql_orderby( $query_arguments );
		if ( false === self::$query_parameters['orderby'] )
			unset( self::$query_parameters['orderby'] );
			
		unset( $query_arguments['orderby'] );
		unset( $query_arguments['order'] );
	
		if ( self::$mla_debug ) {
			add_filter( 'posts_clauses', 'MLAShortcodes::mla_shortcode_query_posts_clauses_filter', 0x7FFFFFFF, 1 );
			add_filter( 'posts_clauses_request', 'MLAShortcodes::mla_shortcode_query_posts_clauses_request_filter', 0x7FFFFFFF, 1 );
		}
		
		add_filter( 'posts_orderby', 'MLAShortcodes::mla_shortcode_query_posts_orderby_filter', 0x7FFFFFFF, 1 );
		add_filter( 'posts_where', 'MLAShortcodes::mla_shortcode_query_posts_where_filter', 0x7FFFFFFF, 1 );

		if ( self::$mla_debug ) {
			global $wp_filter;
			self::$mla_debug_messages .= '<p><strong>mla_debug $wp_filter[posts_where]</strong> = ' . var_export( $wp_filter['posts_where'], true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug $wp_filter[posts_orderby]</strong> = ' . var_export( $wp_filter['posts_orderby'], true ) . '</p>';
		}
		
		self::$mla_gallery_wp_query_object = new WP_Query;
		$attachments = self::$mla_gallery_wp_query_object->query($query_arguments);
		
		/*
		 * $return_found_rows is used to indicate that the call comes from gallery_shortcode(),
		 * which is the only call that supplies it.
		 */
		if ( is_null( $return_found_rows ) )
			$return_found_rows = false;
		else 
			do_action( 'mla_gallery_wp_query_object', $query_arguments );
		
		if ( $return_found_rows ) {
			$attachments['found_rows'] = self::$mla_gallery_wp_query_object->found_posts;
		}
			
		remove_filter( 'posts_where', 'MLAShortcodes::mla_shortcode_query_posts_where_filter', 0x7FFFFFFF, 1 );
		remove_filter( 'posts_orderby', 'MLAShortcodes::mla_shortcode_query_posts_orderby_filter', 0x7FFFFFFF, 1 );
		
		if ( self::$mla_debug ) {
			remove_filter( 'posts_clauses', 'MLAShortcodes::mla_shortcode_query_posts_clauses_filter', 0x7FFFFFFF, 1 );
			remove_filter( 'posts_clauses_request', 'MLAShortcodes::mla_shortcode_query_posts_clauses_request_filter', 0x7FFFFFFF, 1 );

			self::$mla_debug_messages .= '<p><strong>mla_debug query</strong> = ' . var_export( $query_arguments, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug request</strong> = ' . var_export( self::$mla_gallery_wp_query_object->request, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug query_vars</strong> = ' . var_export( self::$mla_gallery_wp_query_object->query_vars, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug post_count</strong> = ' . var_export( self::$mla_gallery_wp_query_object->post_count, true ) . '</p>';
		}
		
		self::$mla_gallery_wp_query_object = NULL;
		return $attachments;
	}

	/**
	 * Filters the WHERE clause for shortcode queries
	 * 
	 * Captures debug information. Adds whitespace to the post_type = 'attachment'
	 * phrase to circumvent subsequent Role Scoper modification of the clause.
	 * Handles post_parent "any" and "none" cases.
	 * Defined as public because it's a filter.
	 *
	 * @since 0.70
	 *
	 * @param	string	query clause before modification
	 *
	 * @return	string	query clause after modification
	 */
	public static function mla_shortcode_query_posts_where_filter( $where_clause ) {
		global $table_prefix;

		if ( self::$mla_debug ) {
			$old_clause = $where_clause;
			self::$mla_debug_messages .= '<p><strong>mla_debug WHERE filter</strong> = ' . var_export( $where_clause, true ) . '</p>';
		}
		
		if ( strpos( $where_clause, "post_type = 'attachment'" ) ) {
			$where_clause = str_replace( "post_type = 'attachment'", "post_type  =  'attachment'", $where_clause );
		}

		if ( isset( self::$query_parameters['post_parent'] ) ) {
			switch ( self::$query_parameters['post_parent'] ) {
			case 'any':
				$where_clause .= " AND {$table_prefix}posts.post_parent > 0";
				break;
			case 'none':
				$where_clause .= " AND {$table_prefix}posts.post_parent < 1";
				break;
			}
		}

		if ( self::$mla_debug && ( $old_clause != $where_clause ) ) 
			self::$mla_debug_messages .= '<p><strong>mla_debug modified WHERE filter</strong> = ' . var_export( $where_clause, true ) . '</p>';

		return $where_clause;
	}

	/**
	 * Filters the ORDERBY clause for shortcode queries
	 * 
	 * This is an enhanced version of the code found in wp-includes/query.php, function get_posts.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.20
	 *
	 * @param	string	query clause before modification
	 *
	 * @return	string	query clause after modification
	 */
	public static function mla_shortcode_query_posts_orderby_filter( $orderby_clause ) {
		global $wpdb;

		if ( self::$mla_debug ) {
			self::$mla_debug_messages .= '<p><strong>mla_debug ORDER BY filter, incoming</strong> = ' . var_export( $orderby_clause, true ) . '<br>Replacement ORDER BY clause = ' . var_export( self::$query_parameters['orderby'], true ) . '</p>';
		}

		if ( isset( self::$query_parameters['orderby'] ) )
			return self::$query_parameters['orderby'];
		else
			return $orderby_clause;
	}

	/**
	 * Filters all clauses for shortcode queries, pre caching plugins
	 * 
	 * This is for debug purposes only.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.30
	 *
	 * @param	array	query clauses before modification
	 *
	 * @return	array	query clauses after modification (none)
	 */
	public static function mla_shortcode_query_posts_clauses_filter( $pieces ) {
		self::$mla_debug_messages .= '<p><strong>mla_debug posts_clauses filter</strong> = ' . var_export( $pieces, true ) . '</p>';

		return $pieces;
	}

	/**
	 * Filters all clauses for shortcode queries, post caching plugins
	 * 
	 * This is for debug purposes only.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.30
	 *
	 * @param	array	query clauses before modification
	 *
	 * @return	array	query clauses after modification (none)
	 */
	public static function mla_shortcode_query_posts_clauses_request_filter( $pieces ) {
		self::$mla_debug_messages .= '<p><strong>mla_debug posts_clauses_request filter</strong> = ' . var_export( $pieces, true ) . '</p>';

		return $pieces;
	}

	/**
	 * Data selection parameters for [mla_tag_cloud]
	 *
	 * @since 1.60
	 *
	 * @var	array
	 */
	private static $mla_get_terms_parameters = array(
		'taxonomy' => 'post_tag',
		'include' => '',
		'exclude' => '',
		'parent' => '',
		'minimum' => 0,
		'number' => 45,
		'orderby' => 'name',
		'order' => 'ASC',
		'preserve_case' => false,
		'limit' => 0,
		'offset' => 0
	);


	/**
	 * Retrieve the terms in one or more taxonomies.
	 *
	 * Alternative to WordPress get_terms() function that provides
	 * an accurate count of attachments associated with each term.
	 *
	 * taxonomy - string containing one or more (comma-delimited) taxonomy names
	 * or an array of taxonomy names.
	 *
	 * include - An array, comma- or space-delimited string of term ids to include
	 * in the return array.
	 *
	 * exclude - An array, comma- or space-delimited string of term ids to exclude
	 * from the return array. If 'include' is non-empty, 'exclude' is ignored.
	 *
	 * parent - term_id of the terms' immediate parent; 0 for top-level terms.
	 *
	 * minimum - minimum number of attachments a term must have to be included.
	 *
	 * number - maximum number of term objects to return. Terms are ordered by count,
	 * descending and then by term_id before this value is applied.
	 *
	 * orderby - 'count', 'id', 'name', 'none', 'random', 'slug'
	 *
	 * order - 'ASC', 'DESC'
	 *
	 * preserve_case - 'true', 'false' to make orderby case-sensitive.
	 *
	 * limit - final number of term objects to return, for pagination.
	 *
	 * offset - number of term objects to skip, for pagination.
	 *
	 * @since 1.60
	 *
	 * @param	array	taxonomies to search and query parameters
	 *
	 * @return	array	array of term objects, empty if none found
	 */
	public static function mla_get_terms( $attr ) {
		global $wpdb;

		/*
		 * Make sure $attr is an array, even if it's empty
		 */
		if ( empty( $attr ) )
			$attr = array();
		elseif ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );

		/*
		 * Merge input arguments with defaults
		 */
		$attr = apply_filters( 'mla_get_terms_query_attributes', $attr );
		$arguments = shortcode_atts( self::$mla_get_terms_parameters, $attr );
		$arguments = apply_filters( 'mla_get_terms_query_arguments', $arguments );
		
		$query = array();
		$query_parameters = array();

		$query[] = 'SELECT t.term_id, t.name, t.slug, t.term_group, tt.term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, COUNT(p.ID) AS `count`';
		$query[] = 'FROM `' . $wpdb->terms . '` AS t';
		$query[] = 'JOIN `' . $wpdb->term_taxonomy . '` AS tt ON t.term_id = tt.term_id';
		$query[] = 'LEFT JOIN `' . $wpdb->term_relationships . '` AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id';
		$query[] = 'LEFT JOIN `' . $wpdb->posts . '` AS p ON tr.object_id = p.ID';
		$query[] = "AND p.post_type IN ('attachment') AND p.post_status IN ('inherit')";

		/*
		 * Add taxonomy constraint
		 */
		if ( is_array( $arguments['taxonomy'] ) )
			$taxonomies = $arguments['taxonomy'];
		else
			$taxonomies = array( $arguments['taxonomy'] );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$error = new WP_Error( 'invalid_taxonomy', __('Invalid taxonomy'), $taxonomy );
				return $error;
			}
		}
	
		$placeholders = array();
		foreach ($taxonomies as $taxonomy) {
		    $placeholders[] = '%s';
		    $query_parameters[] = $taxonomy;
		}

		$query[] = 'WHERE ( tt.taxonomy IN (' . join( ',', $placeholders ) . ')';
		
		/*
		 * Add include/exclude and parent constraints to WHERE cluse
		 */
		if ( ! empty( $arguments['include'] ) ) {
		    $placeholders = implode( "','", wp_parse_id_list( $arguments['include'] ) );
			$query[] = "AND t.term_id IN ( '{$placeholders}' )";
		}
		elseif ( ! empty( $arguments['exclude'] ) ) {
		    $placeholders = implode( "','", wp_parse_id_list( $arguments['exclude'] ) );
			$query[] = "AND t.term_id NOT IN ( '{$placeholders}' )";
		}

		if ( '' !== $arguments['parent'] ) {
			$parent = (int) $arguments['parent'];
			$query[] = "AND tt.parent = '{$parent}'";
		}

		$query[] = ' ) GROUP BY tr.term_taxonomy_id';

		if ( 0 < absint( $arguments['minimum'] ) ) {
			$query[] = 'HAVING count >= %d';
			$query_parameters[] = absint( $arguments['minimum'] );
		}
		
		/*
		 * For now, always select the most popular terms
		 */
		$query[] = 'ORDER BY count DESC, t.term_id ASC';

		/*
		 * Limit the total number of terms returned
		 */
		$terms_limit = absint( $arguments['number'] );
		if ( 0 < $terms_limit ) {
			$query[] = 'LIMIT %d';
			$query_parameters[] = $terms_limit;
		}

		/*
		 * $final_parameters, if present, require an SQL subquery
		 */
		$final_parameters = array();
		
		/*
		 * Add sort order
		 */
		$orderby = strtolower( $arguments['orderby'] );
		$order = strtoupper( $arguments['order'] );
		if ( 'DESC' != $order )
			$order = 'ASC';
			
		/*
		 * Count, Descending, is the default order so no further work
		 * is needed unless a different order is specified
		 */
		if ( 'count' != $orderby || 'DESC' != $order ) {
		    $binary = ( 'true' == strtolower( $arguments['preserve_case'] ) ) ? 'BINARY ' : '';

			switch ($orderby) {
				case 'count':
					$final_parameters[] = 'ORDER BY count ' . $order;
					break;
				case 'id':
					$final_parameters[] = 'ORDER BY term_id ' . $order;
					break;
				case 'name':
					$final_parameters[] = 'ORDER BY ' . $binary . 'name ' . $order;
					break;
				case 'none':
					break;
				case 'random':
					$final_parameters[] = 'ORDER BY RAND() ' . $order;
					break;
				case 'slug':
					$final_parameters[] = 'ORDER BY ' . $binary . 'slug ' . $order;
					break;
			}
		}
		
		/*
		 * Add pagination
		 */
		$offset = absint( $arguments['offset'] );
		$limit = absint( $arguments['limit'] );
		if ( 0 < $offset && 0 < $limit ) {
			$final_parameters[] = 'LIMIT %d, %d';
			$query_parameters[] = $offset;
			$query_parameters[] = $limit;
		}
		elseif ( 0 < $limit ) {
			$final_parameters[] = 'LIMIT %d';
			$query_parameters[] = $limit;
		}
		elseif ( 0 < $offset ) {
			$final_parameters[] = 'LIMIT %d, %d';
			$query_parameters[] = $offset;
			$query_parameters[] = 0x7FFFFFFF; // big number!
		}
		
		/*
		 * If we're limiting the final results, we need to get an accurate total count first
		 */
		if ( 0 < $offset || 0 < $limit ) {
			$count_query = 'SELECT COUNT(*) as count FROM (' . join(' ', $query) . ' ) as subQuery';
			$count = $wpdb->get_results( $wpdb->prepare( $count_query, $query_parameters ) );
			$found_rows = $count[0]->count;
		}
		
		if ( ! empty( $final_parameters ) ) {
		    array_unshift($query, 'SELECT * FROM (');
		    $query[] = ') AS subQuery';
			$query = array_merge( $query, $final_parameters );
		}
		
		$query =  join(' ', $query);
		
		$tags = $wpdb->get_results(	$wpdb->prepare( $query, $query_parameters )	);
		if ( ! isset( $found_rows ) )
			$found_rows = $wpdb->num_rows;

		if ( self::$mla_debug ) {
			self::$mla_debug_messages .= '<p><strong>mla_debug query arguments</strong> = ' . var_export( $arguments, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug last_query</strong> = ' . var_export( $wpdb->last_query, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug last_error</strong> = ' . var_export( $wpdb->last_error, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug num_rows</strong> = ' . var_export( $wpdb->num_rows, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug found_rows</strong> = ' . var_export( $found_rows, true ) . '</p>';
		}
		
		$tags['found_rows'] = $found_rows;
		$tags = apply_filters( 'mla_get_terms_query_results', $tags );

		return $tags;
	}
} // Class MLAShortcodes
?>