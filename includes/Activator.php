<?php
/**
 * Fired during plugin activation.
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO;

/**
 * Class Activator
 *
 * Handles everything that must happen when the plugin is activated:
 * - Create custom database tables
 * - Set default option values
 * - Schedule WP-Cron events
 */
class Activator {

	/**
	 * Run all activation routines.
	 *
	 * Called by register_activation_hook() in the main plugin file.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		self::schedule_cron();

		// Store the activated version so we can handle future upgrades.
		update_option( 'techrappy_seo_version', TECHRAPPY_SEO_VERSION );

		// Flush rewrite rules in case we register custom post types later.
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables needed by the plugin.
	 *
	 * Uses dbDelta() so the function is safe to call on updates too.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Queue table — stores async analysis jobs.
		$table_queue = $wpdb->prefix . 'techrappy_seo_queue';

		$sql_queue = "CREATE TABLE IF NOT EXISTS {$table_queue} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id      BIGINT(20) UNSIGNED NOT NULL,
			action       VARCHAR(100)        NOT NULL,
			status       VARCHAR(20)         NOT NULL DEFAULT 'pending',
			priority     TINYINT(2)          NOT NULL DEFAULT 10,
			payload      LONGTEXT,
			attempts     TINYINT(3)          NOT NULL DEFAULT 0,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME,
			PRIMARY KEY  (id),
			KEY post_id  (post_id),
			KEY status   (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_queue );
	}

	/**
	 * Persist default plugin settings if they do not already exist.
	 *
	 * Using add_option() is intentional: it won't overwrite existing values.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		$defaults = array(
			'ai_provider'       => '',
			'ai_api_key'        => '',
			'ai_model'          => '',
			'seo_auto_analyze'  => false,
			'yoast_integration' => false,
			'divi_integration'  => false,
			'queue_batch_size'  => 10,
		);

		add_option( 'techrappy_seo_settings', $defaults );
	}

	/**
	 * Register the WP-Cron event for the async queue worker.
	 *
	 * @return void
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'techrappy_seo_run_queue' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'techrappy_seo_run_queue' );
		}
	}
}
