<?php

$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ': {
	support_system_shortcodes: {
		is_network: "' . (int)is_multisite() . '",
		tickets_list_menu_title: "' . esc_js( __( 'Tickets Liste', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '",
		submit_ticket_form_text: "' . esc_js( __( 'Ticketformular einreichen', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '",
		submit_ticket_form_submit_ticket_form_title: "' . esc_js( __( 'Ticketformular einreichen', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '",
		submit_ticket_form_blog_field_label: "' . esc_js( __( 'Seiten-Auswahlfeld anzeigen', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '",
		submit_ticket_form_category_field_label: "' . esc_js( __( 'Auswahlfeld für Kategorie anzeigen', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '",
		submit_ticket_form_priority_field_label: "' . esc_js( __( 'Prioritätsauswahlfeld anzeigen', PSOURCE_SUPPORT_LANG_DOMAIN ) ) . '"
	}
}});';