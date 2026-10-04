<?php
/**
 * Étape du pipeline : Analyse d'intention SEO.
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
 * Class StepIntent
 *
 * Étape 1 : analyse l'intention de recherche (SERP), les topics must-have,
 * les questions PAA et les mots-clés secondaires.
 */
class StepIntent implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'intent' );
        if ( ! $prompt_data ) {
            $logger->error( 'intent', 'Prompt "intent" introuvable en base.' );
            return [];
        }

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'      => $job['keyword'] ?? '',
            'profession'   => $job['profession'] ?? '',
            'type_contenu' => $job['type'] ?? 'page',
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'intent', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'intent';
    }
}
