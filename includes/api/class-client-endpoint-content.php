<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Endpoint_Content {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    public function register_route() {
        register_rest_route( 'lms/v1', '/content', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => [ 'WP_EDU_Client_Auth', 'verify_request' ]
        ] );
    }

    public function handle_request( WP_REST_Request $request ) {
        $student_user_id = $request->get_param( 'student_user_id' );
        if ( empty( $student_user_id ) ) {
            return rest_ensure_response( [ 'status' => 'error', 'message' => __( 'Author identification failed.', 'wp-edu-client' ) ] );
        }
        
        $paged = max( 1, intval( $request->get_param( 'page' ) ) );
        $posts_data = [];
        
        $query = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'author'         => intval( $student_user_id ),
            'posts_per_page' => 20,
            'paged'          => $paged,
            'orderby'        => 'modified',
            'order'          => 'DESC'
        ]);

        $student_domain = parse_url( site_url(), PHP_URL_HOST );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                $raw_content   = $post->post_content;
                
                $clean_content = wp_strip_all_tags( $raw_content );
                $words_array   = preg_split( '/[\s\p{P}]+/u', trim( $clean_content ), -1, PREG_SPLIT_NO_EMPTY );
                $word_count    = is_array( $words_array ) ? count( $words_array ) : 0;

                $content = apply_filters( 'the_content', $raw_content );
                if ( empty( $content ) ) {
                    $content = $raw_content;
                }

                $internal_links = 0; $external_links = 0; $total_images = 0; $missing_alt = 0;

                if ( ! empty( $content ) ) {
                    libxml_use_internal_errors( true );
                    $dom = new DOMDocument();
                    $dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );

                    foreach ( $dom->getElementsByTagName('a') as $link ) {
                        $href = $link->getAttribute('href');
                        if ( ! empty( $href ) ) {
                            $link_domain = parse_url( $href, PHP_URL_HOST );
                            if ( $link_domain === $student_domain || strpos( $href, '/' ) === 0 ) {
                                $internal_links++;
                            } else {
                                $external_links++;
                            }
                        }
                    }

                    $images = $dom->getElementsByTagName('img');
                    $total_images = $images->length;
                    foreach ( $images as $img ) {
                        if ( empty( trim( $img->getAttribute('alt') ) ) ) { $missing_alt++; }
                    }
                    libxml_clear_errors();
                }

                $post_tags_array = get_the_tags( $post->ID );
                $tags_string = ( $post_tags_array && ! is_wp_error( $post_tags_array ) ) ? implode( ', ', wp_list_pluck( $post_tags_array, 'name' ) ) : '';

                $start_time = get_post_meta( $post->ID, 'lms_post_start_time', true );
                $end_time   = get_post_meta( $post->ID, 'lms_post_end_time', true );

                $posts_data[] = [
                    'id'             => $post->ID,
                    'title'          => $post->post_title,
                    'url'            => get_permalink( $post->ID ),
                    'content'        => $content,
                    'post_date'      => $post->post_date,
                    'post_modified'  => $post->post_modified,
                    'word_count'     => $word_count,
                    'internal_links' => $internal_links,
                    'external_links' => $external_links,
                    'total_images'   => $total_images,
                    'missing_alt'    => $missing_alt,
                    'post_tags'      => $tags_string,
                    'hash'           => hash( 'sha256', $post->post_title . $content ),
                    'post_start_time'=> !empty( $start_time ) ? $start_time : $post->post_date,
                    'post_end_time'  => !empty( $end_time ) ? $end_time : $post->post_modified
                ];
            }
            wp_reset_postdata();
        }

        return rest_ensure_response( [
            'status'       => 'success',
            'total_pages'  => intval( $query->max_num_pages ),
            'current_page' => $paged,
            'posts'        => $posts_data
        ] );
    }
}