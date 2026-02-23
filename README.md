# Techrappy SEO — Plugin WordPress

Génération automatique de pages et articles SEO-ready avec Divi Builder et OpenAI GPT-4o.

---

## Prérequis

- WordPress 6.2+
- PHP 8.0+
- Divi Builder activé sur le site
- Clé API OpenAI (`sk-…`)
- Plugin [Action Scheduler](https://actionscheduler.org/) (inclus dans WooCommerce ou installable séparément)

---

## Installation

1. Cloner le dépôt dans `wp-content/plugins/` :
   ```bash
   git clone https://github.com/Techrappy-ou/techrappy-seo-plugin.git techrappy-seo
   ```
2. Activer le plugin dans **Extensions → Extensions installées**.
3. Aller dans **Techrappy SEO → Réglages** et renseigner votre clé API OpenAI.

---

## Fonctionnalités

### 1. Wizard de génération
Menu **Techrappy SEO → Nouvelle génération**.

7 étapes guidées :
1. Choix du mode : page, article ou bulk
2. Sélection du template Divi
3. Audit du template (tokens détectés / manquants)
4. Paramètres WordPress (catégorie, parent, statut)
5. Paramètres SEO (mot-clé, type de contenu, règle de slug)
6. Prévisualisation du plan H1/H2
7. Prévisualisation HTML finale + publication

### 2. Pipeline IA (10 étapes)
Chaque étape est loguée et relançable :

| # | Clé | Description |
|---|-----|-------------|
| 1 | `intent` | Analyse d'intention SERP |
| 2 | `plan` | Plan H1/H2/H3 |
| 3 | `blocks_list` | Liste des blocs à rédiger |
| 4 | `intro` | Introduction SEO |
| 5 | `block_write` | Rédaction blocs H2 |
| 6 | `conclusion_cta` | Conclusion + CTA |
| 7 | `meta` | Meta title + meta description |
| 8 | `faq` | FAQ HTML + JSON-LD FAQPage |
| 9 | `internal_links` | Maillage interne (3–5 liens) |
| 10 | `anti_duplicate` | Variations pour bulk |

### 3. Génération en masse (pages locales)
Menu **Techrappy SEO → Jobs en masse**.

- Saisie d'un code postal + rayon (10 / 20 / 30 km)
- Récupération automatique des communes via API villes-voisines.fr + BAN
- Sélection des villes avec checkboxes
- Gestion automatique des menus WordPress (ajouter à un menu existant ou en créer un nouveau)
- File d'attente via Action Scheduler
- Logs par page générée

### 4. Audit de templates Divi
Menu **Techrappy SEO → Audit de templates**.

- Scan des tokens `{{...}}` dans le contenu Divi
- Rapport : tokens trouvés / manquants
- Mapping personnalisé token → source
- **Export JSON** du template (contenu Divi + mapping)
- **Import JSON** → création automatique d'un brouillon WP

### 5. Prompt Studio
Menu **Techrappy SEO → Prompt Studio**.

- Éditeur des prompts par étape de pipeline
- Onglet Variables (liste + insertion)
- Onglet Tester (exécution avec variables manuelles, résultat JSON, tokens, temps)
- Historique des versions + restauration
- Réinitialisation des prompts par défaut

### 6. Logs
Menu **Techrappy SEO → Logs**.

- Liste de tous les jobs avec statut
- Détail des logs par job (niveau info/warning/error/debug)
- Purge des jobs terminés/échoués

### 7. Réglages
Menu **Techrappy SEO → Réglages**.

- Clé API OpenAI (stockée chiffrée)
- Modèle (gpt-4o, gpt-4o-mini…)
- Température, max tokens, timeout
- Mode debug

---

## Tokens Divi reconnus

```
{{H1}}, {{intro}}, {{metatitle}}, {{metadescription}}, {{slug}},
{{keyword}}, {{city}},
{{h2}}, {{text}}, {{h2_1}}, {{text_1}}, …
{{internal_links}}, {{faq_block}}, {{cta_block}}
```

---

## Architecture

```
techrappy-seo/
├── techrappy-seo.php          # Bootstrap
├── includes/
│   ├── AI/                    # AIClient + Pipeline (10 Steps)
│   ├── Admin/                 # AdminMenu, AdminAssets, Pages, Ajax
│   ├── Core/                  # Plugin, Loader, Activator, Installer
│   ├── Divi/                  # DiviTemplateHandler, TokenScanner, TokenReplacer
│   ├── Geo/                   # VillesVoisinesClient, BanClient, GeoCache
│   ├── Jobs/                  # BulkJobManager, JobRepository, JobRunner, PostWriter
│   ├── Menu/                  # MenuManager
│   ├── Prompts/               # PromptRepository, DefaultPrompts, PromptVersioner
│   ├── SEO/                   # SlugGenerator, YoastIntegration, InternalLinksBuilder
│   ├── Settings/              # SettingsRepository, SettingsValidator
│   ├── Templates/             # TemplateAuditor, TemplateMappingRepository
│   └── Utils/                 # Logger, ContentAssembler, CostEstimator, Sanitizer
├── views/
│   ├── admin/                 # Vues des pages admin
│   └── partials/              # Header, footer, spinner
└── assets/
    ├── css/                   # Styles admin
    └── js/                    # Scripts admin
```

---

## Sécurité

- Nonces sur tous les formulaires et appels AJAX
- Capacité `manage_options` requise partout
- Clé API OpenAI chiffrée (non-autoload)
- Sanitisation de toutes les entrées utilisateur
- Redaction des clés API dans les logs

---

## Licence

Proprietary — © Techrappy
