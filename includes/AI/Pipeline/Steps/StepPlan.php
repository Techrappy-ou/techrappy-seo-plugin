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

        // ── Forcer H1 = mot-clé exact (toujours) ─────────────────────────────
        // Le titre de la page doit toujours être le mot-clé exact, jamais une
        // reformulation créative de l'IA.
        // Règle :
        //   • Sans ville  → H1 = keyword
        //   • Avec ville  → H1 = keyword + ville (sauf si ville déjà présente dans keyword)
        //   • Bulk (profession + ville) → même règle : profession + ville
        $keyword = trim( $job['keyword']  ?? '' );
        $city    = trim( $job['city']     ?? '' );

        $h1 = ( '' !== $city && false === mb_stripos( $keyword, $city ) )
            ? $keyword . ' ' . $city
            : $keyword;

        if ( '' !== $h1 ) {
            $result['H1']           = $h1;
            $result['slug_suggere'] = sanitize_title( $h1 );
            $logger->info( 'plan', 'H1 forcé (mot-clé exact) : ' . $h1 );
        }

        return $result;
    }

    public function get_name(): string {
        return 'plan';
    }
}
