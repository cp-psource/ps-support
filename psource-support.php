<?php
/*
Plugin Name: PS Support System
Plugin URI: https://n3rds.work/cp_psource/ps-support-system-plugin/
Description: Richte auf jeder ClassicPress-Seite ein fantastisches Support-Ticket-System mit häufig gestellten Fragen ein.
Author: DerN3rd (WMS N@W)
Network: true
Version: 2.2.3
License: GPLv2
Author URI: https://n3rds.work
Domain Path: languages
Text Domain: psource-support
*/

/*
Copyright 2018-2023 WMS N3rds@Work (https://n3rds.work)
Author DerN3rd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 � GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

Foundation 5 License: See license-foundation.txt
*/
require 'psource/psource-plugin-update/psource-plugin-updater.php';
use Psource\PluginUpdateChecker\v5\PucFactory;
$MyUpdateChecker = PucFactory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=ps-support', 
	__FILE__, 
	'ps-support' 
);

define( 'PSOURCE_SUPPORT_PLUGIN_VERSION', '2.2.2' );

if ( ! defined( 'PSOURCE_SUPPORT_LANG_DOMAIN' ) )
	define('PSOURCE_SUPPORT_LANG_DOMAIN', 'psource-support');

define( 'PSOURCE_SUPPORT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PSOURCE_SUPPORT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'PSOURCE_SUPPORT_ASSETS_URL', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/' );

if ( ! class_exists( 'MU_Support_System') ) {

	class MU_Support_System {
		// Current version of the plugin
		public static $version = PSOURCE_SUPPORT_PLUGIN_VERSION;

		// Sets of valid values like status, or privacy options
		public static $ticket_status;
		public static $ticket_priority;
		public static $responsibilities;
		public static $privacy;
		public static $fetch_imap;
		public static $psource_support_imap_ssl;

		// Plugin settings
		public $settings = array();

		// Network menus
		public static $network_main_menu;
		public static $network_single_ticket_menu;
		public static $network_faq_manager_menu;
		public static $network_ticket_categories;
		public static $network_single_faq_question_menu;
		public static $network_faq_categories_menu;
		public static $network_support_settings_menu;

		// Blogs menus
		public static $admin_main_menu;
		public static $admin_new_ticket_menu;
		public static $admin_single_ticket_menu;
		public static $admin_faq_menu;

		public $admin;
		public $shortcodes = null;

		public $integrators = array();

		private static $instance = null;

		public $model;
		public $query;

		public static function get_instance() {
			if ( ! self::$instance )
				self::$instance = new self();

			return self::$instance;
		}


		/**
		 * Constructor: Initializes the plugin
		 *
		 * @since 1.8
		 */
		public function __construct() {

			// Include needed files
			$this->includes();

			// Initializes plugin
			add_action( 'init', array( &$this, 'init_plugin' ), 1 );

			// Activation/Upgrades
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

			// Is this an upgrade?
			add_action( 'init', 'psource_support_check_for_upgrades' );

			add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ), 100 );

			// Create Admin menus
			//add_action( 'init', array( &$this, 'admin_menus' ) );


		}


		public function init_plugin() {

			$this->model = psource_support_get_model();
			$this->settings = new PSource_Support_Settings();
			$this->query = new PSource_Support_Query();

			if ( is_admin() )
				$this->admin = new PSource_Support_Admin();

			// Setting properties
			self::$ticket_status = array(
				0	=>	__( 'Neu', 'psource-support' ),
				1	=>	__( 'In Bearbeitung', 'psource-support' ),
				2	=>	__( 'Warten auf die Antwort des Benutzers', 'psource-support' ),
				3	=>	__( 'Warten auf Admin, um zu antworten', 'psource-support' ),
				4	=>	__( 'Eingestellt', 'psource-support' ),
				5	=>	__( 'Geschlossen', 'psource-support' )
			);

			self::$ticket_priority = array(
				0	=>	__( 'Niedrig', 'psource-support' ),
				1	=>	__( 'Normal', 'psource-support' ),
				2	=>	__( 'Hoch', 'psource-support' ),
				3	=>	__( 'Eilt', 'psource-support' ),
				4	=>	__( 'Dringend', 'psource-support' )
			);

			self::$responsibilities = array(
				'keep',
				'punt',
				'accept',
				'help'
			);

			self::$privacy = array(
				'all' => is_multisite() ? __( 'Erlaube allen Benutzern, alle Tickets auf einer Seite zu sehen', 'psource-support' ) : __( 'Allen Benutzern erlauben, alle Tickets zu sehen', 'psource-support' ),
				'requestor' => __( 'Erlaube nur Ticketerstellern, ihre eigenen Tickets zu sehen', 'psource-support' )
			);


			if ( ( is_multisite() && $this->settings->get( 'psource_support_blog_id' ) == get_current_blog_id() ) || ! is_multisite() )
				$this->shortcodes = new PSource_Support_Shortcodes();
		}

		public function load_text_domain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), 'psource-support' );

			load_textdomain( PSOURCE_SUPPORT_LANG_DOMAIN, WP_LANG_DIR . '/' . PSOURCE_SUPPORT_LANG_DOMAIN . '/' . PSOURCE_SUPPORT_LANG_DOMAIN . '-' . $locale . '.mo' );
			load_plugin_textdomain( PSOURCE_SUPPORT_LANG_DOMAIN, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		}


		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'model/model.php');

			if ( is_admin() ) {
				require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'admin/class-psource-support-admin.php' );
			}

			// Classes
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-ticket.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-ticket-category.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-ticket-reply.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-shortcodes.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-query.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-faq.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-faq-category.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/classes/class-settings.php');

			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/general.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/ticket.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/ticket-category.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/ticket-reply.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/template.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/settings.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/capabilities.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/faq.php');
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/helpers/faq-category.php');

			// Integration
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/integration/pro-sites.php');

			// Mail templates
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/mail-contents.php');

			// Upgrades
			require_once( PSOURCE_SUPPORT_PLUGIN_DIR . 'inc/upgrades.php');
		}

		/**
		 * Activates the plugin
		 *
		 * @since 1.8
		 */
		public function activate() {
			$model = psource_support_get_model();
			$model->create_tables();

			$settings = new PSource_Support_Settings();

			update_site_option( 'psource_support_version', self::$version );
			update_site_option( 'psource_support_settings', $settings->get_all() );

			set_transient( 'psource_support_welcome', true );
		}

		/**
		 * Deactivates the plugin
		 *
		 * @since 1.8
		 */
		public function deactivate() {
			//delete_site_option( 'psource_support_version' );
			//delete_site_option( 'psource_support_settings' );
		}



		/**
		 * Add actions for admin menus
		 */
		public function admin_menus() {

			if ( is_multisite() ) {
				if ( is_network_admin() ) {
					// Order is important
					self::$network_main_menu = new MU_Support_Network_Main_Menu();
					self::$network_single_ticket_menu = new MU_Support_Network_Single_Ticket_Menu();
					self::$network_ticket_categories = new MU_Support_Network_Ticket_Categories();
					self::$network_single_faq_question_menu = new MU_Support_Network_Single_FAQ_Question_Menu();
					self::$network_faq_manager_menu = new MU_Support_Network_FAQ_Manager_Menu();
					self::$network_faq_categories_menu = new MU_Support_Network_FAQ_Categories();
					self::$network_support_settings_menu = new MU_Support_Network_Support_settings();
				}
				elseif ( is_admin() ) {

					$user = get_userdata( get_current_user_id() );
					$user_role = isset( $user->roles[0] ) ? $user->roles[0] : ( is_super_admin() ? 'administrator' : '' );

					$admin_ticket_menu_allowed = false;
					// Tickets allowed?
					foreach ( $this->settings->get( 'psource_support_tickets_role' ) as $ticket_role ) {
						if ( $user_role == $ticket_role ) {
							$admin_ticket_menu_allowed = true;
							break;
						}
					}

					if ( (boolean)$this->settings->get( 'psource_allow_only_pro_sites' ) && $admin_ticket_menu_allowed )
						$admin_ticket_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $this->settings->get( 'psource_pro_sites_level' ) ) );

					// FAQs allowed?
					$admin_faq_menu_allowed = false;
					foreach ( $this->settings->get( 'psource_support_faqs_role' ) as $faq_role ) {
						if ( $user_role == $faq_role ) {
							$admin_faq_menu_allowed = true;
							break;
						}
					}
					if ( $this->settings->get( 'psource_allow_only_pro_sites_faq' ) && $admin_faq_menu_allowed )
						$admin_faq_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $this->settings->get( 'psource_pro_sites_faq_level' ) ) );

					// If is not a Pro site we will not create the menu
					if ( $admin_ticket_menu_allowed ) {
						self::$admin_single_ticket_menu = new MU_Support_Admin_Single_Ticket_Menu();
						self::$admin_new_ticket_menu = new MU_Support_Admin_New_Ticket_Menu();
						self::$admin_main_menu = new MU_Support_Admin_Main_Menu();
					}

					if ( ! $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( true );
					elseif ( $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( false );
				}
			}
			elseif ( ! is_multisite() && is_admin() ) {
				if ( current_user_can( 'administrator' ) ) {
					self::$network_main_menu = new MU_Support_Network_Main_Menu( false, 'manage_options' );
					self::$network_single_ticket_menu = new MU_Support_Network_Single_Ticket_Menu( false, false, 'manage_options' );
					self::$network_ticket_categories = new MU_Support_Network_Ticket_Categories( false, 'manage_options' );
					self::$network_single_faq_question_menu = new MU_Support_Network_Single_FAQ_Question_Menu( false, 'manage_options' );
					self::$network_faq_manager_menu = new MU_Support_Network_FAQ_Manager_Menu( false, 'manage_options' );
					self::$network_faq_categories_menu = new MU_Support_Network_FAQ_Categories( false, 'manage_options' );
					self::$network_support_settings_menu = new MU_Support_Network_Support_settings( false, 'manage_options' );
				}
				else {

					$user = get_userdata( get_current_user_id() );
					$user_role = $user->roles[0];

					// Tickets allowed?
					$admin_ticket_menu_allowed = false;
					foreach ( $this->settings->get( 'psource_support_tickets_role' ) as $ticket_role ) {
						if ( $user_role == $ticket_role ) {
							$admin_ticket_menu_allowed = true;
							break;
						}
					}
					if ( $admin_ticket_menu_allowed ) {
						self::$admin_single_ticket_menu = new MU_Support_Admin_Single_Ticket_Menu();
						self::$admin_new_ticket_menu = new MU_Support_Admin_New_Ticket_Menu();
						self::$admin_main_menu = new MU_Support_Admin_Main_Menu();
					}


					// FAQs allowed?
					$admin_faq_menu_allowed = false;
					foreach ( $this->settings->get( 'psource_support_faqs_role' ) as $faq_role ) {
						if ( $user_role == $faq_role ) {
							$admin_faq_menu_allowed = true;
							break;
						}
					}
					if ( ! $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( true );
					elseif ( $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( false );
				}
			}
		}

		/**
		 * Sets HTML content for mails
		 *
		 * @return String Content type
		 */
		public function set_mail_content_type() {
			return 'text/html';
		}

		/**
		 * Return all roles depending on multisite or not
		 *
		 * @since 1.9
		 */
		public static function get_roles() {

			global $wp_roles;

			$just_roles = $wp_roles->roles;

			if ( ! is_multisite() )
				unset( $just_roles['administrator'] );

			$support_roles = array();
			foreach ( $just_roles as $key => $role ) {
				$support_roles[ $key ] = translate_user_role( $role['name'] );
			}
			$support_roles['support-guest'] = __( 'Besucher', 'psource-support' );
			return $support_roles;

		}

		/**
		 * Return a ist of Super Admins for multisites or Admins for single sites
		 *
		 * @since 1.9.5
		 *
		 * @return Array of Super Admins/Admins
		 */
		public static function get_super_admins() {

			if ( is_multisite() ) {
				$super_admins = get_super_admins();
			}
			else {
				$admins = get_users(
					array(
						'role' => 'administrator',
						'fields' => array( 'ID', 'user_login' )
					)
				);

				$super_admins = array();
				foreach ( $admins as $admin ) {
					$super_admins[ $admin->ID ] = $admin->user_login;
				}

			}

			return apply_filters( 'psource_support_get_super_admins', $super_admins );
		}

		public static function get_main_admin_email() {
			$administrator = psource_support_get_setting( 'psource_support_main_super_admin' );
			$super_admins = MU_Support_System::get_super_admins();
			$admin_login = $super_admins[ $administrator ];
			$admin_user = get_user_by( 'login', $admin_login );
			return $admin_user->user_email;
		}

		public static function get_main_admin_details() {
			$administrator = psource_support_get_setting( 'psource_support_main_super_admin' );
			$super_admins = MU_Support_System::get_super_admins();
			$admin_login = $super_admins[ $administrator ];
			$admin_user = get_user_by( 'login', $admin_login );
			return $admin_user;
		}

		public function add_integrator( $integrator ) {
			$this->integrators[] = $integrator;
		}

	}

}

function psource_support() {
	return MU_Support_System::get_instance();
}


add_action( 'plugins_loaded', 'support_system_init' );
function support_system_init() {
	global $mu_support_system;
	$mu_support_system = psource_support();
}

