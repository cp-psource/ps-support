<?php

function psource_support_get_template( $slug, $name = false ) {
	$template = $slug . '.php';

	$template = apply_filters( 'support_system_get_template', $template, $slug );

	$templates = array();
	if ( $name )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	$locations = support_system_get_template_locations();

	$located = false;

	foreach ( $templates as $template ) {
		if ( empty( $template ) )
			return;

		$template = ltrim( $template, '/' );

		
		foreach ( $locations as $location ) {
			if ( empty( $location ) )
				continue;

			if ( file_exists( trailingslashit( $location ) . $template ) ) {
				$located = trailingslashit( $location ) . $template;
				break;
			}

		}

		if ( ! empty( $located ) ) {
			load_template( $located, false );
			break;
		}
	}

	return $located;
}

function support_system_get_template_locations() {
	return apply_filters( 'support_system_templates_locations', array(
		get_stylesheet_directory() . '/psource-support',
		PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/templates'
	) );
}

function psource_support_get_the_ticket_attachments() {
	$ticket_id = psource_support()->query->ticket->ticket_id;
	$ticket = psource_support_get_ticket( $ticket_id );

	if ( ! $ticket )
		return array();

	$replies = $ticket->get_replies();
	$main_reply = wp_list_filter( $replies, array( 'is_main_reply' => true ) );
	return $main_reply[0]->attachments;
}

function psource_support_ticket_replies() {
	$ticket_id = psource_support()->query->ticket->ticket_id;
	psource_support_get_template( 'ticket-replies', $ticket_id );
}

function psource_support_tickets_list_nav() {
	psource_support_get_template( 'tickets-nav' );
}

function psource_support_faqs_nav() {
	psource_support_get_template( 'faqs-nav' );
}

function psource_support_reply_form() {
	ob_start();
	?>
		<form method="post" id="support-system-reply-form" action="#support-system-reply-form-wrap" enctype="multipart/form-data">
			<?php psource_support_reply_form_errors(); ?>
			<div class="support-system-attachments"></div>
			<?php psource_support_editor( 'reply' ); ?>
			<?php psource_support_reply_form_fields(); ?>
			<br/>
			<input type="submit" name="support-system-submit-reply" class="button small" value="<?php esc_attr_e( 'Antwort einreichen', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>" />
		</form>
	<?php

	echo apply_filters( 'support_system_reply_form', ob_get_clean() );
}

function psource_support_list_replies( $args = array() ) {
	$replies = psource_support()->query->ticket->get_replies();

	// Remove the main reply
	unset( $replies[0] );


	global $ticket_reply;

	foreach ( $replies as $reply ) {
		$ticket_reply = $reply;		

		psource_support_get_template( 'ticket-reply' );

	}

}

function psource_support_the_reply_class() {
	global $ticket_reply;

	$class = array();
	if ( is_multisite() && is_super_admin( $ticket_reply->get_poster_id() ) ) {
		$class[] = 'support-system-reply-staff';
	}

	if ( ! is_multisite() && current_user_can( 'manage_options' ) )
		$class[] = 'support-system-reply-staff';

	return implode( ' ' , $class );
}

function psource_support_get_the_reply_id() {
	global $ticket_reply;
	return $ticket_reply->message_id;
}

function psource_support_get_the_poster_id() {
	global $ticket_reply;
	return $ticket_reply->get_poster_id();
}

function psource_support_get_the_poster_username() {
	global $ticket_reply;

	$user = get_userdata( $ticket_reply->get_poster_id() );
	if ( ! $user ) {
		$username = __( 'Unbekannter Benutzer', PSOURCE_SUPPORT_LANG_DOMAIN );
	}
	else {
		$username = $user->data->display_name;
	}

	return $username;
}

function psource_support_get_the_reply_message() {
	global $ticket_reply;
	return $ticket_reply->message;
}

function psource_support_get_the_reply_date() {
	global $ticket_reply;
	return psource_support_get_translated_date( $ticket_reply->message_date );
}

function psource_support_reply_has_attachments() {
	global $ticket_reply;

	if ( ! empty( $ticket_reply->attachments ) && is_array( $ticket_reply->attachments ) )
		return true;

	return false;
}

function psource_support_get_the_reply_attachments() {
	global $ticket_reply;

	return $ticket_reply->attachments;
}

function psource_support_the_ticket_category_filter( $class = '' ) {
	$selected = psource_support_get_queried_ticket_category_id();

	$args = array(
		'class' => $class,
		'selected' => $selected,
		'name' => 'ticket-cat-id'
	);

	psource_support_ticket_categories_dropdown( $args );
}

function psource_support_the_faq_category_filter( $class = '' ) {
	$selected = psource_support_get_queried_faq_category_id();

	$args = array(
		'class' => $class,
		'selected' => $selected,
		'name' => 'faq-cat-id'
	);

	psource_support_faq_categories_dropdown( $args );
}

function psource_support_the_search_input( $args = array() ) {
	$defaults = array(
		'class' => '',
		'placeholder' => __( 'Suche', PSOURCE_SUPPORT_LANG_DOMAIN ),
		'type' => 'ticket'
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$search = '';

	if ( $type === 'faq' ) {
		$search = psource_support_get_the_faqs_search_query();
		$name = 'support-system-faq-s';
	}
	elseif ( $type === 'ticket' ) {
		$search = psource_support_get_the_tickets_search_query();
		$name = 'support-system-ticket-s';
	}

	?>
		<input type="text" placeholder="<?php esc_attr_e( $placeholder ); ?>" name="<?php echo $name; ?>" class="<?php echo esc_attr( $class ); ?>" value="<?php echo esc_attr( $search ); ?>"/>
	<?php
}


function psource_support_paginate_links( $args = '' ) {
	global $wp_query, $wp_rewrite;

	$total = isset( psource_support()->query->total_pages ) ? psource_support()->query->total_pages : 0;
	$current = isset( psource_support()->query->tickets_page ) ? psource_support()->query->tickets_page : 1;

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$pagenum_link = html_entity_decode( remove_query_arg( 'support-system-page', $current_url ) );

	$defaults = array(
		'ul_class' => 'support-system-pagination',
		'disabled_class' => 'support-system-pag-disabled',
		'arrow_class' => 'support-system-pag-arrow',
		'current_class' => 'support-system-current',
		'prev_text' => __( '&laquo; Bisherige', PSOURCE_SUPPORT_LANG_DOMAIN ),
		'next_text' => __( 'Nächstes &raquo;', PSOURCE_SUPPORT_LANG_DOMAIN ),
		'end_size' => 1,
		'mid_size' => 2,
		'type' => 'plain',
		'before_page_number' => '',
		'after_page_number' => ''
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	// Who knows what else people pass in $args
	$total = (int) $total;
	if ( $total < 2 ) {
		return;
	}

	$end_size = (int) $end_size; // Out of bounds?  Make it the default.

	if ( $end_size < 1 ) {
		$end_size = 1;
	}

	$mid_size = (int) $mid_size;
	if ( $mid_size < 0 ) {
		$mid_size = 2;
	}

	$r = '';
	$page_links = array();
	$dots = false;


	if ( $current && 1 < $current ) {
		$link = $pagenum_link;
		if ( $current != 2 )
			$link = add_query_arg( 'support-system-page', $current - 1, $link );

		$page_links[] = '<li class="support-system-prev support-system-page-numbers"><a href="' . esc_url( $link ) . '">' . $prev_text . '</a></li>';
	}

	for ( $n = 1; $n <= $total; $n++ ) {
		if ( $n == $current ) {
			$link = $pagenum_link;
			$link = add_query_arg( 'support-system-page', $n, $link );
			$page_links[] = "<li class='support-system-page-numbers $current_class'><a href='" . esc_url( $link ) . "'>" . $before_page_number . number_format_i18n( $n ) . $after_page_number . "</a></li>";
			$dots = true;
		} 
		else {
			if ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) {
				$link = $pagenum_link;
				if ( 1 != $n ) 
					$link = add_query_arg( 'support-system-page', $n, $link );

				$page_links[] = "<li class='support-system-page-numbers'><a href='" . esc_url( $link ) . "'>" . $before_page_number . number_format_i18n( $n ) . $after_page_number . "</a></li>";
				$dots = true;
			}
			elseif ( $dots ) {
				$page_links[] = '<li class="support-system-page-numbers support-system-dots ' . esc_attr( $disabled_class . " " . $arrow_class ) . '"><a href="">' . __( '&hellip;' ) . '</a></li>';
				$dots = false;
			}
		}
	}

	if ( $current && ( $current < $total || -1 == $total ) ) {
		$link = $pagenum_link;
		$link = add_query_arg( 'support-system-page', $current + 1, $link );
		$page_links[] = '<li class="support-system-next support-system-page-numbers"><a href="' . esc_url( $link ) . '">' . $next_text . '</a></li>';
	}

	$r .= "<ul class='" . $ul_class . "' role='menubar' aria-label='" .  esc_attr__( 'Seitennummerierung', PSOURCE_SUPPORT_LANG_DOMAIN ) . "'>" . join( $page_links ) . "</ul>";

	echo $r;
}

function psource_support_the_ticket_badges( $args = array() ) {
	$ticket = psource_support()->query->ticket;

	$defaults = array(
		'badge_base_class' => 'support-system-badge',
		'replies_badge_class' => 'support-system-replies-badge',
		'status_badge_class' => 'support-system-closed-badge'
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$badges = array();

	// Ticket status
	$badges[] = '<span class="' . esc_attr( $badge_base_class . ' ' . $status_badge_class ) . '">' . psource_support_get_ticket_status_name( $ticket->ticket_status ) . '</span>';	

	// Replies number
	$num_replies = number_format_i18n( $ticket->num_replies, 0 );
	$badges[] = '<span class="' . esc_attr( $badge_base_class . ' ' . $replies_badge_class ) . '">' . esc_html( sprintf( _n( '1 Antwort', '%s Antworten', $num_replies , PSOURCE_SUPPORT_LANG_DOMAIN ), $num_replies ) ) . '</span>';

	$badges = implode( ' ', $badges );
	echo $badges;
}


function psource_support_editor( $type ) {
	$content = '';
	if ( isset( $_POST['support-system-' . $type . '-message'] ) )
		$content = stripslashes_deep( $_POST['support-system-' . $type . '-message'] );

	$settings = array(
		'media_buttons' => false,
		'quicktags' => false,
		'textarea_rows' => 10,
		'teeny' => true
	);
	wp_editor( $content, 'support-system-' . $type . '-message', $settings );
}

function psource_support_reply_form_fields() {
	$ticket = psource_support()->query->ticket;
	wp_nonce_field( 'support-system-submit-reply-' . $ticket->ticket_id . '-' . get_current_user_id() . '-' . get_current_blog_id() );
	?>
		<input type="hidden" name="support-system-reply-fields[user]" value="<?php echo get_current_user_id(); ?>" />
		<input type="hidden" name="support-system-reply-fields[ticket]" value="<?php echo $ticket->ticket_id; ?>" />
		<input type="hidden" name="support-system-reply-fields[blog]" value="<?php echo get_current_blog_id(); ?>" />
	<?php
}

function psource_support_user_sites_dropdown( $args = array() ) {
	
	$defaults = array(
		'name' => 'support-system-user-sites',
		'id' => false,
		'echo' => false,
		'user_id' => false,
		'selected' => ''
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	if ( ! $id )
		$id = $name;

	if ( ! $user_id )
		$user_id = get_current_user_id();

	if ( ! $echo )
		ob_start();

	if ( is_multisite() && psource_support_user_can( $user_id, 'insert_ticket' ) ) {
		$list = wp_list_pluck( get_blogs_of_user( $user_id ), 'blogname' );
		?>
			<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
				<option value="" <?php selected( empty( $selected ) ); ?>><?php _e( '-- Standardseite --', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></option>
				<?php foreach ( $list as $blog_id => $blog_name ): ?>
					<option value="<?php echo $blog_id; ?>" <?php selected( $selected == $blog_id ); ?>><?php echo $blog_name; ?></option>
				<?php endforeach; ?>
			</select>
		<?php
	}
	else {
		echo '';
	}

	if ( ! $echo )
		return ob_get_clean();

}

function psource_support_reply_form_errors() {
	psource_support_get_errors( 'support-system-reply-form' );
}

function psource_support_widget( $widget_args, $callback, $callback_args = array() ) {
	if ( ! function_exists( $callback ) )
		return '';

	$defaults = array(
		'title' => '',
		'class' => 'panel'
	);

	$widget_args = wp_parse_args( $widget_args, $defaults );
	extract( $widget_args );

	$class .= ' support-system-widget';
	ob_start();
	?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php if ( $title ): ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<?php call_user_func( $callback, $callback_args ); ?>
		</div>
	<?php

	return ob_get_clean();
}


function psource_support_the_staff_box( $args = array() ) {
	if ( ! psource_support_is_staff() )
		return;

	$defaults = array(
		'class' => 'support-system-staff-box',
		'submit_class' => 'button'
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	?>		
		<form action="" method="post">
			<ul>
				<li>
					<label>
						<?php _e( 'Kategorie', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>
						<?php psource_support_ticket_categories_dropdown( array( 'show_empty' => false, 'selected' => psource_support_get_the_ticket_category_id() ) ); ?>
					</label>
					<label>
						<?php _e( 'Priorität', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>
						<?php psource_support_priority_dropdown( array( 'show_empty' => false, 'selected' => psource_support_get_the_ticket_priority_id() ) ); ?>
					</label>
					<label>
						<?php _e( 'Zuweisen', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>
						<?php psource_support_super_admins_dropdown( 
							array( 
								'show_empty' => __( 'Noch nicht zugewiesen', PSOURCE_SUPPORT_LANG_DOMAIN ), 
								'name' => 'ticket-staff' ,
								'selected' => psource_support_the_ticket_staff_login()
							) 
						); ?>
					</label>
				</li>
				<li>
					<?php echo '<strong>' . __( 'Status:', PSOURCE_SUPPORT_LANG_DOMAIN ) . '</strong> ' . psource_support_get_the_ticket_status(); ?>
				</li>
			</ul>
			<p class="support-system-staff-box-submit-wrap">
				<?php wp_nonce_field( 'submit-ticket-details-' . psource_support_get_the_ticket_id() ); ?>
				<input type="hidden" name="ticket_id" value="<?php echo psource_support_get_the_ticket_id(); ?>" />
				<input type="submit" class="<?php echo esc_attr( $submit_class ); ?>" name="submit-ticket-details" value="<?php esc_attr_e( 'Update', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>" />
			</p>
		</form>
	<?php
}

function psource_support_the_ticket_details_box( $args = array() ) {
	?>
		<ul>
			<li><?php echo '<strong>' . __( 'Kategorie:', PSOURCE_SUPPORT_LANG_DOMAIN ) . '</strong> ' . psource_support_get_the_ticket_category(); ?></li>
			<li><?php echo '<strong>' . __( 'Priorität:', PSOURCE_SUPPORT_LANG_DOMAIN ) . '</strong> ' . psource_support_get_the_ticket_priority(); ?></li>
			<li><?php echo '<strong>' . __( 'Status:', PSOURCE_SUPPORT_LANG_DOMAIN ) . '</strong> ' . psource_support_get_the_ticket_status(); ?></li>
		</ul>
	<?php
}

function psource_support_the_open_close_box( $args = array() ) {

	$defaults = array(
		'class' => 'support-system-staff-box',
		'submit_class' => 'button'
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	$ticket = psource_support()->query->ticket;

	?>
	<form action="" method="post">
		<p>
			<input type="checkbox" id="close-ticket" name="close-ticket" <?php checked( psource_support_is_ticket_closed( $ticket->ticket_id ) ); ?> />
			<label for="close-ticket"><strong><?php _e( 'Ticket schließen', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></strong></label>
			
			<?php wp_nonce_field( 'submit-close-ticket-' . psource_support_get_the_ticket_id() ); ?>
			<input type="hidden" name="ticket_id" value="<?php echo psource_support_get_the_ticket_id(); ?>" />
			<input type="submit" class="<?php echo esc_attr( $submit_class ); ?>" name="submit-close-ticket" value="<?php esc_attr_e( 'Update', PSOURCE_SUPPORT_LANG_DOMAIN ); ?>" />
		</p>
	</form>
	<?php
}

function psource_support_new_ticket_form_link( $class = '' ) {
	$new_ticket_page = psource_support_get_new_ticket_page_id();

	if ( ! $new_ticket_page )
		return '';

	$permalink = get_permalink( $new_ticket_page );
	if ( $permalink ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $permalink ) . '" title="' . esc_attr__( 'Neues Ticket einreichen', PSOURCE_SUPPORT_LANG_DOMAIN ) . '">' . __( 'Neues Ticket einreichen', PSOURCE_SUPPORT_LANG_DOMAIN ) . '</a>';
	}

	return '';
}

function psource_support_the_faq_vote_box( $faq_id = false ) {
	if ( ! $faq_id )
		$faq_id = psource_support_get_the_faq_id();

	if ( ! psource_support_get_faq( $faq_id ) )
		return;
 
	?>
	<div class="support-system-faq-vote-wrap">
		<h4><?php _e( 'War diese Lösung hilfreich?', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></h4>
		<button class="support-system-faq-vote vote-button button tiny success" data-faq-id="<?php echo psource_support_get_the_faq_id(); ?>" data-vote="yes"><?php _e( 'JA', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></button>
		<button class="support-system-faq-vote vote-button button tiny alert" data-faq-id="<?php echo psource_support_get_the_faq_id(); ?>" data-vote="no"><?php _e( 'NEIN', PSOURCE_SUPPORT_LANG_DOMAIN ); ?></button>
		<span class="support-system-spinner"></span>
	</div>
	<?php
}