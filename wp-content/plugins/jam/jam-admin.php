<?php

if (!is_admin()) exit;

// XML file for display purposes only of meta information and descriptions in the admin panel
// XML data not to be used for submission into WP database
$xml = simplexml_load_file($jamwp_plugin_directory.'/library.xml');
$s = json_decode(json_encode($xml),TRUE);
foreach ( $s['item'] as $x ) {
	$name = $x['name'];
	$jplug[$name] = array(
		'description' => $x['description'],
		'url' => $x['url'],
		'license' => $x['license']
		);
} // foreach s item

function jamwp_options() {

GLOBAL $jplug,$jamwp_collection,$jamwp_wp;
$jamwp_plugin_file = plugin_basename(__FILE__);
$jamwp_plugin_url = plugin_dir_url(__FILE__);
$jamwp_plugin_directory = dirname(__FILE__);

$jplug_options = get_option('jamwp');
$jplug_active = $jplug_options['active'];

if ( $_GET['activate'] && $_GET['tab'] != "jamexternal" ):
	check_admin_referer('jamwp-activate');
	$target_plugin = sanitize_text_field( $_GET['plugin'] ); // DEBUG: sanitize and urldecode this
	if  ( !in_array($target_plugin, $jamwp_collection) && !in_array($target_plugin, $jamwp_wp[0] ) && !in_array($target_plugin, $jamwp_wp['ui'] ) && !in_array($target_plugin, $jamwp_wp['effects'] )):
		print "<div class=\"wrap\">";
		print "Are you sure?";
		print "</div>";
		exit;
	endif;
	switch ( $_GET['activate'] ) {
		case "on":
			// These cases look reversed, because its based on table display
			unset($jplug_active[$target_plugin]);
		break;
		case "off":
			$jplug_active[$target_plugin] = 1;
		break;
		default:
		// nothing to inject.  
	}
	$jplug_options['active'] = $jplug_active;
	update_option('jamwp',$jplug_options);
endif;

switch ( $_GET['tab'] ) {
	case "wp": $wpTab = " nav-tab-active"; break;
	case "jampops": $optsTab = " nav-tab-active"; break;
	case "jamedit": $editTab = " nav-tab-active"; break;
	case "jamexternal": $externalTab = " nav-tab-active"; break;
	case "jamimport": $importTab = " nav-tab-active"; break;
	default: $mainTab = " nav-tab-active";
}// GET tab switch :: menu and pre-parsing

print <<<ThisHTML
	<div class="wrap">
		<style>
			.onoff a { outline:0; }
		</style>
		<h2>JAM Options</h2>
ThisHTML;
		print "<h3 class=\"nav-tab-wrapper\">";
			print " &nbsp;<a href=\"admin.php?page=jamwp&tab=main\" class=\"nav-tab$mainTab\">Library</a>";
			print "<a href=\"admin.php?page=jamwp&tab=wp\" class=\"nav-tab$wpTab\">WP Built-in</a>";
			print "<a href=\"admin.php?page=jamwp&tab=jamedit\" class=\"nav-tab$editTab\">Script Editors</a>";
			print "<a href=\"admin.php?page=jamwp&tab=jamexternal\" class=\"nav-tab$externalTab\">External Scripts</a>";
			//print "<a href=\"admin.php?page=jamwp&tab=jampops\" class=\"nav-tab$optsTab\">Options</a>";
		print "</h3>";
		
switch ( $_GET['tab'] ) {


case "jamexternal":

	$jamwp_plugin_file = plugin_basename(__FILE__);
	$jamwp_plugin_url = plugin_dir_url(__FILE__);
	$jamwp_plugin_directory = dirname(__FILE__);

	$options = get_option('jamwp');
	
	if ( $_GET['activate'] ):
		check_admin_referer( 'jamwp-activate' );
		$plugs = $options['external'];
		$plugs[$_GET['plugin']]['active'] = ($plugs[$_GET['plugin']]['active'])?false:true;
		$options['external'] = $plugs;
		update_option('jamwp',$options);
	endif;
	
	if ( $_GET['jamheader'] ):
		check_admin_referer( 'jamwp-locheader' );
		$plugs = $options['external'];
		if ( $plugs[$_GET['plugin']] ):
			$plugs[$_GET['plugin']]['header'] = ($_GET['jamheader'] == "h")?true:false; // could use the 1/0 from the GET but this prevents eroneous data
			$options['external'] = $plugs;
			update_option('jamwp',$options);
		else:
			print "<div class=\"error\"><p><strong>Script {$_GET['plugin']} not found</strong></p></div>";
		endif;
	endif;

	if ( $_GET['purge'] ):	
		if ( $_POST['purgeconfirm'] ):
			// verified choice, check nonce and purge if so
			check_admin_referer( 'jamwp_purge_externals', 'jamwp_nonce' );
			unset ($options['external']);
			update_option('jamwp',$options);
			print "<h4>All external scripts removed</h4>";
			print '<a href="admin.php?page=jamwp&tab=jamexternal" class="button">Continue</a>';
			// Display success notice, with link to continue
		else:
			// Not verified, so ask for them being sure
			print '<h4>This action will remove all external scripts from JAM settings. Are you sure?</h4>';
			print '<form method="post" action="admin.php?page=jamwp&tab=jamexternal&purge=1">';
			wp_nonce_field( 'jamwp_purge_externals', 'jamwp_nonce' );
			print '<input type="submit" class="button" value="Yes" name="purgeconfirm" /> <a href="admin.php?page=jamwp&tab=jamexternal" class="button">No</a>';
			print '</form>';
		endif;	
		break;
	endif; // GET PURGE
	
	if ( $_GET['delete'] ):
		check_admin_referer( 'jamwp_delete_external' );
		$externals = $options['external'];
		unset( $externals[$_GET['delete']] );
		$options['external'] = $externals;
		update_option('jamwp',$options);
	endif; // GET delete

	if ( $_GET['editexternal'] ):
		$externals = $options['external'];
		if ( !$externals[$_GET['editexternal']] ):
			print "Script not found";
			break;
		endif;
		$x = $externals[$_GET['editexternal']];
		$jamwp_url_submit_nonce = wp_nonce_field( 'jamwp_add_external', 'jamwp_nonce', true, false );
print <<<ThisHTML
		<form method="post" action="admin.php?page=jamwp&tab=jamexternal">
			{$jamwp_url_submit_nonce}
			<input type="hidden" name="jam_old_name" value="{$_GET['editexternal']}" />
			<label for="jam_nam">Name:</label> <input type="text" class="regular-text" name="jam_name" id="jam_name" value="{$_GET['editexternal']}" />
			<label for="jam_url" style="margin-left:20px;">URL:</label> <input style="margin-right:10px;" type="text" class="regular-text" name="jam_url" id="jam_url" value="{$x['url']}" />
			<input type="submit" value="Update" name="jam_edit_submit" class="button" /> <a href="admin.php?page=jamwp&tab=jamexternal" class="button">Cancel</a>
		</form>
ThisHTML;
		break;
	endif;

	if ( $_POST['jam_url'] ):
		// verify nonce
		check_admin_referer( 'jamwp_add_external', 'jamwp_nonce' );
		if ( !$_POST['jam_name'] ):
			print "<div class=\"error\"><p><strong>Must provide a script name</strong></p></div>";
		else:
			$externals = $options['external'];
			// validate that it looks like a URL? And add protocol if it doesn't have one?  Assume http://? UPDATE
			// if there's an old name, delete that one
			if ( $_POST['jam_old_name'] ) unset( $externals[$_POST['jam_old_name']] );
			$externals[$_POST['jam_name']]['url'] = $_POST['jam_url'];
			$options['external'] = $externals;
			update_option('jamwp',$options);
			if ( $_POST['jam_edit_submit'] ) print "<div class=\"updated\"><p><strong>Script updated</strong></p></div>";
		endif;
	endif;

	$jamwp_url_submit_nonce = wp_nonce_field( 'jamwp_add_external', 'jamwp_nonce', true, false );
	
print <<<ThisHTML
<p>
	<form method="post">
		{$jamwp_url_submit_nonce}
		<label for="jam_name">NAME: </label><input type="text" class="regular-text" name="jam_name" id="jam_name" />
		<label for="jam_url" style="margin-left:25px;">URL: </label><input type="text" class="regular-text" name="jam_url" id="jam_url" /> <input type="submit" name="jam_url_submit" id="jam_url_submit_top" class="button" value="Add" /><br />
	</form>
</p>
	<style>
		.widefat td { vertical-align:middle; }
	</style>
	<table class="widefat" id="exlibrary">
		<thead>
			<tr>
				<th width="22">&nbsp;</th>
				<th width="200">Script</th>
				<th>URL</th>
				<th width="15">Location</th>
				<th width="10"> </th>
				<th width="10"> </th>
			</tr>
		</thead>
		<tbody>
ThisHTML;

	if ( $options['external'] ):
		foreach ( $options['external'] as $x=>$y ) {	
			print "<tr>";
				$plugin_on = ($y['active'])?"on":"off";
					if ( $plugin_on == "on" ):
						$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/on-state.png\" alt=\"on\" />";
					else:
						$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/off-state.png\" alt=\"off\" />";
					endif;
					$on_style = ($plugin_on == "on")?' style="font-weight:bold; color:green"':"";
					$url = wp_nonce_url("admin.php?page=jamwp&tab=jamexternal&activate=$plugin_on&plugin=$x",'jamwp-activate');
				print "<td class=\"onoff\" valign=\"middle\"><a data-plugin=\"$x\" href=\"$url\"$on_style>$plugin_on_image</a></td>";
				print "<td valign=\"middle\">$x</td>";
				print "<td valign=\"middle\">{$y['url']}</td>";
				if ( $y['header']):
					$url = wp_nonce_url("admin.php?page=jamwp&tab=jamexternal&jamheader=f&plugin=$x",'jamwp-locheader');
					print "<td valign=\"middle\" align=\"center\"><a href=\"$url\">header</a></td>";
				else:
					$url = wp_nonce_url("admin.php?page=jamwp&tab=jamexternal&jamheader=h&plugin=$x",'jamwp-locheader');
					print "<td valign=\"middle\" align=\"center\"><a href=\"$url\">footer</a></td>";
				endif;
				print '<td valign=\"middle\"><a href="admin.php?page=jamwp&tab=jamexternal&editexternal='.$x.'">edit</a></td>';
				$url = wp_nonce_url("admin.php?page=jamwp&tab=jamexternal&delete=$x",'jamwp_delete_external');
				print '<td valign=\"bottom\"><a href="'.$url.'">delete</a></td>';
			print "</tr>";
		} // external loop
	else:
		print "<tr><td> </td><td>No External Scripts</td></tr>";
	endif;

print <<<ThisHTML
		</tbody>
	</table>
ThisHTML;
		
	// only display the remove all button if there is more than one item.
	if ( count($options['external']) > 1 ) print '<p><a href="admin.php?page=jamwp&tab=jamexternal&purge=1" class="button">Remove All</a></p>';
	
print <<<ThisHTML
<div style="margin-top:30px; padding-top:20px; border-top:1px solid #ccc;">
<h3>External Scripts Help</h3>
<h4>Adding a new script</h4>
<p>Simply insert information into the name and URL spaces in the form at the top of the page. The name can be anything the administrator likes, it is only for display in the list. Refrain from using symbols or non alpha-numeric characters for script names.</p>
<p>The URL must be explicit to the location, for example <strong>http://www.mydomain.com/myscript.js</strong>. The script can be hosted on the local site webhost, or a remote script location, such as CDN hosted scripts at Google.com.</p>
<p>By default, scripts are disabled and set to load in the footer - as is the Wordpress standard for script location. Click the checkbox left of the script name to enable the script, and click the link "footer" under heading "Location" to change the location to header. When the location is already 'header', clicking will set the location back to footer.
</div>
ThisHTML;

break; // case jamexternal

// ------------------------------------------------------------------------------------------------
case "jampops":
break; // case jampop

case "jamedit":

	$jplug_options = get_option('jamwp');

	if ( $_POST['jamscriptedits'] ):
		check_admin_referer( 'jamwp_script_update', 'jamwp_script_edit' );
		// preg_replace to remove script tags - just in case
		$_POST['headerscript'] = preg_replace('/<script>|<\/script>/','',$_POST['headerscript']);
		$_POST['footerscript'] = preg_replace('/<script>|<\/script>/','',$_POST['footerscript']);
		$jplug_options['headerscript'] = $_POST['headerscript'];
		$jplug_options['footerscript'] = $_POST['footerscript'];
		update_option('jamwp',$jplug_options);
		print "<div class=\"updated\"><p><strong>Scripts Updated</strong></p></div>";
	endif;

	$headerscript = stripslashes($jplug_options['headerscript']);
	$footerscript = stripslashes($jplug_options['footerscript']);

print <<<ThisHTML
	<form method="post">
ThisHTML;
	wp_nonce_field( 'jamwp_script_update','jamwp_script_edit' );
print <<<ThisHTML
		<table class="form-table">
			<tr>
				<th scope="row">Header Script<br />
					<p class="description">Add javascript here to add javascript to header. Script will appear after other script requests.</p>
					<p class="description">Do not use &lt;script&gt; tags.</p>
				</th>
				<td>
					<textarea cols="50" rows="10" name="headerscript" id="headerscript" class="large-text">$headerscript</textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">Footer Script
					<p class="description">Add javascript here to add javascript to footer. Script will appear after other script requests.</p>
					<p class="description">Do not use &lt;script&gt; tags</p>
				</th>
				<td>
					<textarea cols="50" rows="10" name="footerscript" id="footerscript" class="large-text">$footerscript</textarea>
				</td>
			</tr>
			<tr>
				<th>
					<input type="submit" class="button-primary" value="Update Scripts" name="jamscriptedits" id="jamscriptedits" />
				</th>
				<td> </td>
			</tr>
		</table>
	</form>
ThisHTML;
break;

case "wp":

	$mainClass = $uiClass = $effectsClass = $otherClass = ' class="button-secondary"'; // set all tabs 
	switch ( $_GET['wptab'] ){
		case "ui": $uiClass = ' class="button-primary"'; $jamwp_wp_collection = "ui"; break;
		case "effects": $effectsClass = ' class="button-primary"'; $jamwp_wp_collection = "effects"; break;
		case "other": $otherClass = ' class="button-primary"'; $jamwp_wp_collection = "other"; break;
		default:
		$mainClass = ' class="button-primary"'; $jamwp_wp_collection = 0;
	} // wp tab switch
print <<<ThisHTML
	<div style="margin-bottom:8px">
		<a href="admin.php?page=jamwp&tab=wp"$mainClass>jQuery</a>
		<a href="admin.php?page=jamwp&tab=wp&wptab=ui"$uiClass>jQuery UI</a>
		<a href="admin.php?page=jamwp&tab=wp&wptab=effects"$effectsClass>jQuery Effects</a>
		<a href="admin.php?page=jamwp&tab=wp&wptab=other"$otherClass>Other</a>
	</div>
	<style>
		.widefat td { vertical-align:middle; }
		.jamwp-meta { font-size:10px; color:#666; }
	</style>
	<table class="widefat" id="library">
		<thead>
			<tr>
				<th width="22" align="center"></th>
				<th>Name</th>
				<th>Description</th>
			</tr>
ThisHTML;
foreach ( $jamwp_wp[$jamwp_wp_collection] as $x ) {
	print "<tr>";
		$plugin_on = ($jplug_active[$x])?"on":"off";
		if ( $plugin_on == "on" ):
			$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/on-state.png\" alt=\"on\" />";
		else:
			$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/off-state.png\" alt=\"off\" />";
		endif;
		$on_style = ($plugin_on == "on")?' style="font-weight:bold; color:green"':"";
	print "<td class=\"onoff\"><a data-plugin=\"$x\" href=\"".wp_nonce_url("admin.php?page=jamwp&tab=wp&wptab=$jamwp_wp_collection&activate=$plugin_on&plugin=$x",'jamwp-activate')."\"$on_style>$plugin_on_image</a></td>";
	$jamwp_list_name = ($jplug[$x]['url'])?"<a href=\"{$jplug[$x]['url']}\" target=\"_blank\">$x</a>":$x;
	print "<td valign=\"middle\">$jamwp_list_name</td>";
		unset($metaset);
		$metaset = array();
		if ($jplug[$x]['url']) $metaset[] = "<a href=\"$jplug[$x]['url']\">Site</a>";
		switch ($jplug[$x]['license']){
			case "MIT":
				$metaset[] = 'Licence: <a href="http://opensource.org/licenses/MIT">MIT</a>';
			break;
			case "GPL":
				$metaset[] = 'Licence: <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>';
			break;
			case "WTFPL":
				$metaset[] = 'Licence: <a href="http://www.wtfpl.net/">GPL</a>';
			break;
			default:
				// no license if default - no action
		} // license switch
		$metadisplay = implode(" | ",$metaset); // UPGRADE - not used in initial release
	print "<td>{$jplug[$x]['description']}</td>";
	print "</tr>";
}
print <<<ThisHTML

		</thead>
	</table>
ThisHTML;
break;

default:
print <<<ThisHTML
	<style>
		.widefat td { vertical-align:middle; }
		.jamwp-meta { font-size:10px; color:#666; }
	</style>
	<table class="widefat" id="library">
		<thead>
			<tr>
				<th width="22" align="center"></th>
				<th>Plugin</th>
				<th>Description</th>
			</tr>
		</thead>
		<tbody>
			<tr>
ThisHTML;
foreach ( $jamwp_collection as $x ) {
	print "<tr>";
		$plugin_on = ($jplug_active[$x])?"on":"off";
		if ( $plugin_on == "on" ):
			$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/on-state.png\" alt=\"on\" />";
		else:
			$plugin_on_image = "<img src=\"{$jamwp_plugin_url}grfx/off-state.png\" alt=\"off\" />";
		endif;
		$on_style = ($plugin_on == "on")?' style="font-weight:bold; color:green"':"";
	print "<td valign=\"middle\" class=\"onoff\"><a data-plugin=\"$x\" href=\"".wp_nonce_url("admin.php?page=jamwp&activate=$plugin_on&plugin=$x",'jamwp-activate')."\"$on_style>$plugin_on_image</a></td>";
	$jamwp_list_name = ($jplug[$x]['url'])?"<a href=\"{$jplug[$x]['url']}\" target=\"_blank\">$x</a>":$x;
	print "<td valign=\"middle\">$jamwp_list_name</td>";
		unset($metaset);
		$metaset = array();
		if ($jplug[$x]['url']) $metaset[] = "<a href=\"$jplug[$x]['url']\">Site</a>";
		switch ($jplug[$x]['license']){
			case "MIT":
				$metaset[] = 'Licence: <a href="http://opensource.org/licenses/MIT">MIT</a>';
			break;
			case "GPL":
				$metaset[] = 'Licence: <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>';
			break;
			case "WTFPL":
				$metaset[] = 'Licence: <a href="http://www.wtfpl.net/">GPL</a>';
			break;
			default:
				// no license if default - no action
		} // license switch
		$metadisplay = implode(" | ",$metaset); // not used yet
	print "<td valign=\"middle\">{$jplug[$x]['description']}";
	print "</tr>";
}
print <<<ThisHTML
			</tr>
		</tbody>
		<tfoot>
		
		</tfoot>
	</div><!-- .wrap -->
ThisHTML;
} // GET tab switch :: body

} // jamwp_options()

function jamwp_options_menu() {
	add_menu_page( "JAM Options","JAM","manage_options","jamwp","jamwp_options",$icon );
} // jamwp_options_menu


add_action('admin_footer','jawmp_ajax_script');
function jawmp_ajax_script(){
// only do this if it's a JAM page
if ( $_GET['page'] == "jamwp" ):	
	$jamwp_plugin_file = plugin_basename(__FILE__);
	$jamwp_plugin_url = plugin_dir_url(__FILE__);
	$jamwp_plugin_directory = dirname(__FILE__);
	$jawmp_ajax_nonce = wp_create_nonce( 'jawmp-ajax-activate' );
	
print <<<ThisHTML
<script>
	(function($){
		$(document).ready(function(){
		
			$("#exlibrary .onoff a").on('click',function(_e) {
				// ignore right now. No-script method first
			});
		
			$("#library .onoff a").on('click',function(_e){
				_e.preventDefault();
				var targetCheck = $(this);
				var jamwp_plug_to_switch = $(this).attr('data-plugin');
				var data = {
					action: 'switch_onoff',
					plugin: jamwp_plug_to_switch,
					security: '{$jawmp_ajax_nonce}'
				};
				$.post(ajaxurl,data,function(_r){
					if ( _r == "on" ) {
						$(targetCheck).html('<img src="{$jamwp_plugin_url}grfx/on-state.png" alt="on" />');
					} else {
						$(targetCheck).html('<img src="{$jamwp_plugin_url}grfx/off-state.png" alt="off" />');
					}
				});
				alert(_r);
			});
				
		});
	})(jQuery);
</script>
ThisHTML;
endif; // GET jamwp
} // jawmp_ajax_script()

add_action('wp_ajax_switch_onoff','jawmp_ajax_onoff');
function jawmp_ajax_onoff() {

	check_ajax_referer( 'jawmp-ajax-activate', 'security' );
	global $wpdb;
	$jplug_options = get_option('jamwp');
	$jplug_active = $jplug_options['active'];
	$target_plugin = sanitize_text_field( $_POST['plugin'] );
	if ( $jplug_active[$target_plugin] ):
		unset($jplug_active[$target_plugin]);
		echo "off";
	else:
		$jplug_active[$target_plugin] = 1;
		echo "on";
	endif;
	$jplug_options['active'] = $jplug_active;
	update_option('jamwp',$jplug_options);
	die();
}

?>