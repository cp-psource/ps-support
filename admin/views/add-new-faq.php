<?php settings_errors( 'support_system_submit_new_faq' ); ?>
<form method="post" action="">
		
	<table class="form-table">
		<?php ob_start(); ?>
			<input type="text" value="<?php echo esc_attr( $question ); ?>" class="widefat" name="question" id="question">
		<?php $this->render_row( 'Question', ob_get_clean() ); ?>

		<?php $this->render_row( __( 'FAQ Kategorie', 'psource-support' ), $categories_dropdown ); ?>

		<?php ob_start(); ?>
			<?php wp_editor( $answer, 'answer', array( 'media_buttons' => true ) ); ?> 
		<?php $this->render_row( 'Answer', ob_get_clean() ); ?>

	</table>

	<p class="submit">
		<?php wp_nonce_field( 'add-new-faq' ); ?>
		<?php submit_button( __( 'Sende neue FAQ', 'psource-support' ), 'primary', 'submit-new-faq', false ); ?>
		<a href="<?php echo esc_attr( $list_menu_url ); ?>" class="button-secondary"><?php _e( 'Zurück zur FAQ-Liste', 'psource-support' ); ?></a>
	</p>
</form>
