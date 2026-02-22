<?php
/**
 * Création et gestion des jobs de génération en masse.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BulkJobManager
 */
class BulkJobManager {

    /**
     * Crée un job parent bulk et dispatche N jobs enfants.
     *
     * @param array<string, mixed>                    $bulk_params Paramètres du job bulk.
     * @param array<int, array{city: string, cp: string}> $cities  Liste des villes.
     *
     * @return string|false UUID du job parent ou false si erreur.
     */
    public function create_and_dispatch( array $bulk_params, array $cities ): string|false {
        // TODO : implémenter création job parent + enfants + dispatch.
        return false;
    }

    /**
     * Vérifie la progression d'un job bulk et met à jour le job parent.
     *
     * @param string $parent_job_id UUID du job parent.
     *
     * @return void
     */
    public function check_progress( string $parent_job_id ): void {
        // TODO : implémenter vérification progression.
    }
}
