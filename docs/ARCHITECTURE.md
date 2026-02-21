# Architecture — Techrappy SEO Plugin

## Vue d'ensemble

**Techrappy SEO** est un plugin WordPress de type SEO-IA. Il analyse le contenu des publications WordPress, génère des suggestions via un fournisseur d'IA externe (OpenAI / Anthropic / autre), et expose ces suggestions dans l'interface admin.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Langage backend | PHP 8.0+ |
| Framework | WordPress Plugin API (hooks, REST API) |
| Namespacing | `TechrappySEO\` (PSR-4) |
| Autoloading | Composer PSR-4 ou chargement manuel via `includes/Plugin.php` |
| Base de données | Tables WordPress custom via `$wpdb` + options API |
| Tâches asynchrones | File d'attente custom (DB) avec cron WP |
| Frontend admin | Vanilla JS + CSS natif (pas de framework JS pour la V1) |

---

## Arborescence du plugin

```
techrappy-seo-plugin/
├── techrappy-seo.php              # Point d'entrée du plugin (header WordPress)
├── uninstall.php                  # Nettoyage à la désinstallation
├── index.php                      # Sécurité : silence is golden
│
├── docs/                          # Documentation contractuelle
│   ├── ARCHITECTURE.md
│   ├── SQUELETTE_V1.md
│   └── INSTRUCTIONS_DEV.md
│
├── includes/                      # Core bootstrap (chargé dans l'ordre)
│   ├── Plugin.php                 # Classe principale : orchestre tout
│   ├── Loader.php                 # Registre des hooks actions/filtres
│   ├── Activator.php             # Logique d'activation (tables, options)
│   └── Deactivator.php           # Logique de désactivation (crons)
│
├── admin/                         # Interface d'administration
│   ├── Admin.php                  # Enregistrement menus, scripts admin
│   ├── Settings.php               # Page de réglages du plugin
│   ├── MetaBox.php                # Meta box sur les posts/pages
│   ├── views/                     # Templates PHP des pages admin
│   │   ├── settings-page.php
│   │   └── meta-box.php
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
│
├── public/                        # Couche front-end (si nécessaire)
│   ├── Frontend.php
│   ├── css/
│   │   └── public.css
│   └── js/
│       └── public.js
│
└── services/                      # Couche métier
    ├── ai/                        # Client IA
    │   ├── AIClient.php           # Façade principale d'appel IA
    │   └── ProviderInterface.php  # Contrat pour chaque fournisseur
    ├── seo/                       # Moteur SEO
    │   ├── SEOEngine.php          # Orchestrateur d'analyse SEO
    │   └── ContentAnalyzer.php    # Analyse textuelle du contenu
    ├── queue/                     # File d'attente asynchrone
    │   ├── Queue.php              # Gestion de la file (CRUD DB)
    │   └── QueueWorker.php        # Exécuteur des tâches (via WP-Cron)
    └── integrations/              # Connecteurs tiers
        ├── YoastIntegration.php   # Bridge Yoast SEO
        ├── DiviIntegration.php    # Bridge Divi Builder
        └── BulkProcessor.php      # Traitement en masse des posts
```

---

## Flux de données principal

```
[Post WordPress]
      │
      ▼
[MetaBox Admin]  ──── déclenche ──►  [SEOEngine]
                                          │
                                          ▼
                                    [ContentAnalyzer]
                                          │
                                          ▼ (si IA activée)
                                    [AIClient]  ──► [Provider API]
                                          │
                                          ▼
                                    [Queue] ──► [QueueWorker via WP-Cron]
                                          │
                                          ▼
                                    [Résultats sauvegardés en post_meta]
```

---

## Conventions de nommage

| Élément | Convention |
|---|---|
| Classes PHP | `PascalCase` dans namespace `TechrappySEO\` |
| Hooks | `techrappy_seo_{action}` |
| Options DB | `techrappy_seo_{option_name}` |
| Post meta | `_techrappy_seo_{meta_key}` |
| Tables DB | `{prefix}techrappy_seo_{table_name}` |
| Constantes | `TECHRAPPY_SEO_{NAME}` |
| Text domain | `techrappy-seo` |
| Slug plugin | `techrappy-seo` |

---

## Modules V1 (squelettes uniquement)

| Module | Description | Statut V1 |
|---|---|---|
| Core | Bootstrap, activation, hooks | Implémenté |
| Admin UI | Menus, settings, meta box | Squelette |
| AI Client | Appels API fournisseur IA | Squelette |
| SEO Engine | Analyse contenu, score SEO | Squelette |
| Queue | File d'attente async | Squelette |
| Yoast Bridge | Lecture/écriture champs Yoast | Squelette |
| Divi Bridge | Support modules Divi | Squelette |
| Bulk Processor | Traitement en masse | Squelette |

---

## Sécurité

- Chaque fichier PHP commence par `defined('ABSPATH') || exit;`
- Nonces WordPress sur tous les formulaires et requêtes AJAX
- Capabilities WordPress vérifiées avant toute action admin
- Données nettoyées avec `sanitize_*()` à l'entrée
- Données échappées avec `esc_*()` à la sortie
- Accès directs aux fichiers bloqués

---

## Compatibilité

- WordPress : 6.0+
- PHP : 8.0+
- MySQL : 5.7+ / MariaDB 10.3+
- Multisite : prévu mais non implémenté en V1
