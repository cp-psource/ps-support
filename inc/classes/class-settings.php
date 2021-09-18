<?php

class PSource_Support_Settings {

	public $options_name = 'psource_support_settings';

	public function __construct() {
		add_filter( 'psource_support_menus', array( $this, 'filter_menus' ) );
		add_filter( 'support_system_tickets_table_query_args', array( $this, 'filter_admin_tickets_table' ) );
		add_filter( 'support_system_query_get_tickets_args', array( $this, 'filter_query' ) );
		add_filter( 'support_system_add_editor_shortcodes', array( $this, 'toggle_editor_shortcode_button' ) );
		add_filter( 'support_system_front_stylesheet', array( $this, 'set_front_stylesheet' ), 1 );
	}

	public function get( $name ) {
		$settings = $this->get_all();
		if ( isset( $settings[ $name ] ) )
			return $settings[ $name ];

		return false;
	}

	public function get_all() {
		$settings = get_site_option( $this->options_name, array() );
		return wp_parse_args( $settings, $this->get_default_settings() );
	}

	public function update( $new_settings ) {
		$settings = update_site_option( $this->options_name, $new_settings );
	}

	public function set( $name, $value ) {
		$settings = $this->get_all();
		$settings[ $name ] = $value;
		$this->update( $settings );
	}

	public function get_default_settings() {
		$plugin = psource_support();
		$super_admins = call_user_func( array( $plugin, 'get_super_admins' ) );
		$main_super_admin = ! empty( $super_admins ) ? key( $super_admins ) : false;
		return apply_filters( 'support_system_default_settings', array(
			'psource_support_menu_name' => __( 'Support', PSOURCE_SUPPORT_LANG_DOMAIN ),
			'psource_support_from_name' => get_bloginfo( 'blogname' ),
			'psource_support_from_mail' => get_bloginfo( 'admin_email' ),
			'psource_support_fetch_imap' => 'disabled',
			'psource_support_imap_frequency' => '',
			'psource_ticket_privacy' => 'all',
			'psource_support_tickets_role' => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),
			'psource_support_faqs_role' => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),
			'psource_support_main_super_admin' => $main_super_admin, //First of the Super Admins
			'psource_support_support_page' => 0,
			'psource_support_create_new_ticket_page' => 0,
			'psource_support_faqs_page' => 0,
			'psource_support_blog_id' => false,
			'psource_support_activate_front' => false,
			'psource_support_use_default_settings' => true			
		) );
	}

	public function filter_menus( $menus ) {
		if ( ( is_multisite() && ! is_network_admin() ) ) {
			$settings = $this->get_all();

			if ( isset( $menus['admin_faq_menu'] ) && ! psource_support_current_user_can( 'read_faq' ) ) {
				unset( $menus['admin_faq_menu'] );
			}

			if ( isset( $menus['admin_support_menu'] ) && ! psource_support_current_user_can( 'read_ticket' ) ) {
				unset( $menus['admin_support_menu'] );
			}

			if ( isset( $menus['admin_faq_menu'] ) && ! isset( $menus['admin_support_menu'] ) ) {
				// The parent menu is not present but the child one, we need to change the child menu to be the main one
				add_filter( 'support_system_add_faq_menu_as_submenu', '__return_false' );
			}

		}
		elseif ( ! is_multisite() ) {
			if ( isset( $menus['network_ticket_categories_menu'] ) && ! psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['network_ticket_categories_menu'] );

			if ( isset( $menus['admin_support_menu'] ) && ! psource_support_current_user_can( 'read_ticket' ) )
				unset( $menus['admin_support_menu'] );

			if ( isset( $menus['network_faq_categories_menu'] ) && ! psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['network_faq_categories_menu'] );

			if ( isset( $menus['network_settings_menu'] ) && ! psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['network_settings_menu'] );

			if ( isset( $menus['network_faqs_menu'] ) && ! psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['network_faqs_menu'] );

			if ( isset( $menus['network_welcome'] ) && ! psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['network_welcome'] );

			if ( isset( $menus['admin_faq_menu'] ) && psource_support_current_user_can( 'manage_options' ) )
				unset( $menus['admin_faq_menu'] );

			if ( isset( $menus['admin_faq_menu'] ) && ! psource_support_current_user_can( 'read_faq' ) )
				unset( $menus['admin_faq_menu'] );

		}

		return $menus;
	}

	public function filter_admin_tickets_table( $args ) {
		$privacy = psource_support_get_setting( 'psource_ticket_privacy' );
		if ( 'requestor' === $privacy && ! psource_support_current_user_can( 'manage_options' ) )
			$args['user_in'] = array( get_current_user_id() );

		return $args;
	}

	public function filter_query( $args ) {
		$privacy = psource_support_get_setting( 'psource_ticket_privacy' );
		if ( 'requestor' === $privacy && ! psource_support_current_user_can( 'manage_options' ) )
			$args['user_in'] = array( get_current_user_id() );

		return $args;
	}

	public function toggle_editor_shortcode_button( $current ) {

		if ( ! $this->get( 'psource_support_activate_front' ) )
			return false;

		if ( ! is_admin() )
			return false;
		
		if ( ! psource_support_current_user_can( 'manage_options' ) )
			return false;

		if ( is_multisite() ) {
			$blog_id = $this->get( 'psource_support_blog_id' );
			if ( $blog_id != get_current_blog_id() )
				return false;

			if ( is_network_admin() )
				return false;
		}


		return $current;
	} 

	function set_front_stylesheet( $stylesheet ) {
		if ( ! is_support_system() )
			return false;

		if ( $this->get( 'psource_support_use_default_settings' ) )
			return PSOURCE_SUPPORT_ASSETS_URL . 'css/psource-support.css';
		else
			return false;
	}

}