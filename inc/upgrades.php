<?php



/**
 * Groups the old settings into just one setting option
 * 
 * @since 1.9
 */
function psource_support_group_settings_upgrade() {
	$saved_version = get_site_option( 'psource_support_version', false );
	if ( ! $saved_version || version_compare( $saved_version, '1.9' ) < 0 ) {
		// We're going to group all settings into one option
		$default_settings = psource_support()->settings->get_default_settings();
		$old_settings = array(
			'psource_support_menu_name' => get_site_option( 'psource_support_menu_name', $default_settings['psource_support_menu_name'] ),
			'psource_support_from_name' => get_site_option( 'psource_support_from_name', $default_settings['psource_support_from_name'] ),
			'psource_support_from_mail' => get_site_option( 'psource_support_from_mail', $default_settings['psource_support_from_mail'] ),
			'psource_support_fetch_imap' => get_site_option('psource_support_fetch_imap', $default_settings['psource_support_fetch_imap'] ),
			'psource_support_imap_frequency' => get_site_option('psource_support_imap_frequency', $default_settings['psource_support_imap_frequency'] ),
			'psource_allow_only_pro_sites' => get_site_option( 'psource_allow_only_pro_sites', $default_settings['psource_allow_only_pro_sites'] ),
			'psource_pro_sites_level' => get_site_option( 'psource_pro_sites_level', $default_settings['psource_pro_sites_level'] ),
			'psource_allow_only_pro_sites_faq' => get_site_option( 'psource_allow_only_pro_sites_faq', $default_settings['psource_allow_only_pro_sites_faq'] ),
			'psource_pro_sites_faq_level' => get_site_option( 'psource_pro_sites_faq_level', $default_settings['psource_pro_sites_faq_level'] ),
			'psource_ticket_privacy' => get_site_option( 'psource_ticket_privacy', $default_settings['psource_ticket_privacy'] ),
			'psource_support_faq_enabled' => get_site_option( 'psource_support_faq_enabled', false ),
			'psource_support_tickets_role' => get_site_option( 'psource_support_tickets_role', $default_settings['psource_support_tickets_role'] ),
			'psource_support_faqs_role' => get_site_option( 'psource_support_faqs_role', $default_settings['psource_support_faqs_role'] )
		);
		update_site_option( 'psource_support_settings', $old_settings );

		foreach ( $old_settings as $key => $value ) {
			delete_site_option( $key );
		}
	}
}


/**
 * Upgrades the plugin
 * 
 * @since 1.8
 * 
 */
function psource_support_check_for_upgrades() {

	$saved_version = get_site_option( 'psource_support_version', false );

	if ( $saved_version === false ) {
		psource_support()->activate();
	}

	if ( ! $saved_version || version_compare( $saved_version, PSOURCE_SUPPORT_PLUGIN_VERSION ) < 0 ) {

		$model = MU_Support_System_Model::get_instance();

		if ( version_compare( $saved_version, '1.7.2.2' ) < 0 )
			$model->upgrade_1722();

		if ( version_compare( $saved_version, '1.8' ) < 0 )
			$model->upgrade_18();

		if ( version_compare( $saved_version, '1.8.1' ) < 0 )
			$model->upgrade_181();

		if ( version_compare( $saved_version, '1.9.1' ) < 0 ) {
			psource_support_set_new_roles();
		}

		if ( version_compare( $saved_version, '1.9.6' ) < 0 ) {
			$model->upgrade_196();
		}

		if ( version_compare( $saved_version, '1.9.8' ) < 0 ) {
			$model->upgrade_198();
		}

		if ( version_compare( $saved_version, '1.9.8.1' ) < 0 ) {
			$model->upgrade_1981();
		}

		if ( version_compare( $saved_version, '2.0beta4' ) < 0 ) {
			psource_support_upgrade_20beta4();
		}

		if ( version_compare( $saved_version, '2.1' ) < 0 ) {
			psource_support()->model->create_tables();
		}

		if ( version_compare( $saved_version, '2.1.8' ) < 0 ) {
			psource_support()->model->create_tables();
		}

		update_site_option( 'psource_support_version', PSOURCE_SUPPORT_PLUGIN_VERSION );

		set_transient( 'psource_support_welcome', true );		
	}

}

function psource_support_upgrade_20beta4() {
	$settings = psource_support_get_settings();
	$super_admin = $settings['psource_support_main_super_admin'];

	if ( ! is_numeric( $super_admin ) ) {
		$user = get_user_by( 'login', $super_admin );
		$super_admins = MU_Support_System::get_super_admins();
		if ( $user ) {
			$user_id = $user->ID;
			$found = false;
			foreach ( $super_admins as $key => $value ) {
				if ( $value === $super_admin )
					$found = $key;
			}

			if ( $found !== false ) {
				$settings['psource_support_main_super_admin'] = $found;	
			}
			
		}
		else {
			$settings['psource_support_main_super_admin'] = key( $super_admins );
		}
	}

	psource_support_update_settings( $settings );
}


/**
 * Sets a new system based on roles instead of capabilities
 * 
 * @since 1.9.1
 */
function psource_support_set_new_roles() {
	global $wp_roles;

	$roles_settings = array( 
		'psource_support_tickets_role' => psource_support_get_setting( 'psource_support_tickets_role' ), 
		'psource_support_faqs_role' => psource_support_get_setting( 'psource_support_faqs_role' ) 
	);

	/**
	foreach ( $roles_settings as $key => $value ) {
		switch ( $value ) {
			case 'manage_options':
				psource_support_get_setting(  $key  ) = array( 'administrator' );
				break;
			case 'publish_pages':
				psource_support_get_setting(  $key  ) = array( 'administrator', 'editor' );
				break;
			case 'publish_posts':
				psource_support_get_setting(  $key  ) = array( 'administrator', 'editor', 'author' );
				break;
			case 'edit_posts':
				psource_support_get_setting(  $key  ) = array( 'administrator', 'editor', 'author', 'contributor' );
				break;
			case 'read':
				psource_support_get_setting(  $key  ) = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
				break;
		}
	}
	**/
}