<?php 
	get_header();

	if(have_posts()) : while( have_posts() ) : the_post();
	
	$background = get_post_custom_values("background");
	if(isset($background[0]))
	{
		echo '<style>';
		echo '#screen {';
		echo 'background:url("'.$background[0].'") center 345px no-repeat #e7e7e2;';
		echo '}';
		echo '</style>';
	}
 ?>
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
		<div class="note">
			<?php
				$note = get_post_custom_values("note");
				if(isset($note[0]))
				{
					echo the_author_meta('nickname')."'s verdict <span class='note'>".$note[0]." / 100</span>";
				}
			?>
		</div>
	</div>

	<div class="news">
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
		<div class="cb"></div>

		<?php
			comments_template();
		?>
		<div class="cb"></div>
	</div> 
</div>

<?php	
	endwhile;
	endif;

	get_footer(); 
?>