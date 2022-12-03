<?php

class PSource_Support_Welcome_Menu extends PSource_Support_Admin_Menu {

	public function add_menu() {		
		$this->menu_title = sprintf( __( 'Willkommen beim Support System %s', 'psource-support' ), psource_support_get_version() );
		$this->page_id = add_dashboard_page( 
			$this->menu_title,
			$this->menu_title,
			is_multisite() ? 'manage_network' : 'manage_options',
			$this->slug,
			array( $this, 'render_page' ) 
		);


		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_here' ), 99 );
	}

	public function enqueue_styles() {
		$file = 'about';
		if ( is_rtl() )
			$file .= '-rtl';

		$file .= '.css';

		wp_enqueue_style( 'support-system-welcome-custom', PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/css/support-welcome.css');
	}


	public function render_page() {

		if ( is_multisite() )
			$settings_url = network_admin_url( 'admin.php?page=mu-support-settings' );	
		else
			$settings_url = admin_url( 'admin.php?page=mu-support-settings' );	
		
		include_once( 'views/welcome.php' );
				
	}

	public function render_inner_page() {}

	public function admin_head() {
		remove_submenu_page( 'index.php', $this->slug );
	}

	public function redirect_to_here() {
	    if ( ! get_transient( 'psource_support_welcome' ) ) {
			return;
	    }

	    if ( is_multisite() && ! is_super_admin() )
	    	return;
	    elseif ( ! is_multisite() && ! current_user_can( 'manage_options' ) )
	    	return;

		delete_transient( 'psource_support_welcome' );

		$url = is_multisite() ? network_admin_url( 'index.php?page=' . $this->slug ) : admin_url( 'index.php?page=' . $this->slug );
		wp_redirect( $url );
		exit;
	}

}