<?php
/**
 * Étape du pipeline : Rédaction de la conclusion et du CTA.
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
 * Étape 5 : génère 2 variantes de conclusion (douce/pro) + 1 CTA
 * + 3 suggestions de titres H2 de fin.
 */
class StepConclusion implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'conclusion_cta' );
        if ( ! $prompt_data ) {
            $logger->error( 'conclusion_cta', 'Prompt "conclusion_cta" introuvable en base.' );
            return [];
        }

        $plan_data = $job['steps']['plan']['data'] ?? [];
        $h1        = $plan_data['H1'] ?? '';
        $plan_json = ! empty( $plan_data ) ? wp_json_encode( $plan_data ) : '{}';

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'   => $job['keyword'] ?? '',
            'H1'        => $h1,
            'plan_json' => $plan_json,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'conclusion_cta', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'conclusion_cta';
    }
}
