<?php
/**
 * Étape du pipeline : variantes anti-duplication pour pages ville.
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
 * Class StepAntiDuplicate
 *
 * Génère des variantes d'intro et de phrases locales pour éviter le duplicate content
 * sur les pages ville. Uniquement activé lorsque $job['city'] est renseigné.
 * Retourne : angles[], intro_finale_html, variantes_locales[].
 */
class StepAntiDuplicate implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $city = $job['city'] ?? '';

        // Ignorer silencieusement si pas de ville (mode single sans ville).
        if ( empty( $city ) ) {
            $logger->info( 'anti_duplicate', 'Pas de ville — étape ignorée.' );
            return [];
        }

        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'anti_duplicate' );
        if ( ! $prompt_data ) {
            $logger->error( 'anti_duplicate', 'Prompt "anti_duplicate" introuvable en base.' );
            return [];
        }

        $vars = [
            'keyword_base' => $job['keyword'] ?? '',
            'city'         => $city,
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'anti_duplicate', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'anti_duplicate';
    }
}
