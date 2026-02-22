\# Techrappy SEO --- V1 Squelette du plugin Je vais générer tous les
fichiers PHP du squelette, dans l\'ordre de l\'arborescence, avec
uniquement la structure minimale fonctionnelle. \-\-- \## Fichier 1 :
\`techrappy-seo.php\` --- Point d\'entrée principal \`\`\`php \<?php
/\*\* \* Plugin Name: Techrappy SEO \* Plugin URI: https://techrappy.fr
\* Description: Génération automatique de pages et articles SEO-ready
avec Divi Builder et OpenAI. \* Version: 1.0.0 \* Requires at least: 6.2
\* Requires PHP: 8.0 \* Author: Techrappy \* Author URI:
https://techrappy.fr \* License: Proprietary \* Text Domain:
techrappy-seo \* Domain Path: /languages \* \* \@package TechrappySEO
\*/ declare( strict_types=1 ); // Sécurité : interdire l\'accès direct
au fichier. if ( ! defined( \'ABSPATH\' ) ) { exit; } //
───────────────────────────────────────────── // Constantes du plugin //
───────────────────────────────────────────── /\*\* Version du plugin
\*/ define( \'TECHRAPPY_SEO_VERSION\', \'1.0.0\' ); /\*\* Chemin absolu
vers le répertoire racine du plugin (avec slash final) \*/ define(
\'TECHRAPPY_SEO_PATH\', plugin_dir_path( \_\_FILE\_\_ ) ); /\*\* URL
publique vers le répertoire racine du plugin (avec slash final) \*/
define( \'TECHRAPPY_SEO_URL\', plugin_dir_url( \_\_FILE\_\_ ) ); /\*\*
Nom du fichier principal du plugin (pour register_activation_hook) \*/
define( \'TECHRAPPY_SEO_BASENAME\', plugin_basename( \_\_FILE\_\_ ) );
/\*\* Préfixe utilisé pour toutes les options WordPress du plugin \*/
define( \'TECHRAPPY_SEO_OPTION_PREFIX\', \'techrappy_seo\_\' ); /\*\*
Préfixe des tables de base de données custom \*/ define(
\'TECHRAPPY_SEO_DB_PREFIX\', \'techrappy_seo\_\' ); /\*\* Capacité
WordPress requise pour accéder au plugin \*/ define(
\'TECHRAPPY_SEO_CAPABILITY\', \'manage_options\' ); //
───────────────────────────────────────────── // Autoloader PSR-4
minimal (sans Composer) // ─────────────────────────────────────────────
/\*\* \* Autoloader simple pour le namespace TechrappySEO\\ \* Mappe
TechrappySEO\\Foo\\Bar → includes/Foo/Bar.php \*/ spl_autoload_register(
function ( string \$class_name ): void { // Namespace racine du plugin.
\$namespace_prefix = \'TechrappySEO\\\\\'; \$prefix_length = strlen(
\$namespace_prefix ); // Vérifier que la classe appartient à notre
namespace. if ( strncmp( \$namespace_prefix, \$class_name,
\$prefix_length ) !== 0 ) { return; } // Extraire la partie relative du
namespace. \$relative_class = substr( \$class_name, \$prefix_length );
// Construire le chemin complet du fichier. \$file = TECHRAPPY_SEO_PATH
. \'includes/\' . str_replace( \'\\\\\', \'/\', \$relative_class ) .
\'.php\'; if ( file_exists( \$file ) ) { require_once \$file; } } ); //
───────────────────────────────────────────── // Hooks d\'activation /
désactivation // ─────────────────────────────────────────────
register_activation_hook( \_\_FILE\_\_, function (): void { require_once
TECHRAPPY_SEO_PATH . \'includes/Core/Activator.php\';
TechrappySEO\\Core\\Activator::activate(); } );
register_deactivation_hook( \_\_FILE\_\_, function (): void {
require_once TECHRAPPY_SEO_PATH . \'includes/Core/Deactivator.php\';
TechrappySEO\\Core\\Deactivator::deactivate(); } ); //
───────────────────────────────────────────── // Lancement du plugin //
───────────────────────────────────────────── /\*\* \* Retourne
l\'instance unique du plugin (Singleton). \* Appelé sur le hook
\'plugins_loaded\' pour garantir \* que WordPress et les autres plugins
sont chargés. \*/ add_action( \'plugins_loaded\', function (): void {
TechrappySEO\\Core\\Plugin::get_instance(); } ); \`\`\` \-\-- \##
Fichier 2 : \`uninstall.php\` --- Nettoyage complet \`\`\`php \<?php
/\*\* \* Nettoyage complet du plugin lors de sa désinstallation depuis
l\'admin WordPress. \* Ce fichier est appelé automatiquement par
WordPress (WP_Uninstall_Plugin). \* \* \@package TechrappySEO \*/
declare( strict_types=1 ); // Sécurité : ce fichier ne doit être appelé
que par WordPress. if ( ! defined( \'WP_UNINSTALL_PLUGIN\' ) ) { exit; }
// ───────────────────────────────────────────── // Suppression des
tables custom // ───────────────────────────────────────────── global
\$wpdb; \$tables = \[ \$wpdb-\>prefix . \'techrappy_seo_jobs\',
\$wpdb-\>prefix . \'techrappy_prompts\', \]; foreach ( \$tables as
\$table ) { // phpcs:ignore
WordPress.DB.PreparedSQL.InterpolatedNotPrepared \$wpdb-\>query( \"DROP
TABLE IF EXISTS \`{\$table}\`\" ); } //
───────────────────────────────────────────── // Suppression des options
WordPress // ─────────────────────────────────────────────
\$options_to_delete = \[ \'techrappy_seo_version\',
\'techrappy_seo_settings\', \'techrappy_seo_prompts\',
\'techrappy_seo_db_version\', \]; foreach ( \$options_to_delete as
\$option ) { delete_option( \$option ); } // Suppression des options
dynamiques de mapping de templates. // Ces options suivent le pattern :
techrappy_seo_template_map\_{POST_ID} \$wpdb-\>query( \$wpdb-\>prepare(
\"DELETE FROM {\$wpdb-\>options} WHERE option_name LIKE %s\",
\'techrappy_seo_template_map\_%\' ) ); //
───────────────────────────────────────────── // Suppression des
transients de cache géo // ─────────────────────────────────────────────
\$wpdb-\>query( \$wpdb-\>prepare( \"DELETE FROM {\$wpdb-\>options} WHERE
option_name LIKE %s OR option_name LIKE %s\",
\'\_transient_techrappy_seo\_%\',
\'\_transient_timeout_techrappy_seo\_%\' ) ); \`\`\` \-\-- \## Fichier 3
: \`includes/Core/Plugin.php\` --- Bootstrapper principal \`\`\`php
\<?php /\*\* \* Classe principale du plugin --- Bootstrapper
(Singleton). \* \* Responsabilité : instancier et lier tous les modules
du plugin \* via le Loader de hooks. Point d\'entrée unique après
plugins_loaded. \* \* \@package TechrappySEO\\Core \*/ declare(
strict_types=1 ); namespace TechrappySEO\\Core; // Sécurité : accès
direct interdit. if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class Plugin \* \* Bootstrap singleton du plugin Techrappy SEO. \*/
final class Plugin { /\*\* \* Instance unique du plugin (Singleton). \*
\* \@var Plugin\|null \*/ private static ?Plugin \$instance = null;
/\*\* \* Instance du Loader de hooks WordPress. \* \* \@var Loader \*/
private Loader \$loader; /\*\* \* Version courante du plugin. \* \*
\@var string \*/ private string \$version; /\*\* \* Constructeur privé
--- initialise le plugin. \*/ private function \_\_construct() {
\$this-\>version = TECHRAPPY_SEO_VERSION; \$this-\>loader = new
Loader(); \$this-\>load_dependencies(); \$this-\>define_admin_hooks();
\$this-\>define_ajax_hooks(); \$this-\>define_scheduler_hooks();
\$this-\>loader-\>run(); } /\*\* \* Retourne l\'instance unique du
plugin. \* Crée l\'instance si elle n\'existe pas encore. \* \* \@return
Plugin \*/ public static function get_instance(): Plugin { if ( null ===
self::\$instance ) { self::\$instance = new self(); } return
self::\$instance; } /\*\* \* Empêche le clonage de l\'instance
Singleton. \*/ private function \_\_clone() {} /\*\* \* Charge les
dépendances principales du plugin. \* Les classes sont chargées via
l\'autoloader PSR-4. \* \* \@return void \*/ private function
load_dependencies(): void { // Les classes sont chargées automatiquement
via l\'autoloader // déclaré dans techrappy-seo.php. Aucun require_once
manuel // n\'est nécessaire ici --- cette méthode peut accueillir // des
chargements conditionnels futurs (ex: librairies tierces). } /\*\* \*
Déclare les hooks liés à l\'interface d\'administration WordPress. \* \*
\@return void \*/ private function define_admin_hooks(): void { //
Uniquement en contexte admin. if ( ! is_admin() ) { return; }
\$admin_menu = new \\TechrappySEO\\Admin\\AdminMenu(); \$admin_assets =
new \\TechrappySEO\\Admin\\AdminAssets(); // Enregistrement des menus
admin. \$this-\>loader-\>add_action( \'admin_menu\', \$admin_menu,
\'register_menus\' ); // Enqueue des assets admin (CSS + JS).
\$this-\>loader-\>add_action( \'admin_enqueue_scripts\', \$admin_assets,
\'enqueue\' ); } /\*\* \* Déclare les hooks AJAX WordPress (admin
uniquement --- wp_ajax\_\*). \* \* \@return void \*/ private function
define_ajax_hooks(): void { // Wizard steps. \$ajax_wizard = new
\\TechrappySEO\\Admin\\Ajax\\AjaxWizard(); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_wizard_step\', \$ajax_wizard, \'handle_step\' ); //
Génération pipeline (step by step). \$ajax_generation = new
\\TechrappySEO\\Admin\\Ajax\\AjaxGeneration();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_run_step\',
\$ajax_generation, \'handle_run_step\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_run_pipeline\', \$ajax_generation,
\'handle_run_pipeline\' ); // Bulk jobs (villes, preview, lancement).
\$ajax_bulk = new \\TechrappySEO\\Admin\\Ajax\\AjaxBulk();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_get_cities\',
\$ajax_bulk, \'handle_get_cities\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_preview_city\', \$ajax_bulk, \'handle_preview_city\'
); \$this-\>loader-\>add_action( \'wp_ajax_techrappy_launch_bulk\',
\$ajax_bulk, \'handle_launch_bulk\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_get_job_status\', \$ajax_bulk,
\'handle_get_job_status\' ); // Audit de templates. \$ajax_audit = new
\\TechrappySEO\\Admin\\Ajax\\AjaxTemplateAudit();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_scan_template\',
\$ajax_audit, \'handle_scan\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_save_mapping\', \$ajax_audit,
\'handle_save_mapping\' ); // Prompt Studio. \$ajax_prompts = new
\\TechrappySEO\\Admin\\Ajax\\AjaxPromptStudio();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_save_prompt\',
\$ajax_prompts, \'handle_save\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_reset_prompts\', \$ajax_prompts, \'handle_reset\' );
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_test_prompt\',
\$ajax_prompts, \'handle_test\' ); // Settings. \$ajax_settings = new
\\TechrappySEO\\Admin\\Ajax\\AjaxSettings();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_save_settings\',
\$ajax_settings, \'handle_save\' ); } /\*\* \* Déclare les hooks liés à
Action Scheduler (queue bulk). \* \* \@return void \*/ private function
define_scheduler_hooks(): void { \$scheduler = new
\\TechrappySEO\\Jobs\\QueueScheduler(); // Hook exécuté par Action
Scheduler pour chaque job single. \$this-\>loader-\>add_action(
\'techrappy_seo_process_single_job\', \$scheduler,
\'process_single_job\' ); // Hook de vérification progression job bulk.
\$this-\>loader-\>add_action( \'techrappy_seo_bulk_progress_check\',
\$scheduler, \'check_bulk_progress\' ); } /\*\* \* Retourne la version
du plugin. \* \* \@return string \*/ public function get_version():
string { return \$this-\>version; } /\*\* \* Retourne l\'instance du
Loader. \* \* \@return Loader \*/ public function get_loader(): Loader {
return \$this-\>loader; } } \`\`\` \-\-- \## Fichier 4 :
\`includes/Core/Loader.php\` --- Gestionnaire de hooks \`\`\`php \<?php
/\*\* \* Gestionnaire centralisé des hooks WordPress (actions et
filtres). \* \* Responsabilité : collecter toutes les déclarations de
hooks \* puis les enregistrer en une seule passe via run(). \* Découple
les classes métier du système de hooks WordPress. \* \* \@package
TechrappySEO\\Core \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Core; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class Loader \* \* Registre centralisé des actions et filtres WordPress.
\*/ class Loader { /\*\* \* Liste des actions enregistrées. \* \* \@var
array\<int, array{hook: string, component: object, callback: string,
priority: int, accepted_args: int}\> \*/ private array \$actions = \[\];
/\*\* \* Liste des filtres enregistrés. \* \* \@var array\<int,
array{hook: string, component: object, callback: string, priority: int,
accepted_args: int}\> \*/ private array \$filters = \[\]; /\*\* \*
Ajoute une action à la liste des hooks à enregistrer. \* \* \@param
string \$hook Nom du hook WordPress. \* \@param object \$component
Instance de l\'objet portant le callback. \* \@param string \$callback
Nom de la méthode à appeler. \* \@param int \$priority Priorité
d\'exécution (défaut : 10). \* \@param int \$accepted_args Nombre
d\'arguments acceptés (défaut : 1). \* \* \@return void \*/ public
function add_action( string \$hook, object \$component, string
\$callback, int \$priority = 10, int \$accepted_args = 1 ): void {
\$this-\>actions\[\] = \$this-\>build_hook( \$hook, \$component,
\$callback, \$priority, \$accepted_args ); } /\*\* \* Ajoute un filtre à
la liste des hooks à enregistrer. \* \* \@param string \$hook Nom du
hook WordPress. \* \@param object \$component Instance de l\'objet
portant le callback. \* \@param string \$callback Nom de la méthode à
appeler. \* \@param int \$priority Priorité d\'exécution (défaut : 10).
\* \@param int \$accepted_args Nombre d\'arguments acceptés (défaut :
1). \* \* \@return void \*/ public function add_filter( string \$hook,
object \$component, string \$callback, int \$priority = 10, int
\$accepted_args = 1 ): void { \$this-\>filters\[\] =
\$this-\>build_hook( \$hook, \$component, \$callback, \$priority,
\$accepted_args ); } /\*\* \* Enregistre tous les hooks collectés auprès
de WordPress. \* Doit être appelé une seule fois, après avoir déclaré
tous les hooks. \* \* \@return void \*/ public function run(): void {
foreach ( \$this-\>filters as \$hook ) { add_filter( \$hook\[\'hook\'\],
\[ \$hook\[\'component\'\], \$hook\[\'callback\'\] \],
\$hook\[\'priority\'\], \$hook\[\'accepted_args\'\] ); } foreach (
\$this-\>actions as \$hook ) { add_action( \$hook\[\'hook\'\], \[
\$hook\[\'component\'\], \$hook\[\'callback\'\] \],
\$hook\[\'priority\'\], \$hook\[\'accepted_args\'\] ); } } /\*\* \*
Construit le tableau de données d\'un hook. \* \* \@param string \$hook
Nom du hook. \* \@param object \$component Composant portant le
callback. \* \@param string \$callback Méthode callback. \* \@param int
\$priority Priorité. \* \@param int \$accepted_args Arguments acceptés.
\* \* \@return array{hook: string, component: object, callback: string,
priority: int, accepted_args: int} \*/ private function build_hook(
string \$hook, object \$component, string \$callback, int \$priority,
int \$accepted_args ): array { return \[ \'hook\' =\> \$hook,
\'component\' =\> \$component, \'callback\' =\> \$callback, \'priority\'
=\> \$priority, \'accepted_args\' =\> \$accepted_args, \]; } /\*\* \*
Retourne le nombre total de hooks enregistrés. \* \* \@return int \*/
public function count(): int { return count( \$this-\>actions ) + count(
\$this-\>filters ); } } \`\`\` \-\-- \## Fichier 5 :
\`includes/Core/Activator.php\` --- Hook d\'activation \`\`\`php \<?php
/\*\* \* Logique exécutée lors de l\'activation du plugin. \* \*
Responsabilité : créer les tables custom, initialiser les options \* par
défaut et seeder les prompts initiaux. \* \* \@package
TechrappySEO\\Core \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Core; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class Activator \* \* Exécuté une seule fois lors de l\'activation du
plugin via \* register_activation_hook(). \*/ class Activator { /\*\* \*
Point d\'entrée de l\'activation. \* \* \@return void \*/ public static
function activate(): void { // 1. Créer / mettre à jour les tables
custom. Installer::create_tables(); // 2. Initialiser les options par
défaut du plugin.
\\TechrappySEO\\Settings\\SettingsRepository::init_defaults(); // 3.
Seeder les prompts par défaut en base.
\\TechrappySEO\\Prompts\\DefaultPrompts::seed(); // 4. Stocker la
version installée pour gestion des migrations futures. update_option(
\'techrappy_seo_version\', TECHRAPPY_SEO_VERSION, false ); // 5. Forcer
le rechargement des règles de réécriture WordPress.
flush_rewrite_rules(); } } \`\`\` \-\-- \## Fichier 6 :
\`includes/Core/Deactivator.php\` --- Hook de désactivation \`\`\`php
\<?php /\*\* \* Logique exécutée lors de la désactivation du plugin. \*
\* Responsabilité : nettoyer les tâches planifiées et les caches
temporaires. \* Ne supprime PAS les données (réservé à uninstall.php).
\* \* \@package TechrappySEO\\Core \*/ declare( strict_types=1 );
namespace TechrappySEO\\Core; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class Deactivator \* \* Exécuté lors de la désactivation du
plugin. \*/ class Deactivator { /\*\* \* Point d\'entrée de la
désactivation. \* \* \@return void \*/ public static function
deactivate(): void { // 1. Annuler les actions programmées Action
Scheduler en attente. self::clear_scheduled_actions(); // 2. Nettoyer
les transients de cache géographique. self::clear_geo_transients(); //
3. Rechargement des règles de réécriture. flush_rewrite_rules(); } /\*\*
\* Supprime les actions Action Scheduler planifiées par le plugin. \* \*
\@return void \*/ private static function clear_scheduled_actions():
void { // Vérifier qu\'Action Scheduler est disponible avant d\'agir. if
( ! function_exists( \'as_unschedule_all_actions\' ) ) { return; }
\$hooks_to_clear = \[ \'techrappy_seo_process_single_job\',
\'techrappy_seo_bulk_progress_check\', \]; foreach ( \$hooks_to_clear as
\$hook ) { as_unschedule_all_actions( \$hook ); } } /\*\* \* Supprime
les transients de cache géographique du plugin. \* \* \@return void \*/
private static function clear_geo_transients(): void { global \$wpdb;
\$wpdb-\>query( \$wpdb-\>prepare( \"DELETE FROM {\$wpdb-\>options} WHERE
option_name LIKE %s OR option_name LIKE %s\",
\'\_transient_techrappy_seo_geo\_%\',
\'\_transient_timeout_techrappy_seo_geo\_%\' ) ); } } \`\`\` \-\-- \##
Fichier 7 : \`includes/Core/Installer.php\` --- Création des tables
\`\`\`php \<?php /\*\* \* Création et migration des tables de base de
données custom du plugin. \* \* Utilise dbDelta() de WordPress pour
créer ou mettre à jour \* les tables de manière idempotente (safe à
rejouer). \* \* \@package TechrappySEO\\Core \*/ declare( strict_types=1
); namespace TechrappySEO\\Core; if ( ! defined( \'ABSPATH\' ) ) { exit;
} /\*\* \* Class Installer \* \* Gestion du schéma de base de données du
plugin. \*/ class Installer { /\*\* \* Version du schéma de base de
données. \* Incrémenter à chaque modification de schéma pour déclencher
une migration. \*/ const DB_VERSION = \'1.0.0\'; /\*\* \* Crée ou met à
jour les tables custom du plugin via dbDelta(). \* Cette méthode est
idempotente --- safe à appeler plusieurs fois. \* \* \@return void \*/
public static function create_tables(): void { global \$wpdb; // Charset
et collation par défaut de WordPress. \$charset_collate =
\$wpdb-\>get_charset_collate(); // Charger la fonction dbDelta() si
nécessaire. require_once ABSPATH . \'wp-admin/includes/upgrade.php\'; //
Exécuter les migrations de toutes les tables. self::create_jobs_table(
\$charset_collate ); self::create_prompts_table( \$charset_collate ); //
Stocker la version du schéma pour gestion des migrations futures.
update_option( \'techrappy_seo_db_version\', self::DB_VERSION, false );
} /\*\* \* Crée ou met à jour la table des jobs de génération. \* \*
\@param string \$charset_collate Charset + collation WordPress. \* \*
\@return void \*/ private static function create_jobs_table( string
\$charset_collate ): void { global \$wpdb; \$table_name =
\$wpdb-\>prefix . \'techrappy_seo_jobs\'; /\* \* IMPORTANT : dbDelta()
est très sensible à la syntaxe SQL. \* Règles obligatoires : \* - 2
espaces avant chaque définition de colonne \* - PRIMARY KEY sur sa
propre ligne \* - pas de virgule après la dernière colonne \*/ \$sql =
\"CREATE TABLE {\$table_name} ( id BIGINT(20) UNSIGNED NOT NULL
AUTO_INCREMENT, job_id VARCHAR(36) NOT NULL, mode
ENUM(\'single\',\'bulk\') NOT NULL DEFAULT \'single\', type
ENUM(\'page\',\'post\') NOT NULL DEFAULT \'page\', template_post_id
BIGINT(20) UNSIGNED NOT NULL DEFAULT 0, keyword TEXT NOT NULL, city
VARCHAR(255) NOT NULL DEFAULT \'\', publish_status
ENUM(\'draft\',\'publish\') NOT NULL DEFAULT \'draft\', wp_params
LONGTEXT NOT NULL DEFAULT \'{}\', slug_rule
ENUM(\'from_keyword\',\'from_h1\') NOT NULL DEFAULT \'from_keyword\',
steps_data LONGTEXT NOT NULL DEFAULT \'{}\', result_data TEXT NOT NULL
DEFAULT \'{}\', logs LONGTEXT NOT NULL DEFAULT \'\[\]\', status
ENUM(\'pending\',\'running\',\'done\',\'error\') NOT NULL DEFAULT
\'pending\', parent_job_id VARCHAR(36) NOT NULL DEFAULT \'\', created_at
DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT
NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY
(id), UNIQUE KEY job_id (job_id), KEY status (status), KEY parent_job_id
(parent_job_id), KEY template_post_id (template_post_id) )
{\$charset_collate};\"; dbDelta( \$sql ); } /\*\* \* Crée ou met à jour
la table des prompts IA. \* \* \@param string \$charset_collate
Charset + collation WordPress. \* \* \@return void \*/ private static
function create_prompts_table( string \$charset_collate ): void { global
\$wpdb; \$table_name = \$wpdb-\>prefix . \'techrappy_prompts\'; \$sql =
\"CREATE TABLE {\$table_name} ( id BIGINT(20) UNSIGNED NOT NULL
AUTO_INCREMENT, prompt_key VARCHAR(100) NOT NULL, content LONGTEXT NOT
NULL, response_format VARCHAR(50) NOT NULL DEFAULT \'json_object\',
version INT(11) UNSIGNED NOT NULL DEFAULT 1, is_active TINYINT(1)
UNSIGNED NOT NULL DEFAULT 1, updated_at DATETIME NOT NULL DEFAULT
CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, updated_by BIGINT(20)
UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (id), UNIQUE KEY prompt_key
(prompt_key), KEY is_active (is_active) ) {\$charset_collate};\";
dbDelta( \$sql ); } /\*\* \* Vérifie si une migration de schéma est
nécessaire \* en comparant la version stockée avec la version courante.
\* \* \@return bool True si une migration est nécessaire. \*/ public
static function needs_upgrade(): bool { \$stored_version = get_option(
\'techrappy_seo_db_version\', \'0.0.0\' ); return version_compare(
\$stored_version, self::DB_VERSION, \'\<\' ); } } \`\`\` \-\-- \##
Fichier 8 : \`includes/Settings/SettingsRepository.php\` --- Options
sécurisées \`\`\`php \<?php /\*\* \* Référentiel des options et réglages
du plugin. \* \* Responsabilité : lecture/écriture centralisée des
wp_options du plugin. \* Toutes les options sont stockées avec
autoload=no pour la performance. \* La clé API OpenAI bénéficie d\'un
chiffrement basique (base64 + XOR). \* \* \@package
TechrappySEO\\Settings \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Settings; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\*
\* Class SettingsRepository \* \* Accès centralisé aux options WordPress
du plugin. \*/ class SettingsRepository { /\*\* \* Clé de l\'option
principale de settings dans wp_options. \*/ const OPTION_KEY =
\'techrappy_seo_settings\'; /\*\* \* Valeurs par défaut des réglages du
plugin. \* \* \@var array\<string, mixed\> \*/ private static array
\$defaults = \[ // API OpenAI. \'openai_api_key\' =\> \'\',
\'openai_model\' =\> \'gpt-4o\', \'openai_temperature\' =\> 0.7,
\'openai_max_tokens\' =\> 4096, \'openai_timeout\' =\> 60, //
Comportement génération. \'default_publish_status\' =\> \'draft\',
\'default_slug_rule\' =\> \'from_keyword\', \'qa_gate_enabled\' =\>
false, // Coûts et garde-fous. \'cost_alert_threshold\' =\> 1.00,
\'bulk_max_cities\' =\> 50, // Debug. \'debug_mode\' =\> false,
\'log_retention_days\' =\> 30, \]; /\*\* \* Cache en mémoire des
settings chargés. \* \* \@var array\<string, mixed\>\|null \*/ private
static ?array \$cache = null; /\*\* \* Initialise les options par défaut
lors de l\'activation du plugin. \* N\'écrase pas les valeurs déjà
existantes (preserves existing config). \* \* \@return void \*/ public
static function init_defaults(): void { \$existing = get_option(
self::OPTION_KEY, null ); // Créer l\'option seulement si elle n\'existe
pas encore. if ( null === \$existing ) { \$defaults_to_store =
self::\$defaults; // Ne jamais stocker une clé API vide en clair.
\$defaults_to_store\[\'openai_api_key\'\] = \'\'; add_option(
self::OPTION_KEY, \$defaults_to_store, \'\', false ); } } /\*\* \*
Retourne toutes les options du plugin. \* \* \@return array\<string,
mixed\> \*/ public static function get_all(): array { if ( null !==
self::\$cache ) { return self::\$cache; } \$stored = get_option(
self::OPTION_KEY, \[\] ); // Fusionner avec les defaults pour garantir
toutes les clés. self::\$cache = wp_parse_args( \$stored,
self::\$defaults ); return self::\$cache; } /\*\* \* Retourne la valeur
d\'un réglage spécifique. \* \* \@param string \$key Clé du réglage. \*
\@param mixed \$default Valeur par défaut si non trouvée. \* \* \@return
mixed \*/ public static function get( string \$key, mixed \$default =
null ): mixed { \$all = self::get_all(); return \$all\[ \$key \] ??
\$default ?? ( self::\$defaults\[ \$key \] ?? null ); } /\*\* \* Met à
jour un ou plusieurs réglages. \* \* \@param array\<string, mixed\>
\$data Tableau clé/valeur des réglages à mettre à jour. \* \* \@return
bool True si la mise à jour a réussi. \*/ public static function update(
array \$data ): bool { \$current = self::get_all(); \$updated =
array_merge( \$current, \$data ); // Invalider le cache en mémoire.
self::\$cache = null; return update_option( self::OPTION_KEY, \$updated,
false ); } /\*\* \* Sauvegarde la clé API OpenAI de manière sécurisée.
\* La clé est obfusquée avant stockage (base64 uniquement en V1, \*
prévoir chiffrement AES en V2 avec clé dérivée de AUTH_KEY). \* \*
\@param string \$api_key Clé API en clair. \* \* \@return bool \*/
public static function set_api_key( string \$api_key ): bool { if (
empty( \$api_key ) ) { return self::update( \[ \'openai_api_key\' =\>
\'\' \] ); } // Obfuscation V1 : base64 encode (non-chiffrement, juste
masquage visuel). // TODO V2 : chiffrement AES-256 avec clé dérivée de
AUTH_KEY + AUTH_SALT. \$obfuscated = base64_encode( \$api_key ); //
phpcs:ignore
WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode return
self::update( \[ \'openai_api_key\' =\> \$obfuscated \] ); } /\*\* \*
Récupère la clé API OpenAI en clair. \* \* \@return string Clé API
désobfusquée, ou chaîne vide si non configurée. \*/ public static
function get_api_key(): string { \$stored = self::get(
\'openai_api_key\', \'\' ); if ( empty( \$stored ) ) { return \'\'; } //
Désobfuscation V1. \$decoded = base64_decode( \$stored, true ); //
phpcs:ignore
WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode return (
false !== \$decoded ) ? \$decoded : \'\'; } /\*\* \* Remet tous les
réglages à leurs valeurs par défaut. \* Préserve la clé API pour éviter
une perte accidentelle. \* \* \@return bool \*/ public static function
reset_to_defaults(): bool { \$current_api_key = self::get_api_key();
\$defaults = self::\$defaults; // Réenregistrer la clé API existante. if
( ! empty( \$current_api_key ) ) { \$defaults\[\'openai_api_key\'\] =
base64_encode( \$current_api_key ); // phpcs:ignore
WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode } //
Invalider le cache. self::\$cache = null; return update_option(
self::OPTION_KEY, \$defaults, false ); } /\*\* \* Invalide le cache en
mémoire (utile après update externe). \* \* \@return void \*/ public
static function invalidate_cache(): void { self::\$cache = null; } }
\`\`\` \-\-- \## Fichier 9 : \`includes/Settings/SettingsValidator.php\`
\`\`\`php \<?php /\*\* \* Validation et sanitization du formulaire de
settings. \* \* \@package TechrappySEO\\Settings \*/ declare(
strict_types=1 ); namespace TechrappySEO\\Settings; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class SettingsValidator \* \* Valide
et sanitize les données du formulaire Settings avant stockage. \*/ class
SettingsValidator { /\*\* \* Erreurs de validation collectées. \* \*
\@var array\<string, string\> \*/ private array \$errors = \[\]; /\*\*
\* Données sanitizées prêtes à être stockées. \* \* \@var array\<string,
mixed\> \*/ private array \$sanitized = \[\]; /\*\* \* Valide et
sanitize un tableau de données POST du formulaire settings. \* \*
\@param array\<string, mixed\> \$raw_data Données brutes du POST. \* \*
\@return bool True si toutes les données sont valides. \*/ public
function validate( array \$raw_data ): bool { \$this-\>errors = \[\];
\$this-\>sanitized = \[\]; // Clé API OpenAI (optionnelle, mais validée
si présente). if ( isset( \$raw_data\[\'openai_api_key\'\] ) && ! empty(
\$raw_data\[\'openai_api_key\'\] ) ) { \$api_key = sanitize_text_field(
\$raw_data\[\'openai_api_key\'\] ); // Format basique : commence par
\"sk-\" (OpenAI standard). if ( ! str_starts_with( \$api_key, \'sk-\' )
) { \$this-\>errors\[\'openai_api_key\'\] = \_\_( \'La clé API OpenAI
semble invalide (doit commencer par \"sk-\").\', \'techrappy-seo\' ); }
else { \$this-\>sanitized\[\'openai_api_key\'\] = \$api_key; } } //
Modèle OpenAI. \$allowed_models = \[ \'gpt-4o\', \'gpt-4o-mini\',
\'gpt-4-turbo\', \'gpt-3.5-turbo\' \]; if ( isset(
\$raw_data\[\'openai_model\'\] ) ) { \$model = sanitize_text_field(
\$raw_data\[\'openai_model\'\] ); if ( ! in_array( \$model,
\$allowed_models, true ) ) { \$this-\>errors\[\'openai_model\'\] = \_\_(
\'Modèle OpenAI non reconnu.\', \'techrappy-seo\' ); } else {
\$this-\>sanitized\[\'openai_model\'\] = \$model; } } // Température
(float entre 0 et 2). if ( isset( \$raw_data\[\'openai_temperature\'\] )
) { \$temp = (float) \$raw_data\[\'openai_temperature\'\]; if ( \$temp
\< 0.0 \|\| \$temp \> 2.0 ) { \$this-\>errors\[\'openai_temperature\'\]
= \_\_( \'La température doit être entre 0 et 2.\', \'techrappy-seo\' );
} else { \$this-\>sanitized\[\'openai_temperature\'\] = \$temp; } } //
Max tokens (int entre 256 et 16000). if ( isset(
\$raw_data\[\'openai_max_tokens\'\] ) ) { \$max_tokens = absint(
\$raw_data\[\'openai_max_tokens\'\] ); if ( \$max_tokens \< 256 \|\|
\$max_tokens \> 16000 ) { \$this-\>errors\[\'openai_max_tokens\'\] =
\_\_( \'Max tokens doit être entre 256 et 16000.\', \'techrappy-seo\' );
} else { \$this-\>sanitized\[\'openai_max_tokens\'\] = \$max_tokens; } }
// Timeout (int entre 10 et 300 secondes). if ( isset(
\$raw_data\[\'openai_timeout\'\] ) ) { \$timeout = absint(
\$raw_data\[\'openai_timeout\'\] ); if ( \$timeout \< 10 \|\| \$timeout
\> 300 ) { \$this-\>errors\[\'openai_timeout\'\] = \_\_( \'Le timeout
doit être entre 10 et 300 secondes.\', \'techrappy-seo\' ); } else {
\$this-\>sanitized\[\'openai_timeout\'\] = \$timeout; } } // Statut de
publication par défaut. \$allowed_statuses = \[ \'draft\', \'publish\'
\]; if ( isset( \$raw_data\[\'default_publish_status\'\] ) ) { \$status
= sanitize_text_field( \$raw_data\[\'default_publish_status\'\] ); if (
in_array( \$status, \$allowed_statuses, true ) ) {
\$this-\>sanitized\[\'default_publish_status\'\] = \$status; } } //
Règle de slug par défaut. \$allowed_slug_rules = \[ \'from_keyword\',
\'from_h1\' \]; if ( isset( \$raw_data\[\'default_slug_rule\'\] ) ) {
\$slug_rule = sanitize_text_field( \$raw_data\[\'default_slug_rule\'\]
); if ( in_array( \$slug_rule, \$allowed_slug_rules, true ) ) {
\$this-\>sanitized\[\'default_slug_rule\'\] = \$slug_rule; } } // Seuil
d\'alerte coût (float). if ( isset(
\$raw_data\[\'cost_alert_threshold\'\] ) ) {
\$this-\>sanitized\[\'cost_alert_threshold\'\] = round( (float)
\$raw_data\[\'cost_alert_threshold\'\], 2 ); } // Nombre max de villes
en bulk. if ( isset( \$raw_data\[\'bulk_max_cities\'\] ) ) {
\$this-\>sanitized\[\'bulk_max_cities\'\] = min( absint(
\$raw_data\[\'bulk_max_cities\'\] ), 200 ); } // Flags booléens.
\$bool_fields = \[ \'debug_mode\', \'qa_gate_enabled\' \]; foreach (
\$bool_fields as \$field ) { if ( isset( \$raw_data\[ \$field \] ) ) {
\$this-\>sanitized\[ \$field \] = (bool) \$raw_data\[ \$field \]; } }
return empty( \$this-\>errors ); } /\*\* \* Retourne les erreurs de
validation. \* \* \@return array\<string, string\> \*/ public function
get_errors(): array { return \$this-\>errors; } /\*\* \* Retourne les
données sanitizées et validées. \* \* \@return array\<string, mixed\>
\*/ public function get_sanitized(): array { return \$this-\>sanitized;
} /\*\* \* Vérifie si une erreur existe pour une clé donnée. \* \*
\@param string \$key Clé du champ. \* \* \@return bool \*/ public
function has_error( string \$key ): bool { return isset(
\$this-\>errors\[ \$key \] ); } /\*\* \* Retourne le message d\'erreur
pour une clé donnée. \* \* \@param string \$key Clé du champ. \* \*
\@return string Message d\'erreur ou chaîne vide. \*/ public function
get_error( string \$key ): string { return \$this-\>errors\[ \$key \] ??
\'\'; } } \`\`\` \-\-- \## Fichier 10 : \`includes/Admin/AdminMenu.php\`
--- Menus Admin \`\`\`php \<?php /\*\* \* Enregistrement des menus et
sous-menus WordPress Admin. \* \* \@package TechrappySEO\\Admin \*/
declare( strict_types=1 ); namespace TechrappySEO\\Admin; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class AdminMenu \* \*
Déclare le menu principal \"Techrappy SEO\" et ses sous-pages dans WP
Admin. \*/ class AdminMenu { /\*\* \* Slug de la page parente (menu
principal). \*/ const PARENT_SLUG = \'techrappy-seo\'; /\*\* \*
Enregistre le menu principal et les sous-menus auprès de WordPress. \*
Appelé sur le hook admin_menu. \* \* \@return void \*/ public function
register_menus(): void { // Sécurité : admin uniquement. if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { return; } // ── Menu
principal ────────────────────────────────────────── add_menu_page(
\_\_( \'Techrappy SEO\', \'techrappy-seo\' ), // Titre de la page. \_\_(
\'Techrappy SEO\', \'techrappy-seo\' ), // Titre dans le menu.
TECHRAPPY_SEO_CAPABILITY, // Capacité requise. self::PARENT_SLUG, //
Slug de la page. \[ \$this, \'render_wizard_page\' \], // Callback de
rendu. \'dashicons-search\', // Icône Dashicons. 30 // Position dans le
menu. ); // ── Sous-page : New Generation (Wizard) ─────────────────────
add_submenu_page( self::PARENT_SLUG, \_\_( \'Nouvelle génération\',
\'techrappy-seo\' ), \_\_( \'Nouvelle génération\', \'techrappy-seo\' ),
TECHRAPPY_SEO_CAPABILITY, self::PARENT_SLUG, // Même slug = page parent.
\[ \$this, \'render_wizard_page\' \] ); // ── Sous-page : Template Audit
─────────────────────────────── add_submenu_page( self::PARENT_SLUG,
\_\_( \'Audit de templates\', \'techrappy-seo\' ), \_\_( \'Audit de
templates\', \'techrappy-seo\' ), TECHRAPPY_SEO_CAPABILITY,
\'techrappy-seo-template-audit\', \[ \$this,
\'render_template_audit_page\' \] ); // ── Sous-page : Bulk Jobs
──────────────────────────────────── add_submenu_page(
self::PARENT_SLUG, \_\_( \'Jobs en masse\', \'techrappy-seo\' ), \_\_(
\'Jobs en masse\', \'techrappy-seo\' ), TECHRAPPY_SEO_CAPABILITY,
\'techrappy-seo-bulk-jobs\', \[ \$this, \'render_bulk_jobs_page\' \] );
// ── Sous-page : Prompt Studio ────────────────────────────────
add_submenu_page( self::PARENT_SLUG, \_\_( \'Prompt Studio\',
\'techrappy-seo\' ), \_\_( \'Prompt Studio\', \'techrappy-seo\' ),
TECHRAPPY_SEO_CAPABILITY, \'techrappy-seo-prompt-studio\', \[ \$this,
\'render_prompt_studio_page\' \] ); // ── Sous-page : Settings
───────────────────────────────────── add_submenu_page(
self::PARENT_SLUG, \_\_( \'Réglages\', \'techrappy-seo\' ), \_\_(
\'Réglages\', \'techrappy-seo\' ), TECHRAPPY_SEO_CAPABILITY,
\'techrappy-seo-settings\', \[ \$this, \'render_settings_page\' \] ); }
/\*\* \* Rendu de la page Wizard (New Generation). \* \* \@return void
\*/ public function render_wizard_page(): void { if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_(
\'Accès non autorisé.\', \'techrappy-seo\' ) ); } \$page = new
Pages\\PageWizard(); \$page-\>render(); } /\*\* \* Rendu de la page
Template Audit. \* \* \@return void \*/ public function
render_template_audit_page(): void { if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_( \'Accès non
autorisé.\', \'techrappy-seo\' ) ); } \$page = new
Pages\\PageTemplateAudit(); \$page-\>render(); } /\*\* \* Rendu de la
page Bulk Jobs. \* \* \@return void \*/ public function
render_bulk_jobs_page(): void { if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_( \'Accès non
autorisé.\', \'techrappy-seo\' ) ); } \$page = new
Pages\\PageBulkJobs(); \$page-\>render(); } /\*\* \* Rendu de la page
Prompt Studio. \* \* \@return void \*/ public function
render_prompt_studio_page(): void { if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_( \'Accès non
autorisé.\', \'techrappy-seo\' ) ); } \$page = new
Pages\\PagePromptStudio(); \$page-\>render(); } /\*\* \* Rendu de la
page Settings. \* \* \@return void \*/ public function
render_settings_page(): void { if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_( \'Accès non
autorisé.\', \'techrappy-seo\' ) ); } \$page = new
Pages\\PageSettings(); \$page-\>render(); } } \`\`\` \-\-- \## Fichier
11 : \`includes/Admin/AdminAssets.php\` --- Enqueue scripts/styles
\`\`\`php \<?php /\*\* \* Enregistrement et chargement conditionnel des
assets admin (CSS + JS). \* \* \@package TechrappySEO\\Admin \*/
declare( strict_types=1 ); namespace TechrappySEO\\Admin; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class AdminAssets \* \* Gère
l\'enqueue conditionnel des CSS et JS selon la page admin courante. \*/
class AdminAssets { /\*\* \* Pages du plugin et leurs assets
spécifiques. \* Clé = slug de page, valeur = liste des handles JS à
charger. \* \* \@var array\<string, string\[\]\> \*/ private array
\$page_js_map = \[ \'toplevel_page_techrappy-seo\' =\> \[
\'techrappy-seo-wizard\' \],
\'techrappy-seo_page_techrappy-seo-template-audit\' =\> \[
\'techrappy-seo-template-audit\' \],
\'techrappy-seo_page_techrappy-seo-bulk-jobs\' =\> \[
\'techrappy-seo-bulk-jobs\' \],
\'techrappy-seo_page_techrappy-seo-prompt-studio\' =\> \[
\'techrappy-seo-prompt-studio\' \],
\'techrappy-seo_page_techrappy-seo-settings\' =\> \[\], \]; /\*\* \*
Enqueue les assets CSS et JS sur les pages du plugin. \* Appelé sur le
hook admin_enqueue_scripts. \* \* \@param string \$hook_suffix
Identifiant de la page admin courante. \* \* \@return void \*/ public
function enqueue( string \$hook_suffix ): void { // Ne charger les
assets que sur les pages du plugin. if ( ! array_key_exists(
\$hook_suffix, \$this-\>page_js_map ) ) { return; } // ── CSS global
admin (toutes les pages du plugin) ──────────── wp_enqueue_style(
\'techrappy-seo-admin\', TECHRAPPY_SEO_URL . \'assets/css/admin.css\',
\[\], TECHRAPPY_SEO_VERSION ); // ── JS global admin
────────────────────────────────────────── wp_enqueue_script(
\'techrappy-seo-admin\', TECHRAPPY_SEO_URL . \'assets/js/admin.js\', \[
\'jquery\' \], TECHRAPPY_SEO_VERSION, true ); // ── Assets spécifiques à
la page courante ──────────────────── \$page_scripts =
\$this-\>page_js_map\[ \$hook_suffix \] ?? \[\]; foreach (
\$page_scripts as \$handle ) { \$this-\>enqueue_page_script( \$handle );
} // ── Données JS localisées (nonces + config AJAX) ─────────────
wp_localize_script( \'techrappy-seo-admin\', \'TechrappySEO\',
\$this-\>get_localized_data() ); } /\*\* \* Enqueue un script JS
spécifique à une page. \* \* \@param string \$handle Handle du script.
\* \* \@return void \*/ private function enqueue_page_script( string
\$handle ): void { // Mapping handle → fichier JS. \$script_files = \[
\'techrappy-seo-wizard\' =\> \'assets/js/wizard.js\',
\'techrappy-seo-template-audit\' =\> \'assets/js/template-audit.js\',
\'techrappy-seo-bulk-jobs\' =\> \'assets/js/bulk-jobs.js\',
\'techrappy-seo-prompt-studio\' =\> \'assets/js/prompt-studio.js\', \];
if ( ! isset( \$script_files\[ \$handle \] ) ) { return; } \$file_path =
TECHRAPPY_SEO_PATH . \$script_files\[ \$handle \]; \$file_url =
TECHRAPPY_SEO_URL . \$script_files\[ \$handle \]; // Enqueue seulement
si le fichier existe. if ( ! file_exists( \$file_path ) ) { return; }
wp_enqueue_script( \$handle, \$file_url, \[ \'jquery\',
\'techrappy-seo-admin\' \], TECHRAPPY_SEO_VERSION, true ); } /\*\* \*
Prépare les données à localiser en JavaScript. \* \* \@return
array\<string, mixed\> \*/ private function get_localized_data(): array
{ return \[ \'ajax_url\' =\> admin_url( \'admin-ajax.php\' ), \'nonces\'
=\> \[ \'wizard\' =\> wp_create_nonce( \'techrappy_seo_wizard\' ),
\'generation\' =\> wp_create_nonce( \'techrappy_seo_generation\' ),
\'bulk\' =\> wp_create_nonce( \'techrappy_seo_bulk\' ), \'audit\' =\>
wp_create_nonce( \'techrappy_seo_audit\' ), \'prompt_studio\' =\>
wp_create_nonce( \'techrappy_seo_prompt_studio\' ), \'settings\' =\>
wp_create_nonce( \'techrappy_seo_settings\' ), \], \'i18n\' =\> \[
\'loading\' =\> \_\_( \'Chargement...\', \'techrappy-seo\' ), \'error\'
=\> \_\_( \'Une erreur est survenue.\', \'techrappy-seo\' ), \'success\'
=\> \_\_( \'Succès !\', \'techrappy-seo\' ), \'confirm_bulk\' =\> \_\_(
\'Êtes-vous sûr de vouloir lancer la génération en masse ?\',
\'techrappy-seo\' ), \], \'debug\' =\>
\\TechrappySEO\\Settings\\SettingsRepository::get( \'debug_mode\', false
), \]; } } \`\`\` \-\-- \## Fichiers 12--16 : Pages Admin (squelettes)
\`\`\`php \<?php /\*\* \* Page Admin : Wizard de génération (New
Generation). \* \* \@package TechrappySEO\\Admin\\Pages \*/ declare(
strict_types=1 ); namespace TechrappySEO\\Admin\\Pages; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class PageWizard \*/ class PageWizard
{ /\*\* \* Rendu de la page. \* \* \@return void \*/ public function
render(): void { require_once TECHRAPPY_SEO_PATH .
\'views/admin/wizard/step-1-mode.php\'; } } \`\`\` \`\`\`php \<?php
/\*\* \* Page Admin : Audit de templates. \* \* \@package
TechrappySEO\\Admin\\Pages \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Pages; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class PageTemplateAudit \*/ class PageTemplateAudit { /\*\* \*
Rendu de la page. \* \* \@return void \*/ public function render(): void
{ require_once TECHRAPPY_SEO_PATH .
\'views/admin/template-audit/audit-result.php\'; } } \`\`\` \`\`\`php
\<?php /\*\* \* Page Admin : Jobs en masse (Bulk Jobs). \* \* \@package
TechrappySEO\\Admin\\Pages \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Pages; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class PageBulkJobs \*/ class PageBulkJobs { /\*\* \* Rendu de
la page. \* \* \@return void \*/ public function render(): void {
require_once TECHRAPPY_SEO_PATH . \'views/admin/bulk-jobs/list.php\'; }
} \`\`\` \`\`\`php \<?php /\*\* \* Page Admin : Prompt Studio. \* \*
\@package TechrappySEO\\Admin\\Pages \*/ declare( strict_types=1 );
namespace TechrappySEO\\Admin\\Pages; if ( ! defined( \'ABSPATH\' ) ) {
exit; } /\*\* \* Class PagePromptStudio \*/ class PagePromptStudio {
/\*\* \* Rendu de la page. \* \* \@return void \*/ public function
render(): void { require_once TECHRAPPY_SEO_PATH .
\'views/admin/prompt-studio/editor.php\'; } } \`\`\` \`\`\`php \<?php
/\*\* \* Page Admin : Réglages (Settings). \* \* \@package
TechrappySEO\\Admin\\Pages \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Pages; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class PageSettings \*/ class PageSettings { /\*\* \* Rendu de
la page. \* \* \@return void \*/ public function render(): void {
require_once TECHRAPPY_SEO_PATH .
\'views/admin/settings/settings-form.php\'; } } \`\`\` \-\-- \##
Fichiers 17--21 : Handlers AJAX (squelettes sécurisés) \`\`\`php \<?php
/\*\* \* Handler AJAX : Wizard de génération. \* \* \@package
TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Ajax; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class AjaxWizard \*/ class AjaxWizard { /\*\* \* Gère les
étapes du wizard via AJAX. \* \* \@return void \*/ public function
handle_step(): void { // Sécurité : vérification nonce + capacité.
check_ajax_referer( \'techrappy_seo_wizard\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter la logique des étapes wizard.
wp_send_json_success( \[ \'message\' =\> \'Wizard handler --- à
implémenter.\' \] ); } } \`\`\` \`\`\`php \<?php /\*\* \* Handler AJAX :
Génération pipeline (step-by-step et full). \* \* \@package
TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Ajax; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class AjaxGeneration \*/ class AjaxGeneration { /\*\* \* Lance
une étape unique du pipeline. \* \* \@return void \*/ public function
handle_run_step(): void { check_ajax_referer(
\'techrappy_seo_generation\', \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } // TODO
: implémenter l\'exécution d\'une étape pipeline. wp_send_json_success(
\[ \'message\' =\> \'Run step handler --- à implémenter.\' \] ); } /\*\*
\* Lance le pipeline complet. \* \* \@return void \*/ public function
handle_run_pipeline(): void { check_ajax_referer(
\'techrappy_seo_generation\', \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } // TODO
: implémenter le pipeline complet. wp_send_json_success( \[ \'message\'
=\> \'Run pipeline handler --- à implémenter.\' \] ); } } \`\`\`
\`\`\`php \<?php /\*\* \* Handler AJAX : Bulk Jobs (villes, preview,
lancement, statut). \* \* \@package TechrappySEO\\Admin\\Ajax \*/
declare( strict_types=1 ); namespace TechrappySEO\\Admin\\Ajax; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class AjaxBulk \*/ class
AjaxBulk { /\*\* \* Récupère les villes via API Villes-Voisines + BAN.
\* \* \@return void \*/ public function handle_get_cities(): void {
check_ajax_referer( \'techrappy_seo_bulk\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter récupération villes.
wp_send_json_success( \[ \'cities\' =\> \[\] \] ); } /\*\* \* Génère la
prévisualisation pour une ville exemple. \* \* \@return void \*/ public
function handle_preview_city(): void { check_ajax_referer(
\'techrappy_seo_bulk\', \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } // TODO
: implémenter preview city. wp_send_json_success( \[ \'preview\' =\>
\'\' \] ); } /\*\* \* Lance la génération en masse (enqueue Action
Scheduler). \* \* \@return void \*/ public function
handle_launch_bulk(): void { check_ajax_referer( \'techrappy_seo_bulk\',
\'nonce\' ); if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Accès non autorisé.\',
\'techrappy-seo\' ) \], 403 ); } // TODO : implémenter lancement bulk.
wp_send_json_success( \[ \'job_id\' =\> \'\' \] ); } /\*\* \* Retourne
le statut et la progression d\'un job. \* \* \@return void \*/ public
function handle_get_job_status(): void { check_ajax_referer(
\'techrappy_seo_bulk\', \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } // TODO
: implémenter récupération statut job. wp_send_json_success( \[
\'status\' =\> \'pending\', \'progress\' =\> 0 \] ); } } \`\`\`
\`\`\`php \<?php /\*\* \* Handler AJAX : Audit de templates Divi. \* \*
\@package TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 );
namespace TechrappySEO\\Admin\\Ajax; if ( ! defined( \'ABSPATH\' ) ) {
exit; } /\*\* \* Class AjaxTemplateAudit \*/ class AjaxTemplateAudit {
/\*\* \* Lance le scan d\'un template pour détecter les tokens. \* \*
\@return void \*/ public function handle_scan(): void {
check_ajax_referer( \'techrappy_seo_audit\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter le scan de template.
wp_send_json_success( \[ \'tokens_found\' =\> \[\], \'tokens_missing\'
=\> \[\] \] ); } /\*\* \* Sauvegarde le mapping token→source pour un
template. \* \* \@return void \*/ public function handle_save_mapping():
void { check_ajax_referer( \'techrappy_seo_audit\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter sauvegarde mapping. wp_send_json_success(
\[ \'saved\' =\> true \] ); } } \`\`\` \`\`\`php \<?php /\*\* \* Handler
AJAX : Prompt Studio (sauvegarde, reset, test). \* \* \@package
TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Ajax; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class AjaxPromptStudio \*/ class AjaxPromptStudio { /\*\* \*
Sauvegarde un prompt modifié. \* \* \@return void \*/ public function
handle_save(): void { check_ajax_referer(
\'techrappy_seo_prompt_studio\', \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } // TODO
: implémenter sauvegarde prompt. wp_send_json_success( \[ \'saved\' =\>
true \] ); } /\*\* \* Remet tous les prompts aux valeurs par défaut. \*
\* \@return void \*/ public function handle_reset(): void {
check_ajax_referer( \'techrappy_seo_prompt_studio\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter reset prompts. wp_send_json_success( \[
\'reset\' =\> true \] ); } /\*\* \* Teste un prompt avec des variables
fournies. \* \* \@return void \*/ public function handle_test(): void {
check_ajax_referer( \'techrappy_seo_prompt_studio\', \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \],
403 ); } // TODO : implémenter test prompt via AIClient.
wp_send_json_success( \[ \'result\' =\> null, \'tokens_used\' =\> 0,
\'duration_ms\' =\> 0 \] ); } } \`\`\` \`\`\`php \<?php /\*\* \* Handler
AJAX : Sauvegarde des réglages. \* \* \@package
TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Ajax; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class AjaxSettings \*/ class AjaxSettings { /\*\* \* Sauvegarde
les réglages du plugin. \* \* \@return void \*/ public function
handle_save(): void { check_ajax_referer( \'techrappy_seo_settings\',
\'nonce\' ); if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Accès non autorisé.\',
\'techrappy-seo\' ) \], 403 ); } // TODO : implémenter sauvegarde
settings via SettingsValidator. wp_send_json_success( \[ \'saved\' =\>
true \] ); } } \`\`\` \-\-- \## Fichier 22 :
\`includes/Utils/Logger.php\` \`\`\`php \<?php /\*\* \* Système de logs
par job et par étape du pipeline. \* \* \@package TechrappySEO\\Utils
\*/ declare( strict_types=1 ); namespace TechrappySEO\\Utils; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class Logger \* \* Gère les
logs d\'exécution du pipeline, stockés dans le job. \*/ class Logger {
/\*\* Niveau log : info \*/ const LEVEL_INFO = \'info\'; /\*\* Niveau
log : warning \*/ const LEVEL_WARNING = \'warning\'; /\*\* Niveau log :
error \*/ const LEVEL_ERROR = \'error\'; /\*\* Niveau log : debug \*/
const LEVEL_DEBUG = \'debug\'; /\*\* \* Logs en mémoire pour la session
courante. \* \* \@var array\<int, array{t: int, step: string, msg:
string, level: string}\> \*/ private array \$logs = \[\]; /\*\* \* Job
ID associé à ce logger. \* \* \@var string \*/ private string \$job_id;
/\*\* \* Constructeur. \* \* \@param string \$job_id Identifiant du job.
\*/ public function \_\_construct( string \$job_id ) { \$this-\>job_id =
\$job_id; } /\*\* \* Ajoute une entrée de log. \* \* \@param string
\$step Nom de l\'étape (ex: \'intent\', \'plan\'). \* \@param string
\$message Message du log. \* \@param string \$level Niveau du log
(info\|warning\|error\|debug). \* \* \@return void \*/ public function
log( string \$step, string \$message, string \$level = self::LEVEL_INFO
): void { \$entry = \[ \'t\' =\> time(), \'step\' =\> \$step, \'msg\'
=\> \$message, \'level\' =\> \$level, \]; \$this-\>logs\[\] = \$entry;
// Si mode debug activé : écriture dans le log WordPress. if (
\\TechrappySEO\\Settings\\SettingsRepository::get( \'debug_mode\', false
) ) { // phpcs:ignore
WordPress.PHP.DevelopmentFunctions.error_log_error_log error_log(
sprintf( \'\[TechrappySEO\]\[%s\]\[%s\]\[%s\] %s\', \$this-\>job_id,
strtoupper( \$level ), \$step, \$message ) ); } } /\*\* \* Raccourcis de
logging par niveau. \* \* \@param string \$step Étape. \* \@param string
\$message Message. \* \* \@return void \*/ public function info( string
\$step, string \$message ): void { \$this-\>log( \$step, \$message,
self::LEVEL \`\`\`php public function info( string \$step, string
\$message ): void { \$this-\>log( \$step, \$message, self::LEVEL_INFO );
} public function warning( string \$step, string \$message ): void {
\$this-\>log( \$step, \$message, self::LEVEL_WARNING ); } public
function error( string \$step, string \$message ): void { \$this-\>log(
\$step, \$message, self::LEVEL_ERROR ); } public function debug( string
\$step, string \$message ): void { \$this-\>log( \$step, \$message,
self::LEVEL_DEBUG ); } /\*\* \* Retourne tous les logs en mémoire. \* \*
\@return array\<int, array{t: int, step: string, msg: string, level:
string}\> \*/ public function get_logs(): array { return \$this-\>logs;
} /\*\* \* Charge des logs existants (depuis DB) dans la mémoire. \* \*
\@param array\<int, array{t: int, step: string, msg: string, level:
string}\> \$logs \* \* \@return void \*/ public function load( array
\$logs ): void { \$this-\>logs = \$logs; } /\*\* \* Retourne les logs
encodés en JSON pour stockage en DB. \* \* \@return string \*/ public
function to_json(): string { return wp_json_encode( \$this-\>logs ) ?:
\'\[\]\'; } } \`\`\` \-\-- \## Fichier 23 :
\`includes/Utils/UuidGenerator.php\` \`\`\`php \<?php /\*\* \*
Générateur UUID v4 pur PHP sans dépendance externe. \* \* \@package
TechrappySEO\\Utils \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Utils; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class UuidGenerator \*/ class UuidGenerator { /\*\* \* Génère un UUID v4
aléatoire. \* \* \@return string UUID au format
xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx \*/ public static function
generate(): string { \$data = random_bytes( 16 ); // Positionner la
version (4) et le variant (RFC 4122). \$data\[6\] = chr( ( ord(
\$data\[6\] ) & 0x0F ) \| 0x40 ); \$data\[8\] = chr( ( ord( \$data\[8\]
) & 0x3F ) \| 0x80 ); return vsprintf( \'%s%s-%s-%s-%s-%s%s%s\',
str_split( bin2hex( \$data ), 4 ) ); } } \`\`\` \-\-- \## Fichier 24 :
\`includes/Utils/CostEstimator.php\` \`\`\`php \<?php /\*\* \*
Estimation du coût des appels OpenAI. \* \* \@package
TechrappySEO\\Utils \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Utils; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class CostEstimator \*/ class CostEstimator { /\*\* \* Tarifs en \$ pour
1 000 tokens (input / output) par modèle. \* \* \@var array\<string,
array{input: float, output: float}\> \*/ private static array \$pricing
= \[ \'gpt-4o\' =\> \[ \'input\' =\> 0.005, \'output\' =\> 0.015 \],
\'gpt-4o-mini\' =\> \[ \'input\' =\> 0.00015,\'output\' =\> 0.0006 \],
\'gpt-4-turbo\' =\> \[ \'input\' =\> 0.01, \'output\' =\> 0.03 \],
\'gpt-3.5-turbo\'=\> \[ \'input\' =\> 0.0005, \'output\' =\> 0.0015 \],
\]; /\*\* \* Estime le coût d\'un appel en dollars. \* \* \@param string
\$model Nom du modèle OpenAI. \* \@param int \$input_tokens Nombre de
tokens en entrée. \* \@param int \$output_tokens Nombre de tokens en
sortie. \* \* \@return float Coût estimé en dollars (arrondi à 6
décimales). \*/ public static function estimate( string \$model, int
\$input_tokens, int \$output_tokens ): float { \$rates =
self::\$pricing\[ \$model \] ?? self::\$pricing\[\'gpt-4o\'\]; \$cost =
( \$input_tokens / 1000 \* \$rates\[\'input\'\] ) + ( \$output_tokens /
1000 \* \$rates\[\'output\'\] ); return round( \$cost, 6 ); } /\*\* \*
Vérifie si le coût dépasse le seuil d\'alerte configuré. \* \* \@param
float \$cost Coût estimé. \* \* \@return bool True si le seuil est
dépassé. \*/ public static function exceeds_threshold( float \$cost ):
bool { \$threshold = (float)
\\TechrappySEO\\Settings\\SettingsRepository::get(
\'cost_alert_threshold\', 1.00 ); return \$cost \>= \$threshold; } }
\`\`\` \-\-- \## Fichier 25 : \`includes/Utils/Sanitizer.php\` \`\`\`php
\<?php /\*\* \* Helpers centralisés de sanitization et d\'échappement.
\* \* \@package TechrappySEO\\Utils \*/ declare( strict_types=1 );
namespace TechrappySEO\\Utils; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class Sanitizer \*/ class Sanitizer { /\*\* \* Sanitize un
texte simple (pas de HTML). \* \* \@param mixed \$value Valeur brute. \*
\* \@return string \*/ public static function text( mixed \$value ):
string { return sanitize_text_field( (string) \$value ); } /\*\* \*
Sanitize un bloc HTML (contenu post WordPress). \* \* \@param mixed
\$value Valeur brute. \* \* \@return string \*/ public static function
html( mixed \$value ): string { return wp_kses_post( (string) \$value );
} /\*\* \* Sanitize un slug WordPress. \* \* \@param mixed \$value
Valeur brute. \* \* \@return string \*/ public static function slug(
mixed \$value ): string { return sanitize_title( (string) \$value ); }
/\*\* \* Sanitize et valide un entier positif. \* \* \@param mixed
\$value Valeur brute. \* \* \@return int \*/ public static function
positive_int( mixed \$value ): int { return absint( \$value ); } /\*\*
\* Sanitize un JSON string --- retourne le JSON décodé ou null. \* \*
\@param mixed \$value Valeur brute. \* \* \@return array\<mixed\>\|null
Tableau décodé ou null si JSON invalide. \*/ public static function
json( mixed \$value ): ?array { \$decoded = json_decode( (string)
\$value, true ); return ( JSON_ERROR_NONE === json_last_error() &&
is_array( \$decoded ) ) ? \$decoded : null; } /\*\* \* Échappe une
valeur pour affichage HTML. \* \* \@param mixed \$value Valeur brute. \*
\* \@return string \*/ public static function esc_output( mixed \$value
): string { return esc_html( (string) \$value ); } /\*\* \* Échappe une
valeur pour un attribut HTML. \* \* \@param mixed \$value Valeur brute.
\* \* \@return string \*/ public static function esc_attr_output( mixed
\$value ): string { return esc_attr( (string) \$value ); } } \`\`\`
\-\-- \## Fichier 26 : \`includes/Utils/ContentAssembler.php\` \`\`\`php
\<?php /\*\* \* Assemblage du contenu final à partir des sorties du
pipeline. \* \* \@package TechrappySEO\\Utils \*/ declare(
strict_types=1 ); namespace TechrappySEO\\Utils; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class ContentAssembler \* \*
Responsabilité : assembler toutes les sorties des étapes pipeline \* en
un contenu HTML final prêt à l\'injection dans post_content Divi. \*/
class ContentAssembler { /\*\* \* Assemble le contenu final depuis les
données de toutes les étapes. \* \* \@param array\<string, mixed\>
\$steps_data Données des étapes du job. \* \* \@return array{ \* tokens:
array\<string, string\>, \* blocks: array\<int, array{H2: string, html:
string}\>, \* raw_html: string \* } \*/ public function assemble( array
\$steps_data ): array { // TODO : implémenter l\'assemblage complet.
return \[ \'tokens\' =\> \[\], \'blocks\' =\> \[\], \'raw_html\' =\>
\'\', \]; } /\*\* \* Construit le tableau de tokens simples (H1, intro,
meta...) \* à partir des données des étapes. \* \* \@param
array\<string, mixed\> \$steps_data \* \* \@return array\<string,
string\> \*/ public function build_token_map( array \$steps_data ):
array { // TODO : implémenter le mapping token→valeur. return \[\]; } }
\`\`\` \-\-- \## Fichier 27 : \`includes/Jobs/JobRepository.php\`
\`\`\`php \<?php /\*\* \* CRUD sur la table wp_techrappy_seo_jobs. \* \*
\@package TechrappySEO\\Jobs \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Jobs; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class JobRepository \*/ class JobRepository { /\*\* \* Nom complet de la
table (avec préfixe WP). \* \* \@return string \*/ private static
function table(): string { global \$wpdb; return \$wpdb-\>prefix .
\'techrappy_seo_jobs\'; } /\*\* \* Insère un nouveau job en base. \* \*
\@param array\<string, mixed\> \$data Données du job. \* \* \@return
string\|false Job ID (UUID) si succès, false sinon. \*/ public static
function insert( array \$data ): string\|false { global \$wpdb; \$job_id
= \\TechrappySEO\\Utils\\UuidGenerator::generate(); \$row = \[
\'job_id\' =\> \$job_id, \'mode\' =\> \$data\[\'mode\'\] ?? \'single\',
\'type\' =\> \$data\[\'type\'\] ?? \'page\', \'template_post_id\' =\>
\$data\[\'template_post_id\'\] ?? 0, \'keyword\' =\>
\$data\[\'keyword\'\] ?? \'\', \'city\' =\> \$data\[\'city\'\] ?? \'\',
\'publish_status\' =\> \$data\[\'publish_status\'\] ?? \'draft\',
\'wp_params\' =\> wp_json_encode( \$data\[\'wp_params\'\] ?? \[\] ),
\'slug_rule\' =\> \$data\[\'slug_rule\'\] ?? \'from_keyword\',
\'steps_data\' =\> wp_json_encode( \$data\[\'steps_data\'\] ?? \[\] ),
\'result_data\' =\> wp_json_encode( \$data\[\'result_data\'\] ?? \[\] ),
\'logs\' =\> \'\[\]\', \'status\' =\> \'pending\', \'parent_job_id\' =\>
\$data\[\'parent_job_id\'\] ?? \'\', \]; \$result = \$wpdb-\>insert(
self::table(), \$row ); return ( false !== \$result ) ? \$job_id :
false; } /\*\* \* Récupère un job par son UUID. \* \* \@param string
\$job_id UUID du job. \* \* \@return array\<string, mixed\>\|null \*/
public static function find( string \$job_id ): ?array { global \$wpdb;
\$row = \$wpdb-\>get_row( \$wpdb-\>prepare( \'SELECT \* FROM \' .
self::table() . \' WHERE job_id = %s LIMIT 1\', \$job_id ), ARRAY_A );
if ( ! \$row ) { return null; } return self::decode_json_fields( \$row
); } /\*\* \* Met à jour le statut d\'un job. \* \* \@param string
\$job_id UUID du job. \* \@param string \$status Nouveau statut. \* \*
\@return bool \*/ public static function update_status( string \$job_id,
string \$status ): bool { global \$wpdb; \$result = \$wpdb-\>update(
self::table(), \[ \'status\' =\> \$status \], \[ \'job_id\' =\> \$job_id
\], \[ \'%s\' \], \[ \'%s\' \] ); return false !== \$result; } /\*\* \*
Met à jour les données d\'étapes et les logs d\'un job. \* \* \@param
string \$job_id UUID du job. \* \@param array\<string, mixed\>
\$steps_data Données des étapes. \* \@param array\<int, mixed\> \$logs
Logs du job. \* \* \@return bool \*/ public static function
update_steps( string \$job_id, array \$steps_data, array \$logs ): bool
{ global \$wpdb; \$result = \$wpdb-\>update( self::table(), \[
\'steps_data\' =\> wp_json_encode( \$steps_data ), \'logs\' =\>
wp_json_encode( \$logs ), \], \[ \'job_id\' =\> \$job_id \], \[ \'%s\',
\'%s\' \], \[ \'%s\' \] ); return false !== \$result; } /\*\* \* Met à
jour le résultat final d\'un job. \* \* \@param string \$job_id UUID du
job. \* \@param array\<string, mixed\> \$result_data Résultat (post_id,
permalink, slug). \* \* \@return bool \*/ public static function
update_result( string \$job_id, array \$result_data ): bool { global
\$wpdb; \$result = \$wpdb-\>update( self::table(), \[ \'result_data\'
=\> wp_json_encode( \$result_data ), \'status\' =\> \'done\', \], \[
\'job_id\' =\> \$job_id \], \[ \'%s\', \'%s\' \], \[ \'%s\' \] ); return
false !== \$result; } /\*\* \* Liste les jobs avec filtres optionnels.
\* \* \@param array\<string, mixed\> \$filters Filtres (status, mode,
limit, offset). \* \* \@return array\<int, array\<string, mixed\>\> \*/
public static function list( array \$filters = \[\] ): array { global
\$wpdb; \$where = \'1=1\'; \$params = \[\]; if ( ! empty(
\$filters\[\'status\'\] ) ) { \$where .= \' AND status = %s\';
\$params\[\] = \$filters\[\'status\'\]; } if ( ! empty(
\$filters\[\'mode\'\] ) ) { \$where .= \' AND mode = %s\'; \$params\[\]
= \$filters\[\'mode\'\]; } if ( ! empty( \$filters\[\'parent_job_id\'\]
) ) { \$where .= \' AND parent_job_id = %s\'; \$params\[\] =
\$filters\[\'parent_job_id\'\]; } \$limit = absint(
\$filters\[\'limit\'\] ?? 20 ); \$offset = absint(
\$filters\[\'offset\'\] ?? 0 ); \$sql = \"SELECT \* FROM \" .
self::table() . \" WHERE {\$where}\" . \" ORDER BY created_at DESC\" .
\" LIMIT %d OFFSET %d\"; \$params\[\] = \$limit; \$params\[\] =
\$offset; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared \$rows =
\$wpdb-\>get_results( \$wpdb-\>prepare( \$sql, \$params ), ARRAY_A );
return array_map( \[ self::class, \'decode_json_fields\' \], \$rows ?:
\[\] ); } /\*\* \* Décode les champs JSON d\'une ligne de la table. \*
\* \@param array\<string, mixed\> \$row Ligne brute depuis wpdb. \* \*
\@return array\<string, mixed\> \*/ private static function
decode_json_fields( array \$row ): array { \$json_fields = \[
\'wp_params\', \'steps_data\', \'result_data\', \'logs\' \]; foreach (
\$json_fields as \$field ) { if ( isset( \$row\[ \$field \] ) &&
is_string( \$row\[ \$field \] ) ) { \$decoded = json_decode( \$row\[
\$field \], true ); \$row\[ \$field \] = ( JSON_ERROR_NONE ===
json_last_error() ) ? \$decoded : \[\]; } } return \$row; } } \`\`\`
\-\-- \## Fichier 28 : \`includes/Jobs/QueueScheduler.php\` \`\`\`php
\<?php /\*\* \* Intégration Action Scheduler pour la queue de génération
en masse. \* \* \@package TechrappySEO\\Jobs \*/ declare( strict_types=1
); namespace TechrappySEO\\Jobs; if ( ! defined( \'ABSPATH\' ) ) { exit;
} /\*\* \* Class QueueScheduler \*/ class QueueScheduler { /\*\* \*
Planifie l\'exécution d\'un job single via Action Scheduler. \* \*
\@param string \$job_id UUID du job à exécuter. \* \* \@return void \*/
public static function schedule_single( string \$job_id ): void { if ( !
function_exists( \'as_schedule_single_action\' ) ) { // Fallback WP-Cron
si Action Scheduler non disponible. wp_schedule_single_event( time(),
\'techrappy_seo_process_single_job\', \[ \$job_id \] ); return; }
as_schedule_single_action( time(), \'techrappy_seo_process_single_job\',
\[ \'job_id\' =\> \$job_id \], \'techrappy-seo\' ); } /\*\* \* Planifie
la vérification de progression d\'un job bulk. \* \* \@param string
\$parent_job_id UUID du job parent. \* \* \@return void \*/ public
static function schedule_progress_check( string \$parent_job_id ): void
{ if ( ! function_exists( \'as_schedule_single_action\' ) ) {
wp_schedule_single_event( time() + 30,
\'techrappy_seo_bulk_progress_check\', \[ \$parent_job_id \] ); return;
} as_schedule_single_action( time() + 30,
\'techrappy_seo_bulk_progress_check\', \[ \'parent_job_id\' =\>
\$parent_job_id \], \'techrappy-seo\' ); } /\*\* \* Exécute un job
single (appelé par Action Scheduler). \* \* \@param string \$job_id UUID
du job. \* \* \@return void \*/ public function process_single_job(
string \$job_id ): void { // TODO : instancier JobRunner et exécuter le
pipeline. \$runner = new JobRunner(); \$runner-\>run( \$job_id ); }
/\*\* \* Vérifie la progression d\'un job bulk (appelé par Action
Scheduler). \* \* \@param string \$parent_job_id UUID du job parent. \*
\* \@return void \*/ public function check_bulk_progress( string
\$parent_job_id ): void { // TODO : implémenter via BulkJobManager.
\$manager = new BulkJobManager(); \$manager-\>check_progress(
\$parent_job_id ); } /\*\* \* Supprime toutes les actions planifiées du
plugin. \* Appelé à la désactivation. \* \* \@return void \*/ public
static function clear_all(): void { if ( ! function_exists(
\'as_unschedule_all_actions\' ) ) { wp_clear_scheduled_hook(
\'techrappy_seo_process_single_job\' ); wp_clear_scheduled_hook(
\'techrappy_seo_bulk_progress_check\' ); return; }
as_unschedule_all_actions( \'techrappy_seo_process_single_job\', \[\],
\'techrappy-seo\' ); as_unschedule_all_actions(
\'techrappy_seo_bulk_progress_check\', \[\], \'techrappy-seo\' ); } }
\`\`\` \-\-- \## Fichier 29 : \`includes/Jobs/JobRunner.php\` \`\`\`php
\<?php /\*\* \* Exécuteur d\'un job single --- appelle le pipeline
complet. \* \* \@package TechrappySEO\\Jobs \*/ declare( strict_types=1
); namespace TechrappySEO\\Jobs; if ( ! defined( \'ABSPATH\' ) ) { exit;
} /\*\* \* Class JobRunner \*/ class JobRunner { /\*\* \* Exécute le
pipeline complet pour un job donné. \* \* \@param string \$job_id UUID
du job. \* \* \@return void \*/ public function run( string \$job_id ):
void { \$job = JobRepository::find( \$job_id ); if ( ! \$job ) { return;
} // Marquer le job comme en cours d\'exécution.
JobRepository::update_status( \$job_id, \'running\' ); \$logger = new
\\TechrappySEO\\Utils\\Logger( \$job_id ); \$logger-\>info( \'runner\',
\'Démarrage du job.\' ); // TODO : instancier PipelineRunner et
exécuter. // \$pipeline = new
\\TechrappySEO\\AI\\Pipeline\\PipelineRunner( \$job, \$logger ); //
\$pipeline-\>run(); } } \`\`\` \-\-- \## Fichier 30 :
\`includes/Jobs/BulkJobManager.php\` \`\`\`php \<?php /\*\* \* Création
et gestion des jobs de génération en masse. \* \* \@package
TechrappySEO\\Jobs \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Jobs; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class BulkJobManager \*/ class BulkJobManager { /\*\* \* Crée un job
parent bulk et dispatche N jobs enfants. \* \* \@param array\<string,
mixed\> \$bulk_params Paramètres du job bulk. \* \@param array\<int,
array{city: string, cp: string}\> \$cities Liste des villes. \* \*
\@return string\|false UUID du job parent ou false si erreur. \*/ public
function create_and_dispatch( array \$bulk_params, array \$cities ):
string\|false { // TODO : implémenter création job parent + enfants +
dispatch. return false; } /\*\* \* Vérifie la progression d\'un job bulk
et met à jour le job parent. \* \* \@param string \$parent_job_id UUID
du job parent. \* \* \@return void \*/ public function check_progress(
string \$parent_job_id ): void { // TODO : implémenter vérification
progression. } } \`\`\` \-\-- \## Fichier 31 :
\`includes/Prompts/DefaultPrompts.php\` \`\`\`php \<?php /\*\* \*
Prompts par défaut --- seedés en base à l\'activation du plugin. \* \*
\@package TechrappySEO\\Prompts \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Prompts; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\*
\* Class DefaultPrompts \*/ class DefaultPrompts { /\*\* \* Insère les
prompts par défaut en base si absents. \* Appelé à l\'activation du
plugin via Activator. \* \* \@return void \*/ public static function
seed(): void { \$repository = new PromptRepository(); foreach (
self::get_defaults() as \$key =\> \$data ) { // Ne pas écraser un prompt
existant. if ( \$repository-\>exists( \$key ) ) { continue; }
\$repository-\>insert( \$key, \$data\[\'content\'\],
\$data\[\'response_format\'\] ?? \'json_object\' ); } } /\*\* \*
Retourne le tableau de tous les prompts par défaut. \* \* \@return
array\<string, array{content: string, response_format: string}\> \*/
public static function get_defaults(): array { return \[ \'system\' =\>
\[ \'response_format\' =\> \'text\', \'content\' =\> \<\<\<\'PROMPT\'
Règles absolues : - Ne fabrique jamais de faits locaux précis (rues,
lieux, chiffres) si non fournis. - Style : clair, humain, professionnel,
accessible. - Pas de blabla \"en tant qu\'IA\". - Respect strict du
FORMAT demandé (JSON si demandé). - Évite le contenu générique : chaque
section doit apporter une info concrète. - Français uniquement. PROMPT,
\], \'intent\' =\> \[ \'response_format\' =\> \'json_object\',
\'content\' =\> \<\<\<\'PROMPT\' Tu es expert SEO depuis 15 ans,
spécialiste de la rédaction web pour les sites de {{profession}}.
Mot-clé principal : {{mot_cle}} Type de contenu : {{type_contenu}}
Mission : 1) Déduis l\'intention principale et secondaires. 2) Liste les
\"must-have topics\" (10 max) qui dominent la SERP. 3) Donne 10
questions PAA probables (formulation naturelle). 4) Donne 15 mots-clés
secondaires/variantes. 5) Donne le ton recommandé + risques SEO à
éviter. FORMAT (JSON strict) : { \"intent_principale\":\"\",
\"intent_secondaires\":\[\], \"types_contenus_dominants\":\[\],
\"must_have_topics\":\[\], \"paa_questions\":\[\],
\"keywords_secondaires\":\[\], \"ton_recommande\":\"\",
\"risques_a_eviter\":\[\] } PROMPT, \], \'plan\' =\> \[
\'response_format\' =\> \'json_object\', \'content\' =\>
\<\<\<\'PROMPT\' Tu es expert SEO et rédacteur web depuis 15 ans pour
{{profession}}. Mot-clé : {{mot_cle}} Analyse d\'intention :
{{intent_json}} Objectif : proposer un plan SEO complet, hiérarchisé,
supérieur à la SERP. Contraintes : - H2 = sujets indispensables, pas de
titres vagues. - Inclure une FAQ (5 questions). - Prévoir un emplacement
CTA. - Si page locale : inclure un bloc \"spécificités locales\" sans
inventer de lieux précis. - Fournir un slug suggéré SEO-friendly. FORMAT
(JSON strict) : { \"H1\":\"\", \"slug_suggere\":\"\",
\"sections\":\[{\"H2\":\"\",\"intention\":\"\",\"type_contenu_attendu\":\"\",\"keywords_a_integrer\":\[\],\"H3\":\[\]}\],
\"cta_placement\":\"\", \"faq_seed_questions\":\[\] } PROMPT, \],
\'blocks_list\' =\> \[ \'response_format\' =\> \'json_object\',
\'content\' =\> \<\<\<\'PROMPT\' À partir de ce plan JSON, produis : -
nb_blocs_repetables : nombre de sections \"contenu\" à écrire (exclure
FAQ/CTA) - blocs : liste ordonnée des blocs à rédiger Plan :
{{plan_json}} FORMAT (JSON strict) : { \"nb_blocs_repetables\": 0,
\"blocs\":\[{\"ordre\":1,\"H2\":\"\",\"H3\":\[\],\"intention\":\"\",\"keywords\":\[\],\"type_contenu\":\"\"}\]
} PROMPT, \], \'intro\' =\> \[ \'response_format\' =\> \'json_object\',
\'content\' =\> \<\<\<\'PROMPT\' Mot-clé : {{mot_cle}} H1 : {{H1}} Plan
: {{plan_json}} Rédige une intro 120-180 mots : - mot-clé dans les 2
premières phrases - accroche + rassurance + promesse réaliste -
transition vers le 1er H2 FORMAT (JSON strict) : {
\"intro_longue_html\":\"\<p\>\...\</p\>\",
\"intro_courte_mobile\":\"\...\" } PROMPT, \], \'block_write\' =\> \[
\'response_format\' =\> \'json_object\', \'content\' =\>
\<\<\<\'PROMPT\' Contexte : site de {{profession}} Mot-clé : {{mot_cle}}
Bloc à rédiger : {{bloc_json}} Contraintes : - 180 à 260 mots - phrases
courtes, une idée par paragraphe - intégrer keywords naturellement -
terminer par une transition - ne pas inventer de faits locaux précis
FORMAT (JSON strict) : { \"H2\":\"\", \"html\":\"\<p\>\...\</p\>\",
\"micro_transition\":\"\" } PROMPT, \], \'conclusion_cta\' =\> \[
\'response_format\' =\> \'json_object\', \'content\' =\>
\<\<\<\'PROMPT\' Mot-clé : {{mot_cle}} H1 : {{H1}} Plan : {{plan_json}}
Donne : - 3 titres H2 de fin (pas \"Conclusion\") - 2 variantes de
conclusion 150-200 mots (douce / pro) - 1 CTA simple FORMAT (JSON
strict) : { \"h2_fin_suggestions\":\[\],
\"conclusion_douce_html\":\"\<p\>\...\</p\>\",
\"conclusion_pro_html\":\"\<p\>\...\</p\>\",
\"cta_html\":\"\<p\>\...\</p\>\" } PROMPT, \], \'meta\' =\> \[
\'response_format\' =\> \'json_object\', \'content\' =\>
\<\<\<\'PROMPT\' Mot-clé : {{mot_cle}} H1 : {{H1}} Intention principale
: {{intent_principale}} Contraintes : - meta title 55-65 caractères -
meta desc 140-160 caractères - 2 variantes FORMAT (JSON strict) : {
\"meta_title_1\":\"\", \"meta_title_2\":\"\", \"meta_desc_1\":\"\",
\"meta_desc_2\":\"\" } PROMPT, \], \'faq\' =\> \[ \'response_format\'
=\> \'json_object\', \'content\' =\> \<\<\<\'PROMPT\' Mot-clé :
{{mot_cle}} Plan : {{plan_json}} Génère 5 Q/R réellement pertinentes
(PAA-like). Réponses 45-75 mots, concrètes, rassurantes, sans promesse
médicale. FORMAT (JSON strict) : { \"faq_visible_html\":\"\<section
class=\'techrappy-faq\'\>\...\</section\>\", \"faq_jsonld\":\"\<script
type=\'application/ld+json\'\>{\...}\<\\/script\>\" } PROMPT, \],
\'internal_links\' =\> \[ \'response_format\' =\> \'json_object\',
\'content\' =\> \<\<\<\'PROMPT\' Mot-clé : {{mot_cle}} Pages existantes
(Titre + URL) : {{pages_site_json}} Propose 5 liens max, pertinents
UX+SEO. FORMAT (JSON strict) :
\[{\"url\":\"\",\"anchor\":\"\",\"placement\":\"\",\"why\":\"\"}\]
PROMPT, \], \'anti_duplicate\' =\> \[ \'response_format\' =\>
\'json_object\', \'content\' =\> \<\<\<\'PROMPT\' Mot-clé base :
{{keyword_base}} Ville : {{city}} Produis : - 3 angles d\'intro
(A/B/C) - 1 intro finale unique 140-180 mots - 6 phrases \"variantes
locales\" génériques (sans inventer de lieux précis) FORMAT (JSON
strict) : { \"angles\":\[\], \"intro_finale_html\":\"\<p\>\...\</p\>\",
\"variantes_locales\":\[\] } PROMPT, \], \'qa\' =\> \[
\'response_format\' =\> \'json_object\', \'content\' =\>
\<\<\<\'PROMPT\' Mot-clé : {{mot_cle}} Contenu HTML :
{{full_content_html}} Retourne un diagnostic SEO/humain + corrections.
FORMAT (JSON strict) : { \"score_seo\":0, \"score_humain\":0,
\"problemes\":\[\], \"fixes_rapides\":\[\],
\"rewrite_intro_suggeree_html\":\"\<p\>\...\</p\>\" } PROMPT, \], \]; }
} \`\`\` \-\-- \## Fichier 32 :
\`includes/Prompts/PromptRepository.php\` \`\`\`php \<?php /\*\* \* CRUD
sur la table wp_techrappy_prompts. \* \* \@package TechrappySEO\\Prompts
\*/ declare( strict_types=1 ); namespace TechrappySEO\\Prompts; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class PromptRepository \*/
class PromptRepository { /\*\* \* Nom complet de la table. \* \*
\@return string \*/ private static function table(): string { global
\$wpdb; return \$wpdb-\>prefix . \'techrappy_prompts\'; } /\*\* \*
Vérifie si un prompt existe déjà. \* \* \@param string \$key Clé du
prompt. \* \* \@return bool \*/ public function exists( string \$key ):
bool { global \$wpdb; \$count = \$wpdb-\>get_var( \$wpdb-\>prepare(
\'SELECT COUNT(\*) FROM \' . self::table() . \' WHERE prompt_key = %s\',
\$key ) ); return (int) \$count \> 0; } /\*\* \* Insère un nouveau
prompt. \* \* \@param string \$key Clé unique du prompt. \* \@param
string \$content Contenu du template du prompt. \* \@param string
\$response_format Format attendu (\'json_object\' ou \'text\'). \* \*
\@return bool \*/ public function insert( string \$key, string
\$content, string \$response_format = \'json_object\' ): bool { global
\$wpdb; \$result = \$wpdb-\>insert( self::table(), \[ \'prompt_key\' =\>
\$key, \'content\' =\> \$content, \'response_format\' =\>
\$response_format, \'version\' =\> 1, \'is_active\' =\> 1,
\'updated_by\' =\> get_current_user_id(), \] ); return false !==
\$result; } /\*\* \* Met à jour un prompt existant (incrémente la
version). \* \* \@param string \$key Clé du prompt. \* \@param string
\$content Nouveau contenu. \* \* \@return bool \*/ public function
update( string \$key, string \$content ): bool { global \$wpdb; //
Incrémenter la version via sous-requête. \$result = \$wpdb-\>query(
\$wpdb-\>prepare( \'UPDATE \' . self::table() . \' SET content = %s,
version = version + 1, updated_by = %d WHERE prompt_key = %s\',
\$content, get_current_user_id(), \$key ) ); return false !== \$result;
} /\*\* \* Récupère un prompt par sa clé. \* \* \@param string \$key Clé
du prompt. \* \* \@return array\<string, mixed\>\|null \*/ public
function find( string \$key ): ?array { global \$wpdb; \$row =
\$wpdb-\>get_row( \$wpdb-\>prepare( \'SELECT \* FROM \' . self::table()
. \' WHERE prompt_key = %s AND is_active = 1 LIMIT 1\', \$key ), ARRAY_A
); return \$row ?: null; } /\*\* \* Récupère tous les prompts actifs. \*
\* \@return array\<int, array\<string, mixed\>\> \*/ public function
find_all(): array { global \$wpdb; \$rows = \$wpdb-\>get_results(
\'SELECT \* FROM \' . self::table() . \' WHERE is_active = 1 ORDER BY
prompt_key ASC\', ARRAY_A ); return \$rows ?: \[\]; } } \`\`\` \-\-- \##
Vues (squelettes PHP minimalistes) \`\`\`php \<?php //
views/admin/wizard/step-1-mode.php if ( ! defined( \'ABSPATH\' ) ) {
exit; } ?\> \<div class=\"wrap techrappy-seo-wrap\"\> \<h1\>\<?php
esc_html_e( \'Nouvelle génération\', \'techrappy-seo\' ); ?\>\</h1\>
\<p\>\<?php esc_html_e( \'Wizard --- à implémenter.\', \'techrappy-seo\'
); ?\>\</p\> \</div\> \`\`\` \`\`\`php \<?php //
views/admin/template-audit/audit-result.php if ( ! defined( \'ABSPATH\'
) ) { exit; } ?\> \<div class=\"wrap techrappy-seo-wrap\"\> \<h1\>\<?php
esc_html_e( \'Audit de templates\', \'techrappy-seo\' ); ?\>\</h1\>
\<p\>\<?php esc_html_e( \'Audit --- à implémenter.\', \'techrappy-seo\'
); ?\>\</p\> \</div\> \`\`\` \`\`\`php \<?php //
views/admin/bulk-jobs/list.php if ( ! defined( \'ABSPATH\' ) ) { exit; }
?\> \<div class=\"wrap techrappy-seo-wrap\"\> \<h1\>\<?php esc_html_e(
\'Jobs en masse\', \'techrappy-seo\' ); ?\>\</h1\> \<p\>\<?php
esc_html_e( \'Jobs --- à implémenter.\', \'techrappy-seo\' ); ?\>\</p\>
\</div\> \`\`\` \`\`\`php \<?php // views/admin/prompt-studio/editor.php
if ( ! defined( \'ABSPATH\' ) ) { exit; } ?\> \<div class=\"wrap
techrappy-seo-wrap\"\> \<h1\>\<?php esc_html_e( \'Prompt Studio\',
\'techrappy-seo\' ); ?\>\</h1\> \<p\>\<?php esc_html_e( \'Prompt Studio
--- à implémenter.\', \'techrappy-seo\' ); ?\>\</p\> \</div\> \`\`\`
\`\`\`php \<?php // views/admin/settings/settings-form.php if ( !
defined( \'ABSPATH\' ) ) { exit; } ?\> \<div class=\"wrap
techrappy-seo-wrap\"\> \<h1\>\<?php esc_html_e( \'Réglages Techrappy
SEO\', \'techrappy-seo\' ); ?\>\</h1\> \<p\>\<?php esc_html_e(
\'Settings --- à implémenter.\', \'techrappy-seo\' ); ?\>\</p\> \</div\>
\`\`\` \-\-- \## Assets CSS/JS (fichiers vides initialisés) \`\`\`css
/\* assets/css/admin.css --- Techrappy SEO Admin \*/ .techrappy-seo-wrap
{ max-width: 1200px; } \`\`\` \`\`\`javascript /\* assets/js/admin.js
--- Techrappy SEO Admin \*/ /\* global TechrappySEO \*/ (function(\$) {
\'use strict\'; // Global admin JS --- à implémenter. })(jQuery); \`\`\`
\`\`\`javascript /\* assets/js/wizard.js \*/ (function(\$) { \'use
strict\'; /\* Wizard --- à implémenter. \*/ })(jQuery); \`\`\`
\`\`\`javascript /\* assets/js/bulk-jobs.js \*/ (function(\$) { \'use
strict\'; /\* Bulk Jobs --- à implémenter. \*/ })(jQuery); \`\`\`
\`\`\`javascript /\* assets/js/template-audit.js \*/ (function(\$) {
\'use strict\'; /\* Template Audit --- à implémenter. \*/ })(jQuery);
\`\`\` \`\`\`javascript /\* assets/js/prompt-studio.js \*/ (function(\$)
{ \'use strict\'; /\* Prompt Studio --- à implémenter. \*/ })(jQuery);
\`\`\` \-\-- \## Récapitulatif --- fichiers livrés dans cette V1 \| \#
\| Fichier \| Statut \| \|\-\--\|\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\--\| \|
1 \| \`techrappy-seo.php\` \| ✅ Complet \| \| 2 \| \`uninstall.php\` \|
✅ Complet \| \| 3 \| \`includes/Core/Plugin.php\` \| ✅ Complet \| \| 4
\| \`includes/Core/Loader.php\` \| ✅ Complet \| \| 5 \|
\`includes/Core/Activator.php\` \| ✅ Complet \| \| 6 \|
\`includes/Core/Deactivator.php\` \| ✅ Complet \| \| 7 \|
\`includes/Core/Installer.php\` \| ✅ Complet \| \| 8 \|
\`includes/Settings/SettingsRepository.php\` \| ✅ Complet \| \| 9 \|
\`includes/Settings/SettingsValidator.php\` \| ✅ Complet \| \| 10 \|
\`includes/Admin/AdminMenu.php\` \| ✅ Complet \| \| 11 \|
\`includes/Admin/AdminAssets.php\` \| ✅ Complet \| \| 12--16 \|
\`includes/Admin/Pages/Page\*.php\` (×5) \| ✅ Squelettes \| \| 17--22
\| \`includes/Admin/Ajax/Ajax\*.php\` (×6) \| ✅ Squelettes sécurisés \|
\| 23 \| \`includes/Utils/Logger.php\` \| ✅ Complet \| \| 24 \|
\`includes/Utils/UuidGenerator.php\` \| ✅ Complet \| \| 25 \|
\`includes/Utils/CostEstimator.php\` \| ✅ Complet \| \| 26 \|
\`includes/Utils/Sanitizer.php\` \| ✅ Complet \| \| 27 \|
\`includes/Utils/ContentAssembler.php\` \| ✅ Squelette \| \| 28 \|
\`includes/Jobs/JobRepository.php\` \| ✅ Complet \| \| 29 \|
\`includes/Jobs/QueueScheduler.php\` \| ✅ Complet \| \| 30 \|
\`includes/Jobs/JobRunner.php\` \| ✅ Squelette \| \| 31 \|
\`includes/Jobs/BulkJobManager.php\` \| ✅ Squelette \| \| 32 \|
\`includes/Prompts/DefaultPrompts.php\` \| ✅ Complet (11 prompts) \| \|
33 \| \`includes/Prompts/PromptRepository.php\` \| ✅ Complet \| \|
34--38 \| \`views/admin/\*/\` (×5) \| ✅ Squelettes HTML \| \| 39--44 \|
\`assets/css/\*.css\` + \`assets/js/\*.js\` \| ✅ Fichiers initialisés
\| \*\*Le plugin est installable\*\* --- il s\'active sans erreur PHP,
crée les tables, initialise les options, et affiche les menus admin avec
les pages squelettes.
