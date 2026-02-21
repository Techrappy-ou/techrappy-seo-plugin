\# TECHRAPPY SEO --- Architecture Globale du Plugin \-\-- \## 1.
Arborescence des fichiers \`\`\` techrappy-seo/ │ ├── techrappy-seo.php
\# Point d\'entrée principal (headers, bootstrap) │ ├── uninstall.php \#
Nettoyage à la désinstallation │ ├── readme.txt \# Documentation
WordPress standard │ ├── assets/ │ ├── css/ │ │ ├── admin.css \# Styles
globaux admin │ │ ├── wizard.css \# Styles du wizard de génération │ │
└── prompt-studio.css \# Styles Prompt Studio │ ├── js/ │ │ ├── admin.js
\# JS global admin │ │ ├── wizard.js \# Logique wizard (steps, AJAX) │ │
├── bulk-jobs.js \# Suivi temps réel des jobs en masse │ │ ├──
template-audit.js \# Audit tokens template │ │ └── prompt-studio.js \#
Éditeur prompts + tester │ └── img/ │ └── logo.svg │ ├── includes/ │ │ │
├── Core/ │ │ ├── Plugin.php \# Classe principale --- bootstrapper │ │
├── Loader.php \# Gestionnaire hooks (actions/filters) │ │ ├──
Activator.php \# Hook d\'activation (tables, options) │ │ ├──
Deactivator.php \# Hook de désactivation │ │ └── Installer.php \#
Création tables DB custom │ │ │ ├── Admin/ │ │ ├── AdminMenu.php \#
Enregistrement menus WP Admin │ │ ├── AdminAssets.php \# Enqueue
scripts/styles admin │ │ ├── Pages/ │ │ │ ├── PageWizard.php \# Page :
New Generation (wizard) │ │ │ ├── PageTemplateAudit.php \# Page :
Templates Audit │ │ │ ├── PageBulkJobs.php \# Page : Bulk Jobs (liste +
logs) │ │ │ ├── PageSettings.php \# Page : Settings (API keys, config) │
│ │ └── PagePromptStudio.php \# Page : Prompt Studio │ │ └── Ajax/ │ │
├── AjaxWizard.php \# Handlers AJAX wizard (étapes 1-5) │ │ ├──
AjaxGeneration.php \# Handlers AJAX génération unitaire │ │ ├──
AjaxBulk.php \# Handlers AJAX bulk (villes, preview) │ │ ├──
AjaxTemplateAudit.php \# Handlers AJAX audit template │ │ └──
AjaxPromptStudio.php \# Handlers AJAX prompt studio (test) │ │ │ ├── AI/
│ │ ├── AIClient.php \# Client centralisé OpenAI (Responses API) │ │ ├──
PromptManager.php \# Chargement/rendu prompts depuis DB/options │ │ ├──
PromptRenderer.php \# Injection variables dans templates prompts │ │ └──
Pipeline/ │ │ ├── PipelineRunner.php \# Orchestrateur du pipeline 10
étapes │ │ ├── Steps/ │ │ │ ├── StepIntent.php \# Étape 1 --- Analyse
intention (SERP) │ │ │ ├── StepPlan.php \# Étape 2 --- Plan H1/H2/H3 │ │
│ ├── StepBlocksList.php \# Étape 2B --- Liste des blocs │ │ │ ├──
StepIntro.php \# Étape 3 --- Introduction SEO │ │ │ ├──
StepWriteBlock.php \# Étape 4 --- Rédaction bloc H2 (×N) │ │ │ ├──
StepConclusion.php \# Étape 5 --- Conclusion + CTA │ │ │ ├──
StepMeta.php \# Étape 6 --- Meta title + description │ │ │ ├──
StepFaq.php \# Étape 7 --- FAQ HTML + JSON-LD │ │ │ ├──
StepInternalLinks.php# Étape 8 --- Maillage interne │ │ │ ├──
StepAntiDuplicate.php# Étape 9 --- Anti-duplicate (bulk) │ │ │ └──
StepQA.php \# Étape 10 --- QA score SEO │ │ └── StepInterface.php \#
Interface commune à toutes les étapes │ │ │ ├── Divi/ │ │ ├──
DiviTemplateHandler.php \# Duplication template + copie metas Divi │ │
├── TokenScanner.php \# Détection tokens {{token}} dans post_content │ │
├── TokenReplacer.php \# Remplacement tokens dans shortcodes Divi │ │
└── RepeatableSectionHandler.php \# Gestion sections répétables Divi │ │
│ ├── SEO/ │ │ ├── YoastIntegration.php \# Injection meta Yoast
(\_yoast_wpseo\_\*) │ │ ├── SlugGenerator.php \# Génération slug
SEO-friendly + anti-doublon │ │ └── InternalLinksBuilder.php \#
Construction bloc maillage interne HTML │ │ │ ├── Geo/ │ │ ├──
VillesVoisinesClient.php \# Client API villes-voisines.fr │ │ ├──
BanClient.php \# Client API adresse.data.gouv.fr (CP→commune) │ │ └──
GeoCache.php \# Cache transient 24h/7j des résultats geo │ │ │ ├── Jobs/
│ │ ├── JobRepository.php \# CRUD jobs (table wp_techrappy_seo_jobs) │ │
├── JobRunner.php \# Exécuteur d\'un job single (appelle Pipeline) │ │
├── BulkJobManager.php \# Création + dispatch jobs bulk │ │ └──
QueueScheduler.php \# Intégration Action Scheduler / WP-Cron │ │ │ ├──
Prompts/ │ │ ├── PromptRepository.php \# CRUD prompts (table
wp_techrappy_prompts) │ │ ├── PromptVersioner.php \# Gestion
versioning + historique prompts │ │ └── DefaultPrompts.php \# Prompts
par défaut (factory/seed) │ │ │ ├── Templates/ │ │ ├──
TemplateAuditor.php \# Audit complet d\'un template (tokens + mapping) │
│ ├── TemplateMappingRepository.php# Stockage mapping token→source par
template │ │ └── TokenMappingResolver.php \# Résolution source de chaque
token │ │ │ ├── Utils/ │ │ ├── Logger.php \# Système de logs (par job,
par étape) │ │ ├── UuidGenerator.php \# Génération UUID v4 │ │ ├──
CostEstimator.php \# Estimation coût tokens OpenAI │ │ ├──
ContentAssembler.php \# Assemblage final HTML (tokens → post_content) │
│ └── Sanitizer.php \# Helpers sanitize/escape │ │ │ └── Settings/ │ ├──
SettingsRepository.php \# Lecture/écriture options (autoload=no) │ └──
SettingsValidator.php \# Validation formulaire settings │ ├── views/ │
├── admin/ │ │ ├── wizard/ │ │ │ ├── step-1-mode.php \# Vue étape 1 :
choix mode │ │ │ ├── step-2-template.php \# Vue étape 2 : sélection
template │ │ │ ├── step-3-audit.php \# Vue étape 3 : audit tokens │ │ │
├── step-4-wp-params.php \# Vue étape 4 : paramètres WP │ │ │ ├──
step-5-seo-params.php \# Vue étape 5 : paramètres SEO │ │ │ ├──
step-6-preview-plan.php \# Vue : prévisualisation plan │ │ │ └──
step-7-preview-final.php \# Vue : prévisualisation finale HTML │ │ ├──
bulk-jobs/ │ │ │ ├── list.php \# Liste des jobs bulk │ │ │ ├──
detail.php \# Détail job + logs │ │ │ └── city-selector.php \# Sélecteur
villes (checkbox + compteur) │ │ ├── settings/ │ │ │ └──
settings-form.php \# Formulaire settings complet │ │ ├── prompt-studio/
│ │ │ ├── editor.php \# Éditeur prompts (onglet Prompts) │ │ │ ├──
variables.php \# Liste variables disponibles │ │ │ ├── tester.php \#
Interface test prompt │ │ │ └── history.php \# Historique versions │ │
└── template-audit/ │ │ └── audit-result.php \# Résultat audit template
│ └── partials/ │ ├── header.php \# Header commun pages admin │ ├──
footer.php \# Footer commun pages admin │ ├── notice.php \# Composant
notice (success/error/warning) │ └── spinner.php \# Composant loading
spinner │ ├── database/ │ └── schema.sql \# Schéma SQL des tables custom
(référence) │ └── languages/ └── techrappy-seo.pot \# Fichier de
traduction \`\`\` \-\-- \## 2. Tables de base de données custom \`\`\`
wp_techrappy_seo_jobs ├── id BIGINT PK AUTO_INCREMENT ├── job_id
VARCHAR(36) UNIQUE --- UUID ├── mode ENUM(\'single\',\'bulk\') ├── type
ENUM(\'page\',\'post\') ├── template_post_id BIGINT ├── keyword TEXT ├──
city VARCHAR(255) ├── publish_status ENUM(\'draft\',\'publish\') ├──
wp_params JSON ├── slug_rule ENUM(\'from_keyword\',\'from_h1\') ├──
steps_data LONGTEXT --- JSON complet des étapes ├── result_data TEXT ---
JSON résultat final ├── logs LONGTEXT --- JSON array logs ├── status
ENUM(\'pending\',\'running\',\'done\',\'error\') ├── parent_job_id
VARCHAR(36) NULL --- pour enfants bulk ├── created_at DATETIME └──
updated_at DATETIME wp_techrappy_prompts ├── id BIGINT PK AUTO_INCREMENT
├── prompt_key VARCHAR(100) UNIQUE ├── content LONGTEXT ├──
response_format VARCHAR(50) ├── version INT DEFAULT 1 ├── is_active
TINYINT(1) DEFAULT 1 ├── updated_at DATETIME └── updated_by BIGINT ---
user_id WP \`\`\` \-\-- \## 3. Classes principales & responsabilités
\### 3.1 Couche Core \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \| \`Plugin\` \|
Bootstrap --- instancie et lie tous les modules, point d\'entrée unique
\| \| \`Loader\` \| Registre centralisé des \`add_action\` /
\`add_filter\` --- découplage total \| \| \`Activator\` \| Crée tables,
insère options par défaut, seed prompts par défaut \| \| \`Deactivator\`
\| Supprime crons, libère ressources temporaires \| \| \`Installer\` \|
Exécute \`dbDelta()\` pour créer/migrer les tables custom \| \### 3.2
Couche Admin \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \| \`AdminMenu\` \|
Déclare le menu principal et sous-menus WP Admin \| \| \`AdminAssets\`
\| Enqueue conditionnel CSS/JS selon la page admin courante \| \|
\`PageWizard\` \| Rendu et logique du wizard 7 étapes \| \|
\`PageTemplateAudit\` \| Sélection template + déclenchement audit \| \|
\`PageBulkJobs\` \| Tableau de bord jobs bulk, statuts, logs, actions \|
\| \`PageSettings\` \| Formulaire settings sécurisé (nonce +
capabilities) \| \| \`PagePromptStudio\` \| Interface éditeur prompts
avec onglets \| \| \`AjaxWizard\` \| Handlers AJAX étapes wizard
(sécurisés nonce + \`manage_options\`) \| \| \`AjaxGeneration\` \|
Handlers AJAX pipeline génération (step-by-step ou full) \| \|
\`AjaxBulk\` \| Fetch villes, preview, lancement queue \| \|
\`AjaxTemplateAudit\` \| Scan template, retour tokens JSON \| \|
\`AjaxPromptStudio\` \| Test prompt live, sauvegarde, reset \| \### 3.3
Couche AI \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \| \`AIClient\` \|
\*\*Client centralisé unique\*\* --- POST vers
\`api.openai.com/v1/responses\`, retry ×2, timeout configurable, logs \|
\| \`PromptManager\` \| Charge les prompts depuis
\`wp_techrappy_prompts\` (ou fallback \`wp_options\`) \| \|
\`PromptRenderer\` \| Remplace les variables \`{{var}}\` dans les
templates de prompts \| \| \`PipelineRunner\` \| Orchestre l\'exécution
séquentielle des 10 étapes, gère état job, persistance \| \|
\`StepInterface\` \| Contrat commun : \`execute(array \$context):
array\` + \`getName(): string\` \| \| \`Step\*\` (×10) \| Une classe par
étape --- prépare prompt, appelle AIClient, valide JSON retour \| \###
3.4 Couche Divi \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`DiviTemplateHandler\` \| Lecture template, \`wp_insert_post()\`, copie
meta Divi \| \| \`TokenScanner\` \| Parse \`post_content\`, extrait
tokens \`{{TOKEN}}\` via regex \| \| \`TokenReplacer\` \| Remplace
tokens dans shortcodes Divi, gère tokens manquants \| \|
\`RepeatableSectionHandler\` \| Extrait section template, duplique ×N,
réinsère dans post_content \| \### 3.5 Couche SEO \| Classe \|
Responsabilité \| \|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`YoastIntegration\` \| \`update_post_meta\` pour
\`\_yoast_wpseo_title\` et \`\_yoast_wpseo_metadesc\` \| \|
\`SlugGenerator\` \| Génère slug depuis mot-clé ou H1, sanitize, vérifie
unicité WP \| \| \`InternalLinksBuilder\` \| Construit le bloc HTML
\`\<ul\>\<li\>\` de liens internes depuis données IA \| \### 3.6 Couche
Geo \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`VillesVoisinesClient\` \| GET API villes-voisines.fr, parse JSON codes
postaux, cache 24h \| \| \`BanClient\` \| GET API adresse.data.gouv.fr,
CP → nom commune, cache transient 7j \| \| \`GeoCache\` \| Abstraction
cache WordPress transients pour résultats géo \| \### 3.7 Couche Jobs \|
Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \| \`JobRepository\`
\| CRUD complet sur \`wp_techrappy_seo_jobs\` + sérialisation JSON \| \|
\`JobRunner\` \| Charge un job, instancie PipelineRunner, met à jour
status/logs \| \| \`BulkJobManager\` \| Crée job parent + N jobs
enfants, dispatche vers queue \| \| \`QueueScheduler\` \|
Enregistre/consomme actions Action Scheduler
(\`as_schedule_single_action\`) \| \### 3.8 Couche Prompts \| Classe \|
Responsabilité \| \|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`PromptRepository\` \| CRUD \`wp_techrappy_prompts\` + historique
versions \| \| \`PromptVersioner\` \| Sauvegarde ancienne version avant
mise à jour, liste historique \| \| \`DefaultPrompts\` \| Définit les 11
prompts par défaut (seed à l\'activation) \| \### 3.9 Couche Templates
\| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`TemplateAuditor\` \| Orchestre scan tokens + détection manquants +
mapping recommandé \| \| \`TemplateMappingRepository\` \| Stocke/lit
mapping \`wp_options\` \`techrappy_seo_template_map\_{ID}\` \| \|
\`TokenMappingResolver\` \| Résout source (ai/wp/manual) et step pour
chaque token détecté \| \### 3.10 Couche Utils \| Classe \|
Responsabilité \| \|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`Logger\` \| Ajoute entrée \`{t, step, msg, level}\` aux logs d\'un job
\| \| \`UuidGenerator\` \| Génère UUID v4 pur PHP sans dépendance \| \|
\`CostEstimator\` \| Estime coût en \$ depuis tokens input/output selon
modèle sélectionné \| \| \`ContentAssembler\` \| Assemble toutes les
sorties pipeline en contenu final injectables \| \| \`Sanitizer\` \|
Wrappers \`sanitize\_\*\`, \`wp_kses_post\`, \`esc\_\*\` centralisés \|
\### 3.11 Couche Settings \| Classe \| Responsabilité \|
\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`SettingsRepository\` \| Lecture/écriture \`wp_options\` avec
\`autoload=no\`, chiffrement clé API \| \| \`SettingsValidator\` \|
Valide et sanitize les données du formulaire settings \| \-\-- \## 4.
Hooks WordPress enregistrés \### 4.1 Hooks d\'activation/désactivation
\`\`\` register_activation_hook → Activator::activate() ├──
Installer::create_tables() --- dbDelta tables custom ├──
DefaultPrompts::seed() --- insertion prompts par défaut ├──
SettingsRepository::init_defaults() --- options par défaut (autoload=no)
└── QueueScheduler::register_hooks() --- enregistrement actions AS
register_deactivation_hook → Deactivator::deactivate() ├──
QueueScheduler::clear_scheduled() --- suppression crons en attente └──
GeoCache::flush() --- nettoyage transients geo \`\`\` \### 4.2 Actions
WordPress core \`\`\` plugins_loaded → Plugin::init() --- chargement
conditionnel selon contexte (admin/front) init → Loader::run() ---
enregistrement de tous les hooks déclarés admin_menu →
AdminMenu::register_menus() --- déclaration menus + sous-menus
admin_enqueue_scripts → AdminAssets::enqueue(\$hook) --- enqueue
conditionnel par page slug wp_ajax\_{action} →
Ajax\*::handle\_{action}() ├── techrappy_scan_template ├──
techrappy_save_mapping ├── techrappy_run_step ├── techrappy_run_pipeline
├── techrappy_get_cities ├── techrappy_preview_city ├──
techrappy_launch_bulk ├── techrappy_get_job_status ├──
techrappy_save_prompt ├── techrappy_reset_prompts ├──
techrappy_test_prompt └── techrappy_save_settings \`\`\` \### 4.3 Hooks
Action Scheduler \`\`\` techrappy_seo_process_single_job →
JobRunner::run( \$job_id ) --- Appelé par QueueScheduler pour chaque
ville en bulk --- Exécute pipeline complet + update status
techrappy_seo_bulk_progress_check → BulkJobManager::check_progress(
\$parent_job_id ) --- Vérifie si tous les enfants sont terminés, met à
jour job parent \`\`\` \### 4.4 Filtres internes (extensibilité future)
\`\`\` techrappy_seo_ai_request_args --- Modifier les args avant appel
OpenAI techrappy_seo_token_value --- Modifier valeur d\'un token avant
remplacement techrappy_seo_post_content_before --- Modifier post_content
avant wp_insert_post techrappy_seo_post_content_after --- Modifier
post_content après insertion techrappy_seo_slug_generated --- Modifier
slug généré techrappy_seo_pipeline_steps --- Ajouter/retirer étapes du
pipeline \`\`\` \-\-- \## 5. Schéma des flux de données \`\`\`
┌─────────────────────────────────────────────────────────────────┐ │
ADMIN UI (Browser) │ │ Wizard │ Template Audit │ Bulk Jobs │ Prompt
Studio │
└─────┬─────┴────────┬─────────┴──────┬──────┴────────┬────────────┘ │
AJAX │ AJAX │ AJAX │ AJAX ▼ ▼ ▼ ▼
┌─────────────────────────────────────────────────────────────────┐ │
AJAX HANDLERS LAYER │ │ AjaxWizard │ AjaxTemplateAudit │ AjaxBulk │
AjaxPromptStudio │
└─────┬───────┴────────┬──────────┴─────┬─────┴───────────────────┘ │ │
│ ▼ ▼ ▼ ┌──────────────┐ ┌──────────────┐ ┌────────────────────────────┐
│ PIPELINE │ │ TEMPLATE │ │ BULK JOB MANAGER │ │ RUNNER │ │ AUDITOR │ │
┌────────────────────────┐ │ │ │ │ │ │ │ QUEUE SCHEDULER │ │ │ Step
1..10 │ │ TokenScanner│ │ │ (Action Scheduler) │ │ └──────┬───────┘
└──────┬───────┘ └──┬─────────────────────────┘ │ │ │ ▼ ▼ ▼
┌─────────────────────────────────────────────────────────────────┐ │
SERVICE LAYER │ │ AIClient │ DiviHandler │ YoastInteg │ GeoClients │ │
(OpenAI) │ TokenReplace │ SlugGen │ VV + BAN │
└─────┬──────┴───────┬───────┴──────┬───────┴─────────────────────┘ │ │
│ ▼ ▼ ▼
┌─────────────────────────────────────────────────────────────────┐ │
DATA LAYER │ │ JobRepository │ PromptRepository │ TemplateMappingRepo │
│ SettingsRepo │ Logger │ GeoCache (transients) │ │ │ │
wp_techrappy_seo_jobs │ wp_techrappy_prompts │ wp_options │
└─────────────────────────────────────────────────────────────────┘
\`\`\` \-\-- \## 6. Sécurité --- règles appliquées \| Point \| Règle \|
\|\-\-\-\-\-\--\|\-\-\-\-\-\--\| \| Clé API OpenAI \| Stockée dans
\`wp_options\` avec \`autoload=no\`, non exposée en JS \| \| Toutes les
pages admin \| Vérification \`current_user_can(\'manage_options\')\` \|
\| Tous les handlers AJAX \| \`check_ajax_referer()\` +
\`current_user_can(\'manage_options\')\` \| \| Sorties HTML \|
\`esc_html()\`, \`esc_attr()\`, \`wp_kses_post()\` systématiquement \|
\| Requêtes DB \| Uniquement \`\$wpdb-\>prepare()\` --- zéro
interpolation directe \| \| Données POST \| \`sanitize_text_field()\`,
\`sanitize_textarea_field()\`, \`absint()\` selon type \| \| Options
sensibles \| \`autoload=no\` sur toutes les options du plugin \| \-\--
\## 7. Dépendances & compatibilité \| Dépendance \| Version \| Rôle \|
\|\-\-\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\--\|\-\-\-\-\--\| \| WordPress
\| ≥ 6.2 \| Core \| \| PHP \| ≥ 8.0 \| Runtime \| \| Divi Builder \| ≥
4.x \| Templates source \| \| Yoast SEO \| ≥ 20.x \| Meta SEO (optionnel
graceful) \| \| Action Scheduler \| ≥ 3.6 \| Queue bulk (bundled ou
WooCommerce) \| \| OpenAI API \| v1/responses \| Génération IA \| \-\--
Cette architecture est maintenant posée et validée. La prochaine étape
sera la \*\*génération V1 du squelette PHP\*\* : fichier principal,
bootstrap, Activator, Installer (tables), SettingsRepository, AdminMenu
--- le tout fonctionnel et installable.
