<?php
/**
 * Référentiel des options et réglages du plugin.
 *
 * Responsabilité : lecture/écriture centralisée des wp_options du plugin.
 * Toutes les options sont stockées avec autoload=no pour la performance.
 * La clé API OpenAI est chiffrée via AES-256-CBC (clé dérivée de AUTH_KEY + AUTH_SALT).
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
     * La clé est chiffrée via AES-256-CBC avec une clé dérivée des constantes WP
     * AUTH_KEY + AUTH_SALT (définies dans wp-config.php, propres à chaque site).
     *
     * @param string $api_key Clé API en clair.
     *
     * @return bool
     */
    public static function set_api_key( string $api_key ): bool {
        if ( empty( $api_key ) ) {
            return self::update( [ 'openai_api_key' => '' ] );
        }

        return self::update( [ 'openai_api_key' => self::encrypt_value( $api_key ) ] );
    }

    /**
     * Récupère la clé API OpenAI en clair.
     *
     * @return string Clé API déchiffrée, ou chaîne vide si non configurée.
     */
    public static function get_api_key(): string {
        $stored = self::get( 'openai_api_key', '' );

        if ( empty( $stored ) ) {
            return '';
        }

        return self::decrypt_value( $stored );
    }

    /**
     * Chiffre une valeur sensible avec AES-256-CBC.
     * Préfixe « aes: » pour distinguer des anciennes valeurs base64 brutes.
     * Repli sur base64 seul si openssl_encrypt n'est pas disponible.
     *
     * @param string $plain Valeur en clair.
     *
     * @return string Valeur chiffrée (ou obfusquée en fallback).
     */
    private static function encrypt_value( string $plain ): string {
        if ( function_exists( 'openssl_encrypt' ) && defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) {
            // Clé de 32 octets dérivée des secrets WP (propres à chaque installation).
            $key = hash( 'sha256', AUTH_KEY . AUTH_SALT, true );
            $iv  = openssl_random_pseudo_bytes( 16 );
            $enc = openssl_encrypt( $plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

            if ( false !== $enc ) {
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
                return 'aes:' . base64_encode( $iv . $enc );
            }
        }

        // Fallback : base64 simple (pas de chiffrement, uniquement si openssl absent).
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return 'b64:' . base64_encode( $plain );
    }

    /**
     * Déchiffre une valeur stockée par encrypt_value().
     * Gère les trois formats : « aes: », « b64: » et l'ancien format base64 brut (V1).
     *
     * @param string $stored Valeur telle que stockée en base.
     *
     * @return string Valeur en clair, ou chaîne vide en cas d'échec.
     */
    private static function decrypt_value( string $stored ): string {
        if ( str_starts_with( $stored, 'aes:' ) ) {
            if ( ! function_exists( 'openssl_decrypt' ) || ! defined( 'AUTH_KEY' ) || ! defined( 'AUTH_SALT' ) ) {
                return '';
            }

            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            $raw = base64_decode( substr( $stored, 4 ), true );
            if ( false === $raw || strlen( $raw ) < 17 ) {
                return '';
            }

            $key = hash( 'sha256', AUTH_KEY . AUTH_SALT, true );
            $iv  = substr( $raw, 0, 16 );
            $enc = substr( $raw, 16 );
            $dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

            return ( false !== $dec ) ? $dec : '';
        }

        if ( str_starts_with( $stored, 'b64:' ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            $dec = base64_decode( substr( $stored, 4 ), true );
            return ( false !== $dec ) ? $dec : '';
        }

        // Legacy V1 : base64 brut sans préfixe.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $dec = base64_decode( $stored, true );
        return ( false !== $dec ) ? $dec : '';
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

        // Réenregistrer la clé API existante (re-chiffrée).
        if ( ! empty( $current_api_key ) ) {
            $defaults['openai_api_key'] = self::encrypt_value( $current_api_key );
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
