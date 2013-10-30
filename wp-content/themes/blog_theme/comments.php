<?php if ( have_comments() ) : ?>
	<div class="comments">
		<?php wp_list_comments( array( 'callback' => 'deluxe_comment', 'reverse_top_level' => 'false' ) ); ?>
	</div>

<?php endif; ?>



<div class="bloc_form">
	<?php 
	// formulaire d'envoit de commentaire
	$args = array(
		'id_form' => 'commentform',
		'id_submit' => 'submit',
		'title_reply' => '',
		'title_reply_to' => '',
		'cancel_reply_link' => '',
		'label_submit' => 'Envoyer',
		'comment_field' => '<label for="comment">Commentaire</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>',
		'must_log_in' => '',
		'logged_in_as' => '<p class="loged">connecté en tant que : '.$user_identity.'</p>',
		'comment_notes_before' => '',
		'comment_notes_after' => '',
		'fields' => apply_filters( 'comment_form_default_fields', array(
		'author' => '<label for="author">Pseudo</label> ' . ( $req ? '<span class="required">*</span>' : '' ) . '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" ' . $aria_req . ' />',
		'email' => '',
		'url' => '' ) ) 
		);


	comment_form($args); 
	?>  
</div>