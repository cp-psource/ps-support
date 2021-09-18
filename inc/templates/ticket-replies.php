<?php if ( psource_support_has_replies() ): ?>
	<?php psource_support_list_replies(
		array(
			'reply_class' => 'row',
			'author_class' => '',
			'message_class' => '',
			'date_class' => ''
		)
	); ?>
<?php else: ?>
	<h2 class="alert-box info"><?php _e( 'Es gibt noch keine Antworten', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></h2>
<?php endif; ?>

<?php if ( ! psource_support_is_ticket_closed() && psource_support_current_user_can( 'insert_reply' ) ): ?>
	<div id="support-system-reply-form-wrap">
		<h2><?php _e( 'Antwort hinzufügen', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></h2>
		<?php psource_support_reply_form(); ?>
	</div>
<?php endif; ?>