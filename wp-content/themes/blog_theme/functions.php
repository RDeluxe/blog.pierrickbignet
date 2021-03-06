<?php

include 'admin/theme-options.php';
// -----------------------------------------------------------------------------------------------------------------------------
// We load scripts here instead that in the html <header>
// -----------------------------------------------------------------------------------------------------------------------------
  function add_scripts() {
    wp_enqueue_script(
        'modernizr',
        get_stylesheet_directory_uri() . '/js/modernizr.js'
    );
    wp_enqueue_script(
        'zoombox',
        get_stylesheet_directory_uri() . '/js/zoombox.js',
        array( 'jquery' )
    );
    wp_enqueue_script(
        'knob',
        get_stylesheet_directory_uri() . '/js/jquery.knob.js',
        array( 'jquery' )
    );
    wp_enqueue_script(
        'hoverdir',
        get_stylesheet_directory_uri() . '/js/jquery.hoverdir.js',
        array( 'jquery' )
    );
    wp_enqueue_script(
        'Theme script',
        get_stylesheet_directory_uri() . '/js/script.js',
        array( 'jquery' )
    );
/*    wp_enqueue_script(
        'TweenMax',
        'http://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js',
        array( 'jquery' )
    );
    wp_enqueue_script(
        'ScrollToPlugin',
        'http://cdnjs.cloudflare.com/ajax/libs/gsap/latest/plugins/ScrollToPlugin.min.js',
        array( 'jquery' )
    );*/
  }
  add_action( 'wp_enqueue_scripts', 'add_scripts' );


// -----------------------------------------------------------------------------------------------------------------------------
// On active les images à la une
// -----------------------------------------------------------------------------------------------------------------------------
add_theme_support('post-thumbnails');

set_post_thumbnail(250, 250);



// -----------------------------------------------------------------------------------------------------------------------------
// fonction pour enlever les images dans des balises p dans le contenu d'une news
// -----------------------------------------------------------------------------------------------------------------------------
function filter_ptags_on_images($content){
   return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}

add_filter('the_content', 'filter_ptags_on_images');



// -----------------------------------------------------------------------------------------------------------------------------
// fonction d'affichage des commentaires
// -----------------------------------------------------------------------------------------------------------------------------
function deluxe_comment( $comment, $args, $depth ) {
    // on compte les commentaires pour mettre une classe particuliere a la premiere.
	static $count = 1;
	if($count == 1)
	{
		echo '<div class="comment first">';
	}
	else
	{
		echo '<div class="comment">';
	}
	?>
		<div class="firstrow">
        <?php

            // on affiche le nickname si il est renseigné sinon son nom d'utilisateur (+lien vers son site, le truc par defaut quoi)
            if ($comment->user_id)
            {
                $user=get_userdata($comment->user_id);
                echo $user->user_nicename;
            }
            else
            {
                comment_author_link();
            }
        ?>
        <span> - <?php echo get_comment_date(); ?></span></div>
		<div class="text"><?php comment_text(); ?></div>
	</div>
	<?php
	$count++;
}

// -----------------------------------------------------------------------------------------------------------------------------
// add to tinyMCE custom css classes
// -----------------------------------------------------------------------------------------------------------------------------

add_filter( 'mce_buttons_2', 'my_mce_buttons_2' );

function my_mce_buttons_2( $buttons ) {
    array_unshift( $buttons, 'styleselect' );
    return $buttons;
}

add_filter( 'tiny_mce_before_init', 'my_mce_before_init' );

function my_mce_before_init( $settings ) {

    $style_formats = array(
    	array(
        	'title' => 'Paragraph italic',
        	'block' => 'p',
        	'classes' => 'italic'
    	),
        array(
        	'title' => 'Paragraph bold',
        	'block' => 'p',
        	'classes' => 'bold'
        ),
        array(
        	'title' => 'legend right',
        	'block' => 'p',
        	'classes' => 'legend_right'
        ),
        array(
        	'title' => 'legend left',
        	'block' => 'p',
        	'classes' => 'legend_left'
        ),
        array(
            'title' => 'legend center',
            'block' => 'p',
            'classes' => 'legend_center'
        )
    );

    $settings['style_formats'] = json_encode( $style_formats );

    return $settings;

}

add_action( 'admin_init', 'add_my_editor_style' );

function add_my_editor_style() {
	add_editor_style();
}


// -----------------------------------------------------------------------------------------------------------------------------
//  Custom post error
// -----------------------------------------------------------------------------------------------------------------------------


add_filter('wp_die_handler', 'get_my_custom_die_handler');

function get_my_custom_die_handler() {
    return 'my_custom_die_handler';
}

function my_custom_die_handler($message, $title='', $args=array()) {
 $errorTemplate = get_theme_root().'/'.get_template().'/commenterror.php';
 if(!is_admin() && file_exists($errorTemplate)) {
    $defaults = array( 'response' => 500 );
    $r = wp_parse_args($args, $defaults);
    $have_gettext = function_exists('__');
    if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
        if ( empty( $title ) ) {
            $error_data = $message->get_error_data();
            if ( is_array( $error_data ) && isset( $error_data['title'] ) )
                $title = $error_data['title'];
        }
        $errors = $message->get_error_messages();
        switch ( count( $errors ) ) :
        case 0 :
            $message = '';
            break;
        case 1 :
            $message = "<p>{$errors[0]}</p>";
            break;
        default :
            $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
            break;
        endswitch;
    } elseif ( is_string( $message ) ) {
        $message = "<p>$message</p>";
    }
    if ( isset( $r['back_link'] ) && $r['back_link'] ) {
        $back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
        $message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
    }
    if ( empty($title) )
        $title = $have_gettext ? __('WordPress &rsaquo; Error') : 'WordPress &rsaquo; Error';
    require_once($errorTemplate);
    die();
 } else {
    _default_wp_die_handler($message, $title, $args);
 }
}

// -----------------------------------------------------------------------------------------------------------------------------
//  disable admin bar on front
// -----------------------------------------------------------------------------------------------------------------------------


function my_function_admin_bar(){ return false; }
add_filter( 'show_admin_bar' , 'my_function_admin_bar');


// -----------------------------------------------------------------------------------------------------------------------------
//  custom background
// -----------------------------------------------------------------------------------------------------------------------------

function setCustomBackground() {

    if ( is_home() || is_page() || is_front_page() )
    {
        $theme_options = get_option('theme_options');
        $background = $theme_options['background'];
        $div_id = "index_background_picture";
    }
    else
    {
        $backgroundArray = get_post_custom_values("background");
        $background = $backgroundArray[0];
        $div_id = "news_background_picture";
    }
    $style = '<style type="text/css">';
    $style .= '#' .$div_id . ' {background:url("'.$background.'") center no-repeat #e7e7e2; }';
    $style .= '</style>';

    echo $style;

}
add_action('wp_head', 'setCustomBackground');


