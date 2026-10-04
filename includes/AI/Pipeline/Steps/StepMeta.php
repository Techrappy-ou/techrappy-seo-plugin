<?php
/**
 * Étape du pipeline : Génération des meta title et meta description.
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
 * Étape 6 : génère 2 variantes de meta title (55-65 car.)
 * et meta description (140-160 car.) pour Yoast SEO.
 */
class StepMeta implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'meta' );
        if ( ! $prompt_data ) {
            $logger->error( 'meta', 'Prompt "meta" introuvable en base.' );
            return [];
        }

        $plan_data        = $job['steps']['plan']['data'] ?? [];
        $intent_data      = $job['steps']['intent']['data'] ?? [];
        $h1               = $plan_data['H1'] ?? '';
        $intent_principale = $intent_data['intent_principale'] ?? '';

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'           => $job['keyword'] ?? '',
            'H1'                => $h1,
            'intent_principale' => $intent_principale,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'meta', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'meta';
    }
}
