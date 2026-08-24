<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Client_Github_Updater {

    private $repo_user;
    private $repo_name;
    private $plugin_file;
    private $plugin_slug;
    private $github_api_url;

    public function __construct( $repo_user, $repo_name, $plugin_file ) {
        $this->repo_user   = $repo_user;
        $this->repo_name   = $repo_name;
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename( $plugin_file ); 
        
        $this->github_api_url = "https://api.github.com/repos/{$repo_user}/{$repo_name}/releases/latest";

        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_github_folder_name' ], 10, 3 );
    }

    private function get_github_release() {
        $cache_key = 'wp_edu_updater_' . $this->repo_name;
        
        // ÇÖZÜM 1: Yönetici "Tekrar Kontrol Et" dediğinde önbelleği temizle (API Sınırını korur)
        if ( isset( $_GET['force-check'] ) && $_GET['force-check'] == '1' ) {
            delete_transient( $cache_key );
        }

        $data = get_transient( $cache_key );
        
        if ( false === $data ) {
            $response = wp_remote_get( $this->github_api_url, [
                'headers' => [
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
                ],
                'timeout' => 15
            ] );
            
            if ( ! is_wp_error( $response ) ) {
                $status_code = wp_remote_retrieve_response_code( $response );
                if ( $status_code === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ) );
                    set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS ); // 6 saatte bir otomatik kontrol et
                } else if ( $status_code === 403 ) {
                    // API limiti aşıldıysa, 30 dakika boyunca tekrar istek atmasını engelle
                    set_transient( $cache_key, 'rate_limited', 30 * MINUTE_IN_SECONDS );
                }
            }
        }
        
        return ( $data === 'rate_limited' ) ? false : $data;
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $github_data = $this->get_github_release();
        if ( ! $github_data || empty( $github_data->tag_name ) ) {
            return $transient;
        }

        // ÇÖZÜM 2: Versiyonu doğrudan plugin meta verisinden hatasız okuma
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data     = get_plugin_data( $this->plugin_file );
        $current_version = $plugin_data['Version'];
        $new_version     = ltrim( $github_data->tag_name, 'v' ); 

        if ( version_compare( $current_version, $new_version, '<' ) ) {
            $obj = new stdClass();
            $obj->slug        = dirname( $this->plugin_slug );
            $obj->plugin      = $this->plugin_slug;
            $obj->new_version = $new_version;
            $obj->url         = $github_data->html_url;
            $obj->package     = $github_data->zipball_url; 
            $obj->icons       = []; 
            $obj->banners     = [];
            
            $transient->response[$this->plugin_slug] = $obj;
        }

        return $transient;
    }

    public function plugin_popup_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || empty( $args->slug ) || $args->slug !== dirname( $this->plugin_slug ) ) {
            return $result;
        }

        $github_data = $this->get_github_release();
        if ( ! $github_data ) return $result;

        $obj = new stdClass();
        $obj->name          = 'WP EDU Client';
        $obj->slug          = dirname( $this->plugin_slug );
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
        if ( ! $wp_filesystem || ! isset( $source ) ) return $source;
        
        $source_trail = trailingslashit( $source );
        $main_file    = basename( $this->plugin_file ); 
        
        if ( $wp_filesystem->exists( $source_trail . $main_file ) ) {
            
            // ÇÖZÜM 3: Öğrenci eklentiyi "wp-edu-client-main" adıyla kurduysa bile, 
            // güncellemeyi o dinamik klasör adına göre çıkartır, sistemi çökertmez.
            $expected_folder = trailingslashit( $remote_source ) . dirname( $this->plugin_slug );
            
            if ( $source_trail !== $expected_folder ) {
                if ( $wp_filesystem->exists( $expected_folder ) ) {
                    $wp_filesystem->delete( $expected_folder, true );
                }
                
                if ( $wp_filesystem->move( $source, $expected_folder, true ) ) {
                    return $expected_folder;
                }
            }
        }
        
        return $source;
    }
}