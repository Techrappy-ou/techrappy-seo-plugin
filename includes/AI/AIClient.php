<?php
/**
 * Client IA centralisé — OpenAI Responses API.
 *
 * Responsabilité unique : envoyer des requêtes à l'API OpenAI (endpoint
 * /v1/responses), gérer les erreurs, timeouts, retries et logs.
 *
 * Conçu pour être extensible vers d'autres providers (Anthropic, etc.)
 * via une interface commune AIProviderInterface.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

use TechrappySEO\Settings\SettingsRepository;
use TechrappySEO\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AIClient
 *
 * Client HTTP centralisé pour tous les appels IA du plugin.
 * Tous les appels IA du pipeline DOIVENT passer par cette classe.
 *
 * Usage :
 *   $client   = new AIClient( $logger );
 *   $response = $client->generate( $prompt, $system_prompt, 'json_object' );
 *   if ( $response->is_success() ) {
 *       $data = $response->get_parsed();
 *   }
 */
class AIClient {

    // ─────────────────────────────────────────
    // Constantes
    // ─────────────────────────────────────────

    /**
     * Endpoint OpenAI Responses API.
     */
    const OPENAI_ENDPOINT = 'https://api.openai.com/v1/responses';

    /**
     * Nombre de tentatives maximum avant d'abandonner.
     */
    const MAX_RETRIES = 2;

    /**
     * Délai de base en secondes entre deux tentatives (exponentiel : 1s, 2s).
     */
    const RETRY_DELAY_BASE = 1;

    /**
     * Provider actif (extensibilité future).
     */
    const PROVIDER_OPENAI    = 'openai';
    const PROVIDER_ANTHROPIC = 'anthropic'; // Prévu V2.

    // ─────────────────────────────────────────
    // Propriétés
    // ─────────────────────────────────────────

    /**
     * Instance du logger liée au job courant.
     *
     * @var Logger|null
     */
    private ?Logger $logger;

    /**
     * Provider IA actif.
     *
     * @var string
     */
    private string $provider;

    /**
     * Modèle utilisé (ex : 'gpt-4o').
     *
     * @var string
     */
    private string $model;

    /**
     * Température de génération (0.0 à 2.0).
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
     * Mode debug activé.
     *
     * @var bool
     */
    private bool $debug;

    /**
     * Dernière réponse brute reçue (pour debug/logs).
     *
     * @var array<string, mixed>|null
     */
    private ?array $last_raw_response = null;

    /**
     * Dernière erreur rencontrée.
     *
     * @var string
     */
    private string $last_error = '';

    // ─────────────────────────────────────────
    // Constructeur
    // ─────────────────────────────────────────

    /**
     * Constructeur.
     *
     * @param Logger|null $logger   Logger du job courant (null = pas de log job).
     * @param string      $provider Provider IA ('openai' par défaut).
     */
    public function __construct( ?Logger $logger = null, string $provider = self::PROVIDER_OPENAI ) {
        $this->logger   = $logger;
        $this->provider = $provider;

        // Charger la configuration depuis SettingsRepository.
        $this->model       = (string) SettingsRepository::get( 'openai_model', 'gpt-4o' );
        $this->temperature = (float) SettingsRepository::get( 'openai_temperature', 0.7 );
        $this->max_tokens  = (int) SettingsRepository::get( 'openai_max_tokens', 4096 );
        $this->timeout     = (int) SettingsRepository::get( 'openai_timeout', 60 );
        $this->debug       = (bool) SettingsRepository::get( 'debug_mode', false );
    }

    // ─────────────────────────────────────────
    // API publique
    // ─────────────────────────────────────────

    /**
     * Point d'entrée principal — génère une réponse IA.
     *
     * @param string               $prompt          Prompt utilisateur (contenu de la requête).
     * @param string               $system_prompt   Instructions système (règles absolues, ton…).
     * @param string               $response_format Format attendu : 'json_object' ou 'text'.
     * @param array<string, mixed> $overrides       Surcharge ponctuelle des paramètres (model, temperature…).
     *
     * @return AIResponse Objet réponse standardisé.
     */
    public function generate(
        string $prompt,
        string $system_prompt = '',
        string $response_format = 'json_object',
        array $overrides = []
    ): AIResponse {
        // Vérifier la clé API avant tout appel.
        $api_key = SettingsRepository::get_api_key();
        if ( empty( $api_key ) ) {
            return $this->make_error_response(
                'missing_api_key',
                __( 'Clé API OpenAI non configurée. Rendez-vous dans Techrappy SEO → Réglages.', 'techrappy-seo' )
            );
        }

        // Appliquer les surcharges ponctuelles.
        $model       = (string) ( $overrides['model'] ?? $this->model );
        $temperature = (float) ( $overrides['temperature'] ?? $this->temperature );
        $max_tokens  = (int) ( $overrides['max_tokens'] ?? $this->max_tokens );

        // Construire le payload selon le provider.
        $payload = $this->build_payload( $prompt, $system_prompt, $response_format, $model, $temperature, $max_tokens );

        // Logger le démarrage de l'appel.
        $this->log( 'ai_client', sprintf(
            'Appel %s — modèle: %s — format: %s — prompt: %d chars',
            strtoupper( $this->provider ),
            $model,
            $response_format,
            strlen( $prompt )
        ) );

        // Exécuter avec retry.
        return $this->execute_with_retry( $payload, $api_key, $response_format );
    }

    /**
     * Surcharge du modèle pour un appel spécifique (fluent API).
     *
     * @param string $model Nom du modèle OpenAI (ex: 'gpt-4o-mini').
     *
     * @return static Retourne un clone pour chaînage immutable.
     */
    public function with_model( string $model ): static {
        $clone        = clone $this;
        $clone->model = $model;

        return $clone;
    }

    /**
     * Surcharge de la température pour un appel spécifique (fluent API).
     *
     * @param float $temperature Valeur entre 0.0 et 2.0.
     *
     * @return static
     */
    public function with_temperature( float $temperature ): static {
        $clone              = clone $this;
        $clone->temperature = max( 0.0, min( 2.0, $temperature ) );

        return $clone;
    }

    /**
     * Retourne la dernière réponse brute reçue de l'API (pour debug).
     *
     * @return array<string, mixed>|null
     */
    public function get_last_raw_response(): ?array {
        return $this->last_raw_response;
    }

    /**
     * Retourne le message de la dernière erreur rencontrée.
     *
     * @return string
     */
    public function get_last_error(): string {
        return $this->last_error;
    }

    /**
     * Vérifie que la clé API est configurée.
     *
     * @return bool
     */
    public function has_valid_api_key(): bool {
        $api_key = SettingsRepository::get_api_key();

        return ! empty( $api_key ) && str_starts_with( $api_key, 'sk-' );
    }

    // ─────────────────────────────────────────
    // Construction du payload
    // ─────────────────────────────────────────

    /**
     * Construit le payload JSON pour l'API OpenAI Responses API.
     *
     * Structure cible :
     * {
     *   "model": "gpt-4o",
     *   "input": [
     *     { "role": "system", "content": "..." },
     *     { "role": "user",   "content": "..." }
     *   ],
     *   "text": { "format": { "type": "json_object" } },
     *   "max_output_tokens": 4096,
     *   "temperature": 0.7
     * }
     *
     * @param string $prompt          Prompt utilisateur.
     * @param string $system_prompt   Prompt système.
     * @param string $response_format 'json_object' ou 'text'.
     * @param string $model           Modèle à utiliser.
     * @param float  $temperature     Température.
     * @param int    $max_tokens      Tokens max en sortie.
     *
     * @return array<string, mixed>
     */
    private function build_payload(
        string $prompt,
        string $system_prompt,
        string $response_format,
        string $model,
        float $temperature,
        int $max_tokens
    ): array {
        // Construction des messages d'entrée.
        $input = [];

        if ( ! empty( $system_prompt ) ) {
            $input[] = [
                'role'    => 'system',
                'content' => $system_prompt,
            ];
        }

        $input[] = [
            'role'    => 'user',
            'content' => $prompt,
        ];

        // Format de sortie (Responses API).
        $text_format = ( 'json_object' === $response_format )
            ? [ 'format' => [ 'type' => 'json_object' ] ]
            : [ 'format' => [ 'type' => 'text' ] ];

        return [
            'model'             => $model,
            'input'             => $input,
            'text'              => $text_format,
            'max_output_tokens' => $max_tokens,
            'temperature'       => $temperature,
        ];
    }

    // ─────────────────────────────────────────
    // Exécution HTTP avec retry
    // ─────────────────────────────────────────

    /**
     * Exécute la requête HTTP avec mécanisme de retry exponentiel.
     *
     * @param array<string, mixed> $payload         Payload JSON de la requête.
     * @param string               $api_key         Clé API en clair.
     * @param string               $response_format Format attendu.
     *
     * @return AIResponse
     */
    private function execute_with_retry( array $payload, string $api_key, string $response_format ): AIResponse {
        $attempt    = 0;
        $last_error = '';

        while ( $attempt <= self::MAX_RETRIES ) {
            // Délai exponentiel entre les tentatives (pas sur le premier essai).
            if ( $attempt > 0 ) {
                $delay = self::RETRY_DELAY_BASE * $attempt;
                $this->log( 'ai_client', sprintf(
                    'Tentative %d/%d — attente %ds avant retry.',
                    $attempt,
                    self::MAX_RETRIES,
                    $delay
                ), Logger::LEVEL_WARNING );
                sleep( $delay ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
            }

            $start_time    = microtime( true );
            $http_response = $this->do_http_request( $payload, $api_key );
            $duration_ms   = (int) round( ( microtime( true ) - $start_time ) * 1000 );

            // Erreur WordPress HTTP (réseau, timeout…).
            if ( is_wp_error( $http_response ) ) {
                $last_error = $http_response->get_error_message();
                $this->log( 'ai_client', sprintf(
                    'Erreur HTTP (tentative %d) : %s',
                    $attempt + 1,
                    $last_error
                ), Logger::LEVEL_ERROR );
                $attempt++;
                continue;
            }

            // Analyser la réponse HTTP.
            $result = $this->parse_http_response( $http_response, $response_format, $duration_ms );

            // Succès : retourner immédiatement.
            if ( $result->is_success() ) {
                $this->log( 'ai_client', sprintf(
                    'Réponse reçue en %dms — input: %d tokens — output: %d tokens.',
                    $duration_ms,
                    $result->get_input_tokens(),
                    $result->get_output_tokens()
                ) );

                return $result;
            }

            // Erreur API non-retriable (authentification, quota dépassé…).
            if ( $this->is_non_retriable_error( $result->get_error_code() ) ) {
                $this->log( 'ai_client', sprintf(
                    'Erreur non-retriable (%s) : %s',
                    $result->get_error_code(),
                    $result->get_error_message()
                ), Logger::LEVEL_ERROR );

                return $result;
            }

            // Erreur retriable.
            $last_error = $result->get_error_message();
            $this->log( 'ai_client', sprintf(
                'Erreur API retriable (tentative %d) : %s',
                $attempt + 1,
                $last_error
            ), Logger::LEVEL_WARNING );

            $attempt++;
        }

        // Toutes les tentatives ont échoué.
        $this->last_error = $last_error;

        return $this->make_error_response(
            'max_retries_exceeded',
            sprintf(
                /* translators: 1: nombre de tentatives, 2: dernière erreur */
                __( 'Échec après %d tentatives. Dernière erreur : %s', 'techrappy-seo' ),
                self::MAX_RETRIES + 1,
                $last_error
            )
        );
    }

    // ─────────────────────────────────────────
    // Requête HTTP WordPress
    // ─────────────────────────────────────────

    /**
     * Effectue la requête HTTP via wp_remote_post().
     *
     * @param array<string, mixed> $payload Payload JSON.
     * @param string               $api_key Clé API OpenAI.
     *
     * @return array<string, mixed>|\WP_Error Réponse WordPress ou WP_Error.
     */
    private function do_http_request( array $payload, string $api_key ): array|\WP_Error {
        $endpoint = $this->get_endpoint();

        $args = [
            'method'    => 'POST',
            'timeout'   => $this->timeout,
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'      => wp_json_encode( $payload ),
            // Désactiver le SSL verify en dev si debug activé.
            'sslverify' => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && $this->debug ),
        ];

        // Permettre la modification des args via filtre (extensibilité).
        $args = apply_filters( 'techrappy_seo_ai_request_args', $args, $payload, $this->provider );

        if ( $this->debug ) {
            $this->log( 'ai_client', 'Payload envoyé : ' . wp_json_encode( $payload ), Logger::LEVEL_DEBUG );
        }

        return wp_remote_post( $endpoint, $args );
    }

    /**
     * Retourne l'endpoint API selon le provider actif.
     *
     * @return string URL de l'endpoint.
     */
    private function get_endpoint(): string {
        return match ( $this->provider ) {
            self::PROVIDER_OPENAI => self::OPENAI_ENDPOINT,
            // Prévu V2 :
            // self::PROVIDER_ANTHROPIC => 'https://api.anthropic.com/v1/messages',
            default => self::OPENAI_ENDPOINT,
        };
    }

    // ─────────────────────────────────────────
    // Parsing de la réponse HTTP
    // ─────────────────────────────────────────

    /**
     * Parse et valide la réponse HTTP de l'API OpenAI.
     *
     * @param array<string, mixed> $http_response   Réponse wp_remote_post.
     * @param string               $response_format Format attendu.
     * @param int                  $duration_ms     Durée de l'appel en ms.
     *
     * @return AIResponse
     */
    private function parse_http_response( array $http_response, string $response_format, int $duration_ms ): AIResponse {
        $http_code = (int) wp_remote_retrieve_response_code( $http_response );
        $body      = wp_remote_retrieve_body( $http_response );

        // Logger la réponse brute en mode debug.
        if ( $this->debug ) {
            $this->log( 'ai_client', sprintf(
                'HTTP %d — Body (500 chars) : %s',
                $http_code,
                substr( $body, 0, 500 )
            ), Logger::LEVEL_DEBUG );
        }

        // Décoder le JSON de la réponse.
        $data = json_decode( $body, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            return $this->make_error_response(
                'invalid_json_response',
                __( "La réponse de l'API n'est pas un JSON valide.", 'techrappy-seo' ),
                $http_code
            );
        }

        // Stocker la réponse brute pour debug.
        $this->last_raw_response = $data;

        // Erreur API côté serveur.
        if ( $http_code >= 400 ) {
            return $this->parse_api_error( $data, $http_code );
        }

        // Extraire le contenu selon la structure Responses API.
        return $this->extract_content( $data, $response_format, $duration_ms );
    }

    /**
     * Parse une erreur retournée par l'API OpenAI.
     *
     * Structure d'erreur OpenAI :
     * { "error": { "message": "...", "type": "...", "code": "..." } }
     *
     * @param array<string, mixed> $data      Corps de la réponse décodé.
     * @param int                  $http_code Code HTTP reçu.
     *
     * @return AIResponse
     */
    private function parse_api_error( array $data, int $http_code ): AIResponse {
        $error_message = $data['error']['message'] ?? __( 'Erreur API inconnue.', 'techrappy-seo' );
        $error_code    = $data['error']['code'] ?? (string) $http_code;
        $error_type    = $data['error']['type'] ?? 'api_error';

        $this->last_error = $error_message;

        $this->log( 'ai_client', sprintf(
            'Erreur API OpenAI [%s/%s] HTTP %d : %s',
            $error_type,
            $error_code,
            $http_code,
            $error_message
        ), Logger::LEVEL_ERROR );

        return $this->make_error_response( $error_code, $error_message, $http_code );
    }

    /**
     * Extrait le contenu textuel depuis la réponse Responses API.
     *
     * Structure de réponse OpenAI Responses API :
     * {
     *   "output": [
     *     {
     *       "type": "message",
     *       "content": [
     *         { "type": "output_text", "text": "..." }
     *       ]
     *     }
     *   ],
     *   "usage": {
     *     "input_tokens": 150,
     *     "output_tokens": 300
     *   }
     * }
     *
     * @param array<string, mixed> $data            Réponse décodée.
     * @param string               $response_format Format attendu.
     * @param int                  $duration_ms     Durée appel.
     *
     * @return AIResponse
     */
    private function extract_content( array $data, string $response_format, int $duration_ms ): AIResponse {
        // Extraire le texte depuis output[0].content[0].text.
        $raw_text = $this->extract_text_from_output( $data );

        if ( null === $raw_text ) {
            return $this->make_error_response(
                'empty_output',
                __( "La réponse de l'API ne contient aucun texte exploitable.", 'techrappy-seo' )
            );
        }

        // Extraction des tokens de facturation.
        $input_tokens  = (int) ( $data['usage']['input_tokens'] ?? 0 );
        $output_tokens = (int) ( $data['usage']['output_tokens'] ?? 0 );

        // Si JSON attendu : valider + décoder.
        if ( 'json_object' === $response_format ) {
            return $this->parse_json_content( $raw_text, $input_tokens, $output_tokens, $duration_ms );
        }

        // Format texte : retourner tel quel.
        return AIResponse::success(
            content:       $raw_text,
            parsed:        null,
            input_tokens:  $input_tokens,
            output_tokens: $output_tokens,
            duration_ms:   $duration_ms,
            raw:           $this->last_raw_response ?? []
        );
    }

    /**
     * Extrait la chaîne de texte depuis la structure output de Responses API.
     *
     * @param array<string, mixed> $data Réponse API décodée.
     *
     * @return string|null Texte extrait ou null si structure inattendue.
     */
    private function extract_text_from_output( array $data ): ?string {
        // Responses API : output est un tableau de blocs.
        if ( ! isset( $data['output'] ) || ! is_array( $data['output'] ) ) {
            return null;
        }

        foreach ( $data['output'] as $output_block ) {
            if ( ! is_array( $output_block ) ) {
                continue;
            }

            // Type "message" contient les réponses textuelles.
            if ( ( $output_block['type'] ?? '' ) !== 'message' ) {
                continue;
            }

            if ( ! isset( $output_block['content'] ) || ! is_array( $output_block['content'] ) ) {
                continue;
            }

            foreach ( $output_block['content'] as $content_block ) {
                if ( ! is_array( $content_block ) ) {
                    continue;
                }

                // Type "output_text" contient le texte généré.
                if ( ( $content_block['type'] ?? '' ) === 'output_text' && isset( $content_block['text'] ) ) {
                    return (string) $content_block['text'];
                }
            }
        }

        return null;
    }

    /**
     * Parse et valide le contenu JSON d'une réponse.
     *
     * @param string $raw_text      Texte brut retourné par l'API.
     * @param int    $input_tokens  Tokens d'entrée.
     * @param int    $output_tokens Tokens de sortie.
     * @param int    $duration_ms   Durée.
     *
     * @return AIResponse
     */
    private function parse_json_content( string $raw_text, int $input_tokens, int $output_tokens, int $duration_ms ): AIResponse {
        // Nettoyer les éventuels blocs markdown ```json ... ```.
        $clean_text = $this->strip_markdown_code_blocks( $raw_text );
        $parsed     = json_decode( $clean_text, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $parsed ) ) {
            $this->log( 'ai_client', sprintf(
                'JSON invalide reçu : %s',
                substr( $raw_text, 0, 300 )
            ), Logger::LEVEL_ERROR );

            return $this->make_error_response(
                'invalid_json_content',
                sprintf(
                    /* translators: %s: message d'erreur JSON */
                    __( "Le contenu retourné par l'IA n'est pas un JSON valide : %s", 'techrappy-seo' ),
                    json_last_error_msg()
                )
            );
        }

        return AIResponse::success(
            content:       $clean_text,
            parsed:        $parsed,
            input_tokens:  $input_tokens,
            output_tokens: $output_tokens,
            duration_ms:   $duration_ms,
            raw:           $this->last_raw_response ?? []
        );
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    /**
     * Supprime les blocs markdown ```json ... ``` du texte.
     * Les modèles LLM ajoutent parfois des balises markdown même en mode JSON.
     *
     * @param string $text Texte brut.
     *
     * @return string Texte nettoyé.
     */
    private function strip_markdown_code_blocks( string $text ): string {
        $text = trim( $text );

        // Supprimer ```json ... ``` ou ``` ... ```.
        if ( preg_match( '/^\`\`\`(?:json)?\s*([\s\S]*?)\s*\`\`\`$/m', $text, $matches ) ) {
            return trim( $matches[1] );
        }

        return $text;
    }

    /**
     * Détermine si une erreur API est non-retriable.
     * Évite les retries inutiles sur des erreurs permanentes.
     *
     * @param string $error_code Code d'erreur OpenAI.
     *
     * @return bool True si l'erreur ne doit pas être retentée.
     */
    private function is_non_retriable_error( string $error_code ): bool {
        $non_retriable = [
            'invalid_api_key',
            'insufficient_quota',
            'invalid_request_error',
            'model_not_found',
            '401',
            '403',
            '404',
        ];

        return in_array( $error_code, $non_retriable, true );
    }

    /**
     * Crée un AIResponse d'erreur standardisé.
     *
     * @param string $code      Code d'erreur.
     * @param string $message   Message d'erreur lisible.
     * @param int    $http_code Code HTTP (optionnel).
     *
     * @return AIResponse
     */
    private function make_error_response( string $code, string $message, int $http_code = 0 ): AIResponse {
        $this->last_error = $message;

        return AIResponse::error(
            error_code:    $code,
            error_message: $message,
            http_code:     $http_code
        );
    }

    /**
     * Écrit un message de log via le Logger du job ou error_log en fallback.
     *
     * @param string $step    Étape concernée.
     * @param string $message Message.
     * @param string $level   Niveau de log.
     *
     * @return void
     */
    private function log( string $step, string $message, string $level = Logger::LEVEL_INFO ): void {
        if ( $this->logger instanceof Logger ) {
            $this->logger->log( $step, $message, $level );
            return;
        }

        // Fallback si pas de logger de job (appel standalone / test).
        if ( $this->debug ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( "[TechrappySEO][{$level}][{$step}] {$message}" );
        }
    }
}
