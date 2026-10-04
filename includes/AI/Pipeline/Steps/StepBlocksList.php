<?php
/**
 * Étape du pipeline : Liste des blocs à rédiger.
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
 * Étape 2B : à partir du plan, produit la liste ordonnée des blocs à rédiger
 * et le nombre de sections répétables Divi nécessaires.
 */
class StepBlocksList implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'blocks_list' );
        if ( ! $prompt_data ) {
            $logger->error( 'blocks_list', 'Prompt "blocks_list" introuvable en base.' );
            return [];
        }

        $plan_data = $job['steps']['plan']['data'] ?? [];
        $plan_json = ! empty( $plan_data ) ? wp_json_encode( $plan_data ) : '{}';

        $prompt = $renderer->render( $prompt_data['content'], [
            'plan_json' => $plan_json,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'blocks_list', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'blocks_list';
    }
}
