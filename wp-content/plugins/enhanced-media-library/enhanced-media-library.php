<?php
/*
Plugin Name: Enhanced Media Library
Plugin URI: http://wordpressuxsolutions.com
Description: This plugin will be handy for those who need to manage a lot of media files.
Version: 1.1.1
Author: WordPress UX Solutions
Author URI: http://wordpressuxsolutions.com
License: GPLv2 or later
Text Domain: eml
Domain Path: /languages


Copyright 2013  WordPress UX Solutions  (email : WordPressUXSolutions@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/




$wpuxss_eml_version = '1.1.1';
$wpuxss_eml_old_version = get_option('wpuxss_eml_version', false);
$wpuxss_eml_dir = plugin_dir_url( __FILE__ );




include_once( 'core/mime-types.php' );
include_once( 'core/taxonomies.php' );

if( is_admin() )
{
	include_once( 'core/options-pages.php' );
}




/**
 *  Load plugin text domain
 *
 *  @since    1.0
 *  @created  03/08/13
 */

load_plugin_textdomain('eml', false, basename( dirname( __FILE__ ) ) . '/languages' );




/**
 *  wpuxss_eml_on_init
 *
 *  @since    1.0
 *  @created  03/08/13
 */
 
add_action('init', 'wpuxss_eml_on_init', 12);

function wpuxss_eml_on_init() 
{	
	// on activation
	wpuxss_eml_on_activation();
	
	$wpuxss_eml_taxonomies = get_option('wpuxss_eml_taxonomies');
	if ( empty($wpuxss_eml_taxonomies) ) $wpuxss_eml_taxonomies = array();
	
	// register eml taxonomies
	foreach ( $wpuxss_eml_taxonomies as $taxonomy => $params )
	{		
		if ( $params['eml_media'] && !empty($params['labels']['singular_name']) && !empty($params['labels']['name']) )
		{
			register_taxonomy( 
				$taxonomy, 
				'attachment', 
				array(
					'labels' => $params['labels'],
					'public' => true,
					'show_admin_column' => $params['show_admin_column'],
					'show_in_nav_menus' => $params['show_in_nav_menus'],
					'hierarchical' => $params['hierarchical'],
					'update_count_callback' => '_update_generic_term_count',
					'sort' => $params['sort'],
					'rewrite' => array( 'slug' => $params['rewrite']['slug'] )
				) 
			);
		}
	}
}




/**
 *  wpuxss_eml_on_wp_loaded
 *
 *  @since    1.0
 *  @created  03/11/13
 */
add_action( 'wp_loaded', 'wpuxss_eml_on_wp_loaded' );

function wpuxss_eml_on_wp_loaded() 
{
	$wpuxss_eml_taxonomies = get_option('wpuxss_eml_taxonomies');
	if ( empty($wpuxss_eml_taxonomies) ) $wpuxss_eml_taxonomies = array();
	$taxonomies = get_taxonomies(array(),'object');
	
	// discover 'foreign' taxonomies
	foreach ( $taxonomies as $taxonomy => $params )
	{		
		if ( !empty($params->object_type) && !array_key_exists($taxonomy,$wpuxss_eml_taxonomies) && !in_array('revision',$params->object_type) && !in_array('nav_menu_item',$params->object_type) && $taxonomy != 'post_format' )
		{
			$wpuxss_eml_taxonomies[$taxonomy] = array(
				'eml_media' => 0,
				'admin_filter' => 0,
				'media_uploader_filter' => 0,
				'show_admin_column' => isset($params->show_admin_column) ? $params->show_admin_column : 0,
				'show_in_nav_menus' => isset($params->show_in_nav_menus) ? $params->show_in_nav_menus : 0,
				'hierarchical' => $params->hierarchical ? 1 : 0,
				'sort' => isset($params->sort) ? $params->sort : 0
			);
			
			if ( in_array('attachment',$params->object_type) )
				$wpuxss_eml_taxonomies[$taxonomy]['assigned'] = 1;
			else 
				$wpuxss_eml_taxonomies[$taxonomy]['assigned'] = 0;
		}
	}
	
	// assign/unassign taxonomies to atachment
	foreach ( $wpuxss_eml_taxonomies as $taxonomy => $params )
	{		
		if ( $params['assigned'] )
			register_taxonomy_for_object_type( $taxonomy, 'attachment' );
		
		if ( ! $params['assigned'] )
			wpuxss_eml_unregister_taxonomy_for_object_type( $taxonomy, 'attachment' );
	}
	
	// update_count_callback for attachment taxonomies if needed
	foreach ( $taxonomies as $taxonomy => $params )
	{ 
		if ( in_array('attachment',$params->object_type) )
		{
			global $wp_taxonomies;
			
			if ( !isset($wp_taxonomies[$taxonomy]->update_count_callback) || empty($wp_taxonomies[$taxonomy]->update_count_callback) )
				$wp_taxonomies[$taxonomy]->update_count_callback = '_update_generic_term_count';
		}
	}
	
	update_option( 'wpuxss_eml_taxonomies', $wpuxss_eml_taxonomies );
}




/**
 *  wpuxss_eml_on_admin_init
 *
 *  @since    1.0
 *  @created  03/08/13
 */
 
add_action( 'admin_init', 'wpuxss_eml_on_admin_init' );

function wpuxss_eml_on_admin_init() 
{
	
	// plugin settings: taxonomies
	register_setting( 
		'wpuxss_eml_taxonomies', //option_group
		'wpuxss_eml_taxonomies', //option_name
		'wpuxss_eml_taxonomies_validate' //sanitize_callback
	);
	
	// plugin settings: mime types
	register_setting( 
		'wpuxss_eml_mimes', //option_group
		'wpuxss_eml_mimes', //option_name
		'wpuxss_eml_mimes_validate' //sanitize_callback
	);
	
	// plugin settings: mime types backup
	register_setting( 
		'wpuxss_eml_mimes_backup', //option_group
		'wpuxss_eml_mimes_backup' //option_name
	);
}




/**
 *  wpuxss_eml_admin_enqueue_scripts
 *
 *  @since    1.1.1
 *  @created  07/04/14
 */

add_action( 'admin_enqueue_scripts', 'wpuxss_eml_admin_enqueue_scripts' );

function wpuxss_eml_admin_enqueue_scripts() 
{
	global $wpuxss_eml_version,
	$wpuxss_eml_dir,
	$pagenow;
	
	
	// generic scripts
	wp_enqueue_script(
		'wpuxss-eml-media-models-script',
		$wpuxss_eml_dir . 'js/eml-media-models.js',
		array('jquery','backbone','media-models'),
		$wpuxss_eml_version,
		true
	);
	
	wp_enqueue_script(
		'wpuxss-eml-media-views-script',
		$wpuxss_eml_dir . 'js/eml-media-views.js',
		array('jquery','backbone','media-views'),
		$wpuxss_eml_version,
		true
	);
	
	
	// pass taxonomies to media uploader's filter
	$wpuxss_eml_taxonomies = get_option('wpuxss_eml_taxonomies');
	if ( empty($wpuxss_eml_taxonomies) ) $wpuxss_eml_taxonomies = array();
	
	$taxonomies_array = array();
	foreach ( get_object_taxonomies('attachment','object') as $taxonomy ) 
	{
		$terms_array = array();
		$terms = array();
		
		if ( $wpuxss_eml_taxonomies[$taxonomy->name]['media_uploader_filter'] )
		{

			ob_start();
			
				wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy->name, 'checked_ontop' => false, 'walker' => new Walker_Media_Taxonomy_Uploader_Filter() ) );
				
				$html = '';
				if ( ob_get_contents() != false )
					$html = ob_get_contents();
			
			ob_end_clean();
			
			$terms = array_filter( explode('|', $html) );
			
			if ( !empty($terms) )
			{
				foreach ($terms as $term)
				{
					$term = explode('>', $term);
					array_push($terms_array, array('term_id' => $term[0], 'term_name' => $term[1]));
				}
				$taxonomies_array[$taxonomy->name] = array(
					'list_title' => $taxonomy->labels->all_items,
					'term_list' => $terms_array
				);
			}
		}
	}
		
	wp_localize_script( 
		'wpuxss-eml-media-views-script', 
		'wpuxss_eml_taxonomies', 
		$taxonomies_array
	);
	
	
	// Filters for /wp-admin/theme.php
	if ( 'themes.php' == $pagenow )
	{
		wp_enqueue_script(
			'wpuxss-eml-custom-header-script',
			$wpuxss_eml_dir . 'js/eml-custom-header.js',
			array('jquery','backbone','custom-header'),
			$wpuxss_eml_version,
			true
		);
		
		wp_enqueue_script(
			'wpuxss-eml-custom-background-script',
			$wpuxss_eml_dir . 'js/eml-custom-background.js',
			array('jquery','backbone','custom-background'),
			$wpuxss_eml_version,
			true
		);
	}
	
	
	// admin styles
	wp_enqueue_style( 
		'wpuxss-eml-admin-custom-style', 
		$wpuxss_eml_dir . 'css/eml-admin.css',
		array('media-views'), 
		$wpuxss_eml_version,
		'all' 
	);

}




/**
 *  wpuxss_eml_customize_controls_scripts
 *
 *  Filters for /wp-admin/customize.php
 *
 *  @since    1.1.1
 *  @created  07/04/14
 */

add_action( 'customize_controls_enqueue_scripts', 'wpuxss_eml_customize_controls_scripts' );

function wpuxss_eml_customize_controls_scripts() 
{
	global $wpuxss_eml_version,
	$wpuxss_eml_dir;
	
	wp_enqueue_script(
		'wpuxss-eml-customize-controls-script',
		$wpuxss_eml_dir . 'js/eml-customize-controls.js',
		array('jquery','backbone','customize-controls'),
		$wpuxss_eml_version,
		true
	);
}




/**
 *  wpuxss_eml_on_activation
 *
 *  @since    1.0
 *  @created  28/09/13
 */ 

function wpuxss_eml_on_activation() 
{	
	global $wpuxss_eml_version, $wpuxss_eml_old_version;

	if( $wpuxss_eml_version != $wpuxss_eml_old_version )
		update_option('wpuxss_eml_version', $wpuxss_eml_version );
	
	if( empty($wpuxss_eml_old_version) || $wpuxss_eml_old_version == '0.0.3' )
	{		
		$wpuxss_eml_taxonomies['media_category'] = array(
			'assigned' => 1,
			'eml_media' => 1,
			'admin_filter' => 1,
			'media_uploader_filter' => 1,
			'labels' => array(
				'name' => 'Media Categories',
				'singular_name' => 'Media Category',
				'menu_name' => 'Media Categories',
				'all_items' => 'All Media Categories',
				'edit_item' => 'Edit Media Category',
				'view_item' => 'View Media Category',
				'update_item' => 'Update Media Category',
				'add_new_item' => 'Add New Media Category',
				'new_item_name' => 'New Media Category Name',
				'parent_item' => 'Parent Media Category',
				'parent_item_colon' => 'Parent Media Category:',
				'search_items' => 'Search Media Categories'
			),
			'public' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'media_category' ),
			'sort' => 0
		);
		
		$allowed_mimes = get_allowed_mime_types();
		
		foreach ( wp_get_mime_types() as $type => $mime )
		{
			$wpuxss_eml_mimes[$type] = array(
				'mime' => $mime,
				'singular' => $mime,
				'plural' => $mime,
				'filter' => 0,
				'upload' => isset($allowed_mimes[$type]) ? 1 : 0
			);
		}
		
		$wpuxss_eml_mimes['pdf']['singular'] = 'PDF';
		$wpuxss_eml_mimes['pdf']['plural'] = 'PDFs';
		$wpuxss_eml_mimes['pdf']['filter'] = 1;
	
		update_option( 'wpuxss_eml_taxonomies', $wpuxss_eml_taxonomies );
		update_option( 'wpuxss_eml_mimes', $wpuxss_eml_mimes );
		update_option( 'wpuxss_eml_mimes_backup', $wpuxss_eml_mimes );
	}
}

?>