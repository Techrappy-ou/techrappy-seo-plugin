<?php
/**
 * Étape du pipeline : suggestions de liens internes.
 *
 * @package TechrappySEO\AI\Pipeline\Steps
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline\Steps;

use TechrappySEO\AI\AIClient;
use TechrappySEO\AI\Pipeline\StepInterface;
use TechrappySEO\AI\PromptManager;
use TechrappySEO\AI\PromptRenderer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class StepInternalLinks
 *
 * Propose jusqu'à 5 liens internes pertinents UX+SEO.
 * Retourne un tableau de {url, anchor, placement, why}.
 * Ignoré si aucune liste de pages n'est fournie dans wp_params.
 */
class StepInternalLinks implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $wp_params = $job['wp_params'] ?? [];
        $pages     = $wp_params['pages_site'] ?? [];

        // Ignorer silencieusement si aucune page disponible.
        if ( empty( $pages ) ) {
            $logger->info( 'internal_links', 'Aucune page site fournie — étape ignorée.' );
            return [];
        }

        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'internal_links' );
        if ( ! $prompt_data ) {
            $logger->error( 'internal_links', 'Prompt "internal_links" introuvable en base.' );
            return [];
        }

        $vars = [
            'mot_cle'         => $job['keyword'] ?? '',
            'pages_site_json' => wp_json_encode( $pages, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'internal_links', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        // Le prompt retourne un tableau directement (pas un objet).
        $parsed = $response->get_parsed();
        return is_array( $parsed ) ? $parsed : [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'internal_links';
    }
}
