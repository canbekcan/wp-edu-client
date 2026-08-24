<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_SSO {

    public function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_link' ], 999 );
    }

    public function add_admin_bar_link( $wp_admin_bar ) {
        if ( ! is_user_logged_in() ) return;

        $current_user_id = get_current_user_id();
        $saved_token     = get_user_meta( $current_user_id, 'lms_api_token', true );
        $saved_host      = get_user_meta( $current_user_id, 'lms_host_url', true );

        if ( empty( $saved_token ) || empty( $saved_host ) ) return;

        $sso_time = time();
        $sso_hash = hash( 'sha256', $saved_token . $sso_time );
        $student_sso_link = rtrim( $saved_host, '/' ) . '/?wp_edu_sso=' . $saved_token . '&t=' . $sso_time . '&h=' . $sso_hash;

        $args = array(
            'id'    => 'lms_sso_shortcut',
            'title' => '<span class="ab-icon dashicons dashicons-welcome-student" style="margin-top:2px;"></span> <span class="ab-label" style="font-weight:bold; color:#72aee6;">' . esc_html__( 'Go to Host Panel', 'wp-edu-client' ) . '</span>', 
            'href'  => esc_url( $student_sso_link ),
            'meta'  => array(
                'target' => '_blank',
                'title'  => esc_attr__( 'Log in to your instructor\'s Host panel without a password', 'wp-edu-client' ),
            )
        );

        $wp_admin_bar->add_node( $args );
    }
}