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
        
        // GitHub'daki en son "Release" verisini çeken resmi API
        $this->github_api_url = "https://api.github.com/repos/{$repo_user}/{$repo_name}/releases/latest";

        // WordPress'in güncelleme mekanizmalarına kanca atıyoruz
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
    }

    private function get_github_release() {
        // Rate-limit (istek sınırı) yememek için veriyi 12 saat önbellekte tutuyoruz
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
                # set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
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

        // Kendi eklentimizin şu anki sürümünü al
        $plugin_data     = get_plugin_data( $this->plugin_file );
        $current_version = $plugin_data['Version'];
        
        // GitHub'dan gelen "v1.5.0" gibi bir etiketteki 'v' harfini temizle
        $new_version = ltrim( $github_data->tag_name, 'v' ); 

        // Eğer GitHub'daki sürüm numarası, eklentidekinden büyükse güncelleme uyarısı ver
        if ( version_compare( $current_version, $new_version, '<' ) ) {
            $obj = new stdClass();
            $obj->slug        = $this->plugin_slug;
            $obj->new_version = $new_version;
            $obj->url         = $github_data->html_url;
            $obj->package     = $github_data->zipball_url; // WordPress bu ZIP linkini indirip otomatik kuracak
            
            $transient->response[$this->plugin_slug] = $obj;
        }

        return $transient;
    }
}