<?php
get_header('index'); ?>
<div id="index_background_picture">
</div>

<?php
if(have_posts()) : while( have_posts() ) : the_post();
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
				<!-- a href="#">18 likes</a> // &nbsp; --><?php comments_number( 'no comments', '1 comment', '% comments' ); ?>
				<!-- <span class="level">level 5</span> -->
			</div>
			<?php $note = get_post_custom_values("note"); ?>
			<div id="scoreBar" class="note" <?php if(isset($note[0])) echo "score=".$note[0]; ?>>
				<?php
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
		</div>
	</div>
<?php
endwhile;
endif;
?>

<div id="pagination">
	<?php
	previous_posts_link('« Newer Entries', 0);
	next_posts_link('Older Entries »', 0);
	?>
</div>
<?php get_footer(); ?>