<form id="support-tickets" method="post">
	<?php $tickets_table->search_box( __( 'Tickets suchen', 'psource-support' ), 's' ); ?>
	<?php $tickets_table->display(); ?>
</form>