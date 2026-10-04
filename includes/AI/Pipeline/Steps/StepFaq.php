<?php
/**
 * Étape du pipeline : Génération du bloc FAQ.
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
 * Étape 7 : génère 5 questions/réponses PAA-like en HTML visible
 * + le JSON-LD FAQPage pour le rich snippet Google.
 */
class StepFaq implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'faq' );
        if ( ! $prompt_data ) {
            $logger->error( 'faq', 'Prompt "faq" introuvable en base.' );
            return [];
        }

        $plan_data = $job['steps']['plan']['data'] ?? [];
        $plan_json = ! empty( $plan_data ) ? wp_json_encode( $plan_data ) : '{}';

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'   => $job['keyword'] ?? '',
            'plan_json' => $plan_json,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'faq', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'faq';
    }
}
