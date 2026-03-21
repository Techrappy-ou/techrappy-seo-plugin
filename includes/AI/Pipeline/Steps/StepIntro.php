<?php
/**
 * Étape du pipeline : rédaction de l'introduction.
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
 * Class StepIntro
 *
 * Rédige l'introduction (120-180 mots) avec accroche, rassurance et promesse.
 * Retourne : intro_longue_html, intro_courte_mobile.
 */
class StepIntro implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'intro' );
        if ( ! $prompt_data ) {
            $logger->error( 'intro', 'Prompt "intro" introuvable en base.' );
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
            $logger->error( 'intro', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'intro';
    }
}
