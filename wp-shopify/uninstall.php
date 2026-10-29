<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes saved credentials, settings and cached API responses.
 *
 * @package External_Store_for_Shopify
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'wpsy_db_data' );
delete_option( 'wpsy_version' );
delete_option( 'wpsy_cache_gen' );
delete_transient( 'wpsy_last_error' );

// Cached API responses (transients named wpsy_<md5>).
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wpsy_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wpsy_' ) . '%'
	)
);
