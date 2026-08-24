<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Endpoint_Updates {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    public function register_route() {
        register_rest_route( 'lms/v1', '/updates', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => [ 'WP_EDU_Client_Auth', 'verify_request' ]
        ] );
    }

    public function handle_request( WP_REST_Request $request ) {
        $plugins_transient = get_site_transient( 'update_plugins' );
        $themes_transient  = get_site_transient( 'update_themes' );
        $core_transient    = get_site_transient( 'update_core' );

        $plugin_count = ( is_object( $plugins_transient ) && isset( $plugins_transient->response ) && is_array( $plugins_transient->response ) ) ? count( $plugins_transient->response ) : 0;
        $theme_count  = ( is_object( $themes_transient ) && isset( $themes_transient->response ) && is_array( $themes_transient->response ) ) ? count( $themes_transient->response ) : 0;
        
        $has_core_update = false;
        if ( is_object( $core_transient ) && isset( $core_transient->updates ) && is_array( $core_transient->updates ) ) {
            foreach ( $core_transient->updates as $update ) {
                if ( isset( $update->response ) && $update->response === 'upgrade' ) {
                    $has_core_update = true; break;
                }
            }
        }

        return rest_ensure_response( [
            'status'  => 'success',
            'updates' => [
                'core'    => $has_core_update,
                'plugins' => $plugin_count,
                'themes'  => $theme_count
            ]
        ] );
    }
}