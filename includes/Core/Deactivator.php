<?php
/**
 * Logique exécutée lors de la désactivation du plugin.
 *
 * Responsabilité : nettoyer les tâches planifiées et les caches temporaires.
 * Ne supprime PAS les données (réservé à uninstall.php).
 *
 * @package TechrappySEO\Core
 */

declare( strict_types=1 );

namespace TechrappySEO\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Deactivator
 *
 * Exécuté lors de la désactivation du plugin.
 */
class Deactivator {

    /**
     * Point d'entrée de la désactivation.
     *
     * @return void
     */
    public static function deactivate(): void {
        // 1. Annuler les actions programmées Action Scheduler en attente.
        self::clear_scheduled_actions();

        // 2. Nettoyer les transients de cache géographique.
        self::clear_geo_transients();

        // 3. Rechargement des règles de réécriture.
        flush_rewrite_rules();
    }

    /**
     * Supprime les actions Action Scheduler planifiées par le plugin.
     *
     * @return void
     */
    private static function clear_scheduled_actions(): void {
        // Vérifier qu'Action Scheduler est disponible avant d'agir.
        if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
            return;
        }

        $hooks_to_clear = [
            'techrappy_seo_process_single_job',
            'techrappy_seo_bulk_progress_check',
        ];

        foreach ( $hooks_to_clear as $hook ) {
            as_unschedule_all_actions( $hook );
        }
    }

    /**
     * Supprime les transients de cache géographique du plugin.
     *
     * @return void
     */
    private static function clear_geo_transients(): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_techrappy_seo_geo_%',
            '_transient_timeout_techrappy_seo_geo_%'
        ) );
    }
}
