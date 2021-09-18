<div class="row">
	<div class="large-12 columns">
		<form method="get" class="support-system-filter" action="#ticket-cat-id">
			<div class="row">
				<div class="large-12 columns"><?php echo psource_support_new_ticket_form_link( 'button tiny secondary'); ?></div>
			</div>
			<div class="row">
				<div class="large-5 columns">
					<?php psource_support_the_ticket_category_filter( 'cat-id' ); ?>
				</div>
				<div class="large-4 columns">
					<?php psource_support_the_search_input( array( 'placeholder' => __( 'Suche Tickets', PSOURCE_SUPPORT_LANG_DOMAIN ) ) ); ?>
				</div>
				<div class="large-3 columns">
					<input type="submit" class="button small" name="support-system-submit-filter" value="<?php esc_attr_e( 'Suche' , PSOURCE_SUPPORT_LANG_DOMAIN ); ?>"/>
				</div>
				<div class="large-3 columns">
					<h5 class="support-system-items-count text-right"><?php printf( __( '%d Tickets', PSOURCE_SUPPORT_LANG_DOMAIN ), psource_support_the_tickets_number() ); ?></h5>
				</div>
			</div>
			
		</form>
		
	</div>
</div>