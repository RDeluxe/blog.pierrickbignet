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
  <script src="<?php echo get_template_directory_uri(); ?>/js/jquery-1.9.1.min.js" type="text/javascript"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/js/zoombox.js" type="text/javascript"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/js/jquery.hoverdir.js" type="text/javascript"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/js/modernizr.js" type="text/javascript"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/js/script.js" type="text/javascript"></script>
</head>
<body>
<div id="screen">
	<div id="header">
		<div id="banner">
			<div class="headline">
				<h1><a class="logo" href="index.php"><?php bloginfo('name'); ?></a></h1>
				<?php bloginfo('description'); ?>
			</div>
			 <?php do_action('icl_language_selector'); ?>
			<div class="subscribe"><a href="?page_id=2"><?php echo __('abonnes toi aux reviews', 'wp_deluxe'); ?></a></div>
		</div>
		<div id="slider">
			<div id="content_slider">
				<a href="javascript:void(0);" class="left"></a>
				<a href="javascript:void(0);" class="right"></a>
				<div class="inner_slider">
				<?php 
					// affichage des images du slider
					$archive_query = new WP_Query('showposts=1000');
					while ($archive_query->have_posts()) : $archive_query->the_post();
					$category = get_the_category();
					$catArticle =  $category[0]->cat_name;
				?>
			 		<a class="<?php echo $catArticle; ?>" href="<?php the_permalink() ?>">
			 			<?php the_post_thumbnail(); ?>
			 			<div>
			 				<span class="title"><?php the_title(); ?></span>
			 				<span class="comments"><?php comments_number( 'no comments', '1 comment', '% comments' ); ?></span>
			 			</div>
			 		</a>
			
				<?php endwhile; ?>
				</div>
			</div>
		</div>
		<div id="menu">
			<div id="content_menu">
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