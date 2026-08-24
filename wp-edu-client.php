<?php
/**
 * Plugin Name: BEKCAN Institute (Student)
 * Description: Connects individual student accounts to the Host LMS for targeted content analytics and revision tracking.
 * Version: 0.1.2.2
 * Author: BEKCAN Institute
 * Text Domain: wp-edu-client
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_EDU_CLIENT_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'wp_edu_client_load_textdomain' );
function wp_edu_client_load_textdomain() {
    load_plugin_textdomain( 'wp-edu-client', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

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
new WP_EDU_Client_Menu();
new WP_EDU_Client_Notices();
new WP_EDU_Client_Tracking();
new WP_EDU_Client_SSO();
new WP_EDU_Client_Endpoint_Content();
new WP_EDU_Client_Endpoint_Updates();
new WP_EDU_Client_Endpoint_Notices();

if ( is_admin() ) {
    new WP_EDU_Client_Github_Updater( 'canbekcan', 'wp-edu-client', __FILE__ );

    add_action( 'admin_init', function() {
        if ( isset( $_GET['force_gh_check'] ) && $_GET['force_gh_check'] == '1' ) {
            
            delete_site_transient( 'update_plugins' );
            
            delete_transient( 'wp_edu_updater_wp-edu-client' );
            delete_transient( 'wp_edu_readme_wp-edu-client' );
            
            wp_safe_redirect( remove_query_arg( 'force_gh_check' ) );
            exit;
        }
    });

    add_action( 'admin_notices', function() {
        $url = "https://api.github.com/repos/canbekcan/wp-edu-client/releases/latest";
        $response = wp_remote_get( $url, ['headers' => ['User-Agent' => 'WordPress-Debug']] );
        
        if ( is_wp_error( $response ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>GitHub API Bağlantı Hatası:</strong> ' . esc_html( $response->get_error_message() ) . '</p></div>';
            return;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ) );
        $tag  = isset( $body->tag_name ) ? $body->tag_name : 'Sürüm (Tag) Bulunamadı';
        $msg  = isset( $body->message ) ? $body->message : 'Mesaj Yok';
        
        $color = ($code === 200) ? 'notice-success' : 'notice-error';
        
        $reset_url = add_query_arg( 'force_gh_check', '1' );

        echo "<div class='notice {$color} is-dismissible'>
                <div style='display:flex; justify-content:space-between; align-items:center;'>
                    <div>
                        <p><strong>GitHub API Durumu (Test İçindir):</strong></p>
                        <ul style='list-style-type:disc; margin-left:20px;'>
                            <li>HTTP Kodu: <strong>{$code}</strong></li>
                            <li>Sistemdeki Son Sürüm: <strong>{$tag}</strong></li>
                            <li>GitHub Mesajı: <strong>{$msg}</strong></li>
                        </ul>
                    </div>
                    <div>
                        <a href='" . esc_url( $reset_url ) . "' class='button button-primary' style='background:#d63638; border-color:#d63638; text-shadow:none;'>Önbelleği Sıfırla ve Yenile</a>
                    </div>
                </div>
              </div>";
    });
}