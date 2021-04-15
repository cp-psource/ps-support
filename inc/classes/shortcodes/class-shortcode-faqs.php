<?php

class Incsub_Support_FAQs_Shortcode extends Incsub_Support_Shortcode {
	public function __construct() {
		if ( !is_admin() ) {
			add_shortcode( 'support-system-faqs', array( $this, 'render' ) );
		}
	}

	public function render( $atts ) {
		$this->start();

		if ( ! incsub_support_current_user_can( 'read_faq' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'Du musst <a href="%s">angemeldet</a> sein, um Support zu erhalten', INCSUB_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'Du hast nicht genügend Berechtigungen, um Unterstützung zu erhalten', INCSUB_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_faqs_list_message', $message, 'faq-list' );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			return $this->end();
		}

		incsub_support_get_template( 'index', 'faqs' );

		add_action( 'wp_footer', array( &$this, 'enqueue_custom_scripts' ) );

		return $this->end();
	}

	public function enqueue_custom_scripts() {
		incsub_support_enqueue_foundation_scripts();
		wp_enqueue_script( 'support-system-foundation-init', INCSUB_SUPPORT_PLUGIN_URL . 'assets/js/foundation-init.js', array( 'support-system-foundation-js' ), incsub_support_get_version(), true );
	}
}