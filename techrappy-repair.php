<?php
/**
 * Plugin Name: Techrappy DB Repair
 * Description: Crée la table manquante techrappy_seo_jobs. Supprimez ce plugin après utilisation.
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', function () {
    global $wpdb;

    $table = $wpdb->prefix . 'techrappy_seo_jobs';

    // Table déjà présente → rien à faire.
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
        return;
    }

    $charset_collate = $wpdb->get_charset_collate();

    // Pas de DEFAULT sur TEXT/LONGTEXT → compatible MySQL 5.7 et 8.0.
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id VARCHAR(36) NOT NULL,
  mode ENUM('single','bulk') NOT NULL DEFAULT 'single',
  type ENUM('page','post') NOT NULL DEFAULT 'post',
  template_post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  keyword TEXT NOT NULL,
  city VARCHAR(255) NOT NULL DEFAULT '',
  publish_status ENUM('draft','publish') NOT NULL DEFAULT 'draft',
  wp_params LONGTEXT NOT NULL,
  slug_rule ENUM('from_keyword','from_h1') NOT NULL DEFAULT 'from_keyword',
  steps_data LONGTEXT NOT NULL,
  result_data TEXT NOT NULL,
  logs LONGTEXT NOT NULL,
  status ENUM('pending','running','done','done_with_errors','failed','error') NOT NULL DEFAULT 'pending',
  parent_job_id VARCHAR(36) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY job_id (job_id),
  KEY status (status),
  KEY parent_job_id (parent_job_id),
  KEY template_post_id (template_post_id)
) {$charset_collate}";

    $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
} );
