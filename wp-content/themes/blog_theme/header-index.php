<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <title><?php bloginfo('name'); ?></title>
  <meta name="description" content="<?php bloginfo( 'description' ); ?>">
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/reset.css">
  <link href="http://fonts.googleapis.com/css?family=Raleway:300" rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/global.css">
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/zoombox.css">
  <!-- This way of importing scripts it not recommended for a theme which may be published. Instead, we use wp_enqueue_scripts with dependencies -->
  <!-- <script src="<?php echo get_template_directory_uri(); ?>/js/zoombox.js" type="text/javascript"></script> -->
  <!-- <script src="<?php echo get_template_directory_uri(); ?>/js/jquery.hoverdir.js" type="text/javascript"></script> -->
  <!-- <script src="<?php echo get_template_directory_uri(); ?>/js/modernizr.js" type="text/javascript"></script> -->
  <!-- <script src="<?php echo get_template_directory_uri(); ?>/js/script.js" type="text/javascript"></script> -->
  <?php wp_head(); ?>
</head>
<body>
<div id="screen">
	<div id="header">
		<div id="banner">
			<div class="headline">
				<h1><a class="logo" href="<?php echo get_home_url(); ?>"><?php bloginfo('name'); ?></a></h1>
				<?php bloginfo('description'); ?>
			</div>
			 <?php do_action('icl_language_selector'); ?>
			<div class="subscribe"><a href="?page_id=2"><?php echo __('subscribe to the newsletter', 'wp_deluxe'); ?></a></div>
		</div>
		<?php get_template_part( 'slider', 'index' ); ?>
		<div id="menu">
			<div id="content_menu_index">
				<ul>
					<?php
					// affichage des catégories
					$args=array(
					  'orderby' => 'name',
					  'order' => 'ASC'
					  );
					$categories=get_categories($args);
					foreach($categories as $category)
					{
					    echo '<li><a class="'.$category->name.'" href="javascript:void(0);">'.$category->name.'</a></li> ';
					}
					?>
				</ul>
				<div class="show_all"><a href="#"><?php echo __('see all articles', 'wp_deluxe'); ?></a></div>
			</div>
		</div>
	</div>