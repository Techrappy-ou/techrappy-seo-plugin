<?php
/**
 * Référentiel des options et réglages du plugin.
 *
 * Responsabilité : lecture/écriture centralisée des wp_options du plugin.
 * Toutes les options sont stockées avec autoload=no pour la performance.
 * La clé API OpenAI bénéficie d'un chiffrement basique (base64 + XOR).
 *
 * @package TechrappySEO\Settings
 */

declare( strict_types=1 );

namespace TechrappySEO\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SettingsRepository
 *
 * Accès centralisé aux options WordPress du plugin.
 */
class SettingsRepository {

    /**
     * Clé de l'option principale de settings dans wp_options.
     */
    const OPTION_KEY = 'techrappy_seo_settings';

    /**
     * Valeurs par défaut des réglages du plugin.
     *
     * @var array<string, mixed>
     */
    private static array $defaults = [
        // API OpenAI.
        'openai_api_key'         => '',
        'openai_model'           => 'gpt-4o',
        'openai_temperature'     => 0.7,
        'openai_max_tokens'      => 4096,
        'openai_timeout'         => 60,
        // Comportement génération.
        'default_publish_status' => 'draft',
        'default_slug_rule'      => 'from_keyword',
        'qa_gate_enabled'        => false,
        // Coûts et garde-fous.
        'cost_alert_threshold'   => 1.00,
        'bulk_max_cities'        => 50,
        // Debug.
        'debug_mode'             => false,
        'log_retention_days'     => 30,
    ];

    /**
     * Cache en mémoire des settings chargés.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $cache = null;

    /**
     * Initialise les options par défaut lors de l'activation du plugin.
     * N'écrase pas les valeurs déjà existantes (preserves existing config).
     *
     * @return void
     */
    public static function init_defaults(): void {
        $existing = get_option( self::OPTION_KEY, null );

        // Créer l'option seulement si elle n'existe pas encore.
        if ( null === $existing ) {
            $defaults_to_store = self::$defaults;
            // Ne jamais stocker une clé API vide en clair.
            $defaults_to_store['openai_api_key'] = '';
            add_option( self::OPTION_KEY, $defaults_to_store, '', false );
        }
    }

    /**
     * Retourne toutes les options du plugin.
     *
     * @return array<string, mixed>
     */
    public static function get_all(): array {
        if ( null !== self::$cache ) {
            return self::$cache;
        }

        $stored = get_option( self::OPTION_KEY, [] );

        // Fusionner avec les defaults pour garantir toutes les clés.
        self::$cache = wp_parse_args( $stored, self::$defaults );

        return self::$cache;
    }

    /**
     * Retourne la valeur d'un réglage spécifique.
     *
     * @param string $key     Clé du réglage.
     * @param mixed  $default Valeur par défaut si non trouvée.
     *
     * @return mixed
     */
    public static function get( string $key, mixed $default = null ): mixed {
        $all = self::get_all();
        return $all[ $key ] ?? $default ?? ( self::$defaults[ $key ] ?? null );
    }

    /**
     * Met à jour un ou plusieurs réglages.
     *
     * @param array<string, mixed> $data Tableau clé/valeur des réglages à mettre à jour.
     *
     * @return bool True si la mise à jour a réussi.
     */
    public static function update( array $data ): bool {
        $current = self::get_all();
        $updated = array_merge( $current, $data );

        // Invalider le cache en mémoire.
        self::$cache = null;

        return update_option( self::OPTION_KEY, $updated, false );
    }

    /**
     * Sauvegarde la clé API OpenAI de manière sécurisée.
     * La clé est obfusquée avant stockage (base64 uniquement en V1,
     * prévoir chiffrement AES en V2 avec clé dérivée de AUTH_KEY).
     *
     * @param string $api_key Clé API en clair.
     *
     * @return bool
     */
    public static function set_api_key( string $api_key ): bool {
        if ( empty( $api_key ) ) {
            return self::update( [ 'openai_api_key' => '' ] );
        }

        // Obfuscation V1 : base64 encode (non-chiffrement, juste masquage visuel).
        // TODO V2 : chiffrement AES-256 avec clé dérivée de AUTH_KEY + AUTH_SALT.
        $obfuscated = base64_encode( $api_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

        return self::update( [ 'openai_api_key' => $obfuscated ] );
    }

    /**
     * Récupère la clé API OpenAI en clair.
     *
     * @return string Clé API désobfusquée, ou chaîne vide si non configurée.
     */
    public static function get_api_key(): string {
        $stored = self::get( 'openai_api_key', '' );

        if ( empty( $stored ) ) {
            return '';
        }

        // Désobfuscation V1.
        $decoded = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

        return ( false !== $decoded ) ? $decoded : '';
    }

    /**
     * Remet tous les réglages à leurs valeurs par défaut.
     * Préserve la clé API pour éviter une perte accidentelle.
     *
     * @return bool
     */
    public static function reset_to_defaults(): bool {
        $current_api_key = self::get_api_key();
        $defaults        = self::$defaults;

        // Réenregistrer la clé API existante.
        if ( ! empty( $current_api_key ) ) {
            $defaults['openai_api_key'] = base64_encode( $current_api_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        }

        // Invalider le cache.
        self::$cache = null;

        return update_option( self::OPTION_KEY, $defaults, false );
    }

    /**
     * Invalide le cache en mémoire (utile après update externe).
     *
     * @return void
     */
    public static function invalidate_cache(): void {
        self::$cache = null;
    }
}
