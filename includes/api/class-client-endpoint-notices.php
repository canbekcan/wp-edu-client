<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Endpoint_Notices {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    public function register_route() {
        register_rest_route( 'lms/v1', '/notice', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => [ 'WP_EDU_Client_Auth', 'verify_request' ]
        ] );
    }

    public function handle_request( WP_REST_Request $request ) {
        $message = wp_kses_post( $request->get_param( 'message' ) );
        $type    = sanitize_text_field( $request->get_param( 'type' ) );
        $id      = sanitize_text_field( $request->get_param( 'id' ) );

        if ( empty( $message ) ) {
            delete_option( 'lms_host_admin_notice' );
            return rest_ensure_response( [ 'status' => 'success', 'message' => __( 'Notice cleared.', 'wp-edu-client' ) ] );
        }

        update_option( 'lms_host_admin_notice', [
            'message' => $message,
            'type'    => in_array( $type, ['info', 'success', 'warning', 'error'] ) ? $type : 'info',
            'id'      => $id 
        ] );

        return rest_ensure_response( [ 'status' => 'success', 'message' => __( 'Notice updated.', 'wp-edu-client' ) ] );
    }
}