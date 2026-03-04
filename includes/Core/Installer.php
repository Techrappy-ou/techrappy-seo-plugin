<?php
/**
 * Création et migration des tables de base de données custom du plugin.
 *
 * Utilise dbDelta() de WordPress pour créer ou mettre à jour
 * les tables de manière idempotente (safe à rejouer).
 *
 * @package TechrappySEO\Core
 */

declare( strict_types=1 );

namespace TechrappySEO\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Installer
 *
 * Gestion du schéma de base de données du plugin.
 */
class Installer {

    /**
     * Version du schéma de base de données.
     * Incrémenter à chaque modification de schéma pour déclencher une migration.
     */
    const DB_VERSION = '1.3.0';

    /**
     * Crée ou met à jour les tables custom du plugin via dbDelta().
     * Cette méthode est idempotente — safe à appeler plusieurs fois.
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;

        // Charset et collation par défaut de WordPress.
        $charset_collate = $wpdb->get_charset_collate();

        // Charger la fonction dbDelta() si nécessaire.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Exécuter les migrations de toutes les tables.
        self::create_jobs_table( $charset_collate );
        self::create_prompts_table( $charset_collate );

        // Stocker la version du schéma pour gestion des migrations futures.
        update_option( 'techrappy_seo_db_version', self::DB_VERSION, false );
    }

    /**
     * Crée ou met à jour la table des jobs de génération.
     *
     * @param string $charset_collate Charset + collation WordPress.
     *
     * @return void
     */
    private static function create_jobs_table( string $charset_collate ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'techrappy_seo_jobs';

        /*
         * IMPORTANT : dbDelta() est très sensible à la syntaxe SQL.
         * Règles obligatoires :
         * - 2 espaces avant chaque définition de colonne
         * - PRIMARY KEY sur sa propre ligne
         * - pas de virgule après la dernière colonne
         */
        $sql = "CREATE TABLE {$table_name} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id VARCHAR(36) NOT NULL,
  mode ENUM('single','bulk') NOT NULL DEFAULT 'single',
  type ENUM('page','post') NOT NULL DEFAULT 'page',
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
) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Crée ou met à jour la table des prompts IA.
     *
     * @param string $charset_collate Charset + collation WordPress.
     *
     * @return void
     */
    private static function create_prompts_table( string $charset_collate ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'techrappy_prompts';

        $sql = "CREATE TABLE {$table_name} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  prompt_key VARCHAR(100) NOT NULL,
  content LONGTEXT NOT NULL,
  response_format VARCHAR(50) NOT NULL DEFAULT 'json_object',
  version INT(11) UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY prompt_key (prompt_key),
  KEY is_active (is_active)
) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Vérifie si une migration de schéma est nécessaire
     * en comparant la version stockée avec la version courante.
     *
     * @return bool True si une migration est nécessaire.
     */
    public static function needs_upgrade(): bool {
        $stored_version = get_option( 'techrappy_seo_db_version', '0.0.0' );
        return version_compare( $stored_version, self::DB_VERSION, '<' );
    }

    /**
     * Vérifie que toutes les tables custom existent physiquement en base.
     * Permet de détecter une table manquante même si la version de schéma est à jour.
     *
     * @return bool True si toutes les tables sont présentes.
     */
    public static function tables_exist(): bool {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'techrappy_seo_jobs',
            $wpdb->prefix . 'techrappy_prompts',
        ];

        foreach ( $tables as $table ) {
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
                return false;
            }
        }

        return true;
    }
}
