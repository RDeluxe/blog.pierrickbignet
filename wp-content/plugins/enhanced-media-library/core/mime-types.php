<?php

/**
 *  wpuxss_eml_mimes_validate
 *
 *  @type     callback function
 *  @since    1.0
 *  @created  15/10/13
 */

function wpuxss_eml_mimes_validate($input)
{	
	if ( !$input ) $input = array();
	
	if ( isset($_REQUEST['wpuxss_eml_restore_mimes_backup']) )
	{
		$_REQUEST['_wp_http_referer'] .= '&settings-restored=true';
		$wpuxss_eml_mimes_backup = get_option('wpuxss_eml_mimes_backup');
		$input = $wpuxss_eml_mimes_backup;
	}
	else
	{
		foreach ( $input as $type => $mime )
		{
			$sanitized_type = wpuxss_eml_sanitize_extension($type);
			
			if ( $sanitized_type !== $type ) 
			{
				$input[$sanitized_type] = $input[$type];
				unset($input[$type]);
				$type = $sanitized_type;
			}
			
			if ( !isset($input[$type]['filter']) )
				$input[$type]['filter'] = 0;
			
			if ( !isset($input[$type]['upload']) )
				$input[$type]['upload'] = 0;
				
			$input[$type]['filter'] = intval($input[$type]['filter']);
			$input[$type]['upload'] = intval($input[$type]['upload']);
			
			$input[$type]['mime'] = sanitize_mime_type($mime['mime']);
			$input[$type]['singular'] = sanitize_text_field($mime['singular']);
			$input[$type]['plural'] = sanitize_text_field($mime['plural']);
		}
	}
	
	return $input;
}




/**
 *  wpuxss_eml_sanitize_extension
 *
 *  Based on the original sanitize_key
 *
 *  @since    1.0
 *  @created  24/10/13
 */

function wpuxss_eml_sanitize_extension( $key ) 
{
	$raw_key = $key;
	$key = strtolower( $key );
	$key = preg_replace( '/[^a-z0-9|]/', '', $key );
	return apply_filters( 'sanitize_key', $key, $raw_key );
}




/**
 *  wpuxss_eml_post_mime_types
 *
 *  @since    1.0
 *  @created  03/08/13
 */
 
add_filter('post_mime_types', 'wpuxss_eml_post_mime_types');

function wpuxss_eml_post_mime_types( $post_mime_types ) 
{
	$wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');
	
	if ( !empty($wpuxss_eml_mimes) ) 
	{
		foreach ( $wpuxss_eml_mimes as $type => $mime )
		{
			if ( $mime['filter'] == 1 )
				$post_mime_types[$mime['mime']] = array(
					$mime['singular'],
					'Manage ' . $mime['singular'],
					_n_noop($mime['singular'] . ' <span class="count">(%s)</span>', $mime['plural'] . ' <span class="count">(%s)</span>')
				);
		}
	}
	
	return $post_mime_types;
}




/**
 *  wpuxss_eml_upload_mimes
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_filter('upload_mimes', 'wpuxss_eml_upload_mimes');

function wpuxss_eml_upload_mimes ( $existing_mimes=array() ) 
{	
	$wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');
	
	if ( !empty($wpuxss_eml_mimes) ) 
	{
		foreach ( $wpuxss_eml_mimes as $type => $mime )
		{
			if ( $mime['upload'] == 1 )
			{
				if ( !isset($existing_mimes[$type]) )
					$existing_mimes[$type] = $mime['mime'];
			}
			else 
			{
				 if ( isset($existing_mimes[$type]) )
					unset($existing_mimes[$type]);
			}		
		}
	}

	return $existing_mimes;
}




/**
 *  wpuxss_eml_mime_types
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_filter( 'mime_types', 'wpuxss_eml_mime_types' );

function wpuxss_eml_mime_types( $existing_mimes ) 
{
	$wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');
	
	if ( !empty($wpuxss_eml_mimes) ) 
	{
		foreach ( $wpuxss_eml_mimes as $type => $mime )
		{
			if ( !isset($existing_mimes[$type]) )
				$existing_mimes[$type] = $mime['mime'];
		}
		
		foreach ( $existing_mimes as $type => $mime )
		{
			if ( !isset($wpuxss_eml_mimes[$type]) && isset($existing_mimes[$type]) )
				unset($existing_mimes[$type]);
		}
	}
 
	return $existing_mimes;
}

?>