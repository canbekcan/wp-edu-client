<?php
/**
 * Plugin Name: BEKCAN Institute (Student)
 * Description: Connects individual student accounts to the Host LMS for targeted content analytics and revision tracking.
 * Version: 0.0.2
 * Author: BEKCAN Institute
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

// if ( is_admin() ) {
//     new WP_EDU_Client_Github_Updater( 'canbekcan', 'wp-edu-client', __FILE__ );
    
//     // HATA AYIKLAMA KODU (TEST BİTİNCE SİLECEĞİZ)
//     add_action( 'admin_notices', function() {
//         $url = "https://api.github.com/repos/canbekcan/wp-edu-client/releases/latest";
//         $response = wp_remote_get( $url, ['headers' => ['User-Agent' => 'WordPress-Debug']] );
        
//         if ( is_wp_error( $response ) ) {
//             echo '<div class="notice notice-error is-dismissible"><p><strong>GitHub API Bağlantı Hatası:</strong> ' . esc_html( $response->get_error_message() ) . '</p></div>';
//             return;
//         }
        
//         $code = wp_remote_retrieve_response_code( $response );
//         $body = json_decode( wp_remote_retrieve_body( $response ) );
//         $tag  = isset( $body->tag_name ) ? $body->tag_name : 'Sürüm (Tag) Bulunamadı';
//         $msg  = isset( $body->message ) ? $body->message : 'Mesaj Yok';
        
//         $color = ($code === 200) ? 'notice-success' : 'notice-error';
//         echo "<div class='notice {$color} is-dismissible'>
//                 <p><strong>GitHub API Durumu (Test İçindir):</strong></p>
//                 <ul style='list-style-type:disc; margin-left:20px;'>
//                     <li>HTTP Kodu: <strong>{$code}</strong></li>
//                     <li>Sistemdeki Son Sürüm: <strong>{$tag}</strong></li>
//                     <li>GitHub Mesajı: <strong>{$msg}</strong></li>
//                 </ul>
//               </div>";
//     });
// }


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