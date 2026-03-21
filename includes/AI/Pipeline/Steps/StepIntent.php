<?php
/**
 * Étape du pipeline : analyse de l'intention SEO.
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
 * Analyse l'intention SEO du mot-clé et retourne :
 * intent_principale, must_have_topics, paa_questions, keywords_secondaires, etc.
 */
class StepIntent implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'intent' );
        if ( ! $prompt_data ) {
            $logger->error( 'intent', 'Prompt "intent" introuvable en base.' );
            return [];
        }

        $wp_params = $job['wp_params'] ?? [];

        $vars = [
            'mot_cle'      => $job['keyword'] ?? '',
            'profession'   => $wp_params['profession'] ?? 'professionnel de santé',
            'type_contenu' => $job['type'] ?? 'page_seo',
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'intent', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'intent';
    }
}
