<?php

class PSource_Support_Shortcodes {

	public $shortcodes = array();

	public function __construct() {
		$this->init();
		$this->register_shortcodes();
		$this->init_tiny_mce_button();
	}

	private function init() {
		include_once( 'shortcodes/class-abstract-shortcode.php' );
		include_once( 'shortcodes/class-shortcode-tickets-index.php' );
		include_once( 'shortcodes/class-shortcode-submit-ticket-form.php' );
		include_once( 'shortcodes/class-shortcode-faqs.php' );

		$this->shortcodes = apply_filters( 'support_system_shortccodes', array(
			'support-system-tickets-index' => 'PSource_Support_Tickets_Index_Shortcode',
			'support-system-submit-ticket-form' => 'PSource_Support_Submit_Ticket_Form_Shortcode',
			'support-system-faqs' => 'PSource_Support_FAQs_Shortcode'
		) );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_styles' ) );

		add_action( 'wp_footer', array( &$this, 'enqueue_scripts' ) );

		add_action( 'admin_bar_menu', array( &$this, 'set_admin_bar_fields' ), 300 );
	}

	public function set_admin_bar_fields( $wp_admin_bar ) {
		if ( ! is_user_logged_in() || is_admin() )
			return;

		if ( psource_support_is_single_ticket() && psource_support_current_user_can( 'update_ticket' ) ) {
			$wp_admin_bar->add_menu(
				array(
					'id' => 'support-system-edit-ticket',
					'title' => __( 'Ticket bearbeiten', 'psource-support' ),
					'href' => psource_support_get_edit_ticket_admin_url( psource_support_get_the_ticket_id() )
				)
			);
		}
		
	}

	public function register_styles() {
		/**
		 * Filters the frontend stylsheet URL
		 * 
		 * @param String/Boolean $stylesheet Stylesheet URL
		 */
		$stylesheet = apply_filters( 'support_system_front_stylesheet', false );
		if ( $stylesheet )
			wp_register_style( 'support-system', $stylesheet, array(), PSOURCE_SUPPORT_PLUGIN_VERSION );

		wp_register_style( 'support-system-adminbar', PSOURCE_SUPPORT_ASSETS_URL . 'css/admin-bar.css', array(), PSOURCE_SUPPORT_PLUGIN_VERSION );
	}

	public function register_scripts() {
		psource_support_register_main_script();
		wp_register_script( 'support-system-init', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/support-system-init.js', array( 'support-system' ), PSOURCE_SUPPORT_PLUGIN_VERSION, true );

		$allowed_mimes = psource_support_get_allowed_mime_types();
		$allowed_mimes = array_keys( $allowed_mimes );

		$allowed_mimes = array_map( array( $this, '_allowed_mimes' ), $allowed_mimes );

		$allowed_mimes = '*.' . implode( ', *.', $allowed_mimes );

		$l10n = array(
			'button_text' => __( 'Dateien hinzufügen...', 'psource-support' ),
			'remove_file_title' => __( 'Datei löschen', 'psource-support' ),
			'remove_link_text' => __( 'Datei löschen', 'psource-support' ),
			'desc' => '<p>(' . sprintf( __( 'Zulässige Dateitypen: %s', 'psource-support' ), $allowed_mimes ) . ')</p>'
		);
		
		wp_localize_script( 'support-system-init', 'support_system_i18n', $l10n );
	}

	public function _allowed_mimes( $element ) {
		return str_replace( '|', ', *.', $element );
	}


	public function enqueue_scripts() {
		if ( is_support_system() ) {
			wp_enqueue_script( 'support-system-init' );
			wp_enqueue_style( 'support-system' );
			wp_enqueue_style( 'support-system-adminbar' );
		}
	}

	protected function enqueue_custom_scripts() {}

	public function register_shortcodes() {
		foreach ( $this->shortcodes as $shortcode => $classname ) {
			if ( class_exists( $classname ) ) {
	            $r = new ReflectionClass( $classname );
	            $r->newInstanceArgs();
	        }

		}
	}

	// TinyMCE buttons ( Thanks to Woocommerce Shortcodes plugin: https://wordpress.org/plugins/woocommerce-shortcodes/)
	function init_tiny_mce_button() {
		if ( apply_filters( 'support_system_add_editor_shortcodes', true ) ) {
			add_action( 'admin_head', array( $this, 'add_shortcode_button' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_admin_scripts' ) );
		}
	}

	function add_shortcode_button() {
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_shortcode_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_shortcode_button' ) );
			add_filter( 'mce_external_languages', array( $this, 'add_tinymce_i18n' ) );
		}
	}

	public function add_shortcode_tinymce_plugin( $plugins ) {
		$plugins['psource_support_shortcodes'] = PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/js/editor-shortcodes.js';
		return $plugins;
	}

	public function register_shortcode_button( $buttons ) {
		array_push( $buttons, '|', 'psource_support_shortcodes' );
		return $buttons;
	}

	public function enqueue_editor_admin_scripts() {
		wp_enqueue_style( 'psource-support-shortcodes', PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/css/editor-shortcodes.css' );
	}

	public function add_tinymce_i18n( $i18n ) {
		$i18n['support_system_shortcodes'] = PSOURCE_SUPPORT_PLUGIN_DIR . '/admin/inc/tinymce-shortcodes-i18n.php';

		return $i18n;
	}

}