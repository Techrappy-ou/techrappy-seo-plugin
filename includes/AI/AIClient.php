<?php
/**
 * Client HTTP pour l'API OpenAI.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AIClient
 *
 * Responsabilité : effectuer les appels HTTP vers l'API OpenAI
 * et retourner les réponses parsées.
 */
class AIClient {

    /**
     * Endpoint de l'API OpenAI Chat Completions.
     */
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * Effectue un appel à l'API OpenAI.
     *
     * @param string               $prompt         Prompt utilisateur.
     * @param string               $system_prompt  Prompt système.
     * @param string               $response_format Format de réponse ('json_object' ou 'text').
     *
     * @return array<string, mixed>|null Réponse décodée ou null en cas d'erreur.
     */
    public function complete( string $prompt, string $system_prompt = '', string $response_format = 'json_object' ): ?array {
        // TODO : implémenter l'appel à l'API OpenAI via wp_remote_post().
        return null;
    }
}
