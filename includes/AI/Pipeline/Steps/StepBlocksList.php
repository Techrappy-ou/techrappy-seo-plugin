<?php
/**
 * Étape du pipeline : liste des blocs répétables à rédiger.
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
 * Class StepBlocksList
 *
 * Déduit depuis le plan la liste ordonnée des blocs à rédiger.
 * Retourne : nb_blocs_repetables, blocs[].
 */
class StepBlocksList implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'blocks_list' );
        if ( ! $prompt_data ) {
            $logger->error( 'blocks_list', 'Prompt "blocks_list" introuvable en base.' );
            return [];
        }

        $steps_data = $job['steps_data'] ?? [];
        $plan       = $steps_data['plan'] ?? [];

        $vars = [
            'plan_json' => wp_json_encode( $plan, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'blocks_list', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'blocks_list';
    }
}
