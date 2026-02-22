techrappy-seo/ │ ├── techrappy-seo.php \# Point d\'entrée principal
(plugin header) ├── uninstall.php \# Nettoyage à la désinstallation ├──
readme.txt │ ├── includes/ \# Cœur du plugin (classes métier) │ ├──
class-plugin.php \# Bootstrap, chargement des modules │ ├──
class-installer.php \# Activation/désactivation (tables, options) │ │ │
├── ai/ │ │ ├── class-ai-client.php \# Client OpenAI centralisé
(Responses API) │ │ ├── class-ai-pipeline.php \# Orchestrateur des 10
étapes │ │ ├── class-prompt-manager.php \# Gestion prompts (CRUD,
variables, versioning) │ │ └── class-prompt-renderer.php \# Injection
variables dans templates prompt │ │ │ ├── generation/ │ │ ├──
class-job-manager.php \# CRUD jobs (single + bulk) │ │ ├──
class-single-generator.php \# Pipeline génération unitaire │ │ ├──
class-bulk-generator.php \# Orchestration génération en masse │ │ └──
class-slug-builder.php \# Génération slug SEO-friendly │ │ │ ├── divi/ │
│ ├── class-divi-duplicator.php \# Duplication template Divi │ │ ├──
class-token-scanner.php \# Scan & détection tokens {{}} │ │ ├──
class-token-replacer.php \# Remplacement tokens dans post_content │ │
└── class-section-repeater.php \# Gestion sections répétables │ │ │ ├──
seo/ │ │ ├── class-yoast-integrator.php \# Écriture meta Yoast │ │ └──
class-internal-links.php \# Maillage interne │ │ │ ├── geo/ │ │ ├──
class-villes-voisines.php \# API Villes-Voisines │ │ └──
class-ban-client.php \# API BAN (CP → Communes) │ │ │ ├── queue/ │ │ ├──
class-queue-manager.php \# Interface Action Scheduler │ │ └──
class-job-processor.php \# Traitement d\'un job en queue │ │ │ └──
helpers/ │ ├── class-logger.php \# Système de logs par étape │ ├──
class-security.php \# Nonces, capabilities, sanitize │ └── functions.php
\# Helpers globaux │ ├── admin/ \# Interface WordPress Admin │ ├──
class-admin-menu.php \# Enregistrement menus WP Admin │ │ │ ├── pages/ │
│ ├── page-wizard.php \# Wizard génération (étapes 1-5) │ │ ├──
page-template-audit.php \# Audit tokens template │ │ ├──
page-bulk-jobs.php \# Liste jobs en masse │ │ ├── page-prompt-studio.php
\# Éditeur prompts │ │ └── page-settings.php \# Réglages API, modèles,
etc. │ │ │ └── partials/ │ ├── wizard-step-1.php \# Choix du mode │ ├──
wizard-step-2.php \# Sélection template │ ├── wizard-step-3.php \# Audit
template │ ├── wizard-step-4.php \# Paramètres WordPress │ ├──
wizard-step-5.php \# Paramètres SEO │ ├── preview-plan.php \# Preview
plan éditable │ ├── preview-final.php \# Preview HTML finale │ └──
job-row.php \# Ligne de job dans la liste │ ├── api/ \# AJAX / REST API
handlers │ ├── class-ajax-handler.php \# Handlers wp_ajax\_\* │ └──
class-rest-controller.php \# REST API endpoints (optionnel V2) │ ├──
assets/ │ ├── css/ │ │ └── admin.css \# Styles admin │ └── js/ │ ├──
admin.js \# JS admin général │ ├── wizard.js \# JS wizard étapes │ └──
prompt-studio.js \# JS éditeur prompts │ ├── templates/ \# Templates
HTML/PHP réutilisables │ ├── faq-block.php \# Template FAQ HTML +
JSON-LD │ └── cta-block.php \# Template CTA │ └── data/ └──
default-prompts.json \# Prompts par défaut (seed)
