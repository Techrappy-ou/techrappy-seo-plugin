<?php
/**
 * Validation et sanitization du formulaire de settings.
 *
 * @package TechrappySEO\Settings
 */

declare( strict_types=1 );

namespace TechrappySEO\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SettingsValidator
 *
 * Valide et sanitize les données du formulaire Settings avant stockage.
 */
class SettingsValidator {

    /**
     * Erreurs de validation collectées.
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Données sanitizées prêtes à être stockées.
     *
     * @var array<string, mixed>
     */
    private array $sanitized = [];

    /**
     * Valide et sanitize un tableau de données POST du formulaire settings.
     *
     * @param array<string, mixed> $raw_data Données brutes du POST.
     *
     * @return bool True si toutes les données sont valides.
     */
    public function validate( array $raw_data ): bool {
        $this->errors    = [];
        $this->sanitized = [];

        // Clé API OpenAI (optionnelle, mais validée si présente).
        if ( isset( $raw_data['openai_api_key'] ) && ! empty( $raw_data['openai_api_key'] ) ) {
            $api_key = sanitize_text_field( $raw_data['openai_api_key'] );
            // Format basique : commence par "sk-" (OpenAI standard).
            if ( ! str_starts_with( $api_key, 'sk-' ) ) {
                $this->errors['openai_api_key'] = __( 'La clé API OpenAI semble invalide (doit commencer par "sk-").', 'techrappy-seo' );
            } else {
                $this->sanitized['openai_api_key'] = $api_key;
            }
        }

        // Modèle OpenAI.
        $allowed_models = [ 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo' ];
        if ( isset( $raw_data['openai_model'] ) ) {
            $model = sanitize_text_field( $raw_data['openai_model'] );
            if ( ! in_array( $model, $allowed_models, true ) ) {
                $this->errors['openai_model'] = __( 'Modèle OpenAI non reconnu.', 'techrappy-seo' );
            } else {
                $this->sanitized['openai_model'] = $model;
            }
        }

        // Température (float entre 0 et 2).
        if ( isset( $raw_data['openai_temperature'] ) ) {
            $temp = (float) $raw_data['openai_temperature'];
            if ( $temp < 0.0 || $temp > 2.0 ) {
                $this->errors['openai_temperature'] = __( 'La température doit être entre 0 et 2.', 'techrappy-seo' );
            } else {
                $this->sanitized['openai_temperature'] = $temp;
            }
        }

        // Max tokens (int entre 256 et 16000).
        if ( isset( $raw_data['openai_max_tokens'] ) ) {
            $max_tokens = absint( $raw_data['openai_max_tokens'] );
            if ( $max_tokens < 256 || $max_tokens > 16000 ) {
                $this->errors['openai_max_tokens'] = __( 'Max tokens doit être entre 256 et 16000.', 'techrappy-seo' );
            } else {
                $this->sanitized['openai_max_tokens'] = $max_tokens;
            }
        }

        // Timeout (int entre 10 et 300 secondes).
        if ( isset( $raw_data['openai_timeout'] ) ) {
            $timeout = absint( $raw_data['openai_timeout'] );
            if ( $timeout < 10 || $timeout > 300 ) {
                $this->errors['openai_timeout'] = __( 'Le timeout doit être entre 10 et 300 secondes.', 'techrappy-seo' );
            } else {
                $this->sanitized['openai_timeout'] = $timeout;
            }
        }

        // Statut de publication par défaut.
        $allowed_statuses = [ 'draft', 'publish' ];
        if ( isset( $raw_data['default_publish_status'] ) ) {
            $status = sanitize_text_field( $raw_data['default_publish_status'] );
            if ( in_array( $status, $allowed_statuses, true ) ) {
                $this->sanitized['default_publish_status'] = $status;
            }
        }

        // Règle de slug par défaut.
        $allowed_slug_rules = [ 'from_keyword', 'from_h1' ];
        if ( isset( $raw_data['default_slug_rule'] ) ) {
            $slug_rule = sanitize_text_field( $raw_data['default_slug_rule'] );
            if ( in_array( $slug_rule, $allowed_slug_rules, true ) ) {
                $this->sanitized['default_slug_rule'] = $slug_rule;
            }
        }

        // Seuil d'alerte coût (float).
        if ( isset( $raw_data['cost_alert_threshold'] ) ) {
            $this->sanitized['cost_alert_threshold'] = round( (float) $raw_data['cost_alert_threshold'], 2 );
        }

        // Nombre max de villes en bulk.
        if ( isset( $raw_data['bulk_max_cities'] ) ) {
            $this->sanitized['bulk_max_cities'] = min( absint( $raw_data['bulk_max_cities'] ), 200 );
        }

        // Flags booléens.
        $bool_fields = [ 'debug_mode', 'qa_gate_enabled' ];
        foreach ( $bool_fields as $field ) {
            if ( isset( $raw_data[ $field ] ) ) {
                $this->sanitized[ $field ] = (bool) $raw_data[ $field ];
            }
        }

        return empty( $this->errors );
    }

    /**
     * Retourne les erreurs de validation.
     *
     * @return array<string, string>
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Retourne les données sanitizées et validées.
     *
     * @return array<string, mixed>
     */
    public function get_sanitized(): array {
        return $this->sanitized;
    }

    /**
     * Vérifie si une erreur existe pour une clé donnée.
     *
     * @param string $key Clé du champ.
     *
     * @return bool
     */
    public function has_error( string $key ): bool {
        return isset( $this->errors[ $key ] );
    }

    /**
     * Retourne le message d'erreur pour une clé donnée.
     *
     * @param string $key Clé du champ.
     *
     * @return string Message d'erreur ou chaîne vide.
     */
    public function get_error( string $key ): string {
        return $this->errors[ $key ] ?? '';
    }
}
