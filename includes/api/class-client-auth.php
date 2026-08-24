<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Auth {

    public static function verify_request( WP_REST_Request $request ) {
        $auth_header = $request->get_header( 'authorization' );
        
        if ( empty( $auth_header ) ) {
            return new WP_Error( 'unauthorized', __( 'Missing token.', 'wp-edu-client' ), [ 'status' => 401 ] );
        }

        $token = str_replace( 'Bearer ', '', $auth_header );
        $token_hash = md5( $token );
        
        $user_id = get_transient( 'lms_auth_user_' . $token_hash );

        if ( false === $user_id ) {
            $users = get_users([
                'meta_key'   => 'lms_api_token',
                'meta_value' => $token,
                'number'     => 1,
                'fields'     => 'ID'
            ]);

            if ( empty( $users ) ) {
                return new WP_Error( 'forbidden', __( 'Invalid or unassigned API Token.', 'wp-edu-client' ), [ 'status' => 403 ] );
            }

            $user_id = $users[0];
            
            set_transient( 'lms_auth_user_' . $token_hash, $user_id, 12 * HOUR_IN_SECONDS );
        }

        wp_set_current_user( $user_id );
        $request->set_param( 'student_user_id', $user_id );
        
        return true;
    }
}