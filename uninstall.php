<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$options_to_delete = [
    'wp_edu_client_host_url',
    'wp_edu_client_api_key',
    'wp_edu_client_student_token',
    'wp_edu_client_sync_status'
];

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

delete_transient( 'wp_edu_updater_wp-edu-client' );
delete_transient( 'wp_edu_readme_wp-edu-client' );

global $wpdb;

$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'wp_edu_sso_token'" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'wp_edu_student_id'" );

$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_edu_sync_status'" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_edu_revision_id'" );

$cron_hooks = [
    'wp_edu_client_sync_content_cron',
    'wp_edu_client_fetch_notices_cron'
];

foreach ( $cron_hooks as $hook ) {
    $timestamp = wp_next_scheduled( $hook );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, $hook );
    }
}