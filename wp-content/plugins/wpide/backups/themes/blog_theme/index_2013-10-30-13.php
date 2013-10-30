<?php /* start WPide restore code */
                                    if ($_POST["restorewpnonce"] === "2fdcd54ad3f07e9c65f664f4b2e3d0a7331f932642"){
                                        if ( file_put_contents ( "/var/www/blog.pierrickbignet/wp-content/themes/blog_theme/index.php" ,  preg_replace("#<\?php /\* start WPide(.*)end WPide restore code \*/ \?>#s", "", file_get_contents("/var/www/blog.pierrickbignet/wp-content/plugins/wpide/backups/themes/blog_theme/index_2013-10-30-13.php") )  ) ){
                                            echo "Your file has been restored, overwritting the recently edited file! \n\n The active editor still contains the broken or unwanted code. If you no longer need that content then close the tab and start fresh with the restored file.";
                                        }
                                    }else{
                                        echo "-1";
                                    }
                                    die();
                            /* end WPide restore code */ ?><?php 
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
			<!-- a href="#">18 likes</a> // &nbsp; --><?php comments_number( 'no comments', '1 comment', '% comments' ); ?>
			<span class="level">level 5</span>
		</div>
		<div class="note">
			<?php
				$note = get_post_custom_values("note");
				if(isset($note[0]))
				{
					echo the_author_meta('nickname')."'s note <span class='note'>".$note[0]." / 100</span>";
				}
			?>
		</div>
	</div>

	<div class="news">
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
<?php
	get_footer(); 
	
?>