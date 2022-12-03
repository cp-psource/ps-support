<div class="wrap">
	<?php include( 'ticket-status-links.php' ); ?>
	<?php include( 'tickets-table-form.php' ); ?>
</div>

<script>
	jQuery(document).ready(function($) {
		$( 'span.delete > a' )
			.click( function(e) {
				return confirm( '<?php _e( "Möchtest Du dieses Ticket wirklich löschen?", 'psource-support' ); ?>');
			})
	});
</script>