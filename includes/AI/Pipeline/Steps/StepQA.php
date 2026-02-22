<?php
/**
 * Étape du pipeline : Contrôle qualité SEO avant publication.
 *
 * @package TechrappySEO\AI\Pipeline\Steps
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline\Steps;

use TechrappySEO\AI\AIClient;
use TechrappySEO\AI\Pipeline\StepInterface;
use TechrappySEO\AI\PromptManager;
use TechrappySEO\AI\PromptRenderer;
use TechrappySEO\Utils\ContentAssembler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class StepQA
 *
 * Étape 10 (optionnelle) : analyse le contenu assemblé et retourne un score
 * SEO + humain, la liste des problèmes et des suggestions de corrections.
 * Peut servir de gate avant publication.
 */
class StepQA implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'qa' );
        if ( ! $prompt_data ) {
            $logger->error( 'qa', 'Prompt "qa" introuvable en base.' );
            return [];
        }

        // Assembler le contenu HTML complet à partir des étapes précédentes.
        $full_content_html = $this->assemble_content( $job );

        if ( empty( $full_content_html ) ) {
            $logger->warning( 'qa', 'Impossible d\'assembler le contenu pour le QA.' );
            return [];
        }

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'           => $job['keyword'] ?? '',
            'full_content_html' => $full_content_html,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'qa', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        return $result;
    }

    /**
     * Assemble le contenu HTML complet à partir des données des étapes.
     *
     * @param array<string, mixed> $job Données du job.
     *
     * @return string HTML assemblé.
     */
    private function assemble_content( array $job ): string {
        $parts = [];

        // H1
        $h1 = $job['steps']['plan']['data']['H1'] ?? '';
        if ( $h1 ) {
            $parts[] = '<h1>' . esc_html( $h1 ) . '</h1>';
        }

        // Introduction
        $intro = $job['steps']['intro']['data']['intro_longue_html'] ?? '';
        if ( $intro ) {
            $parts[] = $intro;
        }

        // Blocs H2 rédigés
        $blocks = $job['steps']['blocks']['data'] ?? [];
        foreach ( $blocks as $block ) {
            $h2   = $block['H2'] ?? '';
            $html = $block['html'] ?? '';
            if ( $h2 ) {
                $parts[] = '<h2>' . esc_html( $h2 ) . '</h2>';
            }
            if ( $html ) {
                $parts[] = $html;
            }
        }

        // Conclusion
        $conclusion = $job['steps']['conclusion_cta']['data']['conclusion_douce_html'] ?? '';
        if ( $conclusion ) {
            $parts[] = $conclusion;
        }

        // FAQ
        $faq = $job['steps']['faq']['data']['faq_visible_html'] ?? '';
        if ( $faq ) {
            $parts[] = $faq;
        }

        return implode( "\n", $parts );
    }

    public function get_name(): string {
        return 'qa';
    }
}
