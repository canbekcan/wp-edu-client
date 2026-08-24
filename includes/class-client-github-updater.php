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

        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_github_folder_name' ], 10, 3 );
    }

    private function get_github_release() {
        // Sayfa içinde birden fazla filtre çalıştığında API'yi tekrar çağırmamak için statik değişken
        static $runtime_cache = null;
        if ( null !== $runtime_cache ) {
            return $runtime_cache;
        }

        $cache_key = 'wp_edu_updater_' . $this->repo_name;
        $data      = get_transient( $cache_key );
        
        if ( false === $data ) {
            $response = wp_remote_get( $this->github_api_url, [
                'headers' => [
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
                ],
                'timeout' => 10
            ]);
            
            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ) );
                    set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
                } elseif ( $code === 403 ) {
                    // Limit aşıldıysa sistemi 15 dakika boyunca tekrar istek atmaktan alıkoy
                    set_transient( $cache_key, 'rate_limited', 15 * MINUTE_IN_SECONDS );
                }
            }
        }
        
        $runtime_cache = ( $data === 'rate_limited' ) ? false : $data;
        return $runtime_cache;
    }

    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

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
        
        // Temel Bilgiler
        $obj->name          = 'BEKCAN Institute (Student)';
        $obj->slug          = $this->plugin_slug;
        $obj->version       = ltrim( $github_data->tag_name, 'v' );
        $obj->author        = '<a href="https://bekcan.com">BEKCAN Institute</a>';
        $obj->homepage      = 'https://bekcan.com';
        $obj->download_link = $github_data->zipball_url;
        
        // Yan Panel (Sidebar) Meta Verileri
        $obj->requires      = '6.0';
        $obj->tested        = '6.7';
        $obj->requires_php  = '7.4';
        $obj->last_updated  = date( 'Y-m-d', strtotime( $github_data->published_at ) );
        $obj->active_installs = 100;
        
        // Header Banner Görselleri (Deponuzdaki raw linkleri veya doğrudan URL verilebilir)
        $obj->banners = [
            'low'  => 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/assets/banner-772x250.png',
            'high' => 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/assets/banner-1544x500.png',
        ];

        // İkon Görselleri
        $obj->icons = [
            '1x' => 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/assets/icon-128x128.png',
            '2x' => 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/assets/icon-256x256.png',
        ];

        // Sekmeler (Tabs)
        $obj->sections = [
            'description'  => '<h3>Öğrenci LMS İstemcisi</h3><p>WordPress tabanlı öğrenci web sitelerini merkezi Host LMS altyapısına bağlar; içerik analitiği, revizyon takibi ve duyuru akışı sağlar.</p>',
            'installation' => '<h4>Kurulum Adımları</h4><ol><li>Eklentiyi etkinleştirin.</li><li><strong>BEKCAN EDU</strong> menüsüne gidin.</li><li>Host LMS tarafından sağlanan API anahtarlarınızı girin.</li></ol>',
            'changelog'    => wp_kses_post( wpautop( $github_data->body ) ),
            'faq'          => '<h4>Host bağlantısı nasıl doğrulanır?</h4><p>Ayarlar sayfasındaki durum göstergesi yeşil yandığında bağlantı aktiftir.</p>',
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