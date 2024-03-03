<?php

function psource_support_get_settings() {
	return psource_support()->settings->get_all();
}

function psource_support_get_setting( $name ) {
	return psource_support()->settings->get( $name );
}

function psource_support_get_default_settings() {
	return psource_support()->settings->get_default_settings();
}

function psource_support_update_setting( $name, $value ) {
	psource_support()->settings->set( $name, $value );
}

function psource_support_update_settings( $value ) {
	psource_support()->settings->update( $value );
}

function psource_support_get_support_page_url() {
	$page = psource_support_get_support_page_id();
	if ( 'page' === get_post_type( $page ) )
		return get_permalink( $page );

	return false;
}

function psource_support_get_faqs_page_id() {
	return apply_filters( 'support_system_faqs_page_id', psource_support()->settings->get( 'psource_support_faqs_page' ) );
}

function psource_support_get_support_page_id() {
	return apply_filters( 'support_system_support_page_id', psource_support()->settings->get( 'psource_support_support_page' ) );	
}

function psource_support_get_new_ticket_page_id() {
	return apply_filters( 'support_system_new_ticket_page_id', psource_support()->settings->get( 'psource_support_create_new_ticket_page' ) );	
}

