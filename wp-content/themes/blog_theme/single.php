<?php
	get_header('single');
	if(have_posts()) : while( have_posts() ) : the_post();
 ?>
<div id="news_background_picture">

</div>
<div id="news_content">
	<div class="block_title">
		<div class="title">
			<?php edit_post_link(''); ?>
			<a href="<?php the_permalink() ?>">
				<?php
					echo the_title();
				?>
			</a>
			<div class="date"><?php the_time('j F Y') ?></div>
		</div>
		<div class="social">
			<!-- <a href="#">18 likes</a> // &nbsp; --><?php comments_number( 'no comments', '1 comment', '% comments' ); ?>
			<!-- <span class="level">level 5</span> -->
		</div>
		<div id="scoreBar" class="note" <?php echo "score='".$note[0]."'" ?>>
			<?php
				$note = get_post_custom_values("note");
				if(isset($note[0]))
				{
					echo the_author_meta('nickname')."'s verdict <span>".$note[0]." / 100</span>";
				}
			?>
		</div>
	</div>

	<div class="news clearfix">
		<?php
		echo the_content();
		?>
		<!-- Displaying the rating of the game at the end of the article, using knob jQuery, and final comments, if a rating is present -->
		<?php
			$note = get_post_custom_values("note");
			if(isset($note[0]))
			{
				get_template_part( 'rating-block' );
			}
		?>
	</div>
	<div class="comments_content">
		<div class="commenter">Comments</div>
		<!-- <a href="#"><div class="like">liker</div></a> -->
		<div class="clearfix"></div>

		<?php
			comments_template();
		?>
		<div class="clearfix"></div>
	</div>
</div>

<?php
	endwhile;
	endif;

	get_footer();
?>