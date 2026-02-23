<?php
/**
 * Enregistrement des menus et sous-menus WordPress Admin.
 *
 * @package TechrappySEO\Admin
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AdminMenu
 *
 * Déclare le menu principal "Techrappy SEO" et ses sous-pages dans WP Admin.
 */
class AdminMenu {

    /**
     * Slug de la page parente (menu principal).
     */
    const PARENT_SLUG = 'techrappy-seo';

    /**
     * Enregistre le menu principal et les sous-menus auprès de WordPress.
     * Appelé sur le hook admin_menu.
     *
     * @return void
     */
    public function register_menus(): void {
        // Sécurité : admin uniquement.
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            return;
        }

        // ── Menu principal ──────────────────────────────────────────
        add_menu_page(
            __( 'Techrappy SEO', 'techrappy-seo' ),    // Titre de la page.
            __( 'Techrappy SEO', 'techrappy-seo' ),    // Titre dans le menu.
            TECHRAPPY_SEO_CAPABILITY,                   // Capacité requise.
            self::PARENT_SLUG,                          // Slug de la page.
            [ $this, 'render_wizard_page' ],            // Callback de rendu.
            'dashicons-search',                         // Icône Dashicons.
            30                                          // Position dans le menu.
        );

        // ── Sous-page : New Generation (Wizard) ─────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Nouvelle génération', 'techrappy-seo' ),
            __( 'Nouvelle génération', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            self::PARENT_SLUG, // Même slug = page parent.
            [ $this, 'render_wizard_page' ]
        );

        // ── Sous-page : Template Audit ───────────────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Audit de templates', 'techrappy-seo' ),
            __( 'Audit de templates', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            'techrappy-seo-template-audit',
            [ $this, 'render_template_audit_page' ]
        );

        // ── Sous-page : Bulk Jobs ────────────────────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Jobs en masse', 'techrappy-seo' ),
            __( 'Jobs en masse', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            'techrappy-seo-bulk-jobs',
            [ $this, 'render_bulk_jobs_page' ]
        );

        // ── Sous-page : Prompt Studio ────────────────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Prompt Studio', 'techrappy-seo' ),
            __( 'Prompt Studio', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            'techrappy-seo-prompt-studio',
            [ $this, 'render_prompt_studio_page' ]
        );

        // ── Sous-page : Settings ─────────────────────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Réglages', 'techrappy-seo' ),
            __( 'Réglages', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            'techrappy-seo-settings',
            [ $this, 'render_settings_page' ]
        );

        // ── Sous-page : Logs ──────────────────────────────────────────────
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Logs', 'techrappy-seo' ),
            __( 'Logs', 'techrappy-seo' ),
            TECHRAPPY_SEO_CAPABILITY,
            'techrappy-seo-logs',
            [ $this, 'render_logs_page' ]
        );
    }

    /**
     * Rendu de la page Wizard (New Generation).
     *
     * @return void
     */
    public function render_wizard_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PageWizard();
        $page->render();
    }

    /**
     * Rendu de la page Template Audit.
     *
     * @return void
     */
    public function render_template_audit_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PageTemplateAudit();
        $page->render();
    }

    /**
     * Rendu de la page Bulk Jobs.
     *
     * @return void
     */
    public function render_bulk_jobs_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PageBulkJobs();
        $page->render();
    }

    /**
     * Rendu de la page Prompt Studio.
     *
     * @return void
     */
    public function render_prompt_studio_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PagePromptStudio();
        $page->render();
    }

    /**
     * Rendu de la page Settings.
     *
     * @return void
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PageSettings();
        $page->render();
    }

    /**
     * Rendu de la page Logs.
     *
     * @return void
     */
    public function render_logs_page(): void {
        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'techrappy-seo' ) );
        }
        $page = new Pages\PageLogs();
        $page->render();
    }
}
