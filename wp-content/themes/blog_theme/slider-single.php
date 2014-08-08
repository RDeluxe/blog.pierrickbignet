<div id="slider-single">
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