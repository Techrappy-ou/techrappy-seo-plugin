<?php
/**
 * Système de logs par job et par étape du pipeline.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Logger
 *
 * Gère les logs d'exécution du pipeline, stockés dans le job.
 */
class Logger {

    /** Niveau log : info */
    const LEVEL_INFO    = 'info';
    /** Niveau log : warning */
    const LEVEL_WARNING = 'warning';
    /** Niveau log : error */
    const LEVEL_ERROR   = 'error';
    /** Niveau log : debug */
    const LEVEL_DEBUG   = 'debug';

    /**
     * Logs en mémoire pour la session courante.
     *
     * @var array<int, array{t: int, step: string, msg: string, level: string}>
     */
    private array $logs = [];

    /**
     * Job ID associé à ce logger.
     *
     * @var string
     */
    private string $job_id;

    /**
     * Constructeur.
     *
     * @param string $job_id Identifiant du job.
     */
    public function __construct( string $job_id ) {
        $this->job_id = $job_id;
    }

    /**
     * Ajoute une entrée de log.
     *
     * @param string $step    Nom de l'étape (ex: 'intent', 'plan').
     * @param string $message Message du log.
     * @param string $level   Niveau du log (info|warning|error|debug).
     *
     * @return void
     */
    public function log( string $step, string $message, string $level = self::LEVEL_INFO ): void {
        $entry = [
            't'     => time(),
            'step'  => $step,
            'msg'   => $message,
            'level' => $level,
        ];
        $this->logs[] = $entry;

        // Si mode debug activé : écriture dans le log WordPress.
        if ( \TechrappySEO\Settings\SettingsRepository::get( 'debug_mode', false ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( sprintf(
                '[TechrappySEO][%s][%s][%s] %s',
                $this->job_id,
                strtoupper( $level ),
                $step,
                $this->redact_sensitive( $message )
            ) );
        }
    }

    /**
     * Masque les données sensibles (clés API, tokens) dans un message de log.
     *
     * @param string $message Message brut.
     *
     * @return string Message avec les données sensibles remplacées par [REDACTED].
     */
    private function redact_sensitive( string $message ): string {
        // Masquer les clés API OpenAI (format sk-… ou sk-proj-…).
        $message = preg_replace( '/sk-[A-Za-z0-9\-_]{10,}/', '[REDACTED]', $message ) ?? $message;
        // Masquer toute chaîne ressemblant à Bearer <token>.
        $message = preg_replace( '/Bearer\s+[A-Za-z0-9\-_.~+\/]{10,}/', 'Bearer [REDACTED]', $message ) ?? $message;

        return $message;
    }

    /**
     * Raccourcis de logging par niveau.
     *
     * @param string $step    Étape.
     * @param string $message Message.
     *
     * @return void
     */
    public function info( string $step, string $message ): void {
        $this->log( $step, $message, self::LEVEL_INFO );
    }

    public function warning( string $step, string $message ): void {
        $this->log( $step, $message, self::LEVEL_WARNING );
    }

    public function error( string $step, string $message ): void {
        $this->log( $step, $message, self::LEVEL_ERROR );
    }

    public function debug( string $step, string $message ): void {
        $this->log( $step, $message, self::LEVEL_DEBUG );
    }

    /**
     * Retourne tous les logs en mémoire.
     *
     * @return array<int, array{t: int, step: string, msg: string, level: string}>
     */
    public function get_logs(): array {
        return $this->logs;
    }

    /**
     * Charge des logs existants (depuis DB) dans la mémoire.
     *
     * @param array<int, array{t: int, step: string, msg: string, level: string}> $logs
     *
     * @return void
     */
    public function load( array $logs ): void {
        $this->logs = $logs;
    }

    /**
     * Retourne les logs encodés en JSON pour stockage en DB.
     *
     * @return string
     */
    public function to_json(): string {
        return wp_json_encode( $this->logs ) ?: '[]';
    }
}
