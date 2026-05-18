<?php
/**
 * Uninstall cleanup.
 *
 * @package WPOddsComparison
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpoc_settings' );

global $wpdb;
if ( isset( $wpdb ) ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_wpoc_%',
			'_transient_timeout_wpoc_%'
		)
	);
}
