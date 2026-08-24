<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] ); // YENİ: Başlangıç Widget'ı
    }

    public function register_admin_menu() {
        add_menu_page( 
            __( 'LMS Connection', 'wp-edu-client' ), 
            __( 'LMS Connect', 'wp-edu-client' ), 
            'edit_posts', 
            'lms-connect', 
            [ $this, 'render_settings_page' ], 
            'dashicons-admin-network', 
            80 
        );
    }

    public function render_settings_page() {
        $file_path = WP_EDU_CLIENT_DIR . 'admin/view-client-settings.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Error: view-client-settings.php file is missing.', 'wp-edu-client' ) . '</p></div>';
        }
    }

    // --- YENİ: Başlangıç Sayfası (Dashboard) Skor Panosu ---
    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'lms_student_performance_widget',
            __( 'My LMS Performance', 'wp-edu-client' ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    public function render_dashboard_widget() {
        $current_user_id = get_current_user_id();
        $saved_token     = get_user_meta( $current_user_id, 'lms_api_token', true );
        $saved_host      = get_user_meta( $current_user_id, 'lms_host_url', true );

        // Bağlantı yoksa uyarı göster
        if ( empty( $saved_token ) || empty( $saved_host ) ) {
            echo '<div style="text-align: center; padding: 15px 0;">';
            echo '<p style="color:#666; margin-bottom:15px;">' . esc_html__( 'You are not connected to any LMS Host.', 'wp-edu-client' ) . '</p>';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=lms-connect' ) ) . '" class="button button-primary">' . esc_html__( 'Connect Now', 'wp-edu-client' ) . '</a>';
            echo '</div>';
            return;
        }

        // Performans: WP Dashboard'u yavaşlatmamak için notları 5 dakikalık Transient (Önbellek) ile çekiyoruz.
        $transient_key = 'lms_grades_cache_' . $current_user_id;
        $grades_data   = get_transient( $transient_key );
        $api_error     = '';

        if ( false === $grades_data ) {
            $endpoint = rtrim( $saved_host, '/' ) . '/wp-json/lms/v1/grades?token=' . $saved_token . '&_t=' . time();
            $response = wp_remote_get( $endpoint, [ 'timeout' => 5 ] );

            if ( is_wp_error( $response ) ) {
                $api_error = __( 'Could not retrieve grades.', 'wp-edu-client' );
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );
                
                if ( $status_code === 200 && isset($body['status']) && $body['status'] === 'success' ) {
                    $grades_data = $body['data'];
                    set_transient( $transient_key, $grades_data, 5 * MINUTE_IN_SECONDS ); // 5 dakika hafızada tut
                } else {
                    $api_error = __( 'Host returned an error.', 'wp-edu-client' );
                }
            }
        }

        if ( ! empty( $api_error ) ) {
            echo '<p style="color: #d63638; text-align:center;">' . esc_html( $api_error ) . '</p>';
            return;
        }

        if ( ! is_array( $grades_data ) || empty( $grades_data ) ) {
            echo '<p style="text-align:center; color:#666;">' . esc_html__( 'No content has been scanned yet.', 'wp-edu-client' ) . '</p>';
            return;
        }

        $total_posts_count = count( $grades_data );
        $total_cumulative_grade = 0;
        foreach ( $grades_data as $g ) {
            $total_cumulative_grade += intval( $g['grade'] );
        }
        $average_grade = round( $total_cumulative_grade / $total_posts_count );

        $avg_grade_color = '#00a32a';
        if ( $average_grade < 50 ) $avg_grade_color = '#d63638';
        elseif ( $average_grade < 75 ) $avg_grade_color = '#dba617';

        ?>
        <div style="display: flex; justify-content: space-around; align-items: center; padding: 15px 0;">
            <div style="text-align: center;">
                <span style="font-size: 36px; font-weight: bold; color: #2271b1; display: block; line-height: 1; margin-bottom: 5px;"><?php echo intval( $total_posts_count ); ?></span>
                <small style="color: #666; font-weight: bold; text-transform: uppercase; font-size: 10px;"><?php esc_html_e( 'Total Contents', 'wp-edu-client' ); ?></small>
            </div>
            <div style="width: 1px; background: #ccd0d4; height: 50px;"></div>
            <div style="text-align: center;">
                <span style="font-size: 36px; font-weight: bold; color: <?php echo $avg_grade_color; ?>; display: block; line-height: 1; margin-bottom: 5px;"><?php echo intval( $average_grade ); ?></span>
                <small style="color: #666; font-weight: bold; text-transform: uppercase; font-size: 10px;"><?php esc_html_e( 'Average Grade', 'wp-edu-client' ); ?></small>
            </div>
        </div>
        <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
            <a href="<?php echo esc_url( admin_url('admin.php?page=lms-connect') ); ?>" class="button"><?php esc_html_e( 'View Details', 'wp-edu-client' ); ?></a>
        </div>
        <?php
    }
}