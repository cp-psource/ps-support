

<form id="edit-ticket-form" action="#edit-ticket-form-errors" method="post" enctype="multipart/form-data">
	<?php if ( $errors ): ?>
		<div id="edit-ticket-form-errors">
			<?php foreach ( $errors as $error ): ?>
				<div class="support-system-error"><p><?php echo esc_html( $error['message'] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Neue Antwort einfügen', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>

	<h4 class="support-system-add-reply-subtitle"><?php _e("Bitte gib so viele Informationen wie möglich an, damit der Benutzer die Lösung/Anfrage verstehen kann.", INCSUB_SUPPORT_LANG_DOMAIN); ?></h4>
	<?php remove_all_filters( 'mce_buttons' ); ?>
	<?php remove_all_filters( 'mce_external_plugins' ); ?>
	<?php remove_all_filters( 'mce_buttons_1' ); ?>
	<?php remove_all_filters( 'mce_buttons_2' ); ?>
	<?php remove_all_filters( 'mce_buttons_3' ); ?>
	<?php wp_editor( $ticket_message, 'message-text', array( 'media_buttons' => true, 'quicktags' => array() ) ); ?>

	<table class="form-table">
		<?php ob_start(); ?>
			<div class="support-attachments"></div>
		<?php $this->render_row( __( 'Anhänge', INCSUB_SUPPORT_LANG_DOMAIN ),  ob_get_clean() ); ?>
		<?php $this->render_row(__( 'Kategorie', INCSUB_SUPPORT_LANG_DOMAIN ), $categories_dropdown ); ?>
		<?php $this->render_row(__( 'Priorität', INCSUB_SUPPORT_LANG_DOMAIN ), $priorities_dropdown ); ?>

		<?php if ( incsub_support_current_user_can( 'update_ticket' ) ): ?>

			<?php ob_start(); ?>
				<select name="responsibility" id="responsibility">
					<?php if ( $ticket->admin_id == get_current_user_id() ): ?>
						<option <?php selected( $responsibility, 'keep' ); ?> value="keep"><?php _e("Behalte die Verantwortung für dieses Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<option <?php selected( $responsibility, 'punt' ); ?> value="punt"><?php _e("Gib die Verantwortung auf, damit ein anderer Administrator sie akzeptieren kann", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
					<?php else: ?>
						<option <?php selected( $responsibility, 'accept' ); ?> value="accept"><?php _e("Übernimm die Verantwortung für dieses Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<option <?php selected( $responsibility, 'keep' ); ?> value="keep"><?php _e("Ticket nicht zugewiesen lassen", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<?php if ( ! empty( $ticket->admin_id ) ): ?>
							<option <?php selected( $responsibility, 'help' ); ?> value="help"><?php _e("Behalte den aktuellen Administrator bei und hilf mit einer Antwort", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<?php endif; ?>
					<?php endif; ?>
				</select>
			<?php $this->render_row(__( 'Ticketverantwortung', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
		<?php endif; ?>
		


		<?php if ( incsub_support_current_user_can( 'close_ticket', $ticket->ticket_id ) ): ?>
			<?php ob_start(); ?>
				<label for="closeticket">
					<input type="checkbox" name="closeticket" id="closeticket" value="1" <?php checked( $ticket->is_closed() ); ?>/> <strong><?php _e( 'Ja, schließe dieses Ticket.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></strong><br />
				</label>
				<span class="description"><?php _e("Sobald ein Ticket geschlossen ist, können Benutzer nicht mehr darauf antworten (oder es aktualisieren).", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
			<?php $this->render_row(__( "Ticket schließen?", INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
		<?php endif; ?>
		
		<input type="hidden" name="ticket_id" value="<?php echo $ticket->ticket_id; ?>" />
		<?php wp_nonce_field( 'add-ticket-reply-' . $ticket->ticket_id ); ?>
	</table>
	<p>
		<?php submit_button( __( 'Antwort hinzufügen', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary button-hero', 'submit-ticket-reply', false ); ?>
	</p>

	<div class="clear"></div>

</form>

<script>
	jQuery(document).ready(function($) {
		$('.wrap').support_system({
			attachments: {
				container_selector: '.support-attachments',
				button_text: " <?php _e( 'Dateien hinzufügen...', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
				button_class: 'button-secondary',
				remove_file_title: "<?php esc_attr_e( 'Datei löschen', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
				remove_link_class: "button-secondary",
				remove_link_text: " <?php _e( 'Datei löschen', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
			}
		});
	});
</script>
