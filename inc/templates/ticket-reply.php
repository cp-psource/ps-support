<div class="support-system-reply row <?php echo esc_attr( psource_support_the_reply_class() ); ?>" id="support-system-reply-<?php echo psource_support_get_the_reply_id(); ?>">
	<div class="large-12 columns">
		<div class="support-system-ticket-reply-wrap">
			<div class="support-system-reply-header clearfix">
				<div class="support-system-reply-avatar"><?php echo get_avatar( psource_support_get_the_poster_id(), 32 ); ?></div>
				<div class="support-system-reply-poster">
					<h3>
						<?php echo psource_support_get_the_poster_username(); ?> 
						<?php if ( psource_support_is_staff( psource_support_get_the_poster_id() ) ): ?>
							<span class="label"><?php _e( 'Mitarbeiter', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></span>
						<?php endif; ?>
					</h3>
				</div>
			</div>
			<p class="support-system-reply-date"><strong><?php echo psource_support_get_the_reply_date(); ?></strong></p>
			<hr/>
			<div class="row support-system-reply-message">
				<div class="large-12 columns">
					<?php echo psource_support_get_the_reply_message(); ?>			

					<?php if ( psource_support_reply_has_attachments() ): ?>
						<div class="support-system-reply-attachments">
							<h5><?php _e( 'Anhänge', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></h5>
							<ul>
							<?php foreach ( psource_support_get_the_reply_attachments() as $attachment_url ): ?>
								<li><a href="<?php echo esc_url( $attachment_url ); ?>" title="<?php printf( __( 'Download %s Datei', PSOURCE_SUPPORT_LANG_DOMAIN ), $attachment_url ); ?>" ><?php echo basename( $attachment_url ); ?></a></li>
							<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	

</div>