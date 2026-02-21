# Squelette V1 — Techrappy SEO Plugin

## Objectif de la V1

Produire un plugin WordPress **activable sans erreur fatale**, structuré selon
l'architecture définie, prêt à accueillir la logique métier des modules futurs.

La V1 ne contient **aucune logique métier réelle** : uniquement les classes,
interfaces, hooks et points d'entrée nécessaires au bon fonctionnement du squelette.

---

## Fichiers à générer

### Racine du plugin

| Fichier | Rôle |
|---|---|
| `techrappy-seo.php` | Point d'entrée WordPress. Définit les constantes, charge l'autoloader, lance `Plugin::run()` |
| `uninstall.php` | Supprime les options et tables à la désinstallation |
| `index.php` | Fichier vide de sécurité |

### includes/

| Fichier | Classe | Rôle |
|---|---|---|
| `Plugin.php` | `TechrappySEO\Plugin` | Orchestre les dépendances, enregistre les hooks via Loader |
| `Loader.php` | `TechrappySEO\Loader` | Registre interne des actions et filtres WordPress |
| `Activator.php` | `TechrappySEO\Activator` | Crée les tables DB et initialise les options par défaut |
| `Deactivator.php` | `TechrappySEO\Deactivator` | Supprime les crons planifiés |

### admin/

| Fichier | Classe | Rôle |
|---|---|---|
| `Admin.php` | `TechrappySEO\Admin\Admin` | Enregistre le menu admin, charge CSS/JS admin |
| `Settings.php` | `TechrappySEO\Admin\Settings` | Gère la page de réglages (Settings API) |
| `MetaBox.php` | `TechrappySEO\Admin\MetaBox` | Meta box sur les écrans post/page |
| `views/settings-page.php` | — | Template HTML de la page de réglages |
| `views/meta-box.php` | — | Template HTML de la meta box |

### public/

| Fichier | Classe | Rôle |
|---|---|---|
| `Frontend.php` | `TechrappySEO\Frontend` | Hooks front-end (balises SEO dans `<head>`) |

### services/ai/

| Fichier | Classe | Rôle |
|---|---|---|
| `ProviderInterface.php` | `TechrappySEO\Services\AI\ProviderInterface` | Contrat PHP pour les fournisseurs IA |
| `AIClient.php` | `TechrappySEO\Services\AI\AIClient` | Façade : instancie le bon provider, envoie les requêtes |

### services/seo/

| Fichier | Classe | Rôle |
|---|---|---|
| `SEOEngine.php` | `TechrappySEO\Services\SEO\SEOEngine` | Point d'entrée du moteur SEO |
| `ContentAnalyzer.php` | `TechrappySEO\Services\SEO\ContentAnalyzer` | Analyse le contenu textuel d'un post |

### services/queue/

| Fichier | Classe | Rôle |
|---|---|---|
| `Queue.php` | `TechrappySEO\Services\Queue\Queue` | CRUD sur la table `{prefix}techrappy_seo_queue` |
| `QueueWorker.php` | `TechrappySEO\Services\Queue\QueueWorker` | Exécute les jobs via WP-Cron |

### services/integrations/

| Fichier | Classe | Rôle |
|---|---|---|
| `YoastIntegration.php` | `TechrappySEO\Services\Integrations\YoastIntegration` | Bridge avec Yoast SEO |
| `DiviIntegration.php` | `TechrappySEO\Services\Integrations\DiviIntegration` | Bridge avec Divi Builder |
| `BulkProcessor.php` | `TechrappySEO\Services\Integrations\BulkProcessor` | Traitement en masse des posts |

---

## Constantes définies dans techrappy-seo.php

```php
TECHRAPPY_SEO_VERSION        // '1.0.0'
TECHRAPPY_SEO_PLUGIN_FILE    // __FILE__
TECHRAPPY_SEO_PLUGIN_DIR     // plugin_dir_path(__FILE__)
TECHRAPPY_SEO_PLUGIN_URL     // plugin_dir_url(__FILE__)
TECHRAPPY_SEO_PLUGIN_BASENAME // plugin_basename(__FILE__)
```

---

## Tables créées à l'activation

### `{prefix}techrappy_seo_queue`

```sql
CREATE TABLE IF NOT EXISTS {prefix}techrappy_seo_queue (
    id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id     BIGINT(20) UNSIGNED NOT NULL,
    action      VARCHAR(100)        NOT NULL,
    status      VARCHAR(20)         NOT NULL DEFAULT 'pending',
    priority    TINYINT(2)          NOT NULL DEFAULT 10,
    payload     LONGTEXT,
    attempts    TINYINT(3)          NOT NULL DEFAULT 0,
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status  (status)
) {charset_collate};
```

---

## Options WordPress initialisées à l'activation

```php
techrappy_seo_version          // Version courante du plugin
techrappy_seo_settings         // Tableau de réglages globaux (sérialisé)
```

---

## Hooks enregistrés en V1

| Hook WP | Callback | Priorité |
|---|---|---|
| `admin_menu` | `Admin::add_plugin_page` | 10 |
| `admin_init` | `Settings::register_settings` | 10 |
| `add_meta_boxes` | `MetaBox::register` | 10 |
| `save_post` | `MetaBox::save` | 10 |
| `admin_enqueue_scripts` | `Admin::enqueue_assets` | 10 |
| `wp_head` | `Frontend::output_seo_tags` | 1 |
| `techrappy_seo_run_queue` | `QueueWorker::process` | 10 |

---

## Cron WP enregistré

- **Hook** : `techrappy_seo_run_queue`
- **Fréquence** : `twicedaily` (toutes les 12h en V1)
- **Enregistré à** : activation via `Activator::schedule_cron()`
- **Supprimé à** : désactivation via `Deactivator::unschedule_cron()`
