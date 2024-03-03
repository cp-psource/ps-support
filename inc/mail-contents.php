<?php

add_action( 'support_system_insert_ticket', 'psource_support_send_user_new_ticket_mail' );
add_action( 'support_system_insert_ticket', 'psource_support_send_admin_new_ticket_mail' );

add_action( 'support_system_insert_ticket_reply', 'psource_support_send_emails_on_ticket_reply', 10, 2 );

/**
 * Functions that renders every mail involved in the system
 */
 
function psource_support_get_email_headers() {
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'From: ' . psource_support_get_setting( 'psource_support_from_name' ) . ' <' . psource_support_get_setting( 'psource_support_from_mail' ) . '>';

	return $headers;
}


/**
 * Send a mail to the user that opened a new ticket
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function psource_support_send_user_new_ticket_mail( $ticket_id ) {

	$ticket = psource_support_get_ticket( $ticket_id );

	if ( ! $ticket )
		return;

	$ticket_creator = get_userdata( $ticket->user_id );
	if ( ! $ticket_creator )
		return;

	$headers = psource_support_get_email_headers();

	$visit_link = psource_support_get_user_ticket_url( $ticket->ticket_id, $ticket->user_id );
	if ( ! $visit_link )
		return;

	$plugin = psource_support();
	$args = array(
		'support_fetch_imap' 	=> psource_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->title,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> psource_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> psource_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'site_name'				=> get_bloginfo( 'name' )
	);
	$mail_content = psource_support_user_get_new_ticket_mail_content( $args );

	wp_mail( $ticket_creator->data->user_email, __( "Ticket eingereicht: ", 'psource-support' ) . $ticket->title, $mail_content, $headers );
}

/**
 * Send a mail to the main Administrator when a new
 * ticket is submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function psource_support_send_admin_new_ticket_mail( $ticket_id ) {
	
	$ticket = psource_support_get_ticket( $ticket_id );

	if ( ! $ticket )
		return;

	$headers = psource_support_get_email_headers();

	if ( is_multisite() )
		$network_admin = network_admin_url( 'admin.php?page=ticket-manager' );
	else
		$network_admin = admin_url( 'admin.php?page=ticket-manager' );


	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'action' => 'edit',
			'tab' => 'history'
		),
		$network_admin
	);

	$admin_id = $ticket->admin_id;
	$user = get_userdata( $admin_id );
	if ( ! $user ) {
		$settings = psource_support_get_settings();
		$main_admin = $settings['psource_support_main_super_admin'];
		if ( is_numeric( $main_admin ) ) {
			$super_admins = MU_Support_System::get_super_admins();
			$main_admin = isset( $super_admins[ $main_admin ] ) ? $super_admins[ $main_admin ] : $main_admin;
		}
		$user = get_user_by( 'login', $main_admin );
		if ( ! $user )
			return;
	}

	$poster = get_userdata( $ticket->user_id );
	if ( ! $poster )
		$poster_name = __( 'Unbekannter Benutzer', 'psource-support' );
	else
		$poster_name = $poster->display_name;

	// Email arguments
	$args = array(
		'support_fetch_imap' 	=> psource_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->title,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> psource_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> psource_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $ticket->message,
		'user_nicename'			=> $poster_name
	);

	$mail_content = psource_support_admin_get_new_ticket_mail_content( $args );

	wp_mail( $user->user_email, __( "Neues Support Ticket: ", 'psource-support' ) . $ticket->title, $mail_content, $headers );
}


function psource_support_send_emails_on_ticket_reply( $reply_id, $send_emails ) {
	if ( ! $send_emails )
		return;

	$reply = psource_support_get_ticket_reply( $reply_id );
	if ( ! $reply )
		return;

	$ticket = psource_support_get_ticket( $reply->ticket_id );
	if ( ! $ticket )
		return;

	if ( empty( $ticket->admin_id ) ) {
		$super_admin = MU_Support_System::get_main_admin_details();
		psource_support_send_user_reply_mail( $ticket, $reply );
		psource_support_send_admin_reply_mail( $super_admin, $ticket, $reply );

	}
	else {
		if ( get_current_user_id() == absint( $ticket->admin_id ) ) {
			// Response by assigned staff
			// Send to creator
			psource_support_send_user_reply_mail( $ticket, $reply );
		}
		elseif ( get_current_user_id() == absint( $ticket->user_id ) ) {
			// Response by creator
			// Send to Staff
			$staff = get_userdata( $ticket->admin_id );
			psource_support_send_admin_reply_mail( $staff, $ticket, $reply );
		}
		else {
			// Response by none of them
			// Send to Creator & Staff
			$staff = get_userdata( $ticket->admin_id );
			$creator = get_userdata( $ticket->user_id );

			psource_support_send_user_reply_mail( $ticket, $reply );
			psource_support_send_admin_reply_mail( $staff, $ticket, $reply );

		}
	}
}

/**
 * Send a mail to a user when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function psource_support_send_user_reply_mail( $ticket, $reply ) {
	
	$ticket_creator = get_userdata( $ticket->user_id );
	if ( ! $ticket_creator )
		return;

	$poster_id = $reply->get_poster_id();
	$poster = get_userdata( $poster_id );
	if ( ! $poster )
		return;

	$headers = psource_support_get_email_headers();

	$visit_link = psource_support_get_user_ticket_url( $ticket->ticket_id, $ticket->user_id );
	if ( ! $visit_link )
		return;

	if ( is_multisite() ) {
		switch_to_blog( $ticket->blog_id );
		$blogname = get_bloginfo( 'name' );
		restore_current_blog();	
	}
	else {
		$blogname = get_bloginfo( 'name' );
	}
	

	// Email arguments
	$args = array(
		'title' 				=> $ticket->title,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> psource_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> psource_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $reply->message,
		'user_nicename'			=> $poster->display_name,
		'site_name'				=> $blogname
	);

	$mail_content = psource_support_user_get_reply_ticket_mail_content( $args );

	wp_mail( $ticket_creator->user_email, __( "Benachrichtigung über Ticketantwort: ", 'psource-support' ) . $reply->subject, $mail_content, $headers );
}

/**
 * Send a mail to an admin when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function psource_support_send_admin_reply_mail( $admin_user, $ticket, $reply ) {
	
	$headers = psource_support_get_email_headers();

	$poster_id = $reply->get_poster_id();
	$poster = get_userdata( $poster_id );
	if ( ! $poster )
		return;

	// Variables for the message
	if ( is_multisite() )
		$admin_url = network_admin_url( 'admin.php?page=ticket-manager' );
	else
		$admin_url = admin_url( 'admin.php?page=ticket-manager' );

	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'action' => 'edit',
		),
		$admin_url
	);

	// Email arguments
	$args = array(
		'title' 				=> $ticket->title,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> psource_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> psource_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $reply->message,
		'user_nicename'			=> $poster->display_name
	);

	$mail_content = psource_support_admin_get_reply_ticket_mail_content( $args );

	wp_mail( $admin_user->user_email, __( "Benachrichtigung über Ticketantwort: ", 'psource-support' ) . $reply->subject, $mail_content, $headers );
}


/**
 * Send a mail to a user when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
add_action( 'support_system_close_ticket', 'psource_support_send_user_closed_mail' );
function psource_support_send_user_closed_mail( $ticket_id ) {

	$ticket = psource_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return false;

	$creator = get_userdata( $ticket->user_id );
	if ( ! $creator )
		return false;
	
	$headers = psource_support_get_email_headers();

	$visit_link = psource_support_get_user_ticket_url( $ticket->ticket_id, $ticket->user_id );
	if ( ! $visit_link )
		return;

	// Email arguments
	$args = array(
		'support_fetch_imap' 	=> psource_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->title,
		'ticket_url' 			=> $visit_link,
		'ticket_priority'		=> psource_support_get_ticket_priority_name( $ticket->ticket_priority )
	);
	$mail_content = psource_get_closed_ticket_mail_content( $args );

	wp_mail( $creator->user_email, __( "Benachrichtigung über geschlossenes Ticket: ", 'psource-support' ) . $ticket->title, $mail_content, $headers );
}


function psource_support_get_support_fetch_imap_message() {
	if ( get_site_option( 'psource_support_fetch_imap', 'disabled' ) == 'enabled' )
		$support_fetch_imap = __( "***  NICHT UNTERHALB DIESER LINIE SCHREIBEN  ***", 'psource-support' );
	else
		$support_fetch_imap = __("***  ANTWORTE NICHT AUF DIESE E-MAIL  ***", 'psource-support' );

	return $support_fetch_imap;
}

function psource_support_user_get_new_ticket_mail_content( $args ) {
	$content = __( "
SUPPORT_FETCH_IMAP

Betreff: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priorität: SUPPORT_PRIORITY

Dein Ticket wurde eingereicht

Besuche: SUPPORT_LINK

um zu antworten oder das neue Ticket anzuzeigen.

Danke,
SUPPORT_SITE_NAME", 'psource-support' );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_SITE_NAME', $args['site_name'], $content );

	return $content;
}

function psource_support_admin_get_new_ticket_mail_content( $args ) {
	$content = __( "
SUPPORT_FETCH_IMAP

Betreff: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priorität: SUPPORT_PRIORITY

Ein neues Ticket wurde eingereicht

Besuche: SUPPORT_LINK

um zu antworten oder das neue Ticket anzuzeigen.

==============================================================
	Ticketnachricht starten
==============================================================

SUPPORT_USER_NAME sagt:

SUPPORT_MESSAGE

==============================================================
      Ticketnachricht beendet
==============================================================", 'psource-support' );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'], ENT_NOQUOTES, 'UTF-8' ) ), $content );

	return $content;
}


function psource_support_user_get_reply_ticket_mail_content( $args ) {
	$content = __( "

***  ANTWORTE NICHT AUF DIESE E-MAIL  ***

Betreff: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priorität: SUPPORT_PRIORITY

Bitte melde Dich auf Deiner Website an und besuche die Support-Seite, um bei Bedarf auf dieses Ticket zu antworten.

Besuche: SUPPORT_LINK

==============================================================
     Ticketnachricht starten
==============================================================

SUPPORT_RESPONSE_USER_NAME sagt:

SUPPORT_MESSAGE

==============================================================
      Ticketnachricht beendet
==============================================================

Danke,
SUPPORT_SITE_NAME", 'psource-support' );

	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_RESPONSE_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_SITE_NAME', $args['site_name'], $content );

	return $content;
} 



function psource_support_admin_get_reply_ticket_mail_content( $args ) {
	$content = __( "

***  ANTWORTE NICHT AUF DIESE E-MAIL  ***

Betreff: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priorität: SUPPORT_PRIORITY

Bitte melde Dich beim Netzwerkadministrator an und besuche die Support-Seite, um bei Bedarf auf dieses Ticket zu antworten.

Besuche: SUPPORT_LINK

==============================================================
     Ticketnachricht starten
==============================================================

SUPPORT_RESPONSE_USER_NAME sagt:

SUPPORT_MESSAGE

==============================================================
      Ticketnachricht beendet
==============================================================

Danke", 'psource-support' );

	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_RESPONSE_USER_NAME', $args['user_nicename'], $content );

	return $content;
} 



function psource_get_closed_ticket_mail_content( $args ) {

	$content = __("

SUPPORT_FETCH_IMAP

Betreff: SUPPORT_SUBJECT
Priorität: SUPPORT_PRIORITY

Das Ticket wurde geschlossen.

Ticket URL:
	SUPPORT_TICKET_URL", 'psource-support' );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_TICKET_URL', $args['ticket_url'], $content );

	return $content;
} 