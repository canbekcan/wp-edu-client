<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Notices {
    
    public function __construct() {
        add_action( 'wp_ajax_dismiss_lms_notice', [ $this, 'dismiss_notice' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notice' ] );
        add_action( 'wp_footer', [ $this, 'display_frontend_modal' ] );
    }

    public function dismiss_notice() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Yetkisiz erişim.' );
        }
        
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lms_dismiss_notice' ) ) {
            wp_send_json_error( 'Güvenlik doğrulaması başarısız.' );
        }

        $notice = get_option( 'lms_host_admin_notice' );
        if ( $notice && isset( $notice['id'] ) ) {
            update_user_meta( get_current_user_id(), 'lms_dismissed_notice_' . $notice['id'], true );
        }
        wp_send_json_success();
    }

    public function display_admin_notice() {
        $notice = get_option( 'lms_host_admin_notice' );
        if ( $notice && ! empty( $notice['message'] ) ) {
            
            $notice_id = isset( $notice['id'] ) ? $notice['id'] : '';
            if ( get_user_meta( get_current_user_id(), 'lms_dismissed_notice_' . $notice_id, true ) ) {
                return;
            }
            
            $type    = esc_attr( $notice['type'] );
            $message = wp_kses_post( $notice['message'] );
            $title   = esc_html__( 'Instructor Notice:', 'wp-edu-client' );
            $nonce   = wp_create_nonce( 'lms_dismiss_notice' );
            
            echo "<div class='notice notice-{$type} is-dismissible lms-custom-notice' style='border-left-width: 4px; padding: 12px;'>
                    <p style='font-size: 14px; margin: 0;'><strong><span class='dashicons dashicons-megaphone' style='vertical-align: middle;'></span> {$title}</strong> {$message}</p>
                  </div>";
                  
            ?>
            <script>
                jQuery(document).on('click', '.lms-custom-notice .notice-dismiss', function() {
                    jQuery.post(ajaxurl, { 
                        action: 'dismiss_lms_notice',
                        nonce: '<?php echo esc_js( $nonce ); ?>'
                    });
                });
            </script>
            <?php
        }
    }

    public function display_frontend_modal() {
        if ( ! is_user_logged_in() ) return;

        $notice = get_option( 'lms_host_admin_notice' );
        if ( $notice && ! empty( $notice['message'] ) ) {
            
            $notice_id = isset( $notice['id'] ) ? $notice['id'] : '';
            if ( get_user_meta( get_current_user_id(), 'lms_dismissed_notice_' . $notice_id, true ) ) {
                return;
            }

            $type = esc_attr( $notice['type'] );
            $message = wp_kses_post( $notice['message'] );
            
            $brand_color = '#72aee6'; 
            if ( $type === 'success' ) $brand_color = '#00a32a'; 
            if ( $type === 'warning' ) $brand_color = '#dba617'; 
            if ( $type === 'error' )   $brand_color = '#d63638'; 

            $ajax_url = admin_url( 'admin-ajax.php' );
            $nonce    = wp_create_nonce( 'lms_dismiss_notice' );

            ?>
            <div id="lms-host-notice-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 999999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 450px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-top: 5px solid <?php echo esc_attr( $brand_color ); ?>; position: relative;">
                    <h3 style="margin-top: 0; color: #2271b1; display: flex; align-items: center; gap: 10px; font-size: 20px;">
                        <span style="font-size: 24px;">📣</span> <?php esc_html_e( 'Instructor Notice', 'wp-edu-client' ); ?>
                    </h3>
                    <div style="color: #3c434a; font-size: 15px; line-height: 1.6; margin-bottom: 25px;">
                        <?php echo $message; ?>
                    </div>
                    <button id="lms-dismiss-btn" style="background: <?php echo esc_attr( $brand_color ); ?>; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; width: 100%; transition: opacity 0.2s;">
                        <?php esc_html_e( 'Close Message', 'wp-edu-client' ); ?>
                    </button>
                </div>
            </div>
            
            <script>
                document.getElementById('lms-dismiss-btn').addEventListener('click', function() {
                    document.getElementById('lms-host-notice-modal').style.display = 'none';

                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo esc_url($ajax_url); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.withCredentials = true;
                    xhr.send('action=dismiss_lms_notice&nonce=<?php echo esc_js( $nonce ); ?>');
                });
            </script>
            <?php
        }
    }
}