<form id="support-tickets" method="post">
	<?php $tickets_table->search_box( __( 'Tickets suchen', PSOURCE_SUPPORT_LANG_DOMAIN ), 's' ); ?>
	<?php $tickets_table->display(); ?>
</form>