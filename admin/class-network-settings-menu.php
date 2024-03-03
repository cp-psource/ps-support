<?php

class PSource_Support_Network_Settings_Menu extends PSource_Support_Admin_Menu {

	public function add_menu() {		
		parent::add_submenu_page(
			'ticket-manager',
			__( 'Einstellungen', 'psource-support' ),
			__( 'Support System Einstellungen', 'psource-support' ), 
			is_multisite() ? 'manage_network' : 'manage_options'
		);

	}



	public function render_inner_page() {

		$this->render_tabs();

		$current_tab = $this->get_current_tab();

		/**
		 * Filters the function name that renders the tabs in the Settings screen
		 * 
		 * @param Array/String Function to execute
		 */
		$method = apply_filters( 'support_system_render_tab_function', array( $this, 'render_' . $current_tab . '_settings' ) );
		call_user_func( $method );
				
	}

	public function render_general_settings() {
		$settings = psource_support_get_settings();

		$args = array(
			'name' => 'super_admin',
			'id' => 'super_admin',
			'show_empty' => false,
			'selected' => $settings['psource_support_main_super_admin'],
			'echo' => false,
			'value' => 'integer'
		);
		$staff_dropdown = psource_support_super_admins_dropdown( $args );
		
		$menu_name = $settings['psource_support_menu_name'];
		$from_name = $settings['psource_support_from_name'];
		$from_email = $settings['psource_support_from_mail'];
		$tickets_role = $settings['psource_support_tickets_role'];
		$faqs_role = $settings['psource_support_faqs_role'];
		$ticket_privacy = $settings['psource_ticket_privacy'];
		$roles = MU_Support_System::get_roles();

		$errors = get_settings_errors( 'psource-support-settings' );
		include_once( 'views/network-settings-general.php' );
	}

	public function render_front_settings() {

		$settings = psource_support_get_settings();

		$front_active = $settings['psource_support_activate_front'];

		$blog_id = $settings['psource_support_blog_id'];

		$support_pages_dropdown_args = array(
			'selected' => psource_support_get_support_page_id(), 
			'show_option_none' => __( '-- Seite auswählen --', 'psource-support' ),
			'name' => 'support_page_id',
			'echo' => false
		);



		$submit_ticket_pages_dropdown_args = array(
			'selected' => psource_support_get_new_ticket_page_id(), 
			'show_option_none' => __( '-- Seite auswählen --', 'psource-support' ),
			'name' => 'create_new_ticket_page_id',
			'echo' => false
		);



		$faqs_pages_dropdown_args = array(
			'selected' => psource_support_get_faqs_page_id(), 
			'show_option_none' => __( '-- Seite auswählen --', 'psource-support' ),
			'name' => 'faqs_page_id',
			'echo' => false
		);


		$pages_dropdowns = false;
		if ( ! is_multisite() ) {
			$support_pages_dropdown = wp_dropdown_pages( $support_pages_dropdown_args );
			$submit_ticket_pages_dropdown = wp_dropdown_pages( $submit_ticket_pages_dropdown_args );
			$faqs_pages_dropdown = wp_dropdown_pages( $faqs_pages_dropdown_args );

			$create_list_page_url = admin_url( 'post-new.php?post_type=page' );
			$view_list_page_url = get_permalink( $support_pages_dropdown_args['selected'] );

			$create_ticket_form_page_url = admin_url( 'post-new.php?post_type=page' );
			$view_ticket_form_page_url = get_permalink( $submit_ticket_pages_dropdown_args['selected'] );

			$create_faqs_page_url = admin_url( 'post-new.php?post_type=page' );
			$view_faqs_page_url = get_permalink( $faqs_pages_dropdown_args['selected'] );

			$pages_dropdowns = true;
		}
		elseif ( is_multisite() ) {
			$blog_details = get_blog_details( $blog_id );

			if ( $blog_details && $blog_details->blog_id == $blog_id ) {
				switch_to_blog( $blog_id );
				$support_pages_dropdown = wp_dropdown_pages( $support_pages_dropdown_args );
				$submit_ticket_pages_dropdown = wp_dropdown_pages( $submit_ticket_pages_dropdown_args );
				$faqs_pages_dropdown = wp_dropdown_pages( $faqs_pages_dropdown_args );

				$create_list_page_url = admin_url( 'post-new.php?post_type=page' );
				$view_list_page_url = get_permalink( $support_pages_dropdown_args['selected'] );

				$create_ticket_form_page_url = admin_url( 'post-new.php?post_type=page' );
				$view_ticket_form_page_url = get_permalink( $submit_ticket_pages_dropdown_args['selected'] );

				$create_faqs_page_url = admin_url( 'post-new.php?post_type=page' );
				$view_faqs_page_url = get_permalink( $faqs_pages_dropdown_args['selected'] );
				restore_current_blog();	

				$pages_dropdowns = true;
			}
			else {
				$blog_id = '';
			}

		}

		if ( $pages_dropdowns ) {
			$support_pages_dropdown .= '<a href="' . esc_url( $create_list_page_url ) . '" target="_blank" class="button-primary support-create-page">' . esc_html__( 'Neue Seite erstellen', 'psource-support' ) . '</a>';
			$support_pages_dropdown .= '<a href="' . esc_url( $view_list_page_url ) . '" target="_blank" class="button-secondary support-view-page">' . esc_html__( 'Seite anzeigen', 'psource-support' ) . '</a>';
			$support_pages_dropdown .= '<br/><span class="description">' . __( 'Vergiss nicht den <code>[support-system-tickets-index]</code> Shortcode in diese Seite einzufügen', 'psource-support' ) . '</span>';

			$submit_ticket_pages_dropdown .= '<a href="' . esc_url( $create_ticket_form_page_url ) . '" target="_blank" class="button-primary support-create-page">' . esc_html__( 'Neue Seite erstellen', 'psource-support' ) . '</a>';
			$submit_ticket_pages_dropdown .= '<a href="' . esc_url( $view_ticket_form_page_url ) . '" target="_blank" class="button-secondary support-view-page">' . esc_html__( 'Seite anzeigen', 'psource-support' ) . '</a>';
			$submit_ticket_pages_dropdown .= '<br/><span class="description">' . __( 'Vergiss nicht den <code>[support-system-submit-ticket-form]</code> Shortcode in diese Seite einzufügen', 'psource-support' ) . '</span>';

			$faqs_pages_dropdown .= '<a href="' . esc_url( $create_faqs_page_url ) . '" target="_blank" class="button-primary support-create-page">' . esc_html__( 'Neue Seite erstellen', 'psource-support' ) . '</a>';
			$faqs_pages_dropdown .= '<a href="' . esc_url( $view_faqs_page_url ) . '" target="_blank" class="button-secondary support-view-page">' . esc_html__( 'Seite anzeigen', 'psource-support' ) . '</a>';
			$faqs_pages_dropdown .= '<br/><span class="description">' . __( 'Vergiss nicht den <code>[support-system-faqs]</code> Shortcode in diese Seite einzufügen', 'psource-support' ) . '</span>';

			$support_pages_dropdown = '<div class="support-page-selector-wrap">' . $support_pages_dropdown . '</div>';
			$submit_ticket_pages_dropdown = '<div class="support-page-selector-wrap">' . $submit_ticket_pages_dropdown . '</div>';
			$faqs_pages_dropdown = '<div class="support-page-selector-wrap">' . $faqs_pages_dropdown . '</div>';
		}

		$use_default_styles = $settings['psource_support_use_default_settings'];

		$errors = get_settings_errors( 'psource-support-settings' );
		include_once( 'views/network-settings-front.php' );
	}

	public function render_submit_block() {
		$tab = $this->get_current_tab();
		?>
			<p class="submit">
				<?php wp_nonce_field( 'do-support-settings-' . $tab ); ?>
				<?php submit_button( __( 'Änderungen speichern', 'psource-support' ), 'primary', 'submit-' . $tab, false ); ?>
			</p>
		<?php
	}

	public function on_load() {
		
		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		$validate_method = false;
		foreach ( $tabs as $tab => $name ) {
			if ( isset( $_POST[ 'submit-' . $tab ] ) ) {
				check_admin_referer( 'do-support-settings-' . $current_tab );

				/**
				 * Filters the function name that validates a settings group (tab)
				 *
				 * @param Array/String $function_name Function name that validates the settings tab
				 */
				$validate_method = apply_filters( 'support_system_settings_validate_function', array( $this, 'validate_' . $tab . '_settings' ) );
				$settings = call_user_func( $validate_method );

				/**
				 * Filters the settings after they have been validated for a tab
				 *
				 * @param Array $settings Settings after being validated
				 */
				$settings = apply_filters( 'support_system_validate_' . $current_tab . '_settings', $settings );

				if ( $settings && is_array( $settings ) ) {
					psource_support_update_settings( $settings );
					if ( ! get_settings_errors( 'psource-support-settings' ) ) {
						$redirect_to = add_query_arg( 'updated', 'true' );
						wp_redirect( $redirect_to );
						exit;
					}
				}
			}
					
		}
	}

	function validate_general_settings() {
		$input = $_POST;
		$settings = psource_support_get_settings();

		// MENU NAME
		if ( isset( $input['menu_name'] ) ) {
			$input['menu_name'] = sanitize_text_field( $input['menu_name'] );
			if ( empty( $input['menu_name'] ) )
				add_settings_error( 'psource-support-settings', 'menu-name', __( 'Der Menüname darf nicht leer sein', 'psource-support' ) );
			else
				$settings['psource_support_menu_name'] = $input['menu_name'];
		}

		// FROM NAME
		if ( isset( $input['from_name'] ) ) {
			$input['from_name'] = sanitize_text_field( $input['from_name'] );
			if ( empty( $input['from_name'] ) )
				add_settings_error( 'psource-support-settings', 'site-name', __( 'Der Seiten-Name darf nicht leer sein', 'psource-support' ) );
			else
				$settings['psource_support_from_name'] = $input['from_name'];
		}

		// FROM MAIL
		if ( isset( $input['from_mail'] ) ) {
			$input['from_mail'] = sanitize_email( $input['from_mail'] );
			if ( ! is_email( $input['from_mail'] ) ) {
				add_settings_error( 'psource-support-settings', 'site-mail', __( 'E-Mail muss eine gültige E-Mail sein', 'psource-support' ) );
			}
			else
				$settings['psource_support_from_mail'] = $input['from_mail'];
		}

		// MAIN SUPER ADMIN
		if ( isset( $input['super_admin'] ) ) {
			$plugin = psource_support();
			$possible_values = call_user_func( array( $plugin, 'get_super_admins' ) );
			
			$selected = absint( $input['super_admin'] );
			if ( array_key_exists( $selected, $possible_values ) )
				$settings['psource_support_main_super_admin'] = absint( $selected );
		}

		// PRIVACY
		if ( isset( $input['privacy'] ) && array_key_exists( $input['privacy'], MU_Support_System::$privacy ) ) {
			$settings['psource_ticket_privacy'] = $input['privacy'];
		}

		
		// FETCH IMAP
		if ( isset( $input['fetch_imap'] ) && array_key_exists( $input['fetch_imap'], MU_Support_System::$fetch_imap ) ) {
			$settings['psource_support_fetch_imap'] = $input['fetch_imap'];
		}
				

		// ROLES
		$settings['psource_support_tickets_role'] = array();
		if ( isset( $input['tickets_role'] ) && is_array( $input['tickets_role'] ) ) {
			foreach ( $input['tickets_role'] as $ticket_role ) {
				if ( array_key_exists( $ticket_role, MU_Support_System::get_roles() ) )
					$settings['psource_support_tickets_role'][] = $ticket_role;	
			}
		}

		$settings['psource_support_faqs_role'] = array();
		if ( isset( $input['faqs_role'] ) && is_array( $input['faqs_role'] ) ) {
			foreach ( $input['faqs_role'] as $faq_role ) {
				if ( array_key_exists( $faq_role, MU_Support_System::get_roles() ) )
					$settings['psource_support_faqs_role'][] = $faq_role;	
			}
		}

		return stripslashes_deep( $settings );
	}

	function validate_front_settings() {
		$input = $_POST;
		$settings = psource_support_get_settings();

		// FRONT ACTIVE
		$is_active = $settings['psource_support_activate_front'];
		if ( isset( $input['activate_front'] ) ) {
			$settings['psource_support_activate_front'] = true;			
		}
		else {
			$settings['psource_support_activate_front'] = false;	
			$settings['psource_support_blog_id'] = false;
			$settings['psource_support_support_page'] = 0;
			$settings['psource_support_create_new_ticket_page'] = 0;
			$settings['psource_support_faqs_ticket_page'] = 0;
			$settings['psource_support_use_default_settings'] = true;
		}

		// FRONT STYLES
		$is_active = $settings['psource_support_activate_front'];

		if ( $is_active ) {
			if ( isset( $input['use_default_styles'] ) )
				$settings['psource_support_use_default_settings'] = true;
			else
				$settings['psource_support_use_default_settings'] = false;
		}
		
		
		// BLOG ID
		$current_blog_id = $settings['psource_support_blog_id'];
		if ( is_multisite() && isset( $input['support_blog_id'] ) && $settings['psource_support_activate_front'] ) {
			if ( absint( $input['support_blog_id'] ) && get_blog_details( absint( $input['support_blog_id'] ) ) ) {
				$settings['psource_support_blog_id'] = absint( $input['support_blog_id'] );
				if ( $current_blog_id != $settings['psource_support_blog_id'] ) {
					// The blog ID has changed, let's reset the pages
					$settings['psource_support_support_page'] = 0;
					$settings['psource_support_create_new_ticket_page'] = 0;
					$settings['psource_support_faqs_ticket_page'] = 0;
				}
			}
			else {
				add_settings_error( 'psource-support-settings', 'wrong_blog_id', __( 'Die Blog-ID existiert nicht', 'psource-support' ) );
			}
		}

		// SUPPORT PAGES
		if ( ! empty( $input['support_page_id'] ) )
			$settings['psource_support_support_page'] = absint( $input['support_page_id'] );
		else
			$settings['psource_support_support_page'] = false;

		if ( ! empty( $input['create_new_ticket_page_id'] ) )
			$settings['psource_support_create_new_ticket_page'] = absint( $input['create_new_ticket_page_id'] );
		else
			$settings['psource_support_create_new_ticket_page'] = false;

		if ( ! empty( $input['faqs_page_id'] ) )
			$settings['psource_support_faqs_page'] = absint( $input['faqs_page_id'] );
		else
			$settings['psource_support_faqs_page'] = false;

		
		return $settings;
	}
		

	protected function render_tabs() {
		$updated = isset( $_GET['updated'] );
		$tabs = $this->get_tabs();
		$menu_slug = $this->slug;

		$menu_url = $this->get_menu_url();
		$current_tab = $this->get_current_tab();
		include( 'views/network-settings-tabs.php' );
	}

	protected function get_tabs() {
		/**
		 * Filters the settings tabs
		 * 
		 * @param Array $tabs The tabs array
		 	array(
				[tab_slug] => 'Tab label',
				...
		 	)
		 */
		return apply_filters( 'support_system_settings_tabs', array(
			'general' => __( 'Basiseinstellungen', 'psource-support' ),
			'front' => __( 'Front-End Einstellungen', 'psource-support' )
		) );
	}

	protected function get_current_tab() {
		$tabs = $this->get_tabs();
		if ( empty( $_GET['tab'] ) )
			return key( $tabs );

		if ( ! in_array( $_GET['tab'], array_keys( $tabs ) ) )
			return key( $tabs );

		return $_GET['tab'];
	}

}