<?php
/**
 * Client HTTP pour l'API OpenAI.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

use TechrappySEO\Settings\SettingsRepository;
use TechrappySEO\Utils\CostEstimator;
use TechrappySEO\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AIClient
 *
 * Responsabilité : effectuer les appels HTTP vers l'API OpenAI
 * et retourner les réponses parsées.
 *
 * Fonctionnalités :
 * - Appels chat completions avec system + user prompt
 * - Support JSON mode (response_format: json_object)
 * - Retry automatique sur erreurs transitoires (429, 5xx)
 * - Calcul et tracking du coût par appel
 * - Logging intégré via Logger
 */
class AIClient {

    /**
     * Endpoint de l'API OpenAI Chat Completions.
     */
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * Nombre maximum de tentatives en cas d'échec transitoire.
     */
    const MAX_RETRIES = 3;

    /**
     * Délai de base entre les tentatives (secondes).
     */
    const RETRY_DELAY_BASE = 2;

    /**
     * Clé API OpenAI.
     *
     * @var string
     */
    private string $api_key;

    /**
     * Modèle OpenAI à utiliser.
     *
     * @var string
     */
    private string $model;

    /**
     * Température pour la génération (0.0 à 2.0).
     *
     * @var float
     */
    private float $temperature;

    /**
     * Nombre maximum de tokens en sortie.
     *
     * @var int
     */
    private int $max_tokens;

    /**
     * Timeout HTTP en secondes.
     *
     * @var int
     */
    private int $timeout;

    /**
     * Logger pour tracer les appels et erreurs.
     *
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * Coût total cumulé des appels de cette instance (en dollars).
     *
     * @var float
     */
    private float $total_cost = 0.0;

    /**
     * Constructeur.
     * Lit la configuration depuis SettingsRepository.
     */
    public function __construct() {
        $this->api_key     = SettingsRepository::get_api_key();
        $this->model       = (string) SettingsRepository::get( 'openai_model', 'gpt-4o' );
        $this->temperature = (float) SettingsRepository::get( 'openai_temperature', 0.7 );
        $this->max_tokens  = (int) SettingsRepository::get( 'openai_max_tokens', 4096 );
        $this->timeout     = (int) SettingsRepository::get( 'openai_timeout', 60 );
    }

    /**
     * Injecte un logger pour tracer les appels.
     *
     * @param Logger $logger Instance du logger.
     *
     * @return self
     */
    public function set_logger( Logger $logger ): self {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Effectue un appel à l'API OpenAI Chat Completions.
     *
     * @param string $prompt         Prompt utilisateur (message "user").
     * @param string $system_prompt  Prompt système (message "system"). Optionnel.
     * @param string $response_format Format de réponse : 'json_object' ou 'text'.
     *
     * @return array<string, mixed>|null Tableau contenant 'content', 'usage', 'cost', ou null en cas d'erreur fatale.
     */
    public function complete(
        string $prompt,
        string $system_prompt = '',
        string $response_format = 'json_object'
    ): ?array {
        if ( empty( $this->api_key ) ) {
            $this->log_error( 'aiclient', 'Clé API OpenAI non configurée.' );
            return null;
        }

        $messages = $this->build_messages( $prompt, $system_prompt );
        $body     = $this->build_request_body( $messages, $response_format );

        $attempt = 0;
        while ( $attempt < self::MAX_RETRIES ) {
            $attempt++;

            if ( $attempt > 1 ) {
                // Backoff exponentiel : 2s, 4s, 8s…
                $delay = self::RETRY_DELAY_BASE ** ( $attempt - 1 );
                $this->log_info( 'aiclient', "Tentative {$attempt} après {$delay}s de délai." );
                sleep( $delay );
            }

            $result = $this->do_request( $body );

            if ( null === $result ) {
                // Erreur HTTP non-récupérable (ex: DNS failure).
                return null;
            }

            if ( isset( $result['error'] ) ) {
                $code    = $result['error']['code'] ?? '';
                $message = $result['error']['message'] ?? 'Erreur inconnue';

                // Rate limit ou surcharge serveur : on retente.
                if ( in_array( $code, [ 'rate_limit_exceeded', 'server_error' ], true )
                    || ( isset( $result['http_status'] ) && $result['http_status'] >= 500 )
                ) {
                    $this->log_error( 'aiclient', "Erreur OpenAI récupérable ({$code}) : {$message}. Nouvelle tentative…" );
                    continue;
                }

                // Erreur définitive (ex: invalid_api_key, context_length_exceeded).
                $this->log_error( 'aiclient', "Erreur OpenAI fatale ({$code}) : {$message}" );
                return null;
            }

            // Succès : extraire et retourner la réponse.
            return $this->parse_response( $result, $response_format );
        }

        $this->log_error( 'aiclient', "Échec après " . self::MAX_RETRIES . " tentatives." );
        return null;
    }

    /**
     * Effectue un appel simplifié retournant uniquement le contenu texte.
     *
     * @param string $prompt        Prompt utilisateur.
     * @param string $system_prompt Prompt système.
     *
     * @return string|null Contenu texte ou null.
     */
    public function complete_text( string $prompt, string $system_prompt = '' ): ?string {
        $result = $this->complete( $prompt, $system_prompt, 'text' );
        return $result['content'] ?? null;
    }

    /**
     * Effectue un appel retournant du JSON décodé.
     *
     * @param string $prompt        Prompt utilisateur.
     * @param string $system_prompt Prompt système.
     *
     * @return array<string, mixed>|null JSON décodé ou null.
     */
    public function complete_json( string $prompt, string $system_prompt = '' ): ?array {
        $result = $this->complete( $prompt, $system_prompt, 'json_object' );
        if ( null === $result || ! isset( $result['content'] ) ) {
            return null;
        }

        $decoded = json_decode( $result['content'], true );
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            $this->log_error( 'aiclient', 'Impossible de décoder la réponse JSON : ' . json_last_error_msg() );
            return null;
        }

        return $decoded;
    }

    /**
     * Retourne le coût total cumulé de cette instance en dollars.
     *
     * @return float
     */
    public function get_total_cost(): float {
        return $this->total_cost;
    }

    /**
     * Vérifie que la clé API est configurée et valide syntaxiquement.
     *
     * @return bool
     */
    public function has_valid_api_key(): bool {
        return ! empty( $this->api_key ) && str_starts_with( $this->api_key, 'sk-' );
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Construit le tableau de messages pour l'API.
     *
     * @param string $prompt        Prompt utilisateur.
     * @param string $system_prompt Prompt système.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function build_messages( string $prompt, string $system_prompt ): array {
        $messages = [];

        if ( ! empty( $system_prompt ) ) {
            $messages[] = [
                'role'    => 'system',
                'content' => $system_prompt,
            ];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $prompt,
        ];

        return $messages;
    }

    /**
     * Construit le corps de la requête API.
     *
     * @param array<int, array{role: string, content: string}> $messages      Messages.
     * @param string                                           $response_format Format de réponse.
     *
     * @return array<string, mixed>
     */
    private function build_request_body( array $messages, string $response_format ): array {
        $body = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->max_tokens,
        ];

        // JSON mode : disponible uniquement sur les modèles compatibles.
        if ( 'json_object' === $response_format ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }

        return $body;
    }

    /**
     * Exécute la requête HTTP via wp_remote_post().
     *
     * @param array<string, mixed> $body Corps de la requête.
     *
     * @return array<string, mixed>|null Réponse brute décodée, ou null si erreur WP_Error.
     */
    private function do_request( array $body ): ?array {
        $args = [
            'method'  => 'POST',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
        ];

        $this->log_info( 'aiclient', sprintf(
            'Appel API OpenAI → model=%s, max_tokens=%d',
            $this->model,
            $this->max_tokens
        ) );

        $response = wp_remote_post( self::API_ENDPOINT, $args );

        if ( is_wp_error( $response ) ) {
            $this->log_error( 'aiclient', 'Erreur WP HTTP : ' . $response->get_error_message() );
            return null;
        }

        $http_status = (int) wp_remote_retrieve_response_code( $response );
        $raw_body    = wp_remote_retrieve_body( $response );
        $decoded     = json_decode( $raw_body, true );

        if ( ! is_array( $decoded ) ) {
            $this->log_error( 'aiclient', "Réponse non-JSON (HTTP {$http_status}) : " . substr( $raw_body, 0, 200 ) );
            return null;
        }

        // Injecter le statut HTTP dans la réponse pour faciliter la gestion d'erreurs.
        $decoded['http_status'] = $http_status;

        return $decoded;
    }

    /**
     * Parse la réponse API et extrait le contenu + usage.
     *
     * @param array<string, mixed> $response       Réponse brute décodée.
     * @param string               $response_format Format attendu.
     *
     * @return array<string, mixed> Tableau normalisé avec 'content', 'usage', 'cost'.
     */
    private function parse_response( array $response, string $response_format ): array {
        $content = $response['choices'][0]['message']['content'] ?? '';
        $usage   = $response['usage'] ?? [
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
        ];

        $input_tokens  = (int) ( $usage['prompt_tokens'] ?? 0 );
        $output_tokens = (int) ( $usage['completion_tokens'] ?? 0 );
        $cost          = CostEstimator::estimate( $this->model, $input_tokens, $output_tokens );

        // Accumuler le coût total de l'instance.
        $this->total_cost += $cost;

        $this->log_info( 'aiclient', sprintf(
            'Réponse OK → tokens: %d in + %d out = %d total | coût: $%.6f',
            $input_tokens,
            $output_tokens,
            (int) ( $usage['total_tokens'] ?? 0 ),
            $cost
        ) );

        // Alerte seuil de coût.
        if ( CostEstimator::exceeds_threshold( $this->total_cost ) ) {
            $this->log_warning( 'aiclient', sprintf(
                'Seuil de coût dépassé : $%.4f (seuil configuré atteint).',
                $this->total_cost
            ) );
        }

        return [
            'content'       => $content,
            'usage'         => $usage,
            'cost'          => $cost,
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? 'unknown',
            'model'         => $response['model'] ?? $this->model,
        ];
    }

    /**
     * Log info (no-op si pas de logger).
     *
     * @param string $step    Étape.
     * @param string $message Message.
     *
     * @return void
     */
    private function log_info( string $step, string $message ): void {
        $this->logger?->info( $step, $message );
    }

    /**
     * Log warning.
     *
     * @param string $step    Étape.
     * @param string $message Message.
     *
     * @return void
     */
    private function log_warning( string $step, string $message ): void {
        $this->logger?->warning( $step, $message );
    }

    /**
     * Log error.
     *
     * @param string $step    Étape.
     * @param string $message Message.
     *
     * @return void
     */
    private function log_error( string $step, string $message ): void {
        $this->logger?->error( $step, $message );
    }
}
