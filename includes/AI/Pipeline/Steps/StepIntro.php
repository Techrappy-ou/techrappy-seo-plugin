<?php
/**
 * Étape du pipeline : Rédaction de l'introduction.
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
 * Étape 3 : rédige l'introduction SEO (120-180 mots) avec le mot-clé
 * dans les premières phrases et une transition vers le 1er H2.
 */
class StepIntro implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'intro' );
        if ( ! $prompt_data ) {
            $logger->error( 'intro', 'Prompt "intro" introuvable en base.' );
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
            $logger->error( 'intro', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'intro';
    }
}
