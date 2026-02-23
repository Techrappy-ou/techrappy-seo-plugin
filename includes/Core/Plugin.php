<?php
/**
 * Classe principale du plugin — Bootstrapper (Singleton).
 *
 * Responsabilité : instancier et lier tous les modules du plugin
 * via le Loader de hooks. Point d'entrée unique après plugins_loaded.
 *
 * @package TechrappySEO\Core
 */

declare( strict_types=1 );

namespace TechrappySEO\Core;

// Sécurité : accès direct interdit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Plugin
 *
 * Bootstrap singleton du plugin Techrappy SEO.
 */
final class Plugin {

    /**
     * Instance unique du plugin (Singleton).
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Instance du Loader de hooks WordPress.
     *
     * @var Loader
     */
    private Loader $loader;

    /**
     * Version courante du plugin.
     *
     * @var string
     */
    private string $version;

    /**
     * Constructeur privé — initialise le plugin.
     */
    private function __construct() {
        $this->version = TECHRAPPY_SEO_VERSION;
        $this->loader  = new Loader();
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();
        $this->define_scheduler_hooks();
        $this->loader->run();
    }

    /**
     * Retourne l'instance unique du plugin.
     * Crée l'instance si elle n'existe pas encore.
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Empêche le clonage de l'instance Singleton.
     */
    private function __clone() {}

    /**
     * Charge les dépendances principales du plugin.
     * Les classes sont chargées via l'autoloader PSR-4.
     *
     * @return void
     */
    private function load_dependencies(): void {
        // Les classes sont chargées automatiquement via l'autoloader
        // déclaré dans techrappy-seo.php. Aucun require_once manuel
        // n'est nécessaire ici — cette méthode peut accueillir
        // des chargements conditionnels futurs (ex: librairies tierces).
    }

    /**
     * Déclare les hooks liés à l'interface d'administration WordPress.
     *
     * @return void
     */
    private function define_admin_hooks(): void {
        // Uniquement en contexte admin.
        if ( ! is_admin() ) {
            return;
        }

        $admin_menu   = new \TechrappySEO\Admin\AdminMenu();
        $admin_assets = new \TechrappySEO\Admin\AdminAssets();

        // Enregistrement des menus admin.
        $this->loader->add_action( 'admin_menu', $admin_menu, 'register_menus' );

        // Enqueue des assets admin (CSS + JS).
        $this->loader->add_action( 'admin_enqueue_scripts', $admin_assets, 'enqueue' );
    }

    /**
     * Déclare les hooks AJAX WordPress (admin uniquement — wp_ajax_*).
     *
     * @return void
     */
    private function define_ajax_hooks(): void {
        // Wizard steps.
        $ajax_wizard = new \TechrappySEO\Admin\Ajax\AjaxWizard();
        $this->loader->add_action( 'wp_ajax_techrappy_wizard_step', $ajax_wizard, 'handle_step' );

        // Génération pipeline (step by step).
        $ajax_generation = new \TechrappySEO\Admin\Ajax\AjaxGeneration();
        $this->loader->add_action( 'wp_ajax_techrappy_run_step', $ajax_generation, 'handle_run_step' );
        $this->loader->add_action( 'wp_ajax_techrappy_run_pipeline', $ajax_generation, 'handle_run_pipeline' );

        // Bulk jobs (villes, preview, lancement).
        $ajax_bulk = new \TechrappySEO\Admin\Ajax\AjaxBulk();
        $this->loader->add_action( 'wp_ajax_techrappy_get_cities', $ajax_bulk, 'handle_get_cities' );
        $this->loader->add_action( 'wp_ajax_techrappy_preview_city', $ajax_bulk, 'handle_preview_city' );
        $this->loader->add_action( 'wp_ajax_techrappy_launch_bulk', $ajax_bulk, 'handle_launch_bulk' );
        $this->loader->add_action( 'wp_ajax_techrappy_get_job_status', $ajax_bulk, 'handle_get_job_status' );

        // Audit de templates.
        $ajax_audit = new \TechrappySEO\Admin\Ajax\AjaxTemplateAudit();
        $this->loader->add_action( 'wp_ajax_techrappy_scan_template',   $ajax_audit, 'handle_scan' );
        $this->loader->add_action( 'wp_ajax_techrappy_save_mapping',    $ajax_audit, 'handle_save_mapping' );
        $this->loader->add_action( 'wp_ajax_techrappy_export_template', $ajax_audit, 'handle_export_template' );
        $this->loader->add_action( 'wp_ajax_techrappy_import_template', $ajax_audit, 'handle_import_template' );

        // Prompt Studio.
        $ajax_prompts = new \TechrappySEO\Admin\Ajax\AjaxPromptStudio();
        $this->loader->add_action( 'wp_ajax_techrappy_save_prompt', $ajax_prompts, 'handle_save' );
        $this->loader->add_action( 'wp_ajax_techrappy_reset_prompts', $ajax_prompts, 'handle_reset' );
        $this->loader->add_action( 'wp_ajax_techrappy_test_prompt', $ajax_prompts, 'handle_test' );

        // Settings.
        $ajax_settings = new \TechrappySEO\Admin\Ajax\AjaxSettings();
        $this->loader->add_action( 'wp_ajax_techrappy_save_settings', $ajax_settings, 'handle_save' );

        // Logs.
        $ajax_logs = new \TechrappySEO\Admin\Ajax\AjaxLogs();
        $this->loader->add_action( 'wp_ajax_techrappy_get_job_logs', $ajax_logs, 'handle_get_job_logs' );
        $this->loader->add_action( 'wp_ajax_techrappy_clear_logs',   $ajax_logs, 'handle_clear_logs' );
    }

    /**
     * Déclare les hooks liés à Action Scheduler (queue bulk).
     *
     * @return void
     */
    private function define_scheduler_hooks(): void {
        $scheduler = new \TechrappySEO\Jobs\QueueScheduler();

        // Hook exécuté par Action Scheduler pour chaque job single.
        $this->loader->add_action( 'techrappy_seo_process_single_job', $scheduler, 'process_single_job' );

        // Hook de vérification progression job bulk.
        $this->loader->add_action( 'techrappy_seo_bulk_progress_check', $scheduler, 'check_bulk_progress' );
    }

    /**
     * Retourne la version du plugin.
     *
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }

    /**
     * Retourne l'instance du Loader.
     *
     * @return Loader
     */
    public function get_loader(): Loader {
        return $this->loader;
    }
}
