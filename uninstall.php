<?php
/**
 * Uninstall Techrappy SEO
 *
 * Runs when the plugin is deleted (not deactivated) from the WordPress admin.
 * Removes all plugin data: options and custom database tables.
 *
 * @package TechrappySEO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Remove plugin options.
delete_option( 'techrappy_seo_version' );
delete_option( 'techrappy_seo_settings' );

// Remove custom database tables.
$table_queue = $wpdb->prefix . 'techrappy_seo_queue';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DROP TABLE IF EXISTS `{$table_queue}`" );

// Remove all post meta created by this plugin.
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta}
	 WHERE meta_key LIKE '\_techrappy\_seo\_%'"
);
