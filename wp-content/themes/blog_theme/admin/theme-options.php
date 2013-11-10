<?php 

function theme_options_init() {

	register_setting(
		'blog_options',       // Options group, see settings_fields() call in theme_options_render_page()
		'theme_options', // Database option, see get_theme_options()
		'theme_options_validate' // The sanitization callback, see theme_options_validate()
	);

	// Register our settings field group
	add_settings_section(
		'general', // Unique identifier for the settings section
		'', // Section title (we don't want one)
		'__return_false', // Section callback (we don't want anything)
		'theme_options' // Menu slug, used to uniquely identify the page; see theme_options_add_page()
	);

	add_settings_field( 'background', 'home background', 'settings_field_background', 'theme_options', 'general' );
	add_settings_field( 'google_analytics', 'Google Analytics', 'settings_field_google_analytics', 'theme_options', 'general' );

}
add_action( 'admin_init', 'theme_options_init' );


function option_page_capability( $capability ) {
	return 'edit_theme_options';
}
add_filter( 'option_page_capability_blog_options', 'option_page_capability' );

function theme_options_add_page() {
	$theme_page = add_theme_page(
		__( 'Theme Options', 'blog' ),   // Name of page
		__( 'Theme Options', 'blog' ),   // Label in menu
		'edit_theme_options',                    // Capability required
		'theme_options',                         // Menu slug, used to uniquely identify the page
		'theme_options_render_page' // Function that renders the options page
	);

	if ( ! $theme_page )
		return;

}
add_action( 'admin_menu', 'theme_options_add_page' );

function get_default_theme_options() {
	$default_theme_options = array(
		'background' => 'http://www.hdwallpapers.in/walls/windows_xp_bliss-wide.jpg',
		'google_analytics' => 'GA-XXXXX'
	);

	if ( is_rtl() )
 		$default_theme_options['theme_layout'] = 'sidebar-content';

	return apply_filters( 'default_theme_options', $default_theme_options );
}


function get_theme_options() {
	return get_option( 'theme_options', get_default_theme_options() );
}

function settings_field_background(){
	$options = get_theme_options();
	?>
	<input type="text" name="theme_options[background]" id="background" value="<?php echo esc_attr( $options['background'] ); ?>" />
	<?php
}

function settings_field_google_analytics(){
	$options = get_theme_options();
	?>
	<input type="text" name="theme_options[google_analytics]" id="google_analytics" value="<?php echo esc_attr( $options['google_analytics'] ); ?>" />
	<?php
}

function theme_options_render_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<?php $theme_name = function_exists( 'wp_get_theme' ) ? wp_get_theme() : get_current_theme(); ?>
		<h2><?php printf( __( '%s Theme Options', 'blog' ), $theme_name ); ?></h2>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php
				settings_fields( 'blog_options' );
				do_settings_sections( 'theme_options' );
				submit_button();
			?>
		</form>
	</div>
	<?php
}

function theme_options_validate( $input ) {
	$output = $defaults = get_default_theme_options();

	if ( isset( $input['background'] ))
		$output['background'] = $input['background'];

	if ( isset( $input['google_analytics'] ))
		$output['google_analytics'] = $input['google_analytics'];

	return apply_filters( 'theme_options_validate', $output, $input, $defaults );
}
