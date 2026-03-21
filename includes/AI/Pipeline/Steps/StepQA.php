<?php
/**
 * Étape du pipeline : contrôle qualité (QA gate).
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
 * Class StepQA
 *
 * Analyse le contenu HTML assemblé et retourne un diagnostic SEO/humain.
 * Uniquement activé si qa_gate_enabled = true dans les settings.
 * Retourne : score_seo, score_humain, problemes[], fixes_rapides[], rewrite_intro_suggeree_html.
 */
class StepQA implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $full_html = $job['_assembled_html'] ?? '';

        if ( empty( $full_html ) ) {
            $logger->info( 'qa', 'Contenu HTML absent — étape QA ignorée.' );
            return [];
        }

        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'qa' );
        if ( ! $prompt_data ) {
            $logger->error( 'qa', 'Prompt "qa" introuvable en base.' );
            return [];
        }

        $vars = [
            'mot_cle'          => $job['keyword'] ?? '',
            'full_content_html' => $full_html,
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'qa', 'Erreur IA : ' . $response->get_error_message() );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'qa';
    }
}
