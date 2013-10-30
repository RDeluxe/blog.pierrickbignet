<?php
/*
Plugin Name: Image Size to Media Selection
Plugin URI: https://github.com/media317/image-size-to-media-selection
Description: Adds all available image sizes to the WordPress media size dropdown selector.
Author: Alan Smith
Version: 1.0
Author URI: http://media317.net
License: Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : asmith@media317.com)

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


//** Add image sizes to Media Selection */
add_filter('image_size_names_choose', 'me_display_image_size_names_muploader', 11, 1);
function me_display_image_size_names_muploader( $sizes ) {
  
	$new_sizes = array();
	
	$added_sizes = get_intermediate_image_sizes();
	
	// $added_sizes is an indexed array, therefore need to convert it
	// to associative array, using $value for $key and $value
	foreach( $added_sizes as $key => $value) {
		$new_sizes[$value] = $value;
	}
	
	// This preserves the labels in $sizes, and merges the two arrays
	$new_sizes = array_merge( $new_sizes, $sizes );
	
	return $new_sizes;
}


?>
