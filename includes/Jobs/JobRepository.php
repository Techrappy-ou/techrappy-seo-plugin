<?php
/**
 * CRUD sur la table wp_techrappy_seo_jobs.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class JobRepository
 */
class JobRepository {

    /**
     * Nom complet de la table (avec préfixe WP).
     *
     * @return string
     */
    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'techrappy_seo_jobs';
    }

    /**
     * Insère un nouveau job en base.
     *
     * @param array<string, mixed> $data Données du job.
     *
     * @return string|false Job ID (UUID) si succès, false sinon.
     */
    public static function insert( array $data ): string|false {
        global $wpdb;

        $job_id = \TechrappySEO\Utils\UuidGenerator::generate();

        $row = [
            'job_id'           => $job_id,
            'mode'             => $data['mode']             ?? 'single',
            'type'             => $data['type']             ?? 'page',
            'template_post_id' => $data['template_post_id'] ?? 0,
            'keyword'          => $data['keyword']          ?? '',
            'city'             => $data['city']             ?? '',
            'publish_status'   => $data['publish_status']   ?? 'draft',
            'wp_params'        => wp_json_encode( $data['wp_params']    ?? [] ),
            'slug_rule'        => $data['slug_rule']        ?? 'from_keyword',
            'steps_data'       => wp_json_encode( $data['steps_data']   ?? [] ),
            'result_data'      => wp_json_encode( $data['result_data']  ?? [] ),
            'logs'             => '[]',
            'status'           => 'pending',
            'parent_job_id'    => $data['parent_job_id']   ?? '',
        ];

        $result = $wpdb->insert( self::table(), $row );

        return ( false !== $result ) ? $job_id : false;
    }

    /**
     * Récupère un job par son UUID.
     *
     * @param string $job_id UUID du job.
     *
     * @return array<string, mixed>|null
     */
    public static function find( string $job_id ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::table() . ' WHERE job_id = %s LIMIT 1',
                $job_id
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return null;
        }

        return self::decode_json_fields( $row );
    }

    /**
     * Met à jour le statut d'un job.
     *
     * @param string $job_id UUID du job.
     * @param string $status Nouveau statut.
     *
     * @return bool
     */
    public static function update_status( string $job_id, string $status ): bool {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            [ 'status' => $status ],
            [ 'job_id' => $job_id ],
            [ '%s' ],
            [ '%s' ]
        );

        return false !== $result;
    }

    /**
     * Met à jour les données d'étapes et les logs d'un job.
     *
     * @param string               $job_id     UUID du job.
     * @param array<string, mixed> $steps_data Données des étapes.
     * @param array<int, mixed>    $logs       Logs du job.
     *
     * @return bool
     */
    public static function update_steps( string $job_id, array $steps_data, array $logs ): bool {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            [
                'steps_data' => wp_json_encode( $steps_data ),
                'logs'       => wp_json_encode( $logs ),
            ],
            [ 'job_id' => $job_id ],
            [ '%s', '%s' ],
            [ '%s' ]
        );

        return false !== $result;
    }

    /**
     * Met à jour le résultat final d'un job.
     *
     * @param string               $job_id      UUID du job.
     * @param array<string, mixed> $result_data Résultat (post_id, permalink, slug).
     *
     * @return bool
     */
    public static function update_result( string $job_id, array $result_data ): bool {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            [
                'result_data' => wp_json_encode( $result_data ),
                'status'      => 'done',
            ],
            [ 'job_id' => $job_id ],
            [ '%s', '%s' ],
            [ '%s' ]
        );

        return false !== $result;
    }

    /**
     * Liste les jobs avec filtres optionnels.
     *
     * @param array<string, mixed> $filters Filtres (status, mode, limit, offset).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function list( array $filters = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $filters['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $filters['status'];
        }

        if ( ! empty( $filters['mode'] ) ) {
            $where   .= ' AND mode = %s';
            $params[] = $filters['mode'];
        }

        if ( ! empty( $filters['parent_job_id'] ) ) {
            $where   .= ' AND parent_job_id = %s';
            $params[] = $filters['parent_job_id'];
        }

        // Filtre "top_level" : exclut les enfants bulk (ceux qui ont un parent).
        if ( ! empty( $filters['top_level'] ) ) {
            $where .= " AND (parent_job_id IS NULL OR parent_job_id = '')";
        }

        $limit  = absint( $filters['limit']  ?? 20 );
        $offset = absint( $filters['offset'] ?? 0 );

        $sql = "SELECT * FROM " . self::table()
            . " WHERE {$where}"
            . " ORDER BY created_at DESC"
            . " LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        return array_map( [ self::class, 'decode_json_fields' ], $rows ?: [] );
    }

    /**
     * Remet un job en état 'pending' pour relance (retry).
     * Efface le résultat, les logs et les données d'étapes.
     *
     * @param string $job_id UUID du job.
     *
     * @return bool
     */
    public static function reset_for_retry( string $job_id ): bool {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            [
                'status'      => 'pending',
                'result_data' => '[]',
                'logs'        => '[]',
                'steps_data'  => '[]',
            ],
            [ 'job_id' => $job_id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%s' ]
        );

        return false !== $result;
    }

    /**
     * Reprend un job là où il s'est arrêté.
     * Conserve les étapes 'ok', réinitialise les étapes 'running'/'error'.
     * Les logs existants sont conservés.
     *
     * @param string $job_id UUID du job.
     *
     * @return bool
     */
    public static function resume_failed_steps( string $job_id ): bool {
        global $wpdb;

        $job = self::find( $job_id );
        if ( ! $job ) {
            return false;
        }

        $steps_data = is_array( $job['steps_data'] ) ? $job['steps_data'] : [];

        // Réinitialiser uniquement les étapes non terminées.
        foreach ( $steps_data as $key => $step ) {
            if ( substr( $key, 0, 1 ) === '_' ) {
                continue; // Conserver les métadonnées bulk (_bulk_total, etc.)
            }
            if ( is_array( $step ) ) {
                $status = $step['status'] ?? '';
                if ( 'running' === $status || 'error' === $status ) {
                    unset( $steps_data[ $key ] );
                }
            }
        }

        $result = $wpdb->update(
            self::table(),
            [
                'status'     => 'pending',
                'steps_data' => wp_json_encode( $steps_data ),
                'result_data' => '[]',
            ],
            [ 'job_id' => $job_id ],
            [ '%s', '%s', '%s' ],
            [ '%s' ]
        );

        return false !== $result;
    }

    /**
     * Supprime un job et ses enfants (bulk).
     *
     * @param string $job_id UUID du job.
     *
     * @return bool
     */
    public static function delete( string $job_id ): bool {
        global $wpdb;

        // Supprimer les enfants bulk en premier.
        $wpdb->delete( self::table(), [ 'parent_job_id' => $job_id ], [ '%s' ] );

        $result = $wpdb->delete( self::table(), [ 'job_id' => $job_id ], [ '%s' ] );

        return false !== $result;
    }

    /**
     * Décode les champs JSON d'une ligne de la table.
     *
     * @param array<string, mixed> $row Ligne brute depuis wpdb.
     *
     * @return array<string, mixed>
     */
    private static function decode_json_fields( array $row ): array {
        $json_fields = [ 'wp_params', 'steps_data', 'result_data', 'logs' ];

        foreach ( $json_fields as $field ) {
            if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) ) {
                $decoded     = json_decode( $row[ $field ], true );
                $row[ $field ] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : [];
            }
        }

        return $row;
    }
}
