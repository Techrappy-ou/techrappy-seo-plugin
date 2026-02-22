<?php
/**
 * Gestion du versionnage des prompts.
 *
 * @package TechrappySEO\Prompts
 */

declare( strict_types=1 );

namespace TechrappySEO\Prompts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PromptVersioner
 *
 * Responsabilité : gérer l'historique des versions de prompts.
 * La version courante est stockée dans la table wp_techrappy_prompts.
 * L'historique est stocké dans wp_options (léger, adapté à un usage admin).
 */
class PromptVersioner {

    /**
     * Préfixe des clés d'historique dans wp_options.
     */
    const HISTORY_PREFIX = 'techrappy_seo_prompt_history_';

    /**
     * Nombre maximum de versions conservées par prompt.
     */
    const MAX_HISTORY = 10;

    /**
     * Sauvegarde la version actuelle d'un prompt dans l'historique
     * avant une mise à jour.
     *
     * @param string $key     Clé du prompt.
     * @param string $content Contenu actuel (avant modification).
     * @param int    $version Numéro de version actuelle.
     *
     * @return void
     */
    public function save_version( string $key, string $content, int $version ): void {
        $history = $this->get_history( $key );

        $history[] = [
            'version'    => $version,
            'content'    => $content,
            'saved_at'   => time(),
            'saved_by'   => get_current_user_id(),
        ];

        // Ne conserver que les MAX_HISTORY dernières versions.
        if ( count( $history ) > self::MAX_HISTORY ) {
            $history = array_slice( $history, - self::MAX_HISTORY );
        }

        update_option( self::HISTORY_PREFIX . $key, $history, false );
    }

    /**
     * Récupère l'historique des versions d'un prompt.
     *
     * @param string $key Clé du prompt.
     *
     * @return array<int, array{version: int, content: string, saved_at: int, saved_by: int}>
     */
    public function get_history( string $key ): array {
        $stored = get_option( self::HISTORY_PREFIX . $key, [] );
        return is_array( $stored ) ? $stored : [];
    }

    /**
     * Restaure un prompt à une version spécifique.
     *
     * @param string $key     Clé du prompt.
     * @param int    $version Numéro de version à restaurer.
     *
     * @return string|null Contenu de la version ou null si non trouvée.
     */
    public function get_version_content( string $key, int $version ): ?string {
        $history = $this->get_history( $key );

        foreach ( $history as $entry ) {
            if ( $entry['version'] === $version ) {
                return $entry['content'];
            }
        }

        return null;
    }

    /**
     * Supprime tout l'historique d'un prompt.
     *
     * @param string $key Clé du prompt.
     *
     * @return void
     */
    public function clear_history( string $key ): void {
        delete_option( self::HISTORY_PREFIX . $key );
    }
}
