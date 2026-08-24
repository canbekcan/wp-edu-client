<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Auth {

    public static function verify_request( WP_REST_Request $request ) {
        $auth_header = $request->get_header( 'authorization' );
        
        if ( empty( $auth_header ) ) {
            return new WP_Error( 'unauthorized', __( 'Missing token.', 'wp-edu-client' ), [ 'status' => 401 ] );
        }

        $token = str_replace( 'Bearer ', '', $auth_header );

        $users = get_users([
            'meta_key'   => 'lms_api_token',
            'meta_value' => $token,
            'number'     => 1
        ]);

        if ( empty( $users ) ) {
            return new WP_Error( 'forbidden', __( 'Invalid or unassigned API Token.', 'wp-edu-client' ), [ 'status' => 403 ] );
        }

        wp_set_current_user( $users[0]->ID );
        $request->set_param( 'student_user_id', $users[0]->ID );
        return true;
    }
}