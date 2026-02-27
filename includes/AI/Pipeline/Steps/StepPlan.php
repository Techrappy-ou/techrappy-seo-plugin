<?php
/**
 * Étape du pipeline : Génération du plan SEO.
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
 * Class StepPlan
 *
 * Étape 2 : génère le plan H1/H2/H3 complet basé sur l'intention analysée.
 * Résultat affiché en UI éditable avant de continuer.
 */
class StepPlan implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'plan' );
        if ( ! $prompt_data ) {
            $logger->error( 'plan', 'Prompt "plan" introuvable en base.' );
            return [];
        }

        // Récupérer intent_json depuis l'étape précédente.
        $intent_data = $job['steps']['intent']['data'] ?? [];
        $intent_json = ! empty( $intent_data ) ? wp_json_encode( $intent_data ) : '{}';

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'    => $job['keyword'] ?? '',
            'profession' => $job['profession'] ?? '',
            'intent_json' => $intent_json,
            'city'       => $job['city'] ?? '',
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'plan', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        // ── Forcer H1 = mot-clé + ville pour les pages locales ────────────────
        // L'IA génère souvent un H1 créatif ("Trouver le meilleur...") alors que
        // pour le SEO local on veut EXACTEMENT "keyword ville".
        if ( ! empty( $job['city'] ) ) {
            $h1_local              = trim( ( $job['keyword'] ?? '' ) . ' ' . $job['city'] );
            $result['H1']          = $h1_local;
            $result['slug_suggere'] = sanitize_title( $h1_local );
            $logger->info( 'plan', 'H1 local forcé : ' . $h1_local );
        }

        return $result;
    }

    public function get_name(): string {
        return 'plan';
    }
}
