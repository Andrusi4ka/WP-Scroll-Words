<?php
/**
 * Runs when the plugin is deleted under Plugins (not on deactivation or update): removes the texts,
 * their settings, the roles' rights and the update checker's state. Photos stay in the media library,
 * where other content may use them. The roles themselves were never changed (rights are given at run time).
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

( function () {
	$uninstall_site = function () {
		global $wpdb;

		// Every text, any status, trash included. wp_delete_post() removes its _wpsw_settings meta too.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'wpsw_text' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup.
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}

		delete_option( 'wpsw_roles' );
	};

	if ( is_multisite() ) {
		foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
			switch_to_blog( $site_id );
			$uninstall_site();
			restore_current_blog();
		}
	} else {
		$uninstall_site();
	}

	// Last update check (Plugin Update Checker).
	delete_site_option( 'external_updates-wp-scroll-words' );
} )();
