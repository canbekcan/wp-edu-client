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
        $data      = get_transient( $cache_key );
        
        if ( false === $data ) {
            $response = wp_remote_get( $this->github_api_url, [
                'headers' => [
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
                ]
            ] );
            
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ) );
                // TEST İÇİN: 1 Saniye. Test bitince bunu "12 * HOUR_IN_SECONDS" olarak değiştirin.
                set_transient( $cache_key, $data, 1 ); 
            }
        }
        return $data;
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $github_data = $this->get_github_release();
        if ( ! $github_data || empty( $github_data->tag_name ) ) {
            return $transient;
        }

        // ÇÖZÜM 1: get_plugin_data yerine versiyonu doğrudan transient'ten alıyoruz (Çökmeyi engeller).
        $current_version = isset( $transient->checked[$this->plugin_slug] ) ? $transient->checked[$this->plugin_slug] : '0.0.0';
        $new_version     = ltrim( $github_data->tag_name, 'v' ); 

        if ( version_compare( $current_version, $new_version, '<' ) ) {
            $obj = new stdClass();
            
            // ÇÖZÜM 2: WordPress'in beklediği slug ve plugin ayrımı
            $obj->slug        = dirname( $this->plugin_slug );
            $obj->plugin      = $this->plugin_slug;
            $obj->new_version = $new_version;
            $obj->url         = $github_data->html_url;
            $obj->package     = $github_data->zipball_url; 
            
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
        
        // ÇÖZÜM 3: GitHub'ın uzattığı klasör adını kesip orijinal eklenti ismine (wp-edu-client) geri çeviriyoruz.
        if ( isset( $source ) && strpos( basename( $source ), $this->repo_name ) !== false ) {
            $expected_folder = trailingslashit( $remote_source ) . dirname( $this->plugin_slug );
            
            if ( $source !== $expected_folder ) {
                $wp_filesystem->move( $source, $expected_folder );
                return trailingslashit( $expected_folder );
            }
        }
        
        return $source;
    }
}