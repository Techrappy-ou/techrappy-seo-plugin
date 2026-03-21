<?php
/**
 * Étape du pipeline : génération des métadonnées SEO.
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
 * Class StepMeta
 *
 * Génère 2 variantes de meta title (55-65 chars) et meta description (140-160 chars).
 * Retourne : meta_title_1, meta_title_2, meta_desc_1, meta_desc_2.
 */
class StepMeta implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'meta' );
        if ( ! $prompt_data ) {
            $logger->error( 'meta', 'Prompt "meta" introuvable en base.' );
            return [];
        }

        $steps_data       = $job['steps_data'] ?? [];
        $plan             = $steps_data['plan'] ?? [];
        $intent           = $steps_data['intent'] ?? [];

        $vars = [
            'mot_cle'           => $job['keyword'] ?? '',
            'H1'                => $plan['H1'] ?? '',
            'intent_principale' => $intent['intent_principale'] ?? '',
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'meta', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'meta';
    }
}
