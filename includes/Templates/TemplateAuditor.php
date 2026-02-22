<?php
/**
 * Audit des templates Divi pour validation des tokens.
 *
 * @package TechrappySEO\Templates
 */

declare( strict_types=1 );

namespace TechrappySEO\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TemplateAuditor
 *
 * Responsabilité : analyser un template Divi et vérifier que tous
 * les tokens requis sont présents et correctement mappés.
 */
class TemplateAuditor {

    /**
     * Audite un template et retourne les tokens trouvés et manquants.
     *
     * @param int $post_id ID du post template Divi.
     *
     * @return array{tokens_found: array<string>, tokens_missing: array<string>}
     */
    public function audit( int $post_id ): array {
        // TODO : implémenter l'audit du template.
        return [
            'tokens_found'   => [],
            'tokens_missing' => [],
        ];
    }
}
