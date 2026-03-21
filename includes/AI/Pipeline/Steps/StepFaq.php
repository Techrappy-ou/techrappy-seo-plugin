<?php
/**
 * Étape du pipeline : génération de la FAQ.
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
 * Class StepFaq
 *
 * Génère 5 Q/R PAA-like avec balisage HTML + JSON-LD schema.
 * Retourne : faq_visible_html, faq_jsonld.
 */
class StepFaq implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'faq' );
        if ( ! $prompt_data ) {
            $logger->error( 'faq', 'Prompt "faq" introuvable en base.' );
            return [];
        }

        $steps_data = $job['steps_data'] ?? [];
        $plan       = $steps_data['plan'] ?? [];

        $vars = [
            'mot_cle'   => $job['keyword'] ?? '',
            'plan_json' => wp_json_encode( $plan, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'faq', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'faq';
    }
}
