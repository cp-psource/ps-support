<?php settings_errors( 'support_system_submit_new_ticket' ); ?>
<form method="post" action="" enctype="multipart/form-data">
	<table class="form-table">
		
		<p><span class="description"><?php _e( '* alle Felder sind erforderlich.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></p>
		<?php ob_start(); ?>
			<input type="text" name="subject" class="widefat" maxlength="100" value="<?php echo $subject; ?>"><br/>
			<span class="description"><?php _e( '(max: 100 Zeichen)', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
		<?php $this->render_row( __( 'Betreff', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php $this->render_row( __( 'Kategorie', INCSUB_SUPPORT_LANG_DOMAIN ), $categories_dropdown ); ?>
		<?php $this->render_row( __( 'Priorität', INCSUB_SUPPORT_LANG_DOMAIN ), $priorities_dropdown ); ?>

		<?php ob_start(); ?>
			<?php remove_all_filters( 'mce_buttons' ); ?>
			<?php remove_all_filters( 'mce_external_plugins' ); ?>
			<?php remove_all_filters( 'mce_buttons_1' ); ?>
			<?php remove_all_filters( 'mce_buttons_2' ); ?>
			<?php remove_all_filters( 'mce_buttons_3' ); ?>
			<?php wp_editor( $message, 'message-text', array( 'media_buttons' => true ) ); ?>
		<?php $this->render_row( __( 'Problem Beschreibung', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php ob_start(); ?>
			<div class="support-attachments"></div>
		<?php $this->render_row( __( 'Anhänge', INCSUB_SUPPORT_LANG_DOMAIN ),  ob_get_clean() ); ?>

		<?php do_action( 'support_new_ticket_fields' ); ?>
		
	
	</table>
	<p class="submit">
		<?php wp_nonce_field( 'add-new-ticket' ); ?>
		<?php submit_button( __( 'Neues Ticket einreichen', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-ticket', false ); ?>

	</p>
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
