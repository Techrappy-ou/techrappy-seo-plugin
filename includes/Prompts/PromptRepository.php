<?php
/**
 * CRUD sur la table wp_techrappy_prompts.
 *
 * @package TechrappySEO\Prompts
 */

declare( strict_types=1 );

namespace TechrappySEO\Prompts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PromptRepository
 */
class PromptRepository {

    /**
     * Nom complet de la table.
     *
     * @return string
     */
    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'techrappy_prompts';
    }

    /**
     * Vérifie si un prompt existe déjà.
     *
     * @param string $key Clé du prompt.
     *
     * @return bool
     */
    public function exists( string $key ): bool {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare(
            'SELECT COUNT(*) FROM ' . self::table() . ' WHERE prompt_key = %s',
            $key
        ) );
        return (int) $count > 0;
    }

    /**
     * Insère un nouveau prompt.
     *
     * @param string $key             Clé unique du prompt.
     * @param string $content         Contenu du template du prompt.
     * @param string $response_format Format attendu ('json_object' ou 'text').
     *
     * @return bool
     */
    public function insert( string $key, string $content, string $response_format = 'json_object' ): bool {
        global $wpdb;

        $result = $wpdb->insert( self::table(), [
            'prompt_key'      => $key,
            'content'         => $content,
            'response_format' => $response_format,
            'version'         => 1,
            'is_active'       => 1,
            'updated_by'      => get_current_user_id(),
        ] );

        return false !== $result;
    }

    /**
     * Met à jour un prompt existant (incrémente la version).
     *
     * @param string $key     Clé du prompt.
     * @param string $content Nouveau contenu.
     *
     * @return bool
     */
    public function update( string $key, string $content ): bool {
        global $wpdb;

        // Incrémenter la version via sous-requête.
        $result = $wpdb->query( $wpdb->prepare(
            'UPDATE ' . self::table() . ' SET content = %s, version = version + 1, updated_by = %d WHERE prompt_key = %s',
            $content,
            get_current_user_id(),
            $key
        ) );

        return false !== $result;
    }

    /**
     * Met à jour un prompt uniquement s'il n'a jamais été modifié par l'utilisateur (version = 1).
     * Permet de propager les corrections de prompts par défaut sans écraser les personnalisations.
     *
     * @param string $key     Clé du prompt.
     * @param string $content Nouveau contenu par défaut.
     *
     * @return bool
     */
    public function update_if_default( string $key, string $content ): bool {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare(
            'UPDATE ' . self::table() . ' SET content = %s WHERE prompt_key = %s AND version = 1',
            $content,
            $key
        ) );

        return false !== $result;
    }

    /**
     * Récupère un prompt par sa clé.
     *
     * @param string $key Clé du prompt.
     *
     * @return array<string, mixed>|null
     */
    public function find( string $key ): ?array {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE prompt_key = %s AND is_active = 1 LIMIT 1',
            $key
        ), ARRAY_A );

        return $row ?: null;
    }

    /**
     * Récupère tous les prompts actifs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function find_all(): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT * FROM ' . self::table() . ' WHERE is_active = 1 ORDER BY prompt_key ASC',
            ARRAY_A
        );

        return $rows ?: [];
    }
}
