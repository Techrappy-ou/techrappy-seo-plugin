<?php
/**
 * Étape du pipeline : génération du plan de contenu.
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
 * Class StepPlan
 *
 * Génère le plan SEO complet : H1, slug, sections H2/H3, FAQ seed, CTA placement.
 * Dépend de StepIntent (intent_json requis dans steps_data).
 */
class StepPlan implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'plan' );
        if ( ! $prompt_data ) {
            $logger->error( 'plan', 'Prompt "plan" introuvable en base.' );
            return [];
        }

        $wp_params  = $job['wp_params'] ?? [];
        $steps_data = $job['steps_data'] ?? [];
        $intent     = $steps_data['intent'] ?? [];

        $vars = [
            'mot_cle'     => $job['keyword'] ?? '',
            'profession'  => $wp_params['profession'] ?? 'professionnel de santé',
            'intent_json' => wp_json_encode( $intent, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'plan', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'plan';
    }
}
