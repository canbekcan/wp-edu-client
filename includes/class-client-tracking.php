<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Tracking {

    public function __construct() {
        add_action( 'save_post', [ $this, 'track_times' ], 10, 2 );
    }

    public function track_times( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || $post->post_type !== 'post' ) {
            return;
        }
        
        if ( ! get_post_meta( $post_id, 'lms_post_start_time', true ) ) {
            update_post_meta( $post_id, 'lms_post_start_time', current_time( 'mysql' ) );
        }
        
        update_post_meta( $post_id, 'lms_post_end_time', current_time( 'mysql' ) );
    }
}