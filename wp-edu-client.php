<?php
/**
 * Plugin Name: WP EDU Client (Student - User Centric)
 * Description: Connects individual student accounts to the Host LMS for targeted content analytics and revision tracking.
 * Version: 1.0.0
 * Author: Can Bekcan
 * Text Domain: wp-edu-client
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_EDU_CLIENT_DIR', plugin_dir_path( __FILE__ ) );

// --- Çeviri Altyapısını (i18n) Yükle ---
add_action( 'plugins_loaded', 'wp_edu_client_load_textdomain' );
function wp_edu_client_load_textdomain() {
    load_plugin_textdomain( 'wp-edu-client', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// --- Alt Modülleri Dahil Et ---
require_once WP_EDU_CLIENT_DIR . 'admin/class-client-menu.php';
require_once WP_EDU_CLIENT_DIR . 'includes/class-client-notices.php';
require_once WP_EDU_CLIENT_DIR . 'includes/class-client-tracking.php';
require_once WP_EDU_CLIENT_DIR . 'includes/class-client-sso.php';
require_once WP_EDU_CLIENT_DIR . 'includes/api/class-client-auth.php';
require_once WP_EDU_CLIENT_DIR . 'includes/api/class-client-endpoint-content.php';
require_once WP_EDU_CLIENT_DIR . 'includes/api/class-client-endpoint-updates.php';
require_once WP_EDU_CLIENT_DIR . 'includes/api/class-client-endpoint-notices.php';
require_once WP_EDU_CLIENT_DIR . 'includes/class-client-github-updater.php';

// --- Sınıfları Başlat ---
new WP_EDU_Client_Auth();
new WP_EDU_Client_Menu();
new WP_EDU_Client_Notices();
new WP_EDU_Client_Tracking();
new WP_EDU_Client_SSO();
new WP_EDU_Client_Endpoint_Content();
new WP_EDU_Client_Endpoint_Updates();
new WP_EDU_Client_Endpoint_Notices();


if ( is_admin() ) {
    new WP_EDU_Client_Github_Updater( 'canbekcan', 'wp-edu-client', __FILE__ );
}