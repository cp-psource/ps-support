<div id="support-system-single-ticket">
	<?php if ( incsub_support_has_tickets() ): incsub_support_the_ticket(); ?>
		<div class="support-system-ticket row <?php echo esc_attr( incsub_support_get_the_ticket_class() ); ?>" id="support-system-ticket-<?php echo incsub_support_get_the_ticket_id(); ?>">
			<div class="large-12 columns">
				<h1 class="text-center support-system-ticket-title"><?php echo incsub_support_get_the_ticket_title(); ?></h1>

				<?php if ( incsub_support_is_ticket_closed( incsub_support_get_the_ticket_id() ) ): ?>
					<div data-alert class="alert-box alert">
						<?php _e( 'Dieses Ticket ist geschlossen', INCSUB_SUPPORT_LANG_DOMAIN ); ?>
					</div>
				<?php endif; ?>
				<ul class="row">
					<li class="small-3 large-2 columns">
						<?php echo get_avatar( incsub_support_get_the_author_id(), 96 ); ?><br/>
					</li>
					<li class="small-9 large-10 columns">
						<ul class="row inline-list support-system-ticket-meta">
							<li class="first"><?php echo incsub_support_get_the_author(); ?></li>
							<li><?php echo incsub_support_get_the_ticket_date(); ?></li>
						</ul>
						<div class="row support-system-ticket-message">
							<?php echo incsub_support_get_the_ticket_message(); ?>
						</div>

						<?php $attachments = incsub_support_get_the_ticket_attachments(); ?>
						<?php if ( ! empty( $attachments ) ): ?>
							<div class="row support-system-ticket-attachments">
								<h5><?php _e( 'Anhänge', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h5>
								<ul>
									<?php foreach ( $attachments as $attachment ): ?>
										<li><a href="<?php echo esc_url( $attachment ); ?>" title="<?php printf( esc_attr__( 'Download %s Anhang', INCSUB_SUPPORT_LANG_DOMAIN ), basename( $attachment ) ); ?>"><?php echo basename( $attachment ); ?></a></li>
									<?php endforeach; ?>		
								</ul>
							</div>
						<?php endif; ?>
					</li>
				</ul>

				
			</div>
		</div>
		<hr>
		<div class="row">
			<div class="support-system-ticket-replies large-8 columns">
				<?php incsub_support_ticket_replies(); ?>
			</div>
			
			<div class="large-4 columns">
				<div class="row">
					<?php if ( ! incsub_support_is_staff() ): ?>
						
						<?php 
							echo incsub_support_widget( 
								array( 'class' => 'panel support-system-ticket-details large-12 columns', 'title' => __( 'Ticket Details', INCSUB_SUPPORT_LANG_DOMAIN ) ),
								'incsub_support_the_ticket_details_box'
							); 
						?>
					<?php else: ?>
						<?php 
							echo incsub_support_widget( 
								array( 'class' => 'panel support-system-ticket-details support-system-staff-box large-12 columns', 'title' => __( 'Bearbeite Ticket Details', INCSUB_SUPPORT_LANG_DOMAIN ) ),
								'incsub_support_the_staff_box',
								array( 'submit_class' => 'button expand' ) 
							); 
						?>
					<?php endif; ?>

					<?php if ( incsub_support_current_user_can( 'close_ticket', incsub_support_get_the_ticket_id() ) ): ?>
						<?php 
							echo incsub_support_widget( 
								array( 'class' => 'panel support-system-close-ticket large-12 columns' ),
								'incsub_support_the_open_close_box',
								array( 'submit_class' => 'button tiny' ) 
							); 
						?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>