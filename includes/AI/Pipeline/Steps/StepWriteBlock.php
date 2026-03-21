<?php
/**
 * Étape du pipeline : rédaction d'un bloc de contenu (H2 + texte).
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
 * Class StepWriteBlock
 *
 * Rédige un seul bloc de contenu (H2 + 180-260 mots de HTML).
 * PipelineRunner doit injecter le bloc courant dans $job['_current_bloc'].
 * Retourne : H2, html, micro_transition.
 */
class StepWriteBlock implements StepInterface {

    /**
     * {@inheritDoc}
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $bloc = $job['_current_bloc'] ?? null;
        if ( ! $bloc ) {
            $logger->error( 'block_write', 'Aucun bloc (_current_bloc) fourni dans le job.' );
            return [];
        }

        $ai       = new AIClient( $logger );
        $pm       = new PromptManager();
        $renderer = new PromptRenderer();

        $prompt_data = $pm->get( 'block_write' );
        if ( ! $prompt_data ) {
            $logger->error( 'block_write', 'Prompt "block_write" introuvable en base.' );
            return [];
        }

        $wp_params = $job['wp_params'] ?? [];

        $vars = [
            'profession' => $wp_params['profession'] ?? 'professionnel de santé',
            'mot_cle'    => $job['keyword'] ?? '',
            'bloc_json'  => wp_json_encode( $bloc, JSON_UNESCAPED_UNICODE ),
        ];

        $prompt      = $renderer->render( $prompt_data['content'], $vars );
        $system_data = $pm->get( 'system' );
        $system      = $system_data['content'] ?? '';

        $response = $ai->generate( $prompt, $system, 'json_object' );

        if ( $response->is_error() ) {
            $logger->error( 'block_write', sprintf(
                'Erreur IA (bloc "%s") : %s',
                $bloc['H2'] ?? '?',
                $response->get_error_message()
            ) );
            return [];
        }

        return $response->get_parsed() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'block_write';
    }
}
