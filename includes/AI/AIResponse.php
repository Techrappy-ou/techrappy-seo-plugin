<?php
/**
 * Objet de réponse standardisé retourné par AIClient::generate().
 *
 * Encapsule : contenu texte, données parsées (JSON), tokens, durée,
 * état succès/erreur, code et message d'erreur.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AIResponse
 *
 * Value Object — immuable après création.
 * Utilisé par AIClient::generate() comme type de retour unique.
 */
final class AIResponse {

    // ─────────────────────────────────────────
    // Propriétés
    // ─────────────────────────────────────────

    /**
     * Indique si l'appel a réussi.
     *
     * @var bool
     */
    private bool $success;

    /**
     * Contenu textuel brut retourné par l'IA.
     *
     * @var string
     */
    private string $content;

    /**
     * Données JSON parsées (null si format texte ou si erreur).
     *
     * @var array<string, mixed>|null
     */
    private ?array $parsed;

    /**
     * Nombre de tokens en entrée (prompt).
     *
     * @var int
     */
    private int $input_tokens;

    /**
     * Nombre de tokens en sortie (completion).
     *
     * @var int
     */
    private int $output_tokens;

    /**
     * Durée de l'appel API en millisecondes.
     *
     * @var int
     */
    private int $duration_ms;

    /**
     * Réponse brute complète de l'API (pour debug).
     *
     * @var array<string, mixed>
     */
    private array $raw;

    /**
     * Code d'erreur machine (vide si succès).
     *
     * @var string
     */
    private string $error_code;

    /**
     * Message d'erreur lisible (vide si succès).
     *
     * @var string
     */
    private string $error_message;

    /**
     * Code HTTP reçu (0 si non applicable).
     *
     * @var int
     */
    private int $http_code;

    // ─────────────────────────────────────────
    // Constructeur privé — factory methods
    // ─────────────────────────────────────────

    /**
     * Constructeur privé — utiliser les factory methods.
     */
    private function __construct() {}

    /**
     * Factory : crée une réponse de succès.
     *
     * @param string               $content       Texte brut retourné par l'IA.
     * @param array<string,mixed>|null $parsed    JSON parsé ou null (format texte).
     * @param int                  $input_tokens  Tokens en entrée.
     * @param int                  $output_tokens Tokens en sortie.
     * @param int                  $duration_ms   Durée de l'appel en ms.
     * @param array<string, mixed> $raw           Réponse brute complète de l'API.
     *
     * @return self
     */
    public static function success(
        string $content,
        ?array $parsed,
        int $input_tokens,
        int $output_tokens,
        int $duration_ms,
        array $raw = []
    ): self {
        $instance                = new self();
        $instance->success       = true;
        $instance->content       = $content;
        $instance->parsed        = $parsed;
        $instance->input_tokens  = $input_tokens;
        $instance->output_tokens = $output_tokens;
        $instance->duration_ms   = $duration_ms;
        $instance->raw           = $raw;
        $instance->error_code    = '';
        $instance->error_message = '';
        $instance->http_code     = 200;

        return $instance;
    }

    /**
     * Factory : crée une réponse d'erreur.
     *
     * @param string $error_code    Code d'erreur machine.
     * @param string $error_message Message d'erreur lisible.
     * @param int    $http_code     Code HTTP reçu (0 si N/A).
     *
     * @return self
     */
    public static function error(
        string $error_code,
        string $error_message,
        int $http_code = 0
    ): self {
        $instance                = new self();
        $instance->success       = false;
        $instance->content       = '';
        $instance->parsed        = null;
        $instance->input_tokens  = 0;
        $instance->output_tokens = 0;
        $instance->duration_ms   = 0;
        $instance->raw           = [];
        $instance->error_code    = $error_code;
        $instance->error_message = $error_message;
        $instance->http_code     = $http_code;

        return $instance;
    }

    // ─────────────────────────────────────────
    // Accesseurs
    // ─────────────────────────────────────────

    /** @return bool */
    public function is_success(): bool {
        return $this->success;
    }

    /** @return bool */
    public function is_error(): bool {
        return ! $this->success;
    }

    /** @return string */
    public function get_content(): string {
        return $this->content;
    }

    /**
     * Retourne les données JSON parsées.
     *
     * @return array<string, mixed>|null
     */
    public function get_parsed(): ?array {
        return $this->parsed;
    }

    /**
     * Accès à une clé spécifique du JSON parsé.
     *
     * @param string $key     Clé à lire.
     * @param mixed  $default Valeur par défaut si clé absente.
     *
     * @return mixed
     */
    public function get( string $key, mixed $default = null ): mixed {
        return $this->parsed[ $key ] ?? $default;
    }

    /** @return int */
    public function get_input_tokens(): int {
        return $this->input_tokens;
    }

    /** @return int */
    public function get_output_tokens(): int {
        return $this->output_tokens;
    }

    /** @return int */
    public function get_total_tokens(): int {
        return $this->input_tokens + $this->output_tokens;
    }

    /** @return int */
    public function get_duration_ms(): int {
        return $this->duration_ms;
    }

    /** @return string */
    public function get_error_code(): string {
        return $this->error_code;
    }

    /** @return string */
    public function get_error_message(): string {
        return $this->error_message;
    }

    /** @return int */
    public function get_http_code(): int {
        return $this->http_code;
    }

    /**
     * Retourne la réponse brute complète (pour debug/logs).
     *
     * @return array<string, mixed>
     */
    public function get_raw(): array {
        return $this->raw;
    }

    /**
     * Sérialise la réponse en tableau (pour stockage en DB dans steps_data).
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'success'       => $this->success,
            'content'       => $this->content,
            'parsed'        => $this->parsed,
            'input_tokens'  => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'duration_ms'   => $this->duration_ms,
            'error_code'    => $this->error_code,
            'error_message' => $this->error_message,
            'http_code'     => $this->http_code,
        ];
    }
}
