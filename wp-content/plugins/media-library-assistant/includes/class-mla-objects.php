<?php
/**
 * Media Library Assistant Custom Taxonomy and Widget objects
 *
 * @package Media Library Assistant
 * @since 0.1
 */

/**
 * Class MLA (Media Library Assistant) Objects defines and manages custom taxonomies for Attachment Categories and Tags
 *
 * @package Media Library Assistant
 * @since 0.20
 */
class MLAObjects {
	/**
	 * Initialization function, similar to __construct()
	 *
	 * @since 0.20
	 *
	 * @return	void
	 */
	public static function initialize() {
		self::_build_taxonomies();
	}

	/**
	 * Registers Attachment Categories and Attachment Tags custom taxonomies, adds taxonomy-related filters
	 *
	 * @since 0.1
	 *
	 * @return	void
	 */
	private static function _build_taxonomies( ) {
		if ( MLAOptions::mla_taxonomy_support('attachment_category') ) {
			$labels = array(
				'name' => _x( 'Att. Categories', 'taxonomy general name' ),
				'singular_name' => _x( 'Att. Category', 'taxonomy singular name' ),
				'search_items' => __( 'Search Att. Categories' ),
				'all_items' => __( 'All Att. Categories' ),
				'parent_item' => __( 'Parent Att. Category' ),
				'parent_item_colon' => __( 'Parent Att. Category:' ),
				'edit_item' => __( 'Edit Att. Category' ),
				'update_item' => __( 'Update Att. Category' ),
				'add_new_item' => __( 'Add New Att. Category' ),
				'new_item_name' => __( 'New Att. Category Name' ),
				'menu_name' => __( 'Att. Category' ) 
			);
			
			register_taxonomy(
				'attachment_category',
				array( 'attachment' ),
				array(
				  'hierarchical' => true,
				  'labels' => $labels,
				  'show_ui' => true,
				  'query_var' => true,
				  'rewrite' => true 
				)
			);
		}
		
		if ( MLAOptions::mla_taxonomy_support('attachment_tag') ) {
			$labels = array(
				'name' => _x( 'Att. Tags', 'taxonomy general name' ),
				'singular_name' => _x( 'Att. Tag', 'taxonomy singular name' ),
				'search_items' => __( 'Search Att. Tags' ),
				'all_items' => __( 'All Att. Tags' ),
				'parent_item' => __( 'Parent Att. Tag' ),
				'parent_item_colon' => __( 'Parent Att. Tag:' ),
				'edit_item' => __( 'Edit Att. Tag' ),
				'update_item' => __( 'Update Att. Tag' ),
				'add_new_item' => __( 'Add New Att. Tag' ),
				'new_item_name' => __( 'New Att. Tag Name' ),
				'menu_name' => __( 'Att. Tag' ) 
			);
			
			register_taxonomy(
				'attachment_tag',
				array( 'attachment' ),
				array(
				  'hierarchical' => false,
				  'labels' => $labels,
				  'show_ui' => true,
				  'update_count_callback' => '_update_post_term_count',
				  'query_var' => true,
				  'rewrite' => true 
				)
			);
		}
		
		$taxonomies = get_taxonomies( array ( 'show_ui' => true ), 'names' );
		foreach ( $taxonomies as $tax_name ) {
			if ( MLAOptions::mla_taxonomy_support( $tax_name ) ) {
				register_taxonomy_for_object_type( $tax_name, 'attachment');
				if (  'checked' == MLAOptions::mla_get_option( 'attachments_column' )
) {

					add_filter( "manage_edit-{$tax_name}_columns", 'MLAObjects::mla_taxonomy_get_columns_filter', 10, 1 ); // $columns
					add_filter( "manage_{$tax_name}_custom_column", 'MLAObjects::mla_taxonomy_column_filter', 10, 3 ); // $place_holder, $column_name, $tag->term_id
				} // option is checked
			} // taxonomy support
		} // foreach
	} // _build_taxonomies
	
	/**
	 * WordPress Filter for edit taxonomy "Attachments" column,
	 * which replaces the "Posts" column with an equivalent "Attachments" column.
	 *
	 * @since 0.30
	 *
	 * @param	array	column definitions for the edit taxonomy list table
	 *
	 * @return	array	updated column definitions for the edit taxonomy list table
	 */
	public static function mla_taxonomy_get_columns_filter( $columns ) {
		/*
		 * Adding or inline-editing a tag is done with AJAX, and there's no current screen object
		 */
		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'add-tag', 'inline-save-tax' ) ) ) {
			$post_type = !empty($_POST['post_type']) ? $_POST['post_type'] : 'post';
		}
		else {
			$screen = get_current_screen();
			$post_type = !empty( $screen->post_type ) ? $screen->post_type : 'post';
		}

		if ( 'attachment' == $post_type ) {
			if ( isset ( $columns[ 'posts' ] ) )
				unset( $columns[ 'posts' ] );
				
			$columns[ 'attachments' ] = 'Attachments';
		}
		
		return $columns;
	}
	
	/**
	 * WordPress Filter for edit taxonomy "Attachments" column,
	 * which returns a count of the attachments assigned a given term
	 *
	 * @since 0.30
	 *
	 * @param	string	current column value; always ''
	 * @param	array	name of the column
	 * @param	array	ID of the term for which the count is desired
	 *
	 * @return	array	HTML markup for the column content; number of attachments in the category
	 *					and alink to retrieve a list of them
	 */
	public static function mla_taxonomy_column_filter( $place_holder, $column_name, $term_id ) {
		/*
		 * Adding or inline-editing a tag is done with AJAX, and there's no current screen object
		 */
		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'add-tag', 'inline-save-tax' ) ) ) {
			$taxonomy = !empty($_POST['taxonomy']) ? $_POST['taxonomy'] : 'post_tag';
		}
		else {
			$screen = get_current_screen();
			$taxonomy = !empty( $screen->taxonomy ) ? $screen->taxonomy : 'post_tag';
		}

		$term = get_term( $term_id, $taxonomy );
		
		if ( is_wp_error( $term ) ) {
			error_log( "ERROR: mla_taxonomy_column_filter( {$taxonomy} ) - get_term " . $term->get_error_message(), 0 );
			return 0;
		}
		
		$request = array (
//			'fields' => 'ids',
			'post_type' => 'attachment', 
			'post_status' => 'inherit',
			'orderby' => 'none',
			'nopaging' => true,
			'posts_per_page' => 0,
			'posts_per_archive_page' => 0,
			'update_post_term_cache' => false,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $term->slug,
					'include_children' => false 
				) )
				);
				
		$results = new WP_Query( $request );
		if ( ! empty( $results->error ) ){
			error_log( "ERROR: mla_taxonomy_column_filter( {$taxonomy} ) - WP_Query " . $results->error, 0 );
			return 0;
		}

		$tax_object = get_taxonomy($taxonomy);

		return sprintf( '<a href="%1$s">%2$s</a>', esc_url( add_query_arg(
				array( 'page' => MLA::ADMIN_PAGE_SLUG, 'mla-tax' => $taxonomy, 'mla-term' => $term->slug, 'heading_suffix' => urlencode( $tax_object->label . ':' . $term->name ) ), 'upload.php' ) ), number_format_i18n( $results->post_count ) );
	}
} //Class MLAObjects

/**
 * Class MLA (Media Library Assistant) Text Widget defines a shortcode-enabled version of the WordPress Text widget
 *
 * @package Media Library Assistant
 * @since 1.60
 */
class MLATextWidget extends WP_Widget {

	/**
	 * Provides a unique name for the plugin text domain
	 */
	const MLA_TEXT_DOMAIN = 'media_library_assistant';

	/**
	 * Calls the parent constructor to set some defaults.
	 *
	 * @since 1.60
	 *
	 * @return	void
	 */
	function __construct() {
		$widget_args = array(
			'classname' => 'mla_text_widget',
			'description' => __( 'Shortcode(s), HTML and/or Plain Text', self::MLA_TEXT_DOMAIN )
		);
			
		$control_args = array(
			'width' => 400,
			'height' => 350
		);
		
		parent::__construct( 'mla-text-widget', __( 'MLA Text', self::MLA_TEXT_DOMAIN ), $widget_args, $control_args );
	}

	/**
	 * Display the widget content - called from the WordPress "front end"
	 *
	 * @since 1.60
	 *
	 * @param	array	Widget arguments
	 * @param	array	Widget definition, from the database
	 *
	 * @return	void	Echoes widget output
	 */
	function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$text = do_shortcode( apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance ) );
		echo $args['before_widget'];
		if ( !empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; } ?>
			<div class="textwidget"><?php echo !empty( $instance['filter'] ) ? wpautop( $text ) : $text; ?></div>
		<?php
		echo $args['after_widget'];
	}

	/**
	 * Echo the "edit widget" form on the Appearance/Widgets admin screen
	 *
	 * @since 1.60
	 *
	 * @param	array	Previous definition values, from the database
	 *
	 * @return	void	Echoes "edit widget" form
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags($instance['title']);
		$text = esc_textarea($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', self::MLA_TEXT_DOMAIN); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs', self::MLA_TEXT_DOMAIN); ?></label></p>
<?php
	}

	/**
	 * Sanitize widget definition as it is saved to the database
	 *
	 * @since 1.60
	 *
	 * @param	array	Current definition values, to be saved in the database
	 * @param	array	Previous definition values, from the database
	 *
	 * @return	array	Updated definition values to be saved in the database
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] = isset($new_instance['filter']);
		return $instance;
	}

	/**
	 * Register the widget with WordPress
	 * 
	 * Defined as public because it's an action.
	 *
	 * @since 1.60
	 *
	 * @return	void
	 */
	public static function mla_text_widget_widgets_init_action(){
		register_widget('MLATextWidget');
	}

	/**
	 * Load a plugin text domain
	 * 
	 * Defined as public because it's an action.
	 *
	 * @since 1.60
	 *
	 * @return	void
	 */
	public static function mla_text_widget_plugins_loaded_action(){
		load_plugin_textdomain( self::MLA_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
} // Class MLATextWidget

/*
 * Actions are added here, when the source file is loaded, because the MLATextWidget
 * object(s) are created too late to be useful.
 */
add_action('widgets_init','MLATextWidget::mla_text_widget_widgets_init_action');
add_action('plugins_loaded','MLATextWidget::mla_text_widget_plugins_loaded_action');
?>