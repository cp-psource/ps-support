<?php

class PSource_Support_Submit_Ticket_Form_Shortcode extends PSource_Support_Shortcode {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'process_form' ) );
		if ( !is_admin() ) {
			add_shortcode( 'support-system-submit-ticket-form', array( $this, 'render' ) );
		}
	}

	public function process_form() {
		if ( isset( $_POST['support-system-submit-ticket'] ) && psource_support_current_user_can( 'insert_ticket' ) ) {

			$user_id = get_current_user_id();
			$blog_id = get_current_blog_id();

			$action = 'support-system-submit-ticket-' . $user_id . '-' . $blog_id;
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) )
				wp_die( __( 'Sicherheitsüberprüfungsfehler', PSOURCE_SUPPORT_LANG_DOMAIN ) );

			$subject = $_POST['support-system-ticket-subject'];
			if ( empty( $subject ) )
				wp_die( __( 'Bitte gib einen Betreff für das Ticket ein', PSOURCE_SUPPORT_LANG_DOMAIN ) );

			$message = $_POST['support-system-ticket-message'];
			if ( empty( $message ) )
				wp_die( __( 'Bitte füge eine Nachricht für das Ticket ein', PSOURCE_SUPPORT_LANG_DOMAIN ) );

			if ( isset(  $_POST['support-system-ticket-priority'] ) )
				$priority = absint( $_POST['support-system-ticket-priority'] );
			else
				$priority = 0;

			$args = array(
				'title' => $subject,
				'message' => $message,
				'ticket_priority' => $priority
			);

			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = psource_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded['result'], 'url' );
				}
				elseif ( $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$error_message = '<ul>';
					foreach ( $files_uploaded['result'] as $error ) {
						$error_message .= '<li>' . $error . '</li>';			
					}
					$error_message .= '</ul>';
					wp_die( $error_message );
				}
			}

			if ( isset( $_POST['support-system-ticket-category'] ) && absint( $_POST['support-system-ticket-category'] ) ) {
				$args['cat_id'] = absint( $_POST['support-system-ticket-category'] );
			}

			$args['blog_id'] = $blog_id;
			if ( ! empty( $_POST['support-system-ticket-blog'] ) ) {
				$blog_id = absint( $_POST['support-system-ticket-blog'] );
				$list = wp_list_pluck( get_blogs_of_user( $user_id ), 'userblog_id' );
				if ( in_array( $blog_id, $list ) )
					$args['blog_id'] = $blog_id;
			}

			$ticket_id = psource_support_insert_ticket( $args );

			if ( is_wp_error( $ticket_id ) )
				wp_die( $ticket_id->get_error_message() );

			$redirect_to = psource_support_get_support_page_url();
			if ( $redirect_to ) {
				wp_redirect( add_query_arg( 'tid', $ticket_id, $redirect_to ) );
				exit;
			}

		}
	}

	public function render( $atts ) {
		$this->start();

		if ( ! psource_support_current_user_can( 'insert_ticket' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'Du musst <a href="%s">angemeldet</a> sein, um ein neues Ticket zu erstellen', PSOURCE_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'Du hast nicht genügend Berechtigungen, um ein neues Ticket einzureichen', PSOURCE_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_submit_ticket_form_message', $message, 'ticket-form' );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			return $this->end();
		}

		$defaults = array(
			'blog_field' => true,
			'priority_field' => true,
			'category_field' => true
		);

		$atts = wp_parse_args( $atts, $defaults );
		extract( $atts );

		$blog_field = (bool)$blog_field;

		if ( ! psource_support()->query->is_single_ticket ) {
			?>
				<h2><?php _e( 'Sende ein neues Ticket', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></h2>
				<form method="post" id="support-system-ticket-form" action="#support-system-ticket-form-wrap" enctype="multipart/form-data">
					
					<input type="text" name="support-system-ticket-subject" value="" placeholder="<?php esc_attr_e( 'Betreff', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>"/>
					<br/>

					<?php if ( $priority_field ): ?>
						<?php psource_support_priority_dropdown( array( 'name' => 'support-system-ticket-priority', 'echo' => true ) ); ?><br/>
					<?php endif; ?>

					<?php if ( $category_field ): ?>
						<?php psource_support_ticket_categories_dropdown( array( 'name' => 'support-system-ticket-category', 'echo' => true ) ); ?><br/>
					<?php endif; ?>
					
					<br/>
					<?php if ( $blog_field && is_multisite() ): ?>
						<label for="support-system-ticket-blog">
							<?php _e( 'Meldest Du ein Ticket für eine bestimmte Site?', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>
							<?php psource_support_user_sites_dropdown( array( 'name' => 'support-system-ticket-blog', 'echo' => true ) ); ?>
						</label>
					<?php endif; ?>
					
					<div class="support-system-attachments"></div>

					<?php psource_support_editor( 'ticket' ); ?>
					<?php wp_nonce_field( 'support-system-submit-ticket-' . get_current_user_id() . '-' . get_current_blog_id() ); ?>
					<br/>

					<input type="submit" name="support-system-submit-ticket" class="button small" value="<?php esc_attr_e( 'Ticket übermitteln', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>" />
					
				</form>
				
			<?php
		}
		return $this->end();
	}
}