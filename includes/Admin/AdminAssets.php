<?php
/**
 * Enregistrement et chargement conditionnel des assets admin (CSS + JS).
 *
 * @package TechrappySEO\Admin
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AdminAssets
 *
 * Gère l'enqueue conditionnel des CSS et JS selon la page admin courante.
 */
class AdminAssets {

    /**
     * Pages du plugin et leurs assets spécifiques.
     * Clé = slug de page, valeur = liste des handles JS à charger.
     *
     * @var array<string, string[]>
     */
    private array $page_js_map = [
        'toplevel_page_techrappy-seo'                          => [ 'techrappy-seo-wizard' ],
        'techrappy-seo_page_techrappy-seo-template-audit'      => [ 'techrappy-seo-template-audit' ],
        'techrappy-seo_page_techrappy-seo-bulk-jobs'           => [ 'techrappy-seo-bulk-jobs' ],
        'techrappy-seo_page_techrappy-seo-prompt-studio'       => [ 'techrappy-seo-prompt-studio' ],
        'techrappy-seo_page_techrappy-seo-settings'            => [],
    ];

    /**
     * Enqueue les assets CSS et JS sur les pages du plugin.
     * Appelé sur le hook admin_enqueue_scripts.
     *
     * @param string $hook_suffix Identifiant de la page admin courante.
     *
     * @return void
     */
    public function enqueue( string $hook_suffix ): void {
        // Ne charger les assets que sur les pages du plugin.
        if ( ! array_key_exists( $hook_suffix, $this->page_js_map ) ) {
            return;
        }

        // ── CSS global admin (toutes les pages du plugin) ────────────
        wp_enqueue_style(
            'techrappy-seo-admin',
            TECHRAPPY_SEO_URL . 'assets/css/admin.css',
            [],
            TECHRAPPY_SEO_VERSION
        );

        // ── JS global admin ──────────────────────────────────────────
        wp_enqueue_script(
            'techrappy-seo-admin',
            TECHRAPPY_SEO_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            TECHRAPPY_SEO_VERSION,
            true
        );

        // ── Assets spécifiques à la page courante ────────────────────
        $page_scripts = $this->page_js_map[ $hook_suffix ] ?? [];
        foreach ( $page_scripts as $handle ) {
            $this->enqueue_page_script( $handle );
        }

        // ── Données JS localisées (nonces + config AJAX) ─────────────
        wp_localize_script( 'techrappy-seo-admin', 'TechrappySEO', $this->get_localized_data() );
    }

    /**
     * Enqueue un script JS spécifique à une page.
     *
     * @param string $handle Handle du script.
     *
     * @return void
     */
    private function enqueue_page_script( string $handle ): void {
        // Mapping handle → fichier JS.
        $script_files = [
            'techrappy-seo-wizard'         => 'assets/js/wizard.js',
            'techrappy-seo-template-audit' => 'assets/js/template-audit.js',
            'techrappy-seo-bulk-jobs'      => 'assets/js/bulk-jobs.js',
            'techrappy-seo-prompt-studio'  => 'assets/js/prompt-studio.js',
        ];

        if ( ! isset( $script_files[ $handle ] ) ) {
            return;
        }

        $file_path = TECHRAPPY_SEO_PATH . $script_files[ $handle ];
        $file_url  = TECHRAPPY_SEO_URL . $script_files[ $handle ];

        // Enqueue seulement si le fichier existe.
        if ( ! file_exists( $file_path ) ) {
            return;
        }

        wp_enqueue_script( $handle, $file_url, [ 'jquery', 'techrappy-seo-admin' ], TECHRAPPY_SEO_VERSION, true );
    }

    /**
     * Prépare les données à localiser en JavaScript.
     *
     * @return array<string, mixed>
     */
    private function get_localized_data(): array {
        return [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'admin_url' => admin_url(),
            'nonces'   => [
                'wizard'        => wp_create_nonce( 'techrappy_seo_wizard' ),
                'generation'    => wp_create_nonce( 'techrappy_seo_generation' ),
                'bulk'          => wp_create_nonce( 'techrappy_seo_bulk' ),
                'audit'         => wp_create_nonce( 'techrappy_seo_audit' ),
                'prompt_studio' => wp_create_nonce( 'techrappy_seo_prompt_studio' ),
                'settings'      => wp_create_nonce( 'techrappy_seo_settings' ),
            ],
            'i18n'     => [
                'loading'      => __( 'Chargement...', 'techrappy-seo' ),
                'error'        => __( 'Une erreur est survenue.', 'techrappy-seo' ),
                'success'      => __( 'Succès !', 'techrappy-seo' ),
                'confirm_bulk' => __( 'Êtes-vous sûr de vouloir lancer la génération en masse ?', 'techrappy-seo' ),
            ],
            'debug'    => \TechrappySEO\Settings\SettingsRepository::get( 'debug_mode', false ),
        ];
    }
}
