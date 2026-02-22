\# V3 --- Module Prompt Studio \-\-- \## Fichier 1 :
\`includes/Admin/Pages/PagePromptStudio.php\` \`\`\`php \<?php /\*\* \*
Page Admin : Prompt Studio. \* \* Responsabilité : orchestrer le rendu
des 4 onglets du Prompt Studio \* (Prompts, Variables, Tester,
Historique) et déléguer à la vue. \* \* \@package
TechrappySEO\\Admin\\Pages \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Admin\\Pages; use TechrappySEO\\AI\\PromptManager; use
TechrappySEO\\AI\\PromptRenderer; use
TechrappySEO\\Prompts\\PromptRepository; if ( ! defined( \'ABSPATH\' ) )
{ exit; } /\*\* \* Class PagePromptStudio \*/ class PagePromptStudio {
/\*\* \* Onglets disponibles du Prompt Studio. \* \* \@var
array\<string, string\> slug =\> label \*/ private array \$tabs = \[
\'prompts\' =\> \'Prompts\', \'tester\' =\> \'Tester\', \'variables\'
=\> \'Variables\', \]; /\*\* \* Onglet actif courant. \* \* \@var string
\*/ private string \$active_tab; /\*\* \* Instance PromptManager. \* \*
\@var PromptManager \*/ private PromptManager \$manager; /\*\* \*
Instance PromptRenderer. \* \* \@var PromptRenderer \*/ private
PromptRenderer \$renderer; /\*\* \* Constructeur. \*/ public function
\_\_construct() { \$this-\>manager = new PromptManager();
\$this-\>renderer = new PromptRenderer(); \$this-\>active_tab =
\$this-\>resolve_active_tab(); } /\*\* \* Point d\'entrée de rendu de la
page. \* \* \@return void \*/ public function render(): void { if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_die( esc_html\_\_(
\'Accès non autorisé.\', \'techrappy-seo\' ) ); } // Données communes à
tous les onglets. \$data = \[ \'tabs\' =\> \$this-\>tabs, \'active_tab\'
=\> \$this-\>active_tab, \'prompt_keys\' =\> \$this-\>get_prompt_keys(),
\'all_prompts\' =\> \$this-\>manager-\>get_all(), \'system_prompt\' =\>
\$this-\>manager-\>get_raw_template( \'system\' ), \'manager\' =\>
\$this-\>manager, \'renderer\' =\> \$this-\>renderer, \]; // Vue
principale (layout + onglets). \$this-\>render_view(
\'prompt-studio/layout\', \$data ); } /\*\* \* Retourne la liste
ordonnée des clés de prompts (hors system). \* \* \@return string\[\]
\*/ public function get_prompt_keys(): array { return \[ \'intent\',
\'plan\', \'blocks_list\', \'intro\', \'block_write\',
\'conclusion_cta\', \'meta\', \'faq\', \'internal_links\',
\'anti_duplicate\', \'qa\', \]; } /\*\* \* Retourne le label lisible
d\'une clé de prompt. \* \* \@param string \$key Clé du prompt. \* \*
\@return string \*/ public static function get_prompt_label( string
\$key ): string { \$labels = \[ \'system\' =\> \'⚙️ System prompt
(global)\', \'intent\' =\> \'1 --- Analyse intention (SERP)\', \'plan\'
=\> \'2 --- Plan H1/H2/H3\', \'blocks_list\' =\> \'2B --- Liste des
blocs\', \'intro\' =\> \'3 --- Introduction SEO\', \'block_write\' =\>
\'4 --- Rédaction bloc H2\', \'conclusion_cta\' =\> \'5 --- Conclusion +
CTA\', \'meta\' =\> \'6 --- Meta title + description\', \'faq\' =\> \'7
--- FAQ HTML + JSON-LD\', \'internal_links\' =\> \'8 --- Maillage
interne\', \'anti_duplicate\' =\> \'9 --- Anti-duplicate (bulk)\',
\'qa\' =\> \'10 --- QA scoring\', \]; return \$labels\[ \$key \] ??
\$key; } /\*\* \* Résout l\'onglet actif depuis le paramètre GET. \* \*
\@return string \*/ private function resolve_active_tab(): string {
\$tab = sanitize_key( \$\_GET\[\'tab\'\] ?? \'prompts\' ); //
phpcs:ignore WordPress.Security.NonceVerification return
array_key_exists( \$tab, \$this-\>tabs ) ? \$tab : \'prompts\'; } /\*\*
\* Inclut une vue en injectant les données. \* \* \@param string
\$view_slug Chemin relatif dans views/admin/ (sans .php). \* \@param
array\<string, mixed\> \$data Données injectées dans la vue. \* \*
\@return void \*/ private function render_view( string \$view_slug,
array \$data ): void { // Extraire les variables pour les rendre
disponibles dans la vue. extract( \$data, EXTR_SKIP ); // phpcs:ignore
WordPress.PHP.DontExtract \$view_path = TECHRAPPY_SEO_PATH .
\'views/admin/\' . \$view_slug . \'.php\'; if ( ! file_exists(
\$view_path ) ) { /\* translators: %s: chemin de la vue manquante \*/
printf( \'\<div class=\"notice notice-error\"\>\<p\>%s\</p\>\</div\>\',
esc_html( sprintf( \_\_( \'Vue introuvable : %s\', \'techrappy-seo\' ),
\$view_slug ) ) ); return; } include \$view_path; } } \`\`\` \-\-- \##
Fichier 2 : \`includes/Admin/Ajax/AjaxPromptStudio.php\` \`\`\`php
\<?php /\*\* \* Handlers AJAX du Prompt Studio. \* \* Actions couvertes
: \* - techrappy_save_prompt : sauvegarde un prompt (contenu + format)
\* - techrappy_save_system_prompt : sauvegarde le system prompt global
\* - techrappy_reset_prompts : réinitialise tous les prompts aux
defaults \* - techrappy_test_prompt : teste un prompt via AIClient \* -
techrappy_get_prompt_vars : retourne les variables d\'un prompt \* \*
\@package TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 );
namespace TechrappySEO\\Admin\\Ajax; use TechrappySEO\\AI\\AIClient; use
TechrappySEO\\AI\\PromptManager; use TechrappySEO\\AI\\PromptRenderer;
use TechrappySEO\\Prompts\\PromptRepository; use
TechrappySEO\\Prompts\\DefaultPrompts; use TechrappySEO\\Utils\\Logger;
use TechrappySEO\\Utils\\UuidGenerator; if ( ! defined( \'ABSPATH\' ) )
{ exit; } /\*\* \* Class AjaxPromptStudio \*/ class AjaxPromptStudio {
// ───────────────────────────────────────── // Sauvegarde d\'un prompt
// ───────────────────────────────────────── /\*\* \* Sauvegarde le
contenu d\'un prompt. \* \* POST params : \* - nonce : nonce
\'techrappy_seo_prompt_studio\' \* - prompt_key : clé du prompt (ex:
\'intent\') \* - content : contenu du template \* - response_format :
\'json_object\' \| \'text\' \* \* \@return void \*/ public function
handle_save(): void { \$this-\>verify_request(
\'techrappy_seo_prompt_studio\' ); \$prompt_key = sanitize_key(
\$\_POST\[\'prompt_key\'\] ?? \'\' ); \$content =
sanitize_textarea_field( wp_unslash( \$\_POST\[\'content\'\] ?? \'\' )
); \$response_format = sanitize_key( \$\_POST\[\'response_format\'\] ??
\'json_object\' ); // Validation de la clé. if ( empty( \$prompt_key ) )
{ wp_send_json_error( \[ \'message\' =\> \_\_( \'Clé de prompt
manquante.\', \'techrappy-seo\' ), \], 400 ); } // Validation du
contenu. if ( empty( \$content ) ) { wp_send_json_error( \[ \'message\'
=\> \_\_( \'Le contenu du prompt ne peut pas être vide.\',
\'techrappy-seo\' ), \], 400 ); } // Validation du format de réponse.
\$allowed_formats = \[ \'json_object\', \'text\' \]; if ( ! in_array(
\$response_format, \$allowed_formats, true ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Format de réponse invalide.\', \'techrappy-seo\'
), \], 400 ); } // Sauvegarder via PromptRepository. \$repository = new
PromptRepository(); if ( \$repository-\>exists( \$prompt_key ) ) {
\$success = \$repository-\>update( \$prompt_key, \$content ); } else {
\$success = \$repository-\>insert( \$prompt_key, \$content,
\$response_format ); } if ( ! \$success ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Erreur lors de la sauvegarde en base de
données.\', \'techrappy-seo\' ), \], 500 ); } // Invalider le cache du
PromptManager. \$manager = new PromptManager();
\$manager-\>invalidate_cache( \$prompt_key ); // Extraire les variables
du nouveau contenu. \$renderer = new PromptRenderer(); \$variables =
\$renderer-\>extract_variables( \$content ); wp_send_json_success( \[
\'message\' =\> \_\_( \'Prompt sauvegardé avec succès.\',
\'techrappy-seo\' ), \'prompt_key\' =\> \$prompt_key, \'variables\' =\>
\$variables, \] ); } // ───────────────────────────────────────── //
Sauvegarde du system prompt // ─────────────────────────────────────────
/\*\* \* Sauvegarde le system prompt global. \* \* POST params : \* -
nonce : nonce \'techrappy_seo_prompt_studio\' \* - content : contenu du
system prompt \* \* \@return void \*/ public function
handle_save_system(): void { \$this-\>verify_request(
\'techrappy_seo_prompt_studio\' ); \$content = sanitize_textarea_field(
wp_unslash( \$\_POST\[\'content\'\] ?? \'\' ) ); if ( empty( \$content )
) { wp_send_json_error( \[ \'message\' =\> \_\_( \'Le system prompt ne
peut pas être vide.\', \'techrappy-seo\' ), \], 400 ); } \$repository =
new PromptRepository(); if ( \$repository-\>exists( \'system\' ) ) {
\$success = \$repository-\>update( \'system\', \$content ); } else {
\$success = \$repository-\>insert( \'system\', \$content, \'text\' ); }
if ( ! \$success ) { wp_send_json_error( \[ \'message\' =\> \_\_(
\'Erreur lors de la sauvegarde du system prompt.\', \'techrappy-seo\' ),
\], 500 ); } \$manager = new PromptManager();
\$manager-\>invalidate_cache( \'system\' ); wp_send_json_success( \[
\'message\' =\> \_\_( \'System prompt sauvegardé.\', \'techrappy-seo\'
), \] ); } // ───────────────────────────────────────── //
Réinitialisation aux defaults //
───────────────────────────────────────── /\*\* \* Réinitialise tous les
prompts aux valeurs par défaut. \* Remplace le contenu existant en base
par les defaults hardcodés. \* \* POST params : \* - nonce : nonce
\'techrappy_seo_prompt_studio\' \* \* \@return void \*/ public function
handle_reset(): void { \$this-\>verify_request(
\'techrappy_seo_prompt_studio\' ); \$repository = new
PromptRepository(); \$defaults = DefaultPrompts::get_defaults();
\$reset_count = 0; \$errors = \[\]; foreach ( \$defaults as \$key =\>
\$data ) { if ( \$repository-\>exists( \$key ) ) { \$ok =
\$repository-\>update( \$key, \$data\[\'content\'\] ); } else { \$ok =
\$repository-\>insert( \$key, \$data\[\'content\'\],
\$data\[\'response_format\'\] ?? \'json_object\' ); } if ( \$ok ) {
\$reset_count++; } else { \$errors\[\] = \$key; } } // Vider le cache
complet du PromptManager. \$manager = new PromptManager();
\$manager-\>invalidate_cache(); if ( ! empty( \$errors ) ) {
wp_send_json_error( \[ \'message\' =\> sprintf( /\* translators: %s:
liste des clés en erreur \*/ \_\_( \'Réinitialisation partielle. Erreurs
sur : %s\', \'techrappy-seo\' ), implode( \', \', \$errors ) ),
\'reset_count\' =\> \$reset_count, \], 500 ); } wp_send_json_success( \[
\'message\' =\> sprintf( /\* translators: %d: nombre de prompts
réinitialisés \*/ \_\_( \'%d prompt(s) réinitialisé(s) aux valeurs par
défaut.\', \'techrappy-seo\' ), \$reset_count ), \'reset_count\' =\>
\$reset_count, \] ); } // ───────────────────────────────────────── //
Test d\'un prompt via AIClient //
───────────────────────────────────────── /\*\* \* Teste un prompt en
l\'envoyant directement à l\'API IA. \* \* POST params : \* - nonce :
nonce \'techrappy_seo_prompt_studio\' \* - prompt_key : clé du prompt à
tester \* - variables : JSON objet des variables à injecter \* -
use_saved : \'1\' pour utiliser le prompt sauvegardé, \'0\' pour le
contenu brut \* - raw_content : (optionnel) contenu brut à tester sans
sauvegarder \* \* \@return void \*/ public function handle_test(): void
{ \$this-\>verify_request( \'techrappy_seo_prompt_studio\' );
\$prompt_key = sanitize_key( \$\_POST\[\'prompt_key\'\] ?? \'\' );
\$vars_json = sanitize_textarea_field( wp_unslash(
\$\_POST\[\'variables\'\] ?? \'{}\' ) ); \$use_saved = ( \'1\' === (
\$\_POST\[\'use_saved\'\] ?? \'1\' ) ); \$raw_content =
sanitize_textarea_field( wp_unslash( \$\_POST\[\'raw_content\'\] ?? \'\'
) ); if ( empty( \$prompt_key ) ) { wp_send_json_error( \[ \'message\'
=\> \_\_( \'Clé de prompt manquante.\', \'techrappy-seo\' ), \], 400 );
} // Décoder les variables. \$variables = \[\]; if ( ! empty(
\$vars_json ) ) { \$decoded = json_decode( \$vars_json, true ); if (
JSON_ERROR_NONE === json_last_error() && is_array( \$decoded ) ) {
\$variables = \$decoded; } } // Construire le prompt rendu. \$renderer =
new PromptRenderer(); \$manager = new PromptManager(); try { if (
\$use_saved \|\| empty( \$raw_content ) ) { // Utiliser le prompt depuis
la DB/defaults. \$prompt_cfg = \$manager-\>get_rendered( \$prompt_key,
\$variables ); } else { // Tester un contenu brut non sauvegardé.
\$system_raw = \$manager-\>get_raw_template( \'system\' ); \$prompt_cfg
= \[ \'prompt\' =\> \$renderer-\>render( \$raw_content, \$variables ),
\'system\' =\> \$renderer-\>render( \$system_raw, \$variables ),
\'format\' =\> sanitize_key( \$\_POST\[\'response_format\'\] ??
\'json_object\' ), \]; } } catch ( \\RuntimeException \$e ) {
wp_send_json_error( \[ \'message\' =\> \$e-\>getMessage(), \], 400 ); }
// Vérifier que la clé API est configurée. \$api_key =
\\TechrappySEO\\Settings\\SettingsRepository::get_api_key(); if ( empty(
\$api_key ) ) { wp_send_json_error( \[ \'message\' =\> \_\_( \'Clé API
OpenAI non configurée. Rendez-vous dans Réglages.\', \'techrappy-seo\'
), \], 400 ); } // Logger temporaire pour le test (pas attaché à un
job). \$test_id = \'test\_\' . UuidGenerator::generate(); \$logger = new
Logger( \$test_id ); // Appel IA via le client centralisé. \$start_time
= microtime( true ); \$client = new AIClient( \$logger ); \$response =
\$client-\>generate( prompt: \$prompt_cfg\[\'prompt\'\], system_prompt:
\$prompt_cfg\[\'system\'\], response_format: \$prompt_cfg\[\'format\'\]
); \$duration_ms = (int) round( ( microtime( true ) - \$start_time ) \*
1000 ); if ( \$response-\>is_error() ) { wp_send_json_error( \[
\'message\' =\> \$response-\>get_error_message(), \'error_code\' =\>
\$response-\>get_error_code(), \'http_code\' =\>
\$response-\>get_http_code(), \'duration_ms\' =\> \$duration_ms,
\'logs\' =\> \$logger-\>get_logs(), \], 500 ); } // Validation JSON si
format json_object. \$json_valid = null; \$json_error = \'\';
\$parsed_data = null; if ( \'json_object\' ===
\$prompt_cfg\[\'format\'\] ) { \$parsed_data =
\$response-\>get_parsed(); \$json_valid = ( null !== \$parsed_data );
\$json_error = \$json_valid ? \'\' : json_last_error_msg(); }
wp_send_json_success( \[ \'content\' =\> \$response-\>get_content(),
\'parsed\' =\> \$parsed_data, \'json_valid\' =\> \$json_valid,
\'json_error\' =\> \$json_error, \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'total_tokens\' =\>
\$response-\>get_total_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \'logs\' =\> \$logger-\>get_logs(),
\'prompt_sent\' =\> \$prompt_cfg\[\'prompt\'\], // Pour debug. \] ); }
// ───────────────────────────────────────── // Récupération des
variables d\'un prompt // ─────────────────────────────────────────
/\*\* \* Retourne les variables {{\...}} détectées dans un prompt. \*
Utilisé par le JS pour générer dynamiquement les champs de test. \* \*
POST params : \* - nonce : nonce \'techrappy_seo_prompt_studio\' \* -
prompt_key : clé du prompt \* \* \@return void \*/ public function
handle_get_vars(): void { \$this-\>verify_request(
\'techrappy_seo_prompt_studio\' ); \$prompt_key = sanitize_key(
\$\_POST\[\'prompt_key\'\] ?? \'\' ); if ( empty( \$prompt_key ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Clé de prompt
manquante.\', \'techrappy-seo\' ), \], 400 ); } \$manager = new
PromptManager(); \$variables = \$manager-\>get_variables_for(
\$prompt_key ); wp_send_json_success( \[ \'prompt_key\' =\>
\$prompt_key, \'variables\' =\> \$variables, \] ); } //
───────────────────────────────────────── // Helper sécurité //
───────────────────────────────────────── /\*\* \* Vérifie le nonce et
la capacité de l\'utilisateur. \* Envoie une réponse JSON d\'erreur et
termine si invalide. \* \* \@param string \$nonce_action Action du nonce
à vérifier. \* \* \@return void \*/ private function verify_request(
string \$nonce_action ): void { check_ajax_referer( \$nonce_action,
\'nonce\' ); if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Accès non autorisé.\',
\'techrappy-seo\' ), \], 403 ); } } } \`\`\` \-\-- \## Fichier 3 :
\`includes/Prompts/PromptRepository.php\` --- Mise à jour
(update_format) \`\`\`php \<?php /\*\* \* CRUD sur la table
wp_techrappy_prompts. \* Mise à jour V3 : ajout de update_format() et
find_version_history(). \* \* \@package TechrappySEO\\Prompts \*/
declare( strict_types=1 ); namespace TechrappySEO\\Prompts; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class PromptRepository \*/
class PromptRepository { /\*\* \* Nom complet de la table. \* \*
\@return string \*/ private static function table(): string { global
\$wpdb; return \$wpdb-\>prefix . \'techrappy_prompts\'; } /\*\* \*
Vérifie si un prompt existe déjà. \* \* \@param string \$key Clé du
prompt. \* \@return bool \*/ public function exists( string \$key ):
bool { global \$wpdb; \$count = \$wpdb-\>get_var( \$wpdb-\>prepare(
\'SELECT COUNT(\*) FROM \' . self::table() . \' WHERE prompt_key = %s\',
\$key ) ); return (int) \$count \> 0; } /\*\* \* Insère un nouveau
prompt. \* \* \@param string \$key Clé unique du prompt. \* \@param
string \$content Contenu du template. \* \@param string
\$response_format Format attendu. \* \@return bool \*/ public function
insert( string \$key, string \$content, string \$response_format =
\'json_object\' ): bool { global \$wpdb; \$result = \$wpdb-\>insert(
self::table(), \[ \'prompt_key\' =\> \$key, \'content\' =\> \$content,
\'response_format\' =\> \$response_format, \'version\' =\> 1,
\'is_active\' =\> 1, \'updated_by\' =\> get_current_user_id(), \], \[
\'%s\', \'%s\', \'%s\', \'%d\', \'%d\', \'%d\' \] ); return false !==
\$result; } /\*\* \* Met à jour le contenu d\'un prompt existant. \*
Incrémente automatiquement la version. \* \* \@param string \$key Clé du
prompt. \* \@param string \$content Nouveau contenu. \* \@return bool
\*/ public function update( string \$key, string \$content ): bool {
global \$wpdb; \$result = \$wpdb-\>query( \$wpdb-\>prepare( \'UPDATE \'
. self::table() . \' SET content = %s, version = version + 1, updated_by
= %d WHERE prompt_key = %s\', \$content, get_current_user_id(), \$key )
); return false !== \$result; } /\*\* \* Met à jour uniquement le
response_format d\'un prompt. \* \* \@param string \$key Clé du prompt.
\* \@param string \$format Nouveau format (\'json_object\' \| \'text\').
\* \@return bool \*/ public function update_format( string \$key, string
\$format ): bool { global \$wpdb; \$allowed = \[ \'json_object\',
\'text\' \]; if ( ! in_array( \$format, \$allowed, true ) ) { return
false; } \$result = \$wpdb-\>update( self::table(), \[
\'response_format\' =\> \$format \], \[ \'prompt_key\' =\> \$key \], \[
\'%s\' \], \[ \'%s\' \] ); return false !== \$result; } /\*\* \*
Récupère un prompt par sa clé. \* \* \@param string \$key Clé du prompt.
\* \@return array\<string, mixed\>\|null \*/ public function find(
string \$key ): ?array { global \$wpdb; \$row = \$wpdb-\>get_row(
\$wpdb-\>prepare( \'SELECT \* FROM \' . self::table() . \' WHERE
prompt_key = %s AND is_active = 1 LIMIT 1\', \$key ), ARRAY_A ); return
\$row ?: null; } /\*\* \* Récupère tous les prompts actifs, triés par
prompt_key. \* \* \@return array\<int, array\<string, mixed\>\> \*/
public function find_all(): array { global \$wpdb; \$rows =
\$wpdb-\>get_results( \'SELECT \* FROM \' . self::table() . \' WHERE
is_active = 1 ORDER BY prompt_key ASC\', ARRAY_A ); return \$rows ?:
\[\]; } /\*\* \* Retourne le numéro de version actuel d\'un prompt. \*
\* \@param string \$key Clé du prompt. \* \@return int Version ou 0 si
non trouvé. \*/ public function get_version( string \$key ): int {
global \$wpdb; \$version = \$wpdb-\>get_var( \$wpdb-\>prepare( \'SELECT
version FROM \' . self::table() . \' WHERE prompt_key = %s LIMIT 1\',
\$key ) ); return (int) \$version; } } \`\`\` \-\-- \## Fichier 4 :
\`views/admin/prompt-studio/layout.php\` --- Layout principal \`\`\`php
\<?php /\*\* \* Vue : Layout principal du Prompt Studio. \* \* Variables
disponibles (injectées par PagePromptStudio::render) : \* \@var
array\<string, string\> \$tabs Onglets disponibles. \* \@var string
\$active_tab Onglet actif. \* \@var string\[\] \$prompt_keys Liste des
clés de prompts. \* \@var array\<string, mixed\> \$all_prompts Tous les
prompts chargés. \* \@var string \$system_prompt Contenu du system
prompt. \* \@var \\TechrappySEO\\AI\\PromptManager \$manager Instance
PromptManager. \* \@var \\TechrappySEO\\AI\\PromptRenderer \$renderer
Instance PromptRenderer. \* \* \@package TechrappySEO \*/ if ( !
defined( \'ABSPATH\' ) ) { exit; } \$page_url = admin_url(
\'admin.php?page=techrappy-seo-prompt-studio\' ); ?\> \<div class=\"wrap
techrappy-seo-wrap\" id=\"techrappy-prompt-studio\"\> \<h1
class=\"wp-heading-inline\"\> \<?php esc_html_e( \'Prompt Studio\',
\'techrappy-seo\' ); ?\> \</h1\> \<span class=\"title-count
theme-count\"\> \<?php echo esc_html( count( \$prompt_keys ) + 1 ); ?\>
\<?php esc_html_e( \'prompts\', \'techrappy-seo\' ); ?\> \</span\> \<hr
class=\"wp-header-end\"\> \<?php // ── Notifications AJAX (injectées
dynamiquement par JS) ── ?\> \<div id=\"techrappy-notice\"
class=\"techrappy-notice\" aria-live=\"polite\"
style=\"display:none;\"\>\</div\> \<?php // ── Navigation onglets ── ?\>
\<nav class=\"nav-tab-wrapper techrappy-tabs\" aria-label=\"\<?php
esc_attr_e( \'Onglets Prompt Studio\', \'techrappy-seo\' ); ?\>\"\>
\<?php foreach ( \$tabs as \$tab_slug =\> \$tab_label ) : ?\> \<a
href=\"\<?php echo esc_url( add_query_arg( \'tab\', \$tab_slug,
\$page_url ) ); ?\>\" class=\"nav-tab\<?php echo ( \$active_tab ===
\$tab_slug ) ? \' nav-tab-active\' : \'\'; ?\>\" aria-selected=\"\<?php
echo ( \$active_tab === \$tab_slug ) ? \'true\' : \'false\'; ?\>\"\>
\<?php echo esc_html( \$tab_label ); ?\> \</a\> \<?php endforeach; ?\>
\</nav\> \<div class=\"techrappy-tab-content\"\> \<?php // Déléguer le
rendu au bon onglet. \$tab_view = TECHRAPPY_SEO_PATH .
\'views/admin/prompt-studio/tab-\' . \$active_tab . \'.php\'; if (
file_exists( \$tab_view ) ) { include \$tab_view; } else { printf( \'\<p
class=\"notice notice-warning\"\>%s\</p\>\', esc_html\_\_( \'Onglet
introuvable.\', \'techrappy-seo\' ) ); } ?\> \</div\> \</div\>\<!\--
.techrappy-prompt-studio \--\> \`\`\` \-\-- \## Fichier 5 :
\`views/admin/prompt-studio/tab-prompts.php\` --- Onglet éditeur
\`\`\`php \<?php /\*\* \* Vue : Onglet \"Prompts\" --- éditeur de
prompts. \* \* Variables disponibles depuis layout.php : \* \@var
string\[\] \$prompt_keys \* \@var array\<string, mixed\> \$all_prompts
\* \@var string \$system_prompt \* \@var
\\TechrappySEO\\AI\\PromptRenderer \$renderer \* \* \@package
TechrappySEO \*/ if ( ! defined( \'ABSPATH\' ) ) { exit; } use
TechrappySEO\\Admin\\Pages\\PagePromptStudio; \$save_nonce =
wp_create_nonce( \'techrappy_seo_prompt_studio\' ); \$reset_nonce =
wp_create_nonce( \'techrappy_seo_prompt_studio\' ); ?\> \<div
class=\"techrappy-prompts-editor\"\> \<?php // ── Bouton reset global ──
?\> \<div class=\"techrappy-toolbar\"\> \<button type=\"button\"
id=\"techrappy-btn-reset-all\" class=\"button button-secondary\"
data-nonce=\"\<?php echo esc_attr( \$reset_nonce ); ?\>\"
data-confirm=\"\<?php esc_attr_e( \'Réinitialiser TOUS les prompts aux
valeurs par défaut ? Cette action est irréversible.\', \'techrappy-seo\'
); ?\>\"\> 🔄 \<?php esc_html_e( \'Réinitialiser tous les prompts\',
\'techrappy-seo\' ); ?\> \</button\> \<span
class=\"techrappy-toolbar-hint\"\> \<?php esc_html_e( \'Les prompts
modifiés seront écrasés par les valeurs d\\\'usine.\', \'techrappy-seo\'
); ?\> \</span\> \</div\> \<div class=\"techrappy-prompts-layout\"\>
\<?php // ── Colonne gauche : navigation prompts ── ?\> \<nav
class=\"techrappy-prompt-nav\" aria-label=\"\<?php esc_attr_e(
\'Navigation des prompts\', \'techrappy-seo\' ); ?\>\"\> \<?php //
System prompt en premier, séparé visuellement. ?\> \<a href=\"#\"
class=\"techrappy-prompt-nav-item techrappy-prompt-nav-system\"
data-key=\"system\" role=\"button\" aria-pressed=\"false\"\> \<?php echo
esc_html( PagePromptStudio::get_prompt_label( \'system\' ) ); ?\> \</a\>
\<div class=\"techrappy-prompt-nav-separator\"\>\</div\> \<?php foreach
( \$prompt_keys as \$key ) : \$prompt_data = \$all_prompts\[ \$key \] ??
null; \$version = \$prompt_data ? (int) \$prompt_data\[\'version\'\] :
0; ?\> \<a href=\"#\" class=\"techrappy-prompt-nav-item\"
data-key=\"\<?php echo esc_attr( \$key ); ?\>\" role=\"button\"
aria-pressed=\"false\"\> \<?php echo esc_html(
PagePromptStudio::get_prompt_label( \$key ) ); ?\> \<?php if ( \$version
\> 0 ) : ?\> \<span class=\"techrappy-version-badge\" title=\"\<?php
esc_attr_e( \'Version\', \'techrappy-seo\' ); ?\>\"\> v\<?php echo
esc_html( (string) \$version ); ?\> \</span\> \<?php endif; ?\> \</a\>
\<?php endforeach; ?\> \</nav\> \<?php // ── Colonne droite : éditeur ──
?\> \<div class=\"techrappy-prompt-editor-pane\"\> \<?php // ──
Placeholder quand aucun prompt sélectionné ── ?\> \<div
class=\"techrappy-prompt-placeholder\"
id=\"techrappy-prompt-placeholder\"\> \<p\> \<?php esc_html_e( \'←
Sélectionnez un prompt dans la liste pour l\\\'éditer.\',
\'techrappy-seo\' ); ?\> \</p\> \</div\> \<?php // ── Formulaire
d\'édition (masqué par défaut, affiché par JS) ── ?\> \<div
class=\"techrappy-prompt-form\" id=\"techrappy-prompt-form\"
style=\"display:none;\"\> \<div class=\"techrappy-prompt-form-header\"\>
\<h2 id=\"techrappy-prompt-title\"
class=\"techrappy-prompt-form-title\"\>\</h2\> \<div
class=\"techrappy-prompt-form-meta\"\> \<span
id=\"techrappy-prompt-version\"
class=\"techrappy-prompt-version-label\"\>\</span\> \<label
class=\"techrappy-prompt-format-label\"
for=\"techrappy-response-format\"\> \<?php esc_html_e( \'Format de
réponse :\', \'techrappy-seo\' ); ?\> \<select
id=\"techrappy-response-format\" name=\"response_format\"
class=\"techrappy-select-small\"\> \<option value=\"json_object\"\>JSON
strict\</option\> \<option value=\"text\"\>Texte libre\</option\>
\</select\> \</label\> \</div\> \</div\> \<?php // ── Zone variables
détectées ── ?\> \<div class=\"techrappy-detected-vars\"
id=\"techrappy-detected-vars\" style=\"display:none;\"\>
\<strong\>\<?php esc_html_e( \'Variables détectées :\',
\'techrappy-seo\' ); ?\>\</strong\> \<div class=\"techrappy-vars-list\"
id=\"techrappy-vars-list\"\>\</div\> \<p class=\"techrappy-vars-hint
description\"\> \<?php esc_html_e( \'Cliquez sur une variable pour
l\\\'insérer à la position du curseur.\', \'techrappy-seo\' ); ?\>
\</p\> \</div\> \<?php // ── Textarea principal ── ?\> \<div
class=\"techrappy-editor-wrap\"\> \<label
for=\"techrappy-prompt-content\" class=\"screen-reader-text\"\> \<?php
esc_html_e( \'Contenu du prompt\', \'techrappy-seo\' ); ?\> \</label\>
\<textarea id=\"techrappy-prompt-content\" name=\"content\"
class=\"techrappy-prompt-textarea\" rows=\"20\" spellcheck=\"false\"
autocomplete=\"off\" data-nonce=\"\<?php echo esc_attr( \$save_nonce );
?\>\"\>\</textarea\> \<div class=\"techrappy-editor-footer\"\> \<span
class=\"techrappy-char-count\"\> \<span
id=\"techrappy-char-num\"\>0\</span\> \<?php esc_html_e( \'caractères\',
\'techrappy-seo\' ); ?\> \</span\> \<div
class=\"techrappy-editor-actions\"\> \<button type=\"button\"
id=\"techrappy-btn-save-prompt\" class=\"button button-primary\"\>
\<?php esc_html_e( \'Sauvegarder\', \'techrappy-seo\' ); ?\> \</button\>
\<button type=\"button\" id=\"techrappy-btn-reset-single\"
class=\"button button-secondary\" title=\"\<?php esc_attr_e( \'Remettre
ce prompt aux valeurs par défaut\', \'techrappy-seo\' ); ?\>\"\> \<?php
esc_html_e( \'Réinitialiser\', \'techrappy-seo\' ); ?\> \</button\>
\</div\> \</div\> \</div\> \</div\>\<!\-- .techrappy-prompt-form \--\>
\</div\>\<!\-- .techrappy-prompt-editor-pane \--\> \</div\>\<!\--
.techrappy-prompts-layout \--\> \</div\>\<!\-- .techrappy-prompts-editor
\--\> \<?php // ── Data JS : prompts complets + defaults pour reset
single ── ?\> \<script type=\"application/json\"
id=\"techrappy-prompts-data\"\> \<?php // Préparer les données pour le
JS. // On inclut le system prompt dans la liste pour l\'éditeur.
\$js_prompts = \[\]; // System prompt. \$js_prompts\[\'system\'\] = \[
\'key\' =\> \'system\', \'label\' =\>
PagePromptStudio::get_prompt_label( \'system\' ), \'content\' =\>
\$system_prompt, \'response_format\' =\> \'text\', \'version\' =\>
\$all_prompts\[\'system\'\]\[\'version\'\] ?? 0, \]; // Prompts métier.
foreach ( \$prompt_keys as \$key ) { \$prompt_data = \$all_prompts\[
\$key \] ?? null; \$js_prompts\[ \$key \] = \[ \'key\' =\> \$key,
\'label\' =\> PagePromptStudio::get_prompt_label( \$key ), \'content\'
=\> \$prompt_data ? \$prompt_data\[\'content\'\] : \'\',
\'response_format\' =\> \$prompt_data ?
\$prompt_data\[\'response_format\'\] : \'json_object\', \'version\' =\>
\$prompt_data ? (int) \$prompt_data\[\'version\'\] : 0, \]; } echo
wp_json_encode( \$js_prompts, JSON_UNESCAPED_UNICODE \| JSON_HEX_TAG );
?\> \</script\> \`\`\` \-\-- \## Fichier 6 :
\`views/admin/prompt-studio/tab-tester.php\` --- Onglet test \`\`\`php
\<?php /\*\* \* Vue : Onglet \"Tester\" --- test d\'un prompt avec
variables. \* \* \@var string\[\] \$prompt_keys \* \@var
\\TechrappySEO\\AI\\PromptManager \$manager \* \* \@package TechrappySEO
\*/ if ( ! defined( \'ABSPATH\' ) ) { exit; } use
TechrappySEO\\Admin\\Pages\\PagePromptStudio; use
TechrappySEO\\Settings\\SettingsRepository; \$test_nonce =
wp_create_nonce( \'techrappy_seo_prompt_studio\' ); \$api_key_set = !
empty( SettingsRepository::get_api_key() ); \$model =
SettingsRepository::get( \'openai_model\', \'gpt-4o\' ); ?\> \<div
class=\"techrappy-tester-wrap\"\> \<?php if ( ! \$api_key_set ) : ?\>
\<div class=\"notice notice-warning inline\"\> \<p\> \<?php printf( /\*
translators: %s : lien vers les réglages \*/ esc_html\_\_( \'⚠️ Clé API
OpenAI non configurée. %s\', \'techrappy-seo\' ), sprintf( \'\<a
href=\"%s\"\>%s\</a\>\', esc_url( admin_url(
\'admin.php?page=techrappy-seo-settings\' ) ), esc_html\_\_(
\'Configurer les réglages\', \'techrappy-seo\' ) ) ); ?\> \</p\>
\</div\> \<?php endif; ?\> \<div class=\"techrappy-tester-layout\"\>
\<?php // ── Colonne gauche : configuration du test ── ?\> \<div
class=\"techrappy-tester-config\"\> \<h2\>\<?php esc_html_e(
\'Configuration du test\', \'techrappy-seo\' ); ?\>\</h2\> \<?php // ──
Sélection du prompt ── ?\> \<div class=\"techrappy-field-group\"\>
\<label for=\"tester-prompt-key\"\> \<?php esc_html_e( \'Prompt à
tester\', \'techrappy-seo\' ); ?\> \</label\> \<select
id=\"tester-prompt-key\" class=\"techrappy-select\" data-nonce=\"\<?php
echo esc_attr( \$test_nonce ); ?\>\"\> \<option value=\"\"\>--- \<?php
esc_html_e( \'Choisir un prompt\', \'techrappy-seo\' ); ?\>
---\</option\> \<option value=\"system\"\>\<?php echo esc_html(
PagePromptStudio::get_prompt_label( \'system\' ) ); ?\>\</option\>
\<optgroup label=\"\<?php esc_attr_e( \'Pipeline IA\', \'techrappy-seo\'
); ?\>\"\> \<?php foreach ( \$prompt_keys as \$key ) : ?\> \<option
value=\"\<?php echo esc_attr( \$key ); ?\>\"\> \<?php echo esc_html(
PagePromptStudio::get_prompt_label( \$key ) ); ?\> \</option\> \<?php
endforeach; ?\> \</optgroup\> \</select\> \</div\> \<?php // ── Modèle
utilisé ── ?\> \<div class=\"techrappy-field-group\"\> \<label\>\<?php
esc_html_e( \'Modèle\', \'techrappy-seo\' ); ?\>\</label\> \<span
class=\"techrappy-field-value\"\> \<?php echo esc_html( \$model ); ?\>
\<a href=\"\<?php echo esc_url( admin_url(
\'admin.php?page=techrappy-seo-settings\' ) ); ?\>\"
class=\"techrappy-link-small\"\> \<?php esc_html_e( \'(Modifier)\',
\'techrappy-seo\' ); ?\> \</a\> \</span\> \</div\> \<?php // ──
Variables dynamiques (injectées par JS) ── ?\> \<div
id=\"tester-variables-section\" class=\"techrappy-tester-variables\"
style=\"display:none;\"\> \<h3\>\<?php esc_html_e( \'Variables du
prompt\', \'techrappy-seo\' ); ?\>\</h3\> \<p class=\"description\"\>
\<?php esc_html_e( \'Remplissez les variables détectées dans le prompt
sélectionné.\', \'techrappy-seo\' ); ?\> \</p\> \<div
id=\"tester-variables-fields\"\> \<?php // Champs générés dynamiquement
par JS. ?\> \</div\> \</div\> \<?php // ── Bouton lancer le test ── ?\>
\<div class=\"techrappy-tester-actions\"\> \<button type=\"button\"
id=\"techrappy-btn-run-test\" class=\"button button-primary
button-large\" \<?php disabled( ! \$api_key_set ); ?\>\> ▶ \<?php
esc_html_e( \'Lancer le test\', \'techrappy-seo\' ); ?\> \</button\>
\<span id=\"tester-loading\" class=\"techrappy-spinner\"
style=\"display:none;\" aria-label=\"\<?php esc_attr_e(
\'Chargement...\', \'techrappy-seo\' ); ?\>\"\>\</span\> \</div\>
\</div\>\<!\-- .techrappy-tester-config \--\> \<?php // ── Colonne
droite : résultats ── ?\> \<div class=\"techrappy-tester-result\"
id=\"techrappy-tester-result\"\> \<?php // ── Placeholder résultat ──
?\> \<div id=\"tester-result-placeholder\"\> \<p
class=\"techrappy-placeholder-text\"\> \<?php esc_html_e( \'Les
résultats du test s\\\'afficheront ici.\', \'techrappy-seo\' ); ?\>
\</p\> \</div\> \<?php // ── Panel résultat (masqué par défaut) ── ?\>
\<div id=\"tester-result-panel\" style=\"display:none;\"\> \<?php // ──
Stats de l\'appel ── ?\> \<div class=\"techrappy-result-stats\"
id=\"tester-result-stats\"\> \<span class=\"techrappy-stat\"
id=\"tester-stat-tokens\" title=\"\<?php esc_attr_e( \'Tokens
utilisés\', \'techrappy-seo\' ); ?\>\"\> 🔢 \<strong\>0\</strong\>
tokens \</span\> \<span class=\"techrappy-stat\"
id=\"tester-stat-duration\" title=\"\<?php esc_attr_e( \'Durée\',
\'techrappy-seo\' ); ?\>\"\> ⏱ \<strong\>0\</strong\> ms \</span\>
\<span class=\"techrappy-stat\" id=\"tester-stat-json\" title=\"\<?php
esc_attr_e( \'Validation JSON\', \'techrappy-seo\' ); ?\>\"\> \</span\>
\</div\> \<?php // ── Onglets résultat ── ?\> \<div
class=\"techrappy-result-tabs\"\> \<button type=\"button\"
class=\"techrappy-result-tab-btn active\"
data-result-tab=\"formatted\"\> \<?php esc_html_e( \'Rendu\',
\'techrappy-seo\' ); ?\> \</button\> \<button type=\"button\"
class=\"techrappy-result-tab-btn\" data-result-tab=\"raw\"\> \<?php
esc_html_e( \'JSON brut\', \'techrappy-seo\' ); ?\> \</button\> \<button
type=\"button\" class=\"techrappy-result-tab-btn\"
data-result-tab=\"prompt-sent\"\> \<?php esc_html_e( \'Prompt envoyé\',
\'techrappy-seo\' ); ?\> \</button\> \</div\> \<?php // ── Contenu
onglet Rendu ── ?\> \<div class=\"techrappy-result-tab-content active\"
id=\"result-tab-formatted\"\> \<pre id=\"tester-result-formatted\"
class=\"techrappy-result-pre\"\>\</pre\> \</div\> \<?php // ── Contenu
onglet JSON brut ── ?\> \<div class=\"techrappy-result-tab-content\"
id=\"result-tab-raw\" style=\"display:none;\"\> \<pre
id=\"tester-result-raw\" class=\"techrappy-result-pre
techrappy-result-raw\"\>\</pre\> \</div\> \<?php // ── Contenu onglet
Prompt envoyé ── ?\> \<div class=\"techrappy-result-tab-content\"
id=\"result-tab-prompt-sent\" style=\"display:none;\"\> \<pre
id=\"tester-result-prompt-sent\" class=\"techrappy-result-pre
techrappy-result-prompt-sent\"\>\</pre\> \</div\> \<?php // ── Erreur
JSON (si invalide) ── ?\> \<div id=\"tester-json-error\" class=\"notice
notice-error inline\" style=\"display:none;\"\> \<p
id=\"tester-json-error-msg\"\>\</p\> \</div\> \</div\>\<!\--
#tester-result-panel \--\> \</div\>\<!\-- .techrappy-tester-result \--\>
\</div\>\<!\-- .techrappy-tester-layout \--\> \</div\>\<!\--
.techrappy-tester-wrap \--\> \`\`\` \-\-- \## Fichier 7 :
\`views/admin/prompt-studio/tab-variables.php\` --- Onglet variables
\`\`\`php \<?php /\*\* \* Vue : Onglet \"Variables\" --- référence de
toutes les variables disponibles. \* \* \@var string\[\] \$prompt_keys
\* \@var \\TechrappySEO\\AI\\PromptManager \$manager \* \@var
\\TechrappySEO\\AI\\PromptRenderer \$renderer \* \* \@package
TechrappySEO \*/ if ( ! defined( \'ABSPATH\' ) ) { exit; } use
TechrappySEO\\Admin\\Pages\\PagePromptStudio; // Construire le
dictionnaire global des variables par prompt. \$all_keys = array_merge(
\[ \'system\' \], \$prompt_keys ); \$vars_by_key = \[\]; foreach (
\$all_keys as \$key ) { \$template = \$manager-\>get_raw_template( \$key
); if ( ! empty( \$template ) ) { \$vars_by_key\[ \$key \] =
\$renderer-\>extract_variables( \$template ); } } // Variables globales
(présentes dans plusieurs prompts). \$all_vars = \[\]; \$var_usage =
\[\]; // variable =\> \[prompt_key, \...\] foreach ( \$vars_by_key as
\$key =\> \$vars ) { foreach ( \$vars as \$var ) { \$all_vars\[\] =
\$var; \$var_usage\[ \$var \]\[\] = \$key; } } \$all_vars =
array_values( array_unique( \$all_vars ) ); sort( \$all_vars );
\$global_vars = array_filter( \$var_usage, fn( \$keys ) =\> count(
\$keys ) \>= 2 ); // Dictionnaire des descriptions de variables.
\$var_descriptions = \[ \'mot_cle\' =\> \_\_( \'Mot-clé principal ciblé
(ex: \"ostéopathe Beauzelle\").\', \'techrappy-seo\' ), \'profession\'
=\> \_\_( \'Profession / activité du client (ex: \"ostéopathe\").\',
\'techrappy-seo\' ), \'type_contenu\' =\> \_\_( \'Type : \"page_seo\" ou
\"article_blog\".\', \'techrappy-seo\' ), \'H1\' =\> \_\_( \'Titre H1
généré par l\\\'étape Plan.\', \'techrappy-seo\' ), \'intent_json\' =\>
\_\_( \'JSON complet retourné par l\\\'étape Intent.\',
\'techrappy-seo\' ), \'intent_principale\' =\> \_\_( \'Résumé de
l\\\'intention principale (depuis intent_json).\', \'techrappy-seo\' ),
\'plan_json\' =\> \_\_( \'JSON complet retourné par l\\\'étape Plan.\',
\'techrappy-seo\' ), \'bloc_json\' =\> \_\_( \'Données d\\\'un bloc à
rédiger (depuis blocks_list).\', \'techrappy-seo\' ),
\'pages_site_json\' =\> \_\_( \'Tableau JSON des pages publiées du site
(titre + URL).\', \'techrappy-seo\' ), \'full_content_html\' =\> \_\_(
\'HTML complet assemblé (pour QA).\', \'techrappy-seo\' ),
\'keyword_base\' =\> \_\_( \'Mot-clé de base sans ville (pour
anti-duplicate).\', \'techrappy-seo\' ), \'city\' =\> \_\_( \'Nom de la
ville ciblée (ex: \"Beauzelle\").\', \'techrappy-seo\' ), \]; ?\> \<div
class=\"techrappy-variables-wrap\"\> \<div
class=\"techrappy-variables-intro\"\> \<p\> \<?php esc_html_e(
\'Référence de toutes les variables disponibles dans les templates de
prompts.\', \'techrappy-seo\' ); ?\> \<?php esc_html_e( \'Utilisez la
syntaxe {{nom_variable}} dans vos prompts.\', \'techrappy-seo\' ); ?\>
\</p\> \</div\> \<?php // ── Variables globales (multi-prompts) ── ?\>
\<?php if ( ! empty( \$global_vars ) ) : ?\> \<div
class=\"techrappy-variables-section\"\> \<h2\>\<?php esc_html_e( \'🌐
Variables communes (utilisées dans plusieurs prompts)\',
\'techrappy-seo\' ); ?\>\</h2\> \<table class=\"widefat striped
techrappy-vars-table\"\> \<thead\> \<tr\> \<th\>\<?php esc_html_e(
\'Variable\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php esc_html_e(
\'Description\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php
esc_html_e( \'Prompts concernés\', \'techrappy-seo\' ); ?\>\</th\>
\</tr\> \</thead\> \<tbody\> \<?php foreach ( \$global_vars as \$var =\>
\$used_in ) : ?\> \<tr\> \<td\> \<code
class=\"techrappy-var-chip\"\>\<?php echo esc_html( \'{{\' . \$var .
\'}}\' ); ?\>\</code\> \</td\> \<td\> \<?php echo esc_html(
\$var_descriptions\[ \$var \] ?? \'---\' ); ?\> \</td\> \<td\> \<?php
foreach ( \$used_in as \$k ) : ?\> \<span class=\"techrappy-tag\"\>
\<?php echo esc_html( PagePromptStudio::get_prompt_label( \$k ) ); ?\>
\</span\> \<?php endforeach; ?\> \</td\> \</tr\> \<?php endforeach; ?\>
\</tbody\> \</table\> \</div\> \<?php endif; ?\> \<?php // ── Variables
par prompt ── ?\> \<div class=\"techrappy-variables-section\"\>
\<h2\>\<?php esc_html_e( \'📋 Variables par prompt\', \'techrappy-seo\'
); ?\>\</h2\> \<?php foreach ( \$vars_by_key as \$key =\> \$vars ) : ?\>
\<div class=\"techrappy-vars-prompt-block\"\> \<h3\> \<?php echo
esc_html( PagePromptStudio::get_prompt_label( \$key ) ); ?\> \<?php if (
empty( \$vars ) ) : ?\> \<span class=\"techrappy-badge-neutral\"\>
\<?php esc_html_e( \'Aucune variable\', \'techrappy-seo\' ); ?\>
\</span\> \<?php else : ?\> \<span class=\"techrappy-badge-count\"\>
\<?php echo esc_html( (string) count( \$vars ) ); ?\> \</span\> \<?php
endif; ?\> \</h3\> \<?php if ( ! empty( \$vars ) ) : ?\> \<div
class=\"techrappy-vars-chips\"\> \<?php foreach ( \$vars as \$var ) :
?\> \<span class=\"techrappy-var-chip\" title=\"\<?php echo esc_attr(
\$var_descriptions\[ \$var \] ?? \'\' ); ?\>\"\> \<?php echo esc_html(
\'{{\' . \$var . \'}}\' ); ?\> \</span\> \<?php endforeach; ?\> \</div\>
\<?php endif; ?\> \</div\> \<?php endforeach; ?\> \</div\>
\</div\>\<!\-- .techrappy-variables-wrap \--\> \`\`\` \-\-- \## Fichier
8 : \`assets/css/admin.css\` --- Styles Prompt Studio \`\`\`css /\*
============================================================= TECHRAPPY
SEO --- Admin CSS Inclut les styles du Prompt Studio
============================================================= \*/ /\* ──
Variables CSS ── \*/ :root { \--tr-primary: #2271b1; \--tr-primary-dark:
#135e96; \--tr-success: #00a32a; \--tr-warning: #dba617; \--tr-danger:
#d63638; \--tr-bg: #f6f7f7; \--tr-border: #c3c4c7; \--tr-text: #1d2327;
\--tr-text-light: #646970; \--tr-radius: 4px; \--tr-font-mono:
\'Menlo\', \'Consolas\', \'Courier New\', monospace; \--tr-shadow: 0 1px
3px rgba(0,0,0,0.08); } /\* ── Layout général ── \*/ .techrappy-seo-wrap
{ max-width: 1400px; } /\* ── Notices AJAX ── \*/ .techrappy-notice {
padding: 10px 16px; border-left: 4px solid var(\--tr-primary);
background: #fff; margin: 12px 0; border-radius: var(\--tr-radius);
box-shadow: var(\--tr-shadow); } .techrappy-notice.is-success {
border-color: var(\--tr-success); } .techrappy-notice.is-error {
border-color: var(\--tr-danger); } .techrappy-notice.is-warning {
border-color: var(\--tr-warning); } /\* ── Tabs navigation ── \*/
.techrappy-tabs { margin-bottom: 0; border-bottom: 1px solid
var(\--tr-border); } /\* ── Toolbar ── \*/ .techrappy-toolbar { display:
flex; align-items: center; gap: 12px; padding: 12px 0 16px;
border-bottom: 1px solid var(\--tr-border); margin-bottom: 16px; }
.techrappy-toolbar-hint { color: var(\--tr-text-light); font-size: 12px;
} /\* ───────────────────────────────────────── PROMPT STUDIO --- Onglet
Prompts ───────────────────────────────────────── \*/
.techrappy-prompts-layout { display: grid; grid-template-columns: 260px
1fr; gap: 20px; align-items: start; margin-top: 16px; } /\* ──
Navigation gauche ── \*/ .techrappy-prompt-nav { background: #fff;
border: 1px solid var(\--tr-border); border-radius: var(\--tr-radius);
overflow: hidden; position: sticky; top: 32px; }
.techrappy-prompt-nav-item { display: flex; justify-content:
space-between; align-items: center; padding: 9px 14px; font-size: 13px;
color: var(\--tr-text); text-decoration: none; border-bottom: 1px solid
#f0f0f0; transition: background 0.15s; cursor: pointer; }
.techrappy-prompt-nav-item:last-child { border-bottom: none; }
.techrappy-prompt-nav-item:hover, .techrappy-prompt-nav-item.is-active {
background: #f0f6fc; color: var(\--tr-primary); }
.techrappy-prompt-nav-item.is-active { font-weight: 600; border-left:
3px solid var(\--tr-primary); } .techrappy-prompt-nav-system {
background: var(\--tr-bg); font-weight: 600; }
.techrappy-prompt-nav-separator { height: 1px; background:
var(\--tr-border); } /\* ── Badge version ── \*/
.techrappy-version-badge { font-size: 10px; background: #e8f0fe; color:
var(\--tr-primary); padding: 1px 6px; border-radius: 10px; font-weight:
500; } /\* ── Éditeur pane droite ── \*/ .techrappy-prompt-editor-pane {
background: #fff; border: 1px solid var(\--tr-border); border-radius:
var(\--tr-radius); padding: 20px; min-height: 400px; }
.techrappy-prompt-placeholder { display: flex; align-items: center;
justify-content: center; min-height: 300px; color:
var(\--tr-text-light); font-size: 15px; } /\* ── Header formulaire ──
\*/ .techrappy-prompt-form-header { display: flex; justify-content:
space-between; align-items: center; margin-bottom: 16px; padding-bottom:
12px; border-bottom: 1px solid var(\--tr-border); }
.techrappy-prompt-form-title { margin: 0; font-size: 16px; }
.techrappy-prompt-form-meta { display: flex; align-items: center; gap:
16px; font-size: 12px; color: var(\--tr-text-light); }
.techrappy-prompt-version-label { font-size: 11px; color:
var(\--tr-text-light); } .techrappy-prompt-format-label { display: flex;
align-items: center; gap: 6px; } .techrappy-select-small { font-size:
12px !important; height: 26px !important; padding: 0 4px !important; }
/\* ── Variables détectées ── \*/ .techrappy-detected-vars { background:
#f8f9fb; border: 1px solid #e2e4e7; border-radius: var(\--tr-radius);
padding: 10px 14px; margin-bottom: 12px; } .techrappy-detected-vars
strong { font-size: 12px; display: block; margin-bottom: 8px; color:
var(\--tr-text); } .techrappy-vars-list { display: flex; flex-wrap:
wrap; gap: 6px; margin-bottom: 6px; } .techrappy-vars-hint { margin: 6px
0 0 !important; font-size: 11px !important; } /\* ── Var chip (cliquable
dans l\'éditeur) ── \*/ .techrappy-var-chip { display: inline-block;
background: #e8f0fe; color: var(\--tr-primary); padding: 2px 8px;
border-radius: 12px; font-size: 12px; font-family: var(\--tr-font-mono);
cursor: pointer; border: 1px solid #b8d0f5; transition: background
0.15s; user-select: none; } .techrappy-var-chip:hover { background:
var(\--tr-primary); color: #fff; } /\* ── Textarea ── \*/
.techrappy-editor-wrap { position: relative; }
.techrappy-prompt-textarea { width: 100%; font-family:
var(\--tr-font-mono) !important; font-size: 13px !important;
line-height: 1.6 !important; padding: 12px !important; border: 1px solid
var(\--tr-border) !important; border-radius: var(\--tr-radius)
!important; resize: vertical; background: #fafafa; color:
var(\--tr-text); tab-size: 2; } .techrappy-prompt-textarea:focus {
border-color: var(\--tr-primary) !important; box-shadow: 0 0 0 2px
rgba(34, 113, 177, 0.15) !important; background: #fff; outline: none; }
/\* ── Footer éditeur ── \*/ .techrappy-editor-footer { display: flex;
justify-content: space-between; align-items: center; margin-top: 10px; }
.techrappy-char-count { font-size: 12px; color: var(\--tr-text-light); }
.techrappy-editor-actions { display: flex; gap: 8px; } /\*
───────────────────────────────────────── PROMPT STUDIO --- Onglet
Tester ───────────────────────────────────────── \*/
.techrappy-tester-layout { display: grid; grid-template-columns: 320px
1fr; gap: 20px; margin-top: 16px; align-items: start; } /\* ── Config
colonne gauche ── \*/ .techrappy-tester-config { background: #fff;
border: 1px solid var(\--tr-border); border-radius: var(\--tr-radius);
padding: 20px; position: sticky; top: 32px; } .techrappy-tester-config
h2 { margin-top: 0; font-size: 15px; border-bottom: 1px solid
var(\--tr-border); padding-bottom: 10px; margin-bottom: 16px; }
.techrappy-field-group { margin-bottom: 14px; } .techrappy-field-group
label { display: block; font-size: 12px; font-weight: 600; color:
var(\--tr-text); margin-bottom: 4px; text-transform: uppercase;
letter-spacing: 0.03em; } .techrappy-select { width: 100%; }
.techrappy-field-value { font-size: 13px; color: var(\--tr-text); }
.techrappy-link-small { font-size: 11px; margin-left: 4px; } /\* ──
Variables de test ── \*/ .techrappy-tester-variables h3 { font-size:
13px; margin: 16px 0 8px; padding-top: 12px; border-top: 1px solid
var(\--tr-border); } .techrappy-tester-variables .description {
font-size: 11px; color: var(\--tr-text-light); margin-bottom: 10px; }
.techrappy-var-field { margin-bottom: 10px; } .techrappy-var-field label
{ display: block; font-size: 12px; font-weight: 500; margin-bottom: 3px;
font-family: var(\--tr-font-mono); color: var(\--tr-primary); }
.techrappy-var-field input\[type=\"text\"\], .techrappy-var-field
textarea { width: 100%; font-size: 12px !important; }
.techrappy-var-field textarea { font-family: var(\--tr-font-mono)
!important; font-size: 11px !important; resize: vertical; min-height:
60px; } /\* ── Actions test ── \*/ .techrappy-tester-actions { display:
flex; align-items: center; gap: 10px; margin-top: 20px; padding-top:
14px; border-top: 1px solid var(\--tr-border); } .techrappy-spinner {
display: inline-block; width: 20px; height: 20px; border: 2px solid
var(\--tr-border); border-top-color: var(\--tr-primary); border-radius:
50%; animation: techrappy-spin 0.7s linear infinite; } \@keyframes
techrappy-spin { to { transform: rotate(360deg); } } /\* ── Résultats
colonne droite ── \*/ .techrappy-tester-result { background: #fff;
border: 1px solid var(\--tr-border); border-radius: var(\--tr-radius);
min-height: 400px; overflow: hidden; } #tester-result-placeholder {
display: flex; align-items: center; justify-content: center; min-height:
300px; } .techrappy-placeholder-text { color: var(\--tr-text-light);
font-size: 14px; } /\* Stats de l\'appel \*/ .techrappy-result-stats {
display: flex; gap: 16px; padding: 10px 16px; background: var(\--tr-bg);
border-bottom: 1px solid var(\--tr-border); font-size: 12px; }
.techrappy-stat strong { font-weight: 600; } .techrappy-stat-success {
color: var(\--tr-success); } .techrappy-stat-error { color:
var(\--tr-danger); } /\* Onglets résultat \*/ .techrappy-result-tabs {
display: flex; gap: 0; border-bottom: 1px solid var(\--tr-border);
background: var(\--tr-bg); } .techrappy-result-tab-btn { padding: 8px
16px; font-size: 12px; border: none; background: none; cursor: pointer;
color: var(\--tr-text-light); border-bottom: 2px solid transparent;
transition: all 0.15s; } .techrappy-result-tab-btn:hover { color:
var(\--tr-text); } .techrappy-result-tab-btn.active { color:
var(\--tr-primary); border-bottom-color: var(\--tr-primary);
font-weight: 600; } /\* Pre résultat \*/ .techrappy-result-pre { margin:
0; padding: 16px; font-family: var(\--tr-font-mono); font-size: 12px;
line-height: 1.6; white-space: pre-wrap; word-break: break-word;
max-height: 600px; overflow-y: auto; background: #fff; color:
var(\--tr-text); } .techrappy-result-raw { background: #1e1e1e; color:
#d4d4d4; } .techrappy-result-prompt-sent { background: #f8f4ff; color:
#4a1d96; } /\* ───────────────────────────────────────── PROMPT STUDIO
--- Onglet Variables ───────────────────────────────────────── \*/
.techrappy-variables-wrap { margin-top: 16px; }
.techrappy-variables-intro { background: #fff; border: 1px solid
var(\--tr-border); border-radius: var(\--tr-radius); padding: 14px 18px;
margin-bottom: 20px; font-size: 13px; color: var(\--tr-text-light); }
.techrappy-variables-section { margin-bottom: 28px; }
.techrappy-variables-section h2 { font-size: 14px; margin-bottom: 10px;
} .techrappy-vars-table { font-size: 13px; } .techrappy-vars-table code
{ font-size: 12px; } .techrappy-tag { display: inline-block; background:
#f0f0f0; padding: 1px 8px; border-radius: 12px; font-size: 11px; margin:
2px 3px 2px 0; color: var(\--tr-text); } .techrappy-vars-prompt-block {
background: #fff; \`\`\`css border: 1px solid var(\--tr-border);
border-radius: var(\--tr-radius); padding: 14px 18px; margin-bottom:
12px; } .techrappy-vars-prompt-block h3 { margin: 0 0 10px; font-size:
13px; display: flex; align-items: center; gap: 8px; }
.techrappy-vars-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.techrappy-badge-count { display: inline-flex; align-items: center;
justify-content: center; background: var(\--tr-primary); color: #fff;
border-radius: 10px; font-size: 10px; padding: 1px 7px; font-weight:
600; } .techrappy-badge-neutral { display: inline-flex; align-items:
center; background: var(\--tr-bg); color: var(\--tr-text-light);
border-radius: 10px; font-size: 10px; padding: 1px 7px; border: 1px
solid var(\--tr-border); } /\* ─────────────────────────────────────────
Responsive ───────────────────────────────────────── \*/ \@media screen
and (max-width: 1100px) { .techrappy-prompts-layout,
.techrappy-tester-layout { grid-template-columns: 1fr; }
.techrappy-prompt-nav, .techrappy-tester-config { position: static; } }
\`\`\` \-\-- \## Fichier 9 : \`assets/js/prompt-studio.js\`
\`\`\`javascript /\*\* \* Prompt Studio --- JavaScript \* \* Gère : \* -
Onglet Prompts : navigation, chargement, détection variables,
sauvegarde, \* reset single, reset global, insertion variable au
curseur. \* - Onglet Tester : sélection prompt, génération champs
variables, \* appel AJAX test, affichage résultat + validation JSON. \*
\* Dépend de : jQuery, TechrappySEO (localisé par AdminAssets) \* \*
\@package TechrappySEO \*/ /\* global TechrappySEO, jQuery \*/ (
function ( \$, config ) { \'use strict\'; //
───────────────────────────────────────── // Configuration & constantes
// ───────────────────────────────────────── const AJAX_URL =
config.ajax_url; const NONCES = config.nonces; const I18N = config.i18n;
// Données des prompts injectées par PHP (JSON dans le DOM). let
promptsData = {}; // Clé du prompt actuellement sélectionné dans
l\'éditeur. let currentPromptKey = null; //
───────────────────────────────────────── // Initialisation //
───────────────────────────────────────── \$( document ).ready( function
() { loadPromptsData(); initEditorTab(); initTesterTab();
initResultTabs(); } ); /\*\* \* Charge les données des prompts depuis le
JSON injecté par PHP. \*/ function loadPromptsData() { const \$dataEl =
\$( \'#techrappy-prompts-data\' ); if ( ! \$dataEl.length ) { return; }
try { promptsData = JSON.parse( \$dataEl.text() ); } catch ( e ) {
console.error( \'\[TechrappySEO\] Erreur parsing promptsData :\', e );
promptsData = {}; } } // ───────────────────────────────────────── //
ONGLET PROMPTS --- Éditeur // ─────────────────────────────────────────
function initEditorTab() { if ( ! \$( \'#techrappy-prompt-studio\'
).length ) { return; } // Navigation : clic sur un item de la liste. \$(
document ).on( \'click\', \'.techrappy-prompt-nav-item\', function ( e )
{ e.preventDefault(); const key = \$( this ).data( \'key\' ); if ( key )
{ selectPrompt( key ); } } ); // Sauvegarde du prompt courant. \$(
document ).on( \'click\', \'#techrappy-btn-save-prompt\', function () {
saveCurrentPrompt(); } ); // Reset prompt courant aux defaults. \$(
document ).on( \'click\', \'#techrappy-btn-reset-single\', function () {
resetSinglePrompt(); } ); // Reset global de tous les prompts. \$(
document ).on( \'click\', \'#techrappy-btn-reset-all\', function () {
const confirmMsg = \$( this ).data( \'confirm\' ) \|\|
I18N.confirm_bulk; if ( ! window.confirm( confirmMsg ) ) { return; }
resetAllPrompts( \$( this ).data( \'nonce\' ) ); } ); // Compteur de
caractères en temps réel. \$( document ).on( \'input\',
\'#techrappy-prompt-content\', function () { const len = \$( this
).val().length; \$( \'#techrappy-char-num\' ).text( len.toLocaleString(
\'fr-FR\' ) ); detectAndShowVariables( \$( this ).val() ); } ); //
Insertion d\'une variable au clic sur un chip. \$( document ).on(
\'click\', \'.techrappy-vars-list .techrappy-var-chip\', function () {
insertVariableAtCursor( \$( this ).data( \'var\' ) ); } ); } /\*\* \*
Sélectionne et charge un prompt dans l\'éditeur. \* \* \@param {string}
key Clé du prompt. \*/ function selectPrompt( key ) { const data =
promptsData\[ key \]; if ( ! data ) { showNotice( \'Prompt introuvable :
\' + key, \'error\' ); return; } currentPromptKey = key; // Mettre à
jour la navigation (état actif). \$( \'.techrappy-prompt-nav-item\'
).removeClass( \'is-active\' ).attr( \'aria-pressed\', \'false\' ); \$(
\'.techrappy-prompt-nav-item\[data-key=\"\' + key + \'\"\]\' )
.addClass( \'is-active\' ) .attr( \'aria-pressed\', \'true\' ); //
Remplir le formulaire. \$( \'#techrappy-prompt-title\' ).text(
data.label \|\| key ); \$( \'#techrappy-prompt-content\' ).val(
data.content \|\| \'\' ); \$( \'#techrappy-response-format\' ).val(
data.response_format \|\| \'json_object\' ); // Version. const version =
data.version \> 0 ? \'v\' + data.version : \'Par défaut\'; \$(
\'#techrappy-prompt-version\' ).text( version ); // Compteur de
caractères. \$( \'#techrappy-char-num\' ).text( ( data.content \|\| \'\'
).length.toLocaleString( \'fr-FR\' ) ); // Détecter et afficher les
variables. detectAndShowVariables( data.content \|\| \'\' ); // Afficher
le formulaire. \$( \'#techrappy-prompt-placeholder\' ).hide(); \$(
\'#techrappy-prompt-form\' ).show(); // Focus sur le textarea.
setTimeout( function () { \$( \'#techrappy-prompt-content\' ).focus();
}, 50 ); } /\*\* \* Détecte les variables {{\...}} dans le contenu et
les affiche. \* \* \@param {string} content Contenu du prompt. \*/
function detectAndShowVariables( content ) { const regex =
/\\{\\{(\[a-zA-Z0-9\_\\-\]+)\\}\\}/g; const found = \[\]; const seen =
{}; let match; while ( ( match = regex.exec( content ) ) !== null ) { if
( ! seen\[ match\[ 1 \] \] ) { found.push( match\[ 1 \] ); seen\[
match\[ 1 \] \] = true; } } const \$section = \$(
\'#techrappy-detected-vars\' ); const \$list = \$(
\'#techrappy-vars-list\' ); \$list.empty(); if ( found.length === 0 ) {
\$section.hide(); return; } found.forEach( function ( varName ) {
\$list.append( \$( \'\<span\>\' ) .addClass( \'techrappy-var-chip\' )
.attr( \'data-var\', \'{{\' + varName + \'}}\' ) .attr( \'title\',
\'Cliquer pour insérer\' ) .text( \'{{\' + varName + \'}}\' ) ); } );
\$section.show(); } /\*\* \* Insère une variable à la position du
curseur dans le textarea. \* \* \@param {string} variable Variable à
insérer (ex: \'{{mot_cle}}\'). \*/ function insertVariableAtCursor(
variable ) { const textarea = document.getElementById(
\'techrappy-prompt-content\' ); if ( ! textarea ) { return; } const
start = textarea.selectionStart; const end = textarea.selectionEnd;
const value = textarea.value; textarea.value = value.substring( 0, start
) + variable + value.substring( end ); // Repositionner le curseur après
la variable insérée. const newPos = start + variable.length;
textarea.selectionStart = newPos; textarea.selectionEnd = newPos;
textarea.focus(); // Déclencher l\'événement input pour mettre à jour
compteur + variables. \$( textarea ).trigger( \'input\' ); } /\*\* \*
Sauvegarde le prompt courant via AJAX. \*/ function saveCurrentPrompt()
{ if ( ! currentPromptKey ) { return; } const content = \$(
\'#techrappy-prompt-content\' ).val(); const responseFormat = \$(
\'#techrappy-response-format\' ).val(); const nonce =
NONCES.prompt_studio; if ( ! content.trim() ) { showNotice( \'Le contenu
du prompt ne peut pas être vide.\', \'error\' ); return; } const \$btn =
\$( \'#techrappy-btn-save-prompt\' ); setButtonLoading( \$btn, true );
// Choix de l\'action selon la clé (system prompt séparé). const action
= ( currentPromptKey === \'system\' ) ? \'techrappy_save_system_prompt\'
: \'techrappy_save_prompt\'; const postData = { action: action, nonce:
nonce, content: content, response_format: responseFormat, }; if (
currentPromptKey !== \'system\' ) { postData.prompt_key =
currentPromptKey; } \$.post( AJAX_URL, postData ) .done( function (
response ) { if ( response.success ) { showNotice( response.data.message
\|\| \'Sauvegardé.\', \'success\' ); // Mettre à jour le cache local des
prompts. if ( promptsData\[ currentPromptKey \] ) { promptsData\[
currentPromptKey \].content = content; promptsData\[ currentPromptKey
\].response_format = responseFormat; promptsData\[ currentPromptKey
\].version = ( promptsData\[ currentPromptKey \].version \|\| 0 ) + 1; }
// Mettre à jour l\'affichage de la version. \$(
\'#techrappy-prompt-version\' ).text( \'v\' + promptsData\[
currentPromptKey \].version ); // Mettre à jour le badge de version dans
la nav. const \$navItem = \$(
\'.techrappy-prompt-nav-item\[data-key=\"\' + currentPromptKey +
\'\"\]\' ); const \$badge = \$navItem.find( \'.techrappy-version-badge\'
); const newVersion = promptsData\[ currentPromptKey \].version; if (
\$badge.length ) { \$badge.text( \'v\' + newVersion ); } else {
\$navItem.append( \$( \'\<span\>\' ) .addClass(
\'techrappy-version-badge\' ) .text( \'v\' + newVersion ) ); } } else {
showNotice( response.data.message \|\| I18N.error, \'error\' ); } } )
.fail( function () { showNotice( I18N.error, \'error\' ); } ) .always(
function () { setButtonLoading( \$btn, false ); } ); } /\*\* \*
Réinitialise le prompt courant aux valeurs par défaut. \*/ function
resetSinglePrompt() { if ( ! currentPromptKey ) { return; } //
Confirmation utilisateur. const confirmMsg = \'Réinitialiser \"\' + (
promptsData\[ currentPromptKey \]?.label \|\| currentPromptKey ) + \'\"
aux valeurs par défaut ?\'; if ( ! window.confirm( confirmMsg ) ) {
return; } const \$btn = \$( \'#techrappy-btn-reset-single\' );
setButtonLoading( \$btn, true ); \$.post( AJAX_URL, { action:
\'techrappy_reset_prompts\', nonce: NONCES.prompt_studio, single_key:
currentPromptKey, } ) .done( function ( response ) { if (
response.success ) { showNotice( response.data.message \|\|
\'Réinitialisé.\', \'success\' ); // Recharger la page pour refléter le
reset. setTimeout( function () { window.location.reload(); }, 1200 ); }
else { showNotice( response.data.message \|\| I18N.error, \'error\' ); }
} ) .fail( function () { showNotice( I18N.error, \'error\' ); } )
.always( function () { setButtonLoading( \$btn, false ); } ); } /\*\* \*
Réinitialise tous les prompts aux valeurs par défaut. \* \* \@param
{string} nonce Nonce de sécurité. \*/ function resetAllPrompts( nonce )
{ const \$btn = \$( \'#techrappy-btn-reset-all\' ); setButtonLoading(
\$btn, true ); \$.post( AJAX_URL, { action: \'techrappy_reset_prompts\',
nonce: nonce \|\| NONCES.prompt_studio, } ) .done( function ( response )
{ if ( response.success ) { showNotice( response.data.message \|\|
\'Tous les prompts réinitialisés.\', \'success\' ); setTimeout( function
() { window.location.reload(); }, 1500 ); } else { showNotice(
response.data.message \|\| I18N.error, \'error\' ); } } ) .fail(
function () { showNotice( I18N.error, \'error\' ); } ) .always( function
() { setButtonLoading( \$btn, false ); } ); } //
───────────────────────────────────────── // ONGLET TESTER //
───────────────────────────────────────── function initTesterTab() { if
( ! \$( \'#tester-prompt-key\' ).length ) { return; } // Changement de
prompt sélectionné → générer les champs de variables. \$(
\'#tester-prompt-key\' ).on( \'change\', function () { const key = \$(
this ).val(); if ( key ) { loadTesterVariables( key ); } else { \$(
\'#tester-variables-section\' ).hide(); } } ); // Lancement du test. \$(
\'#techrappy-btn-run-test\' ).on( \'click\', function () {
runPromptTest(); } ); } /\*\* \* Charge les variables d\'un prompt et
génère les champs de saisie. \* \* \@param {string} key Clé du prompt.
\*/ function loadTesterVariables( key ) { const \$section = \$(
\'#tester-variables-section\' ); const \$fields = \$(
\'#tester-variables-fields\' ); \$fields.html( \'\<p
style=\"color:#646970;font-size:12px;\"\>\' + I18N.loading + \'\</p\>\'
); \$section.show(); \$.post( AJAX_URL, { action:
\'techrappy_get_prompt_vars\', nonce: NONCES.prompt_studio, prompt_key:
key, } ) .done( function ( response ) { if ( response.success &&
Array.isArray( response.data.variables ) ) { renderVariableFields(
\$fields, response.data.variables ); } else { \$fields.html( \'\<p
style=\"color:#646970;font-size:12px;\"\>Aucune variable
détectée.\</p\>\' ); } } ) .fail( function () { \$fields.html( \'\<p
style=\"color:#d63638;font-size:12px;\"\>\' + I18N.error + \'\</p\>\' );
} ); } /\*\* \* Génère dynamiquement les champs de saisie pour chaque
variable. \* \* \@param {jQuery} \$container Conteneur jQuery cible. \*
\@param {Array} variables Tableau de noms de variables. \*/ function
renderVariableFields( \$container, variables ) { \$container.empty(); if
( variables.length === 0 ) { \$container.html( \'\<p
style=\"color:#646970;font-size:12px;\"\>Aucune variable dans ce
prompt.\</p\>\' ); return; } // Variables qui nécessitent un textarea
(JSON volumeux). const jsonVars = \[ \'intent_json\', \'plan_json\',
\'bloc_json\', \'pages_site_json\', \'full_content_html\' \];
variables.forEach( function ( varName ) { const isJson =
jsonVars.includes( varName ); const inputId = \'tester-var-\' + varName;
const \$field = \$( \'\<div\>\' ).addClass( \'techrappy-var-field\' );
const \$label = \$( \'\<label\>\' ) .attr( \'for\', inputId ) .text(
\'{{\' + varName + \'}}\' ); let \$input; if ( isJson ) { \$input = \$(
\'\<textarea\>\' ) .attr( \'id\', inputId ) .attr( \'data-var\', varName
) .attr( \'placeholder\', \'JSON (ex: {\"key\":\"value\"})\' )
.addClass( \'techrappy-tester-var-input\' ); } else { \$input = \$(
\'\<input\>\' ) .attr( \'type\', \'text\' ) .attr( \'id\', inputId )
.attr( \'data-var\', varName ) .attr( \'placeholder\', varName )
.addClass( \'regular-text techrappy-tester-var-input\' ); }
\$field.append( \$label, \$input ); \$container.append( \$field ); } );
} /\*\* \* Collecte les valeurs des variables saisies dans le tester. \*
\* \@returns {Object} Objet clé→valeur des variables. \*/ function
collectTesterVariables() { const vars = {}; \$(
\'.techrappy-tester-var-input\' ).each( function () { const varName =
\$( this ).data( \'var\' ); const value = \$( this ).val().trim(); if (
varName && value ) { vars\[ varName \] = value; } } ); return vars; }
/\*\* \* Lance le test du prompt via AJAX. \*/ function runPromptTest()
{ const promptKey = \$( \'#tester-prompt-key\' ).val(); if ( ! promptKey
) { showNotice( \'Sélectionnez un prompt avant de lancer le test.\',
\'warning\' ); return; } const variables = collectTesterVariables();
const \$btn = \$( \'#techrappy-btn-run-test\' ); const \$spinner = \$(
\'#tester-loading\' ); // UI : état chargement. setButtonLoading( \$btn,
true ); \$spinner.show(); \$( \'#tester-result-placeholder\' ).hide();
\$( \'#tester-result-panel\' ).hide(); \$.post( AJAX_URL, { action:
\'techrappy_test_prompt\', nonce: NONCES.prompt_studio, prompt_key:
promptKey, variables: JSON.stringify( variables ), use_saved: \'1\', } )
.done( function ( response ) { if ( response.success ) {
renderTestResult( response.data ); } else { renderTestError(
response.data ); } } ) .fail( function ( xhr ) { renderTestError( {
message: I18N.error + \' (HTTP \' + xhr.status + \')\', duration_ms: 0,
} ); } ) .always( function () { setButtonLoading( \$btn, false );
\$spinner.hide(); } ); } /\*\* \* Affiche le résultat d\'un test réussi.
\* \* \@param {Object} data Données retournées par AJAX. \*/ function
renderTestResult( data ) { // ── Stats ── \$( \'#tester-stat-tokens
strong\' ).text( ( data.total_tokens \|\| 0 ).toLocaleString( \'fr-FR\'
) ); \$( \'#tester-stat-tokens\' ).html( \'🔢 \<strong\>\' + (
data.total_tokens \|\| 0 ).toLocaleString( \'fr-FR\' ) + \'\</strong\>
tokens \' + \'\<small style=\"color:#646970\"\>(\' + ( data.input_tokens
\|\| 0 ) + \' in / \' + ( data.output_tokens \|\| 0 ) + \'
out)\</small\>\' ); \$( \'#tester-stat-duration\' ).html( \'⏱
\<strong\>\' + ( data.duration_ms \|\| 0 ).toLocaleString( \'fr-FR\' ) +
\'\</strong\> ms\' ); // ── Validation JSON ── const \$jsonStat = \$(
\'#tester-stat-json\' ); if ( data.json_valid === true ) {
\$jsonStat.html( \'✅ \<span class=\"techrappy-stat-success\"\>JSON
valide\</span\>\' ); \$( \'#tester-json-error\' ).hide(); } else if (
data.json_valid === false ) { \$jsonStat.html( \'❌ \<span
class=\"techrappy-stat-error\"\>JSON invalide\</span\>\' ); \$(
\'#tester-json-error-msg\' ).text( data.json_error \|\| \'Erreur JSON
inconnue.\' ); \$( \'#tester-json-error\' ).show(); } else {
\$jsonStat.html( \'📄 Format texte\' ); \$( \'#tester-json-error\'
).hide(); } // ── Onglet Rendu ── let formattedContent = data.content
\|\| \'\'; if ( data.parsed ) { try { formattedContent = JSON.stringify(
data.parsed, null, 2 ); } catch ( e ) { formattedContent = data.content
\|\| \'\'; } } \$( \'#tester-result-formatted\' ).text( formattedContent
); // ── Onglet JSON brut ── \$( \'#tester-result-raw\' ).text(
data.content \|\| \'\' ); // ── Onglet Prompt envoyé ── \$(
\'#tester-result-prompt-sent\' ).text( data.prompt_sent \|\| \'\' ); //
── Afficher le panel résultat ── \$( \'#tester-result-placeholder\'
).hide(); \$( \'#tester-result-panel\' ).show(); // Activer le premier
onglet résultat. activateResultTab( \'formatted\' ); } /\*\* \* Affiche
un résultat d\'erreur dans le panel test. \* \* \@param {Object} data
Données d\'erreur. \*/ function renderTestError( data ) { const errorMsg
= data.message \|\| I18N.error; \$( \'#tester-stat-tokens\' ).html( \'🔢
\<strong\>0\</strong\> tokens\' ); \$( \'#tester-stat-duration\' ).html(
\'⏱ \<strong\>\' + ( data.duration_ms \|\| 0 ) + \'\</strong\> ms\' );
\$( \'#tester-stat-json\' ).html( \'❌ \<span
class=\"techrappy-stat-error\"\>Erreur\</span\>\' ); \$(
\'#tester-result-formatted\' ).text( \'❌ \' + errorMsg ); \$(
\'#tester-result-raw\' ).text( JSON.stringify( data, null, 2 ) ); \$(
\'#tester-result-prompt-sent\' ).text( \'\' ); \$(
\'#tester-json-error-msg\' ).text( errorMsg ); \$(
\'#tester-json-error\' ).show(); \$( \'#tester-result-placeholder\'
).hide(); \$( \'#tester-result-panel\' ).show(); activateResultTab(
\'formatted\' ); } // ───────────────────────────────────────── //
ONGLETS RÉSULTAT // ───────────────────────────────────────── function
initResultTabs() { \$( document ).on( \'click\',
\'.techrappy-result-tab-btn\', function () { const tab = \$( this
).data( \'result-tab\' ); activateResultTab( tab ); } ); } /\*\* \*
Active un onglet du panel résultat. \* \* \@param {string} tab Nom de
l\'onglet (\'formatted\' \| \'raw\' \| \'prompt-sent\'). \*/ function
activateResultTab( tab ) { \$( \'.techrappy-result-tab-btn\'
).removeClass( \'active\' ); \$(
\'.techrappy-result-tab-btn\[data-result-tab=\"\' + tab + \'\"\]\'
).addClass( \'active\' ); \$( \'.techrappy-result-tab-content\'
).hide(); \$( \'#result-tab-\' + tab ).show(); } //
───────────────────────────────────────── // HELPERS UI //
───────────────────────────────────────── /\*\* \* Affiche une notice en
haut de la page. \* \* \@param {string} message Message à afficher. \*
\@param {string} type Type : \'success\' \| \'error\' \| \'warning\'.
\*/ function showNotice( message, type ) { type = type \|\| \'success\';
const \$notice = \$( \'#techrappy-notice\' ); \$notice .removeClass(
\'is-success is-error is-warning\' ) .addClass( \'is-\' + type ) .text(
message ) .fadeIn( 200 ); // Auto-masquer après 4 secondes (sauf
erreur). if ( type !== \'error\' ) { setTimeout( function () {
\$notice.fadeOut( 400 ); }, 4000 ); } // Scroll vers la notice. \$(
\'html, body\' ).animate( { scrollTop: \$notice.offset()?.top - 40 \|\|
0 }, 200 ); } /\*\* \* Active / désactive l\'état chargement d\'un
bouton. \* \* \@param {jQuery} \$btn Bouton jQuery. \* \@param {boolean}
loading True pour activer l\'état chargement. \*/ function
setButtonLoading( \$btn, loading ) { if ( loading ) { \$btn .prop(
\'disabled\', true ) .data( \'original-text\', \$btn.text() ) .text(
I18N.loading ); } else { \$btn .prop( \'disabled\', false ) .text(
\$btn.data( \'original-text\' ) \|\| \$btn.text() ); } } } )( jQuery,
window.TechrappySEO \|\| {} ); \`\`\` \-\-- \## Fichier 10 :
Enregistrement des nouvelles actions AJAX dans \`Plugin.php\` \`\`\`php
// Dans includes/Core/Plugin.php --- méthode define_ajax_hooks() //
Remplacer le bloc AjaxPromptStudio existant par : \$ajax_prompts = new
\\TechrappySEO\\Admin\\Ajax\\AjaxPromptStudio();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_save_prompt\',
\$ajax_prompts, \'handle_save\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_save_system_prompt\', \$ajax_prompts,
\'handle_save_system\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_reset_prompts\', \$ajax_prompts, \'handle_reset\' );
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_test_prompt\',
\$ajax_prompts, \'handle_test\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_get_prompt_vars\', \$ajax_prompts,
\'handle_get_vars\' ); \`\`\` \-\-- \## Fichier 11 : \`AdminAssets.php\`
--- Ajout nonce \`prompt_studio\` (delta) \`\`\`php // Dans
includes/Admin/AdminAssets.php --- méthode get_localized_data() //
Ajouter dans le tableau \'nonces\' : \'prompt_studio\' =\>
wp_create_nonce( \'techrappy_seo_prompt_studio\' ), \`\`\` \-\-- \##
Récapitulatif V3 --- Prompt Studio complet \`\`\` Module Prompt Studio │
├── includes/Admin/Pages/PagePromptStudio.php ✅ Orchestrateur (tabs,
data) ├── includes/Admin/Ajax/AjaxPromptStudio.php ✅ 5 handlers AJAX
sécurisés ├── includes/Prompts/PromptRepository.php ✅ +
update_format(), get_version() │ ├── views/admin/prompt-studio/ │ ├──
layout.php ✅ Layout + navigation onglets │ ├── tab-prompts.php ✅
Éditeur (nav gauche + textarea + variables) │ ├── tab-tester.php ✅ Test
prompt (champs dynamiques + résultat) │ └── tab-variables.php ✅
Référence variables (tableau global + par prompt) │ ├──
assets/css/admin.css ✅ Styles complets Prompt Studio └──
assets/js/prompt-studio.js ✅ JS complet (éditeur + tester + tabs)
\`\`\` \*\*Flux complet opérationnel :\*\* - \`nav item click\` →
\`selectPrompt()\` → charge depuis \`promptsData\` JSON - \`textarea
input\` → \`detectAndShowVariables()\` → chips cliquables →
\`insertVariableAtCursor()\` - \`Sauvegarder\` → \`saveCurrentPrompt()\`
→ \`wp_ajax_techrappy_save_prompt\` → \`PromptRepository::update()\` -
\`Tester\` → \`loadTesterVariables()\` →
\`wp_ajax_techrappy_get_prompt_vars\` → champs dynamiques →
\`runPromptTest()\` → \`wp_ajax_techrappy_test_prompt\` →
\`AIClient::generate()\` → \`renderTestResult()\` - \`Reset\` →
\`wp_ajax_techrappy_reset_prompts\` → \`DefaultPrompts::seed()\` force
