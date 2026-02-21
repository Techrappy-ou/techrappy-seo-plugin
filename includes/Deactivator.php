<?php
/**
 * Fired during plugin deactivation.
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO;

/**
 * Class Deactivator
 *
 * Handles everything that must happen when the plugin is deactivated.
 * Note: data is NOT deleted here — that is handled by uninstall.php.
 */
class Deactivator {

	/**
	 * Run all deactivation routines.
	 *
	 * Called by register_deactivation_hook() in the main plugin file.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::unschedule_cron();
		flush_rewrite_rules();
	}

	/**
	 * Remove scheduled WP-Cron events created by this plugin.
	 *
	 * @return void
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'techrappy_seo_run_queue' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'techrappy_seo_run_queue' );
		}
	}
}
