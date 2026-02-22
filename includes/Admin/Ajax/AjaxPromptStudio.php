<?php
/**
 * Handler AJAX : Prompt Studio (sauvegarde, reset, test).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\AI\AIClient;
use TechrappySEO\AI\PromptManager;
use TechrappySEO\AI\PromptRenderer;
use TechrappySEO\Prompts\DefaultPrompts;
use TechrappySEO\Prompts\PromptRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxPromptStudio
 */
class AjaxPromptStudio {

    /**
     * Sauvegarde un prompt modifié.
     *
     * @return void
     */
    public function handle_save(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $key     = sanitize_key( $_POST['prompt_key'] ?? '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $content = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );

        if ( ! $key || ! $content ) {
            wp_send_json_error( [ 'message' => __( 'Clé et contenu requis.', 'techrappy-seo' ) ], 400 );
        }

        $repo = new PromptRepository();

        if ( $repo->exists( $key ) ) {
            $saved = $repo->update( $key, $content );
        } else {
            $saved = $repo->insert( $key, $content );
        }

        if ( ! $saved ) {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la sauvegarde.', 'techrappy-seo' ) ], 500 );
        }

        wp_send_json_success( [
            'saved'      => true,
            'prompt_key' => $key,
        ] );
    }

    /**
     * Remet tous les prompts aux valeurs par défaut (écrase les modifications).
     *
     * @return void
     */
    public function handle_reset(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        $repo     = new PromptRepository();
        $defaults = DefaultPrompts::get_defaults();
        $count    = 0;

        foreach ( $defaults as $key => $data ) {
            $content = $data['content'];
            if ( $repo->exists( $key ) ) {
                $repo->update( $key, $content );
            } else {
                $repo->insert( $key, $content, $data['response_format'] ?? 'json_object' );
            }
            $count++;
        }

        wp_send_json_success( [
            'reset'          => true,
            'prompts_reset'  => $count,
        ] );
    }

    /**
     * Teste un prompt avec des variables fournies en direct via AIClient.
     *
     * @return void
     */
    public function handle_test(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $key = sanitize_key( $_POST['prompt_key'] ?? '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw_variables = $_POST['variables'] ?? [];

        if ( ! $key ) {
            wp_send_json_error( [ 'message' => __( 'Clé du prompt requise.', 'techrappy-seo' ) ], 400 );
        }

        $manager     = new PromptManager();
        $prompt_data = $manager->get( $key );

        if ( ! $prompt_data ) {
            wp_send_json_error( [ 'message' => __( 'Prompt introuvable.', 'techrappy-seo' ) ], 404 );
        }

        // Sanitizer les variables.
        $variables = [];
        if ( is_array( $raw_variables ) ) {
            foreach ( $raw_variables as $var_key => $var_value ) {
                $variables[ sanitize_key( $var_key ) ] = sanitize_textarea_field( wp_unslash( (string) $var_value ) );
            }
        }

        // Charger le system prompt.
        $system_data   = $manager->get( 'system' );
        $system_prompt = $system_data['content'] ?? '';

        // Rendre le prompt avec les variables.
        $renderer = new PromptRenderer();
        $prompt   = $renderer->render( $prompt_data['content'], $variables );

        // Appeler l'API.
        $client    = new AIClient();
        $start     = microtime( true );
        $is_json   = 'json_object' === ( $prompt_data['response_format'] ?? 'json_object' );
        $result    = $is_json
            ? $client->complete_json( $prompt, $system_prompt )
            : $client->complete( $prompt, $system_prompt );
        $duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

        if ( null === $result && ! is_array( $result ) ) {
            wp_send_json_error( [ 'message' => __( 'Aucune réponse de l\'API OpenAI.', 'techrappy-seo' ) ], 502 );
        }

        wp_send_json_success( [
            'result'      => $result,
            'duration_ms' => $duration_ms,
            'prompt_key'  => $key,
        ] );
    }
}
