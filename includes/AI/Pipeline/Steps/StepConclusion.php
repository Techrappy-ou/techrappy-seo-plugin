<?php
/**
 * Étape du pipeline : rédaction de la conclusion et du CTA.
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
 * Class StepConclusion
 *
 * Génère la conclusion (2 variantes) + CTA HTML.
 * Retourne : h2_fin_suggestions, conclusion_douce_html, conclusion_pro_html, cta_html.
 */
class StepConclusion implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'conclusion_cta' );
        if ( ! $prompt_data ) {
            $logger->error( 'conclusion', 'Prompt "conclusion_cta" introuvable en base.' );
            return [];
        }

        $steps_data = $job['steps_data'] ?? [];
        $plan       = $steps_data['plan'] ?? [];

        $vars = [
            'mot_cle'   => $job['keyword'] ?? '',
            'H1'        => $plan['H1'] ?? '',
            'plan_json' => wp_json_encode( $plan, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'conclusion', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'conclusion';
    }
}
