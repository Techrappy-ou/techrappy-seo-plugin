<?php
/**
 * Rendu des prompts avec substitution de variables.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PromptRenderer
 *
 * Responsabilité : substituer les variables {{variable}} dans les templates de prompts.
 */
class PromptRenderer {

    /**
     * Rend un prompt en substituant les variables.
     *
     * @param string               $template  Template du prompt avec {{variables}}.
     * @param array<string, mixed> $variables Tableau de variables à substituer.
     *
     * @return string Prompt rendu.
     */
    public function render( string $template, array $variables ): string {
        foreach ( $variables as $key => $value ) {
            $template = str_replace( '{{' . $key . '}}', (string) $value, $template );
        }
        return $template;
    }
}
