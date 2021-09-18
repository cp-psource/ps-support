<?php if ( $errors ): ?>
	<?php foreach ( $errors as $error ): ?>
		<div class="error">
			<p><?php echo esc_html( $error['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<form method="post" action="">
	<table class="form-table">
		
		<?php ob_start(); ?>
			<input type="checkbox" name="activate_front" value="true" <?php checked( $front_active ); ?>/>
		<?php $this->render_row( __( 'Aktiviere Front-End', PSOURCE_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
	</table>
	
	<div id="front-options" class="<?php echo $front_active ? '' : 'disabled'; ?>">
		<table class="form-table">
			
			<?php ob_start(); ?>
				<input type="checkbox" name="use_default_styles" value="true" <?php checked( $use_default_styles ); ?>/>
			<?php $this->render_row( __( 'Verwende Support-Systemstile', PSOURCE_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

			<?php if ( is_multisite() ): ?>
				<?php ob_start(); ?>
					<input type="number" class="small-text" value="<?php echo $blog_id; ?>" name="support_blog_id" />
					<span class="description"><?php _e( 'Mit dem Support-System können Tickets auf einer Deiner Websites im Front-End angezeigt werden...' ); ?></span>
				<?php $this->render_row( __( 'Blog ID', PSOURCE_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
			<?php endif; ?>

			<?php if ( $pages_dropdowns ): ?>
				<?php $this->render_row( __( 'Support Seite', PSOURCE_SUPPORT_LANG_DOMAIN ), $support_pages_dropdown ); ?>
				<?php $this->render_row( __( 'Ticket-Editor Seite', PSOURCE_SUPPORT_LANG_DOMAIN ), $submit_ticket_pages_dropdown ); ?>
				<?php $this->render_row( __( 'FAQs Seite', PSOURCE_SUPPORT_LANG_DOMAIN ), $faqs_pages_dropdown ); ?>
			<?php endif; ?>
		</table>
		

	</div>

	<?php do_action( 'support_sytem_front_settings' ); ?>
		
	<?php $this->render_submit_block(); ?>
</form>
<style>
	.form-table .support-create-page, 
	.form-table .support-view-page {
		display:none;
		margin-right: 10px;
	}
	
	#front-options {
		display:block;
	}
	#front-options.disabled {
		display:none;
	}
</style>
<script>
	jQuery(document).ready(function($) {

		psource_support_toggle_buttons();

		function psource_support_toggle_buttons() {
			var selectors = $('.support-page-selector-wrap');

			selectors.each(function( index ) {
				 var $this = $(this);
				 var select_box = $this.find('select').first();
				 var create_button = $this.find('.support-create-page');
				 var view_button = $this.find('.support-view-page');

				 if ( ! select_box.val() )
				 	create_button.css( 'display', 'inline-block' );
				 else
				 	view_button.css( 'display', 'inline-block' );

			});
		}


		$('input[name="activate_front"]').on( 'change', function() {
			$this = $(this);

			if ( $this.is(':checked') ) {
				$('#front-options').removeClass( 'disabled' );
			}
			else {
				$('#front-options').addClass( 'disabled' );
			}
		});

	});
</script>
