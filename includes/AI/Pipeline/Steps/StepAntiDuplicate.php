<?php
/**
 * Étape du pipeline : Anti-duplication pour la génération en masse.
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
 * Class StepAntiDuplicate
 *
 * Étape 9 (bulk uniquement) : génère une intro unique par ville et des
 * variantes locales génériques pour éviter le contenu dupliqué.
 * Ne fabrique jamais de lieux précis.
 */
class StepAntiDuplicate implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'anti_duplicate' );
        if ( ! $prompt_data ) {
            $logger->error( 'anti_duplicate', 'Prompt "anti_duplicate" introuvable en base.' );
            return [];
        }

        $keyword_base = $job['keyword_base'] ?? $job['keyword'] ?? '';
        $city         = $job['city'] ?? '';

        if ( empty( $city ) ) {
            $logger->warning( 'anti_duplicate', 'Aucune ville renseignée pour l\'anti-duplicate.' );
            return [];
        }

        $prompt = $renderer->render( $prompt_data['content'], [
            'keyword_base' => $keyword_base,
            'city'         => $city,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'anti_duplicate', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'anti_duplicate';
    }
}
