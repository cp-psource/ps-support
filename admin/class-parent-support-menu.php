<?php

class PSource_Support_Parent_Support_Menu extends PSource_Support_Admin_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Tickets table filters
		add_filter( 'support_system_tickets_table_menu_url', array( $this, 'get_menu_url' ) );
	}

	public function enqueue_styles( $page_id ) {
		if ( $page_id === $this->page_id )
			wp_enqueue_style( 'support-menu-styles', PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/css/support-menu.css' );
	}

	public function enqueue_scripts( $page_id ) {
		if ( $page_id === $this->page_id ) {
			psource_support_enqueue_main_script();

		}
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'psource_support_tickets_per_page' == $option ) 
			return $value;

		return $status;
	}

	public function add_menu() {}


	public function on_load() {

		// Add screen options
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

		if ( $action !== 'edit' )
			add_screen_option( 'per_page', array( 'label' => __( 'Tickets pro Seite', 'psource-support' ), 'default' => 20, 'option' => 'psource_support_tickets_per_page' ) );

		// Check filtering
		if ( isset( $_POST['filter_action'] ) || ! empty( $_POST['s'] ) ) {

			$filters = array(
				'category' => false,
				'priority' => false,
				's' => false
			);

			$url = false;

			if ( ! empty( $_REQUEST['ticket-cat'] ) && $cat_id = absint( $_REQUEST['ticket-cat'] ) )
				$filters['category'] = $cat_id;


			if ( isset( $_REQUEST['ticket-priority'] ) && $_REQUEST['ticket-priority'] !== '' )
				$filters['priority'] = $_REQUEST['ticket-priority'];

			if ( ! empty( $_REQUEST['s'] ) )
				$filters['s'] = stripslashes_deep( $_REQUEST['s'] );

			$url = $_SERVER['REQUEST_URI'];
			foreach ( $filters as $key => $value ) {
				if ( $value === false )
					$url = remove_query_arg( $key, $url );
				else
					$url = add_query_arg( $key, $value, $url );
			}

			wp_redirect( $url );
			exit();
			
		}

		// Are we updating a ticket?
		if ( ! empty( $_POST['submit-ticket-details'] ) ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'update-ticket-details-' . $ticket_id );

			$ticket = psource_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				wp_die( __( 'Das Ticket existiert nicht', 'psource-support' ) );

			$plugin = psource_support();
			$args = array();

			// Update Super Admin
			if ( isset( $_POST['super-admins'] ) ) {
				$possible_users = MU_Support_System::get_super_admins();
				if ( in_array( $_POST['super-admins'], $possible_users ) ) {
					$user = get_user_by( 'login', $_POST['super-admins'] );
					if ( $user )
						$args['admin_id'] = $user->data->ID;
				}

				if ( empty( $_POST['super-admins'] ) )
					$args['admin_id'] = 0;
			}

			if ( isset( $_POST['ticket-priority'] ) ) {
				$possible_values = array_keys( $plugin::$ticket_priority );
				if ( in_array( absint( $_POST['ticket-priority'] ), $possible_values ) )
					$args['ticket_priority'] = absint( $_POST['ticket-priority'] );
			}

			if ( isset( $_POST['ticket-cat'] ) ) {
				$cat_id = absint( $_POST['ticket-cat'] );
				if ( psource_support_get_ticket_category( $cat_id ) )
					$args['cat_id'] = $cat_id;
			}

			// Close ticket?
			if ( isset( $_POST['close-ticket'] ) && psource_support_current_user_can( 'close_ticket', $ticket_id ) ) {
				psource_support_close_ticket( $ticket_id );
			}
			elseif ( psource_support_current_user_can( 'open_ticket', $ticket_id ) && $ticket->is_closed() ) {
				psource_support_restore_ticket_previous_status( $ticket_id );
			}

			if ( psource_support_current_user_can( 'update_ticket' ) ) {
				psource_support_update_ticket( $ticket_id, $args );
			}
			

			$redirect = add_query_arg( 'updated', 'true' );
			wp_redirect( $redirect );
			exit();

		}

		// Are we adding a reply?
		if ( isset( $_POST['submit-ticket-reply'] ) ) {

			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'add-ticket-reply-' . $ticket_id );

			$plugin = psource_support();
			$ticket = psource_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				wp_die( __( 'Das Ticket existiert nicht', 'psource-support' ) );

			$reply_args = array();

			$message = isset( $_POST['message-text'] ) ? wpautop( stripslashes_deep( $_POST['message-text'] ) ) : '';
			if ( empty( $message ) )
				add_settings_error( 'support_system_submit_reply', 'empty-message', __( 'Nachricht kann nicht leer sein', 'psource-support' ) );
			else
				$reply_args['message'] = $message;

			$reply_args['poster_id'] = get_current_user_id();

			// Attachments
			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = psource_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$reply_args['attachments'] = wp_list_pluck( $files_uploaded['result'], 'url' );
				}
				elseif ( $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					foreach ( $files_uploaded['result'] as $error ) {
						add_settings_error( 'support_system_submit_reply', 'file_upload_error', $error );			
					}
				}
			}


			if ( ! get_settings_errors( 'support_system_submit_reply' ) ) {
				$ticket_args = array();
				if ( isset( $_POST['category'] ) ) {
					$category = psource_support_get_ticket_category( absint( $_POST['category'] ) );
					if ( $category )
						$ticket_args['cat_id'] = $category->cat_id;
				}

				if ( isset( $_POST['ticket-priority'] ) ) {
					$possible_values = array_keys( $plugin::$ticket_priority );
					if ( in_array( absint( $_POST['ticket-priority'] ), $possible_values ) )
						$ticket_args['ticket_priority'] = absint( $_POST['ticket-priority'] );
				}


				$responsibility = isset( $_POST['responsibility'] ) ? $_POST['responsibility'] : 'accept';
				if ( in_array( $responsibility, $plugin::$responsibilities ) ) {
					switch ( $responsibility ) {
						case 'punt': { $ticket_args['admin_id'] = 0; break; }
						case 'accept': { $ticket_args['admin_id'] = get_current_user_id(); break; }
						default: { break; }
					}
				}


				// Order is important on this
				if ( psource_support_current_user_can( 'update_ticket' ) ) {
					psource_support_update_ticket( $ticket->ticket_id, $ticket_args );
				}

				if ( psource_support_current_user_can( 'insert_reply' ) ) {
					psource_support_insert_ticket_reply( $ticket->ticket_id, $reply_args );
				}

				$ticket = psource_support_get_ticket( $ticket->ticket_id );

				if ( $ticket->admin_id && $ticket->admin_id === get_current_user_id() && $ticket->user_id != $ticket->admin_id ) {
					$status = 2;
				}
				elseif ( ! $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->ticket_status != 0 ) {
					$status = 1;
				}
				elseif ( ! $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->ticket_status == 0 ) {
					$status = 0;
				}
				elseif ( $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->user_id != $ticket->admin_id ) {
					$status = 3;
				}
				elseif ( $ticket->admin_id && $ticket->admin_id === get_current_user_id() ) {
					$status = 1;
				}
				else {
					$status = $ticket->ticket_status;
				}

				
				if ( isset( $_POST['closeticket'] ) && psource_support_current_user_can( 'close_ticket', $ticket->ticket_id ) )
					psource_support_close_ticket( $ticket->ticket_id );
				elseif ( psource_support_current_user_can( 'open_ticket', $ticket->ticket_id ) )
					psource_support_ticket_transition_status( $ticket->ticket_id, $status );


				// Redirecting to ticket history
				$link = add_query_arg( 'updated', 'true' );
				wp_redirect( $link );
				exit();

			}
		

		}

		// Are we creating a FAQ based on a response?
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'create-faq-from-ticket' && isset( $_REQUEST['tid'] ) && isset( $_REQUEST['rid'] ) ) {
			$ticket_id = absint( $_REQUEST['tid'] );
			$reply_id = absint( $_REQUEST['rid'] );

			check_admin_referer( 'create-faq-from-ticket-' . $ticket_id . '-' . $reply_id );

			if ( ! psource_support_current_user_can( 'insert_faq' ) )
				return;

			$ticket = psource_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				return;

			$reply = psource_support_get_ticket_reply( $reply_id );
			if ( ! $reply )
				return;

			if ( is_multisite() ) {
				$redirect_to = add_query_arg(
					'page',
					'support-faq-manager',
					trailingslashit( network_admin_url() ) . '/admin.php'
				);
			}
			else {
				$redirect_to = add_query_arg(
					'page',
					'support-faq-manager',
					trailingslashit( admin_url() ) . '/admin.php'
				);
			}

			$redirect_to = add_query_arg(
				array(
					'action' => 'add',
					'tid' => $ticket_id,
					'rid' => $reply_id
				),
				$redirect_to
			);

			wp_redirect( $redirect_to );
			exit;
			
		}
	}

	protected function render_inner_page_details() {
		$ticket = psource_support_get_ticket( absint( $_GET['tid'] ) );
		if ( ! $ticket )
			wp_die( __( 'The ticket does not exist', 'psource-support' ) );

		if ( ! $ticket->view_by_super_admin && psource_support_current_user_can( 'update_ticket' ) )
			psource_support_update_ticket( $ticket->ticket_id, array( 'view_by_superadmin' => 1 ) );


		$user_can_update_ticket = psource_support_current_user_can( 'update_ticket' );
		$user_can_close_ticket = psource_support_current_user_can( 'close_ticket', $ticket->ticket_id );

		$screen = get_current_screen();
		add_meta_box( 'support-system-ticket-details', __( 'Ticketdetails', 'psource-support' ), array( $this, 'render_details_metabox' ), $screen->id, 'normal' );
		add_meta_box( 'support-system-ticket-history', __( 'Ticketverlauf', 'psource-support' ), array( $this, 'render_history_metabox' ), $screen->id, 'normal' );
		
		if ( $user_can_update_ticket || $user_can_close_ticket )
			add_meta_box( 'support-system-ticket-update', __( 'Ticket aktualisieren', 'psource-support' ), array( $this, 'render_update_metabox' ), $screen->id, 'side' );

		$columns = ( $user_can_update_ticket || $user_can_close_ticket ) ? 2 : 1;
		?>
			<h2><?php echo stripslashes_deep( $ticket->title ); ?></h2>
			<?php if ( $ticket->is_closed() ): ?>
				<div class="error"><p><?php _e( 'Dieses Ticket wurde geschlossen', 'psource-support' ); ?></p></div>
			<?php endif; ?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-<?php echo $columns; ?>">
					<div id="postbox-container-1" class="postbox-container">
						<?php do_meta_boxes( $screen->id, 'side', $ticket->ticket_id ); ?>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<?php do_meta_boxes( $screen->id, 'normal', $ticket->ticket_id ); ?>
					</div>
				</div>
			</div>
		<?php
	}

	public function render_details_metabox( $ticket_id ) {
		$ticket = psource_support_get_ticket( $ticket_id );

		// Last reply user
		$last_reply_user_name = '';
		$last_reply_user = get_userdata( $ticket->last_reply_user_id );
		if ( $last_reply_user )
			$last_reply_user_name = $last_reply_user->data->user_nicename;

		// Submitted from
		$submitted_blog_link = __( 'Unbekannt', 'psource-support' );
		if ( is_multisite() ) {
            $blog_details = get_blog_details( $ticket->blog_id );
            if ( ! empty( $blog_details ) ) {
                $blog_address = get_blogaddress_by_id( $ticket->blog_id );
				$submitted_blog_link = '<a href="' . $blog_address . '">' . $blog_details->blogname . '</a>';
			}
        }
        else {
            $user = get_userdata( $ticket->user_id );
            if ( ! empty( $user ) )
                $submitted_blog_link = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->user_nicename . '</a>';
        }


		$fields = array(
			'ticket-status' => array( 
				'label' => __( 'Aktueller Status', 'psource-support' ),
				'content' => psource_support_get_ticket_status_name( $ticket->ticket_status )
			),
			'ticket-created' => array( 
				'label' => __( 'Erstellt am (GMT)', 'psource-support' ),
				'content' => psource_support_get_translated_date( $ticket->ticket_opened )
			),
			'ticket-user' => array( 
				'label' => __( 'Meldender Benutzer', 'psource-support' ),
				'content' => $ticket->get_user_name()
			),
			'ticket-last-reply-from' => array( 
				'label' => __( 'Letzte Antwort von', 'psource-support' ),
				'content' => $last_reply_user_name
			),
			'ticket-updated' => array( 
				'label' => __( 'Letzte Aktualisierung (GMT)', 'psource-support' ),
				'content' => psource_support_get_translated_date( $ticket->ticket_updated )
			),
			'ticket-submitted-from' => array( 
				'label' => __( 'Eingereicht von', 'psource-support' ),
				'content' =>  $submitted_blog_link
			)
		);

		$fields = apply_filters( 'support_network_ticket_details_fields', $fields, $ticket );

		include_once( 'views/edit-ticket-details-metabox.php' );
	}

	public function render_update_metabox( $ticket_id ) {
		$ticket = psource_support_get_ticket( $ticket_id );

        if ( psource_support_current_user_can( 'update_ticket' ) ) {
	        // Super admins dropdown
	        $super_admins_dropdown = psource_support_super_admins_dropdown( 
				array( 
					'show_empty' => __( 'Noch nicht zugewiesen', 'psource-support' ) ,
					'echo' => false,
					'selected' => $ticket->get_staff_login()
				) 
			);

			// Priorities dropdown
			$priorities_dropdown = psource_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->ticket_priority
				) 
			);

			// Categories dropdown
			$categories_dropdown = psource_support_ticket_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->cat_id
				) 
			);
		}
		else {
			$super_admins_dropdown = ! $ticket->get_staff_login() ? __( 'Noch nicht zugewiesen', 'psource-support' ) : $ticket->get_staff_login();
			$priorities_dropdown = psource_support_get_ticket_priority_name( $ticket->ticket_priority );
			$ticket_category = psource_support_get_ticket_category( $ticket->cat_id );
			$categories_dropdown = $ticket_category->cat_name;
		}

		$fields = array(
 			'ticket-staff' => array( 
 				'label' => '<label for="super-admins">' . __( 'Personalvertreter', 'psource-support' ) . '</label>',
 				'content' => $super_admins_dropdown
 			),
 			'ticket-priority' => array( 
 				'label' => '<label for="ticket-priority">' . __( 'Priorität', 'psource-support' ) . '</label>',
 				'content' => $priorities_dropdown
 			),
			'ticket-category' => array( 
				'label' => '<label for="ticket-cat">' . __( 'Kategorie', 'psource-support' ) . '</label>',
				'content' => $categories_dropdown
			)
		);

		if ( psource_support_current_user_can( 'close_ticket', $ticket->ticket_id ) ) {
			$fields['ticket-closed'] = array(
				'label' => '<label for="close-ticket-checkbox">' . __( 'Ticket geschlossen', 'psource-support' ) . '</label>',
				'content' => '<input name="close-ticket" id="close-ticket-checkbox" type="checkbox" ' . checked( $ticket->is_closed(), true, false ) . ' />'
			);
		}

		$fields = apply_filters( 'support_network_ticket_update_fields', $fields, $ticket );
		include_once( 'views/edit-ticket-update-metabox.php' );
	}


	public function render_history_metabox() {
		$ticket = psource_support_get_ticket( absint( $_GET['tid'] ) );
		if ( ! $ticket )
			wp_die( __( 'Das Ticket existiert nicht', 'psource-support' ) );

		if ( ! $ticket->view_by_super_admin && psource_support_current_user_can( 'update_ticket' ) )
			psource_support_update_ticket( $ticket->ticket_id, array( 'view_by_superadmin' => 1 ) );


		include_once( 'inc/class-table-tickets-history.php' );
		$ticket_history_table = new PSource_Support_Tickets_History_Table();
		$ticket_history_table->set_ticket( absint( $_GET['tid'] ) );
		$ticket_history_table->prepare_items();
		$ticket_history_table->display();

		if ( isset( $_POST['category'] ) )
			$ticket->cat_id = absint( $_POST['category'] );

		if ( isset( $_POST['ticket-priority'] ) )
			$ticket->ticket_priority = absint( $_POST['ticket-priority'] );

		if ( isset( $_POST['responsibility'] ) ) {
			$responsibility = $_POST['responsibility'];
		}
		else {
			if ( $ticket->admin_id == get_current_user_id() )
				$responsibility = 'keep';
			else
				$responsibility = 'accept';
		}

		if ( isset( $_POST['closeticket'] ) )
			$ticket->ticket_status = 5;

		$ticket_message = '';
		if ( ! empty( $_POST['message-text'] ) )
			$ticket_message = wpautop( stripslashes_deep( $_POST['message-text'] ) );


		if ( psource_support_current_user_can( 'update_ticket' ) ) {
			// Categories dropdown
			$categories_dropdown = psource_support_ticket_categories_dropdown(
				array(
					'echo' => false,
					'name' => 'category',
					'id' => 'category',
					'selected' => $ticket->cat_id,
					'show_empty' => false
				)
			);

			$priorities_dropdown = psource_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->ticket_priority
				) 
			);
		}
		else {
			$priorities_dropdown = psource_support_get_ticket_priority_name( $ticket->ticket_priority );
			$ticket_category = psource_support_get_ticket_category( $ticket->cat_id );
			$categories_dropdown = $ticket_category->cat_name;
		}

		$errors = get_settings_errors( 'support_system_submit_reply' );

		include_once( 'views/edit-ticket-history-metabox.php' );

		if ( psource_support_current_user_can( 'insert_reply' ) && ! $ticket->is_closed() )
			include( 'views/edit-ticket-history.php' );
	}

	protected function render_inner_page_tickets_table() {
		include_once( 'inc/class-table-tickets.php' );

		$status = $this->get_status_filter();
		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );

	    $tickets_table = new PSource_Support_Tickets_Table( array( 'status' => $status ) );

	    $tickets_table->prepare_items();

	    $counts_args = array();
	    if ( false !== $priority )
	    	$counts_args['priority'] = absint( $priority );

	    if ( false !== $category ) 
	    	$counts_args['category'] = absint( $category );

	    /**
	     * Filters the arguments that will be passed to the function that counts the tickets
	     * in the admin page
	     * 
	     * @param Array $counts_args Count arguments
	     */
	    $counts_args = apply_filters( 'support_system_support_menu_counts_args', $counts_args );

	    $all_tickets_count = psource_support_get_tickets_count( $counts_args );

	    $counts_args['status'] = 'archive';
	    $archived_tickets_count = psource_support_get_tickets_count( $counts_args );
	    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

	    include( 'views/network-tickets.php' );
	}

	public function render_inner_page() {}
	

	protected function get_status_filter() {
		if ( ! isset( $_GET['status'] ) ){
			$accepted_statuses = array( 'all', 'active', 'archive' );
			$default_status = apply_filters( 'support_system/support_list/default_status', 'all', $accepted_statuses );
			return in_array( $default_status, $accepted_statuses ) ? $default_status : 'all';
		}

		return $_GET['status'];
	}

	protected function get_filter( $slug ) {
		if ( ! isset( $_REQUEST[ $slug ] ) )
			return false;

		return $_REQUEST[ $slug ];
	}

}
