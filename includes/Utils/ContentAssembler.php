<?php
/**
 * Assemblage du contenu final à partir des sorties du pipeline.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ContentAssembler
 *
 * Responsabilité : assembler toutes les sorties des étapes pipeline
 * en un contenu HTML final prêt à l'injection dans post_content Divi.
 */
class ContentAssembler {

    /**
     * Assemble le contenu final depuis les données de toutes les étapes.
     *
     * @param array<string, mixed> $steps_data Données des étapes du job.
     *
     * @return array{
     *   tokens: array<string, string>,
     *   blocks: array<int, array{H2: string, html: string}>,
     *   raw_html: string
     * }
     */
    public function assemble( array $steps_data ): array {
        // TODO : implémenter l'assemblage complet.
        return [
            'tokens'   => [],
            'blocks'   => [],
            'raw_html' => '',
        ];
    }

    /**
     * Construit le tableau de tokens simples (H1, intro, meta...)
     * à partir des données des étapes.
     *
     * @param array<string, mixed> $steps_data
     *
     * @return array<string, string>
     */
    public function build_token_map( array $steps_data ): array {
        // TODO : implémenter le mapping token→valeur.
        return [];
    }
}
