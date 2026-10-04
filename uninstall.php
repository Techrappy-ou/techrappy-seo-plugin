<?php
/**
 * Nettoyage complet du plugin lors de sa désinstallation depuis l'admin WordPress.
 * Ce fichier est appelé automatiquement par WordPress (WP_Uninstall_Plugin).
 *
 * @package TechrappySEO
 */

declare( strict_types=1 );

// Sécurité : ce fichier ne doit être appelé que par WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ─────────────────────────────────────────────
// Suppression des tables custom
// ─────────────────────────────────────────────

global $wpdb;

$tables = [
	$wpdb->prefix . 'techrappy_seo_jobs',
	$wpdb->prefix . 'techrappy_prompts',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// ─────────────────────────────────────────────
// Suppression des options WordPress
// ─────────────────────────────────────────────

$options_to_delete = [
	'techrappy_seo_version',
	'techrappy_seo_settings',
	'techrappy_seo_prompts',
	'techrappy_seo_db_version',
];

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Suppression des options dynamiques de mapping de templates.
// Ces options suivent le pattern : techrappy_seo_template_map_{POST_ID}
$wpdb->query( $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
	'techrappy_seo_template_map_%'
) );

// ─────────────────────────────────────────────
// Suppression des transients de cache géo
// ─────────────────────────────────────────────

$wpdb->query( $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
	'_transient_techrappy_seo_%',
	'_transient_timeout_techrappy_seo_%'
) );
