<?php

class PSource_Support_Admin_Support_Menu extends PSource_Support_Parent_Support_Menu {

	public function add_menu() {
		$settings = psource_support_get_settings();
		$menu_title = esc_html( $settings['psource_support_menu_name'] );

		parent::add_menu_page(
			$menu_title,
			$menu_title, 
			'read',
			'dashicons-sos'
		);

		add_action( 'load-' . $this->page_id, array( $this, 'maybe_insert_new_ticket' ) );
		add_action( 'load-' . $this->page_id, array( $this, 'set_filters' ) );

		if ( psource_support_current_user_can( 'insert_ticket' ) ) {
			add_submenu_page( 
				$this->slug, 
				__( 'Neues Ticket erstellen', 'psource-support' ), 
				__( 'Neues Ticket erstellen', 'psource-support' ), 
				'read', 
				"admin.php?page=$this->slug&action=add" 
			);
		}
	}

	public function set_filters() {
		if ( ! isset( $_GET['action'] ) && psource_support_current_user_can( 'insert_ticket' ) )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_ticket_link' ) );

		if ( isset( $_GET['action'] ) && isset( $_GET['tid'] ) && 'edit' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', '__return_empty_string' );

		if ( isset( $_GET['action'] ) && 'add' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_ticket_title' ) );

		// Tickets table filters
		add_filter( 'support_system_tickets_table_query_args', array( $this, 'set_tickets_table_query_args' ) );
		add_filter( 'support_system_support_menu_counts_args', array( $this, 'set_counts_args' ) );
		add_filter( 'support_network_ticket_columns', array( $this, 'set_tickets_table_columns' ) );
	}

	public function add_new_ticket_link( $title ) {
		$settings = psource_support_get_settings();
		$menu_title = esc_html( $settings['psource_support_menu_name'] );
		$add_new_link = add_query_arg( 'action', 'add', $this->get_menu_url() );
		return '<h2>'. $menu_title . ' <a href="' . esc_url( $add_new_link ) . '" class="add-new-h2">' . esc_html__( 'Neues Ticket erstellen', 'psource-support' ) . '</a></h2>';
	}

	public function add_new_ticket_title( $title ) {
		return '<h2>' . __( 'Neues Ticket erstellen', 'psource-support' ) . '</h2>';
	}

	public function render_inner_page() {

		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;

		if ( 'edit' == $action && isset( $_GET['tid'] ) ) {
			$this->render_inner_page_details();
		}
		elseif ( 'add' == $action && psource_support_current_user_can( 'insert_ticket' ) ) {

			$priority = 0;
			if ( isset( $_POST['priority'] ) )
				$priority = absint( $_POST['priority'] );

			
			if ( isset( $_POST['category'] ) && $selected_category = psource_sbe_get_ticket_category( absint( $_POST['category'] ) ) ) {
				$category = $selected_category;
			}
			else {
				$category = psource_support_get_default_ticket_category();
			}

			// Priorities dropdown
			$priorities_dropdown = psource_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $priority
				) 
			);

			// Categories dropdown
			$categories_dropdown = psource_support_ticket_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $category->cat_id
				) 
			);

			$message = '';
			if ( ! empty( $_POST['message-text'] ) )
				$message = stripslashes_deep( $_POST['message-text'] );

			$subject = '';
			if ( ! empty( $_POST['subject'] ) )
				$subject = strip_tags( stripslashes_deep( $_POST['subject'] ) );
			
			include( 'views/add-new-ticket.php' );
		}		
		else {

			$this->render_inner_page_tickets_table();
		}

	}

	public function maybe_insert_new_ticket() {
		if ( isset( $_POST['submit-new-ticket'] ) ) {
			check_admin_referer( 'add-new-ticket' );

			$args = array();

			if ( empty( $_POST['message-text'] ) )
				add_settings_error( 'support_system_submit_new_ticket', 'empty_message', __( 'Die Ticketnachricht darf nicht leer sein', 'psource-support' ) );
			else
				$args['message'] = wpautop( stripslashes_deep( $_POST['message-text'] ) );

			$title = strip_tags( stripslashes_deep( $_POST['subject'] ) );
			if ( empty( $title ) )
				add_settings_error( 'support_system_submit_new_ticket', 'empty_subject', __( 'Der Betreff des Tickets darf nicht leer sein', 'psource-support' ) );
			else
				$args['title'] = $title;

			$category = psource_support_get_ticket_category( absint( $_POST['ticket-cat'] ) );
			if ( ! $category ) {
				add_settings_error( 'support_system_submit_new_ticket', 'wrong_category', __( 'Die ausgewählte Kategorie ist nicht gültig', 'psource-support' ) );
			}
			else {
				$args['cat_id'] = $category->cat_id;
			}

			$args['ticket_priority'] = isset( $_POST['ticket-priority'] ) ? absint( $_POST['ticket-priority'] ) : 0;

			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = psource_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded['result'], 'url' );
				}
				elseif ( $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					foreach ( $files_uploaded['result'] as $error ) {
						add_settings_error( 'support_system_submit_new_ticket', 'file_upload_error', $error );			
					}
				}
			}

			if ( ! get_settings_errors( 'support_system_submit_new_ticket' ) ) {
				if ( is_super_admin() )
					$args['view_by_superadmin'] = 1;

				$result = psource_support_insert_ticket( $args );
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'support_system_submit_new_ticket', 'insert_error', $result->get_error_message() );
				}
				else {
					$redirect_to = add_query_arg(
						array(
							'action' => 'edit',
							'tid' => $result,
						),
						$this->get_menu_url()
					);
					wp_redirect( $redirect_to );
					exit();
				}
			}
		}
	}


	public function set_tickets_table_query_args( $args ) {
		$settings = psource_support_get_settings();

		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );
		$s = $this->get_filter( 's' );

		$args['priority'] = $priority;
		$args['category'] = $category;
		$args['s'] = $s;
		$args['blog_id'] = get_current_blog_id();

		return $args;
	}

	public function set_counts_args( $args ) {
		$settings = psource_support_get_settings();

		$args['blog_id'] = get_current_blog_id();

		if ( 'requestor' === $settings['psource_ticket_privacy'] && ! psource_support_current_user_can( 'manage_options' ) )
			$args['user_in'] = array( get_current_user_id() );

		return $args;
	}

	public function set_tickets_table_columns( $columns ) {
		unset( $columns['submitted'] );
		return $columns;
	}


}