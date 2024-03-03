<style>
	.accordion ul li {
		list-style: disc;
		margin-left:25px;
	}
	#faq-categories .faq-categories-column:first-child {
		width: 36%;
	}
	#faq-categories .faq-categories-column {
		width: 32%;
		min-width: 200px;
		float: left;
	}
	.faq-question-title {
		cursor:pointer;
		background:none;
		font-size:15px;
		font-weight:normal;
	}
</style>

<div id="faq-categories" class="metabox-holder">
	<div class="postbox">
		<h3 class="hndle"><span><?php _e( 'FAQ-Kategorien', 'psource-support' ); ?></span></h3>
		<div class="inside">
			<div class="faq-categories-column">
				<h4><?php _e( 'Suche', 'psource-support' ); ?></h4>
				<form method="post">
					<input type="text" name="faq-s" value="<?php echo isset( $_POST['faq-s'] ) ? esc_attr( stripslashes_deep( $_POST['faq-s'] ) ) : ''; ?>">
					<?php wp_nonce_field( 'faq_search' ); ?>
					<?php submit_button( __( 'Suche', 'psource-support' ), 'secondary', 'submit-faq-search' ); ?>
				</form>
			</div>
			<div class="faq-categories-column">
				<ul>
					<?php for ( $i = 0; $i < $half_of_array ; $i++ ): ?>
						<li><a href="#" class="button button-secondary faq-category" class="faq-category" data-cat-id="<?php echo $faq_categories[ $i ]->cat_id; ?>"><?php echo $faq_categories[ $i ]->cat_name . ' (' . $faq_categories[ $i ]->faqs . ')'; ?></a></li>
					<?php endfor; ?>
				</ul>
			</div>
			<div class="faq-categories-column">
				<ul>
					<?php for ( $i = $half_of_array; $i < count( $faq_categories ) ; $i++ ): ?>
						<li><a href="#" class="faq-category button button-secondary" data-cat-id="<?php echo $faq_categories[ $i ]->cat_id; ?>"><?php echo $faq_categories[ $i ]->cat_name . ' (' . $faq_categories[ $i ]->faqs . ')'; ?></a></li>
					<?php endfor; ?>
				</ul>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

<div id="faq-category-details" class="metabox-holder">
	<?php foreach ( $faq_categories as $category ): ?>
		<div id="faq-category-<?php echo $category->cat_id; ?>" class="faq-category-wrap">
			<?php foreach ( $category->answers as $faq ): ?>
				<div class="postbox closed" data-faq-id="<?php echo $faq->faq_id; ?>">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br></div>
					<h3 class="hndle"><span><?php echo $faq->question; ?></span></h3>
					<div class="inside">
		    			<?php 
		    				add_filter( 'the_content', 'wptexturize'        );
							add_filter( 'the_content', 'convert_smilies'    );
							add_filter( 'the_content', 'convert_chars'      );
							add_filter( 'the_content', 'wpautop'            );
							add_filter( 'the_content', 'shortcode_unautop'  );
							add_filter( 'the_content', 'prepend_attachment' );

							$answer = preg_replace_callback( '|^\s*(https?://[^\s"]+)\s*$|im', array( &$this, 'embed_media' ), $faq->answer );
		    				$answer = apply_filters( 'the_content', $answer ); 
						?>

						<div id="faq-answer-<?php echo $faq->faq_id; ?>">
							<?php echo ( $answer ); ?>
							<p class="submit" data-faq-id="<?php echo $faq->faq_id; ?>">
								<h4><u><?php _e( 'War diese Lösung hilfreich?', 'psource-support' ); ?></u></h4>
								<?php echo '<button class="button-primary vote-button" data-faq-id="' . $faq->faq_id . '" data-vote="yes"> ' . __( 'Ja', 'psource-support' ) . '</button> <button href="#" class="button vote-button" data-faq-id="' . $faq->faq_id . '" data-vote="no"> ' . __( 'Nein', 'psource-support' ) . '</button>'; ?>
								<span class="spinner support-system-spinner"></span>
							</p>
						</div>						
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>

<script>
	jQuery(document).ready(function($) {
		$('.wrap').support_system();
	});
</script>
<style>
	.support-system-spinner {
		float:none;
	}
</style>