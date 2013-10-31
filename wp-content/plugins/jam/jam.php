<?php

/*
	Plugin Name: JAM for Wordpress
	Plugin URI: http://www.avant5.com/jam/
	Description: Easy managing and loading of jQuery plugins, libraries and the WP enqueued scripts for jQuery.  Complete with a full library of ready-to-use scripts.
	Author: Avant 5 Multimedia
	Version: 1.32
	Author URI: http://www.avant5.com
	
	Copyright 2013  Avant 5 Multimedia  ( email : info@avant5.com )

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

$blogURL = get_bloginfo('url');
$jamwp_plugin_file = plugin_basename(__FILE__);
$jamwp_plugin_url = plugin_dir_url(__FILE__);
$jamwp_plugin_directory = dirname(__FILE__);

if ( is_admin() ) include("jam-admin.php");

$jamwp_wp = array();
$jamwp_wp[0] = array("jQuery","jQuery Schedule","jQuery Suggest","jQuery Hotkeys","jQuery Form","jQuery Color","jQuery Masonry","Iris","Jcrop","MediaElement","ThickBox");
$jamwp_wp['ui'] = array("jQuery UI Core","jQuery UI Widget","jQuery UI Mouse","jQuery UI Accordion","jQuery UI Autocomplete","jQuery UI Slider","jQuery UI Tabs","jQuery UI Sortable","jQuery UI Draggable","jQuery UI Droppable","jQuery UI Selectable","jQuery UI Position","jQuery UI Datepicker","jQuery UI Resizable","jQuery UI Dialog","jQuery UI Button");
$jamwp_wp['effects'] = array("jQuery UI Effects","jQuery UI Effects - Blind","jQuery UI Effects - Bounce","jQuery UI Effects - Clip","jQuery UI Effects - Drop","jQuery UI Effects - Explode","jQuery UI Effects - Fade","jQuery UI Effects - Fold","jQuery UI Effects - Highlight","jQuery UI Effects - Pulsate","jQuery UI Effects - Scale","jQuery UI Effects - Shake","jQuery UI Effects - Slide","jQuery UI Effects - Transfer");
$jamwp_wp['other'] = array("Backbone JS","Underscore JS","Simple AJAX Code-kit");

$jamwp_collection = array("Arctext","aSlyder","Avgrund Modal","Countdown","FitText","Gridster","iCheck","jQuery Countdown","jQuery Knob","Lettering","Tubular","Typeahead");

function jamwp_scripts() {
	GLOBAL $jamwp_wp,$jamwp_collection;
	$options = get_option('jamwp');
	$plugins = $options['active'];
	if ($plugins['jQuery']) wp_enqueue_script( 'jquery' );
	if ($plugins['jQuery Form']) { wp_enqueue_script( 'jquery-form ' ); }
	if ($plugins['jQuery Color']) wp_enqueue_script( 'jquery-color ' );
	if ($plugins['jQuery Masonry']) wp_enqueue_script( 'jquery-masonry' );
	if ($plugins['jQuery Schedule']) wp_enqueue_script( 'schedule' );
	if ($plugins['jQuery Suggest']) wp_enqueue_script( 'suggest' );
	if ($plugins['jQuery Hotkeys']) wp_enqueue_script( 'jquery-hotkeys' );
	if ($plugins['Iris']) wp_enqueue_script( 'iris' );
	
	if ($plugins['jQuery UI Effects']) wp_enqueue_script( 'jquery-effects-core' );
	if ($plugins['jQuery UI Effects - Blind']) wp_enqueue_script( 'jquery-effects-blind' );
	if ($plugins['jQuery UI Effects - Bounce']) wp_enqueue_script( 'jquery-effects-bounce' );
	if ($plugins['jQuery UI Effects - Clip']) wp_enqueue_script( 'jquery-effects-clip' );
	if ($plugins['jQuery UI Effects - Drop']) wp_enqueue_script( 'jquery-effects-drop' );
	if ($plugins['jQuery UI Effects - Explode']) wp_enqueue_script( 'jquery-effects-explode' );
	if ($plugins['jQuery UI Effects - Fade']) wp_enqueue_script( 'jquery-effects-fade' );
	if ($plugins['jQuery UI Effects - Fold']) wp_enqueue_script( 'jquery-effects-fold' );
	if ($plugins['jQuery UI Effects - Highlight']) wp_enqueue_script( 'jquery-effects-highlight' );
	if ($plugins['jQuery UI Effects - Pulsate']) wp_enqueue_script( 'jquery-effects-pulsate' );
	if ($plugins['jQuery UI Effects - Scale']) wp_enqueue_script( 'jquery-effects-scale' );
	if ($plugins['jQuery UI Effects - Shake']) wp_enqueue_script( 'jquery-effects-shake' );
	if ($plugins['jQuery UI Effects - Slide']) wp_enqueue_script( 'jquery-effects-slide' );
	if ($plugins['jQuery UI Effects - Transfer']) wp_enqueue_script( 'jquery-effects-transfer' );
	
	if ($plugins['jQuery UI Core']) wp_enqueue_script( 'jquery-ui-core ' );
	if ($plugins['jQuery UI Widget']) wp_enqueue_script( '	jquery-ui-widget' );
	if ($plugins['jQuery UI Mouse']) wp_enqueue_script( 'jquery-ui-mouse' );
	if ($plugins['jQuery UI Accordion']) wp_enqueue_script( 'jquery-ui-accordion' );
	if ($plugins['jQuery UI Autocomplete']) wp_enqueue_script( 'jquery-ui-autocomplete' );
	if ($plugins['jQuery UI Slider']) wp_enqueue_script( 'jquery-ui-slider' );
	if ($plugins['jQuery UI Tabs']) wp_enqueue_script( 'jquery-ui-tabs' );
	if ($plugins['jQuery UI Sortable']) wp_enqueue_script( 'jquery-ui-sortable' );
	if ($plugins['jQuery UI Draggable']) wp_enqueue_script( 'jquery-ui-draggable' );
	if ($plugins['jQuery UI Droppable']) wp_enqueue_script( 'jquery-ui-droppable' );
	if ($plugins['jQuery UI Selectable']) wp_enqueue_script( 'jquery-ui-selectable' );
	if ($plugins['jQuery UI Position']) wp_enqueue_script( 'jquery-ui-position' );
	if ($plugins['jQuery UI Datepicker']) wp_enqueue_script( 'jquery-ui-datepicker' );
	if ($plugins['jQuery UI Resizable']) wp_enqueue_script( 'jquery-ui-resizable' );
	if ($plugins['jQuery UI Dialog']) wp_enqueue_script( 'jquery-ui-dialog' );
	if ($plugins['jQuery UI Button']) wp_enqueue_script( 'jquery-ui-button' );
	
	if ($plugins['Backbone JS']) wp_enqueue_script( 'backbone' );
	if ($plugins['Underscore JS']) wp_enqueue_script( 'underscore' );
	if ($plugins['Jcrop']) wp_enqueue_script( 'jcrop' );
	if ($plugins['Simple AJAX Code-kit']) wp_enqueue_script( 'sack' );
	if ($plugins['ThickBox']) wp_enqueue_script( 'thickbox' );
	if ($plugins['MediaElement']) wp_enqueue_script( 'wp-mediaelement' );
	
} // jamwp_scripts()

function jamwp_header() {
	GLOBAL $jamwp_wp,$jamwp_collection;
	$jamwp_plugin_file = plugin_basename(__FILE__);
	$jamwp_plugin_url = plugin_dir_url(__FILE__);
	$jamwp_plugin_directory = dirname(__FILE__);
	$options = get_option('jamwp');	
	$plugins = $options['active'];
	
	if ( $plugins['Arctext'] ) 
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/arctext/jquery.arctext.js\"></script>\n";
	if ( $plugins['aSlyder'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/aslyder/aslyder.js\"></script>\n";
	if ( $plugins['Avgrund Modal'] ) 
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/avgrund-modal/jquery.avgrund.js\"></script>\n";
	if ( $plugins['FitText'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/fittext/jquery.fittext.js\"></script>\n";
	if ( $plugins['Countdown'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/fittext/countdown.js\"></script>\n";
	if ( $plugins['Gridster'] ) 
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/gridster/jquery.gridster.min.js\"></script>\n";
	if ( $plugins['iCheck'] ) 
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/icheck/jquery.jquery.icheck.min.js\"></script>\n";
	if ( $plugins['jQuery Knob'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/jquery-knob/jquery.knob.js\"></script>\n";
	if ( $plugins['jQuery Countdown'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/jquery-countdown/jquery.countdown.js\"></script>\n";
	if ( $plugins['Lettering'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/lettering/jquery.lettering.js\"></script>\n";
	if ( $plugins['Tubular'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/tubular/jquery.tubular.1.0.js\"></script>\n";
	if ( $plugins['Typeahead'] )
		print "<script type=\"text/javascript\" src=\"{$jamwp_plugin_url}jquery-scripts/typeahead/typeahead.min.js\"></script>\n";
	if ( $options['external'] ):
		foreach ( $options['external'] as $x=>$y ) {
			if ( $y['active'] && $y['header'] ) print "\n<script type=\"text/javascript\" src=\"{$y['url']}\"></script>";
		}
		print "\n";
	endif;
	if ( $options['headerscript'] )
		print "<script type=\"text/javascript\">".stripslashes($options['headerscript'])."</script>";
} // jamwp_header()

function jamwp_footer() {
	$options = get_option('jamwp');
	if ( $options['external'] ):
		foreach ( $options['external'] as $x=>$y ) {
			if ( $y['active'] && !$y['header'] ) print "\n<script type=\"text/javascript\" src=\"{$y['url']}\"></script>";
		}
		print "\n";
	endif;
	if ( $options['footerscript'] ):
		print "\n<script type=\"text/javascript\">\n".stripslashes($options['footerscript'])."\n</script>\n";
	endif;
} // jamwp_footer()


add_action( 'wp_enqueue_scripts', 'jamwp_scripts' );
add_action('wp_head','jamwp_header');
add_action('wp_footer','jamwp_footer',20);


if ( is_admin() ):
	add_action('admin_menu', 'jamwp_options_menu');
endif;


?>