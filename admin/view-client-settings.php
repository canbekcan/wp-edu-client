<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user_id = get_current_user_id();
$current_user    = wp_get_current_user();

if ( isset( $_POST['disconnect_lms'] ) && check_admin_referer( 'edu_disconnect_nonce' ) ) {
    delete_user_meta( $current_user_id, 'lms_api_token' );
    delete_user_meta( $current_user_id, 'lms_host_url' );
    echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Connection successfully reset. Please reconnect with your new registration code.', 'wp-edu-client' ) . '</p></div>';
} 

if ( isset( $_POST['connect_lms'] ) && check_admin_referer( 'edu_connect_nonce' ) ) {
    $host_url = esc_url_raw( $_POST['host_url'] );
    $reg_code = sanitize_text_field( $_POST['registration_code'] );
    $student_email = $current_user->user_email;
    $site_url      = site_url();
    $endpoint = rtrim( $host_url, '/' ) . '/wp-json/lms/v1/register';
    
    $response = wp_remote_post( $endpoint, [
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode([
            'registration_code' => $reg_code,
            'site_url'          => $site_url,
            'student_email'     => $student_email
        ]),
        'timeout' => 15
    ]);

    if ( is_wp_error( $response ) ) {
        echo '<div class="error"><p>' . sprintf( esc_html__( 'Connection failed: %s', 'wp-edu-client' ), esc_html( $response->get_error_message() ) ) . '</p></div>';
    } else {
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( $status_code === 200 && isset( $body['api_token'] ) ) {
            update_user_meta( $current_user_id, 'lms_api_token', sanitize_text_field( $body['api_token'] ) );
            update_user_meta( $current_user_id, 'lms_host_url', $host_url );
            echo '<div class="updated"><p>' . esc_html__( 'Successfully connected your account to the LMS Dashboard!', 'wp-edu-client' ) . '</p></div>';
        } else {
            $error_msg = isset( $body['message'] ) ? $body['message'] : __( 'Unknown error.', 'wp-edu-client' );
            echo '<div class="error"><p>' . sprintf( esc_html__( 'Registration denied: %s', 'wp-edu-client' ), esc_html( $error_msg ) ) . '</p></div>';
        }
    }
} 

$saved_token = get_user_meta( $current_user_id, 'lms_api_token', true );
$saved_host  = get_user_meta( $current_user_id, 'lms_host_url', true );
$host_url    = ! empty( $saved_host ) ? $saved_host : ''; 
$api_token   = $saved_token; 
$student_sso_link = ! empty( $host_url ) ? rtrim( $host_url, '/' ) . '/?wp_edu_sso=' . $api_token : '#';

$api_error = '';
$total_posts_count = 0;
$average_grade     = 0;
$grades_data       = [];
$transient_key     = 'lms_settings_grades_' . $current_user_id;

if ( $saved_token && ! empty( $host_url ) ) {
    $grades_data = get_transient( $transient_key );
    
    if ( false === $grades_data ) {
        $grades_endpoint = rtrim( $host_url, '/' ) . '/wp-json/lms/v1/grades?token=' . $api_token . '&_t=' . time();
        $grades_response = wp_remote_get( $grades_endpoint, [
            'headers' => [ 'Authorization' => 'Bearer ' . $api_token ],
            'timeout' => 15
        ]);
        
        if ( is_wp_error( $grades_response ) ) {
            $api_error = sprintf( __( 'Could not retrieve grades. Error: %s', 'wp-edu-client' ), $grades_response->get_error_message() );
        } else {
            $status_code = wp_remote_retrieve_response_code( $grades_response );
            $body_raw    = wp_remote_retrieve_body( $grades_response );
            $body        = json_decode( $body_raw, true );
            
            if ( $status_code === 200 && isset($body['status']) && $body['status'] === 'success' ) {
                $grades_data = $body['data'];
                set_transient( $transient_key, $grades_data, 5 * MINUTE_IN_SECONDS ); // 5 Dakikalık önbellek
            } else {
                $error_detail = isset($body['message']) ? $body['message'] : sprintf( __( 'HTTP Code: %d', 'wp-edu-client' ), $status_code );
                $api_error = sprintf( __( 'Host returned an error: %s', 'wp-edu-client' ), $error_detail );
            }
        }
    }

    if ( is_array( $grades_data ) ) {
        $total_posts_count = count( $grades_data );
        $total_cumulative_grade = 0;
        if ( $total_posts_count > 0 ) {
            foreach ( $grades_data as $g ) {
                $total_cumulative_grade += intval( $g['grade'] );
            }
            $average_grade = round( $total_cumulative_grade / $total_posts_count );
        }
    }
}

$avg_grade_color = '#00a32a';
if ( $average_grade < 50 ) $avg_grade_color = '#d63638';
elseif ( $average_grade < 75 ) $avg_grade_color = '#dba617';
?>

<div class="wrap">
    <h1><?php printf( esc_html__( 'Student Connection Dashboard (%s)', 'wp-edu-client' ), esc_html( $current_user->display_name ) ); ?></h1>
    
    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 400px;">
            <?php if ( $saved_token ) : ?>
                <div style="background: #e7f5ea; border-left: 4px solid #46b450; padding: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; color: #1e7e34;"><?php esc_html_e( 'Status: Connected', 'wp-edu-client' ); ?></h3>
                        <p style="margin-bottom: 0;"><?php printf( esc_html__( 'Your account (%s) is linked to the Host LMS.', 'wp-edu-client' ), esc_html( $current_user->user_email ) ); ?></p>
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to disconnect? Your data on the host will not be deleted, but you will need to enter a registration code again.', 'wp-edu-client' ) ); ?>');">
                        <?php wp_nonce_field( 'edu_disconnect_nonce' ); ?>
                        <button type="submit" name="disconnect_lms" class="button" style="color: #d63638; border-color: #d63638;"><?php esc_attr_e( 'Disconnect', 'wp-edu-client' ); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-top:20px;">
                <form method="POST" action="">
                    <?php wp_nonce_field( 'edu_connect_nonce' ); ?>
                    <p>
                        <label><?php esc_html_e( 'Host LMS URL', 'wp-edu-client' ); ?></label><br/>
                        <input type="url" name="host_url" required class="regular-text" value="<?php echo esc_attr( $host_url ); ?>" placeholder="https://lms.example.edu">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Registration Code', 'wp-edu-client' ); ?></label><br/>
                        <input type="text" name="registration_code" required class="regular-text" style="text-transform: uppercase;">
                        <br/><small><?php esc_html_e( 'Enter the code provided by your instructor for your specific class.', 'wp-edu-client' ); ?></small>
                    </p>
                    <p>
                        <input type="submit" name="connect_lms" class="button button-primary" value="<?php echo $saved_token ? esc_attr__( 'Reconnect / Update', 'wp-edu-client' ) : esc_attr__( 'Connect Account', 'wp-edu-client' ); ?>">
                    </p>
                </form>
            </div>

            <?php if ( $saved_token && ! empty( $host_url ) ) : ?>
            <div class="card" style="margin-top: 20px; padding: 15px;">
                <h3 style="margin-top:0;"><?php esc_html_e( 'Host Panel', 'wp-edu-client' ); ?></h3>
                <p><?php esc_html_e( 'Go to the main administration to see detailed notifications and reports from your instructor.', 'wp-edu-client' ); ?></p>
                <a href="<?php echo esc_url( $student_sso_link ); ?>" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Go to Host (SSO)', 'wp-edu-client' ); ?></a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( $saved_token ) : ?>
        <div style="flex: 2; min-width: 500px; margin-top: 20px; background:#fff; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
            
            <div style="padding: 15px; border-bottom: 1px solid #ccd0d4; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0;"><?php esc_html_e( 'My Content Performance & Grades', 'wp-edu-client' ); ?></h3>
                
                <?php if ( empty( $api_error ) && $total_posts_count > 0 ) : ?>
                <div style="display: flex; gap: 20px;">
                    <div style="text-align: center;">
                        <small style="color: #666; font-weight: bold; text-transform: uppercase; font-size: 10px;"><?php esc_html_e( 'Total Contents', 'wp-edu-client' ); ?></small><br/>
                        <span style="font-size: 18px; font-weight: bold; color: #2271b1;"><?php echo intval( $total_posts_count ); ?></span>
                    </div>
                    <div style="border-left: 1px solid #ccc; padding-left: 20px; text-align: center;">
                        <small style="color: #666; font-weight: bold; text-transform: uppercase; font-size: 10px;"><?php esc_html_e( 'Average Grade', 'wp-edu-client' ); ?></small><br/>
                        <span style="font-size: 18px; font-weight: bold; color: <?php echo $avg_grade_color; ?>;"><?php echo intval( $average_grade ); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ( ! empty( $api_error ) ) : ?>
                <div style="padding: 15px;"><span style="color: #d63638; font-weight:bold;"><?php echo esc_html( $api_error ); ?></span></div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php esc_html_e( 'Post Title', 'wp-edu-client' ); ?></th>
                            <th style="width: 20%;"><?php esc_html_e( 'Date Sent', 'wp-edu-client' ); ?></th>
                            <th style="width: 15%;"><center><?php esc_html_e( 'Speed (WPM)', 'wp-edu-client' ); ?></center></th>
                            <th style="width: 15%;"><center><?php esc_html_e( 'Status', 'wp-edu-client' ); ?></center></th>
                            <th style="width: 10%;"><center><?php esc_html_e( 'Grade', 'wp-edu-client' ); ?></center></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $grades_data ) ) : ?>
                            <tr><td colspan="5" style="padding: 15px;"><?php esc_html_e( 'No content has been scanned by the host yet. Data will appear after the next synchronization.', 'wp-edu-client' ); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ( $grades_data as $g ) : 
                                $dur_mins = intval($g['duration']);
                                $d_days   = floor($dur_mins / 1440);
                                $d_hours  = floor(($dur_mins % 1440) / 60);
                                $d_mins   = $dur_mins % 60;
                                
                                $dur_text = '';
                                if ( $d_days > 0 )  $dur_text .= $d_days . ' ' . __( 'd', 'wp-edu-client' ) . ' ';
                                if ( $d_hours > 0 ) $dur_text .= $d_hours . ' ' . __( 'h', 'wp-edu-client' ) . ' ';
                                if ( $d_mins > 0 || $dur_text === '' ) $dur_text .= $d_mins . ' ' . __( 'm', 'wp-edu-client' );
                                $dur_text = trim( $dur_text );

                                $grade_color = '#00a32a';
                                if ( $g['grade'] < 50 ) $grade_color = '#d63638';
                                elseif ( $g['grade'] < 75 ) $grade_color = '#dba617';
                            ?>
                            <tr>
                                <td><a href="<?php echo esc_url($g['url']); ?>" target="_blank"><strong><?php echo esc_html($g['title']); ?></strong></a><br/><small><?php echo intval($g['word_count']); ?> <?php esc_html_e( 'Words', 'wp-edu-client' ); ?></small></td>
                                <td><?php echo esc_html($g['date']); ?></td>
                                <td><center><strong><?php echo intval($g['wpm']); ?></strong><br/><small><?php echo esc_html($dur_text); ?></small></center></td>
                                <td><center>
                                    <?php if ( $g['is_modified'] == 1 ) : ?>
                                        <span style="display:inline-block; padding:3px 6px; background:#f8d7da; color:#721c24; border-radius:3px; font-size:10px; font-weight:bold;"><?php esc_html_e( 'MODIFIED', 'wp-edu-client' ); ?></span>
                                    <?php else : ?>
                                        <span style="display:inline-block; padding:3px 6px; background:#d4edda; color:#155724; border-radius:3px; font-size:10px; font-weight:bold;"><?php esc_html_e( 'ORIGINAL', 'wp-edu-client' ); ?></span>
                                    <?php endif; ?>
                                </center></td>
                                <td><center><span style="font-size: 16px; font-weight: bold; color: <?php echo $grade_color; ?>"><?php echo intval($g['grade']); ?></span></center></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>