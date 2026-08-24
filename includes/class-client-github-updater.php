<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Github_Updater {

    private $repo_user;
    private $repo_name;
    private $plugin_file;
    private $plugin_slug;
    private $plugin_basename;
    private $github_api_url;

    public function __construct( $repo_user, $repo_name, $plugin_file ) {
        $this->repo_user       = $repo_user;
        $this->repo_name       = $repo_name;
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $this->plugin_slug     = dirname( $this->plugin_basename );
        
        $this->github_api_url = "https://api.github.com/repos/{$repo_user}/{$repo_name}/releases/latest";

        // GÜNCELLEME: Hem kaydetme hem de okuma kancalarına müdahale ediyoruz
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );
        
        add_filter( 'plugins_api', [ $this, 'plugin_popup_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_github_folder_name' ], 10, 3 );
    }

    private function get_github_release() {
        $cache_key = 'wp_edu_updater_' . $this->repo_name;
        
        // TEST İÇİN ÖNBELLEĞİ EZİYORUZ (Canlı kullanımda bu satırı kaldırabilirsiniz)
        delete_transient( $cache_key ); 

        $data = get_transient( $cache_key );
        
        if ( false === $data ) {
            $response = wp_remote_get( $this->github_api_url, [
                'headers' => [
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
                ],
                'timeout' => 15
            ]);
            
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ) );
                set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
            }
        }
        
        return $data;
    }

    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        // KRİTİK DÜZELTME: empty( $transient->checked ) kontrolü KALDIRILDI!

        $github_data = $this->get_github_release();
        if ( ! $github_data || empty( $github_data->tag_name ) ) {
            return $transient;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_data     = get_plugin_data( $this->plugin_file );
        $current_version = $plugin_data['Version'];
        $new_version     = ltrim( $github_data->tag_name, 'v' ); 

        $obj = new stdClass();
        $obj->slug        = $this->plugin_slug;
        $obj->plugin      = $this->plugin_basename;
        $obj->new_version = $new_version;
        $obj->url         = $github_data->html_url;
        $obj->package     = $github_data->zipball_url;
        $obj->tested      = '6.5';
        $obj->requires_php = '7.4';

        if ( version_compare( $current_version, $new_version, '<' ) ) {
            $transient->response[$this->plugin_basename] = $obj;
        } else {
            if ( ! isset( $transient->no_update ) ) {
                $transient->no_update = [];
            }
            $transient->no_update[$this->plugin_basename] = $obj;
            
            if ( isset( $transient->response[$this->plugin_basename] ) ) {
                unset( $transient->response[$this->plugin_basename] );
            }
        }

        return $transient;
    }

    public function plugin_popup_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || empty( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $github_data = $this->get_github_release();
        if ( ! $github_data ) return $result;

        $obj = new stdClass();
        $obj->name          = 'BEKCAN Institute (Student)';
        $obj->slug          = $this->plugin_slug;
        $obj->version       = ltrim( $github_data->tag_name, 'v' );
        $obj->author        = 'BEKCAN Institute';
        $obj->homepage      = $github_data->html_url;
        $obj->download_link = $github_data->zipball_url;
        $obj->sections      = [
            'description' => 'Yayınlanan son sürüm notları:<br><br>' . nl2br( esc_html( $github_data->body ) )
        ];

        return $obj;
    }

    public function fix_github_folder_name( $source, $remote_source, $upgrader ) {
        global $wp_filesystem;
        
        if ( ! $wp_filesystem || ! isset( $source ) ) {
            return $source;
        }
        
        $source_clean = untrailingslashit( $source );
        $parent_dir   = dirname( $source_clean ); 
        $expected_dir = trailingslashit( $parent_dir ) . $this->plugin_slug; 
        
        $source_trail = trailingslashit( $source );
        $main_file    = basename( $this->plugin_file );
        
        if ( $wp_filesystem->exists( $source_trail . $main_file ) ) {
            if ( $source_clean !== $expected_dir ) {
                if ( $wp_filesystem->exists( $expected_dir ) ) {
                    $wp_filesystem->delete( $expected_dir, true );
                }
                
                if ( $wp_filesystem->move( $source_clean, $expected_dir, true ) ) {
                    return trailingslashit( $expected_dir );
                }
            }
        }
        
        return $source;
    }
}