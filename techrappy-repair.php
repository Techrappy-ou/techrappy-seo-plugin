<?php
/**
 * Plugin Name: Techrappy DB Repair
 * Description: Crée la table manquante techrappy_seo_jobs. Supprimez ce plugin après activation.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

register_activation_hook( __FILE__, function () {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'techrappy_seo_jobs';

    $sql = "CREATE TABLE {$table_name} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id VARCHAR(36) NOT NULL,
  mode ENUM('single','bulk') NOT NULL DEFAULT 'single',
  type ENUM('page','post') NOT NULL DEFAULT 'post',
  template_post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  keyword TEXT NOT NULL,
  city VARCHAR(255) NOT NULL DEFAULT '',
  publish_status ENUM('draft','publish') NOT NULL DEFAULT 'draft',
  wp_params LONGTEXT NOT NULL DEFAULT '{}',
  slug_rule ENUM('from_keyword','from_h1') NOT NULL DEFAULT 'from_keyword',
  steps_data LONGTEXT NOT NULL DEFAULT '{}',
  result_data TEXT NOT NULL DEFAULT '{}',
  logs LONGTEXT NOT NULL DEFAULT '[]',
  status ENUM('pending','running','done','done_with_errors','failed','error') NOT NULL DEFAULT 'pending',
  parent_job_id VARCHAR(36) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY job_id (job_id),
  KEY status (status),
  KEY parent_job_id (parent_job_id),
  KEY template_post_id (template_post_id)
) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
} );
