# Instructions de développement — Techrappy SEO Plugin

## Prérequis

- PHP 8.0 ou supérieur
- WordPress 6.0 ou supérieur
- Composer (optionnel pour la V1, chargement manuel)
- WP-CLI (recommandé pour les tests)

---

## Installation locale

```bash
# Cloner le repo dans le dossier plugins WordPress
cd /path/to/wordpress/wp-content/plugins/
git clone <repo-url> techrappy-seo

# Activer le plugin
wp plugin activate techrappy-seo
```

---

## Conventions de code

### PHP

- Standard : **WordPress Coding Standards (WPCS)**
- Namespace racine : `TechrappySEO\`
- Chaque fichier de classe = une classe
- `defined('ABSPATH') || exit;` en première ligne (après `<?php`)
- Pas de `?>` en fin de fichier PHP
- Indentation : 1 tabulation (tab) comme WPCS

### Hooks

- Préfixe systématique : `techrappy_seo_`
- Actions : `do_action('techrappy_seo_{event}', $args)`
- Filtres : `apply_filters('techrappy_seo_{filter}', $value, $args)`

### Sécurité (obligatoire)

```php
// Vérification nonce dans save_post et AJAX
check_ajax_referer('techrappy_seo_nonce', '_nonce');

// Vérification capability
if (!current_user_can('edit_posts')) {
    wp_die(__('Permission refusée.', 'techrappy-seo'));
}

// Sanitisation des entrées
$value = sanitize_text_field($_POST['field']);

// Échappement des sorties
echo esc_html($value);
echo esc_url($url);
echo esc_attr($attr);
```

---

## Structure des options

Toutes les options du plugin sont stockées dans une seule option sérialisée :

```php
$settings = get_option('techrappy_seo_settings', []);

// Accès à une valeur
$api_key = $settings['ai_api_key'] ?? '';

// Mise à jour
$settings['ai_api_key'] = sanitize_text_field($new_key);
update_option('techrappy_seo_settings', $settings);
```

**Clés disponibles dans `techrappy_seo_settings` :**

| Clé | Type | Description |
|---|---|---|
| `ai_provider` | string | Fournisseur IA actif (`openai`, `anthropic`, etc.) |
| `ai_api_key` | string | Clé API du fournisseur IA |
| `ai_model` | string | Modèle IA à utiliser |
| `seo_auto_analyze` | bool | Analyse auto à la sauvegarde d'un post |
| `yoast_integration` | bool | Activer le bridge Yoast |
| `divi_integration` | bool | Activer le bridge Divi |
| `queue_batch_size` | int | Nombre de jobs traités par passe cron |

---

## Post Meta

Chaque post analysé stocke ses résultats SEO dans des meta :

| Meta key | Type | Description |
|---|---|---|
| `_techrappy_seo_score` | int | Score SEO (0-100) |
| `_techrappy_seo_suggestions` | array | Tableau de suggestions IA |
| `_techrappy_seo_focus_keyword` | string | Mot-clé principal |
| `_techrappy_seo_meta_title` | string | Title SEO personnalisé |
| `_techrappy_seo_meta_description` | string | Description SEO |
| `_techrappy_seo_analyzed_at` | datetime | Date de dernière analyse |
| `_techrappy_seo_ai_used` | bool | Analyse IA effectuée |

---

## Ajouter un fournisseur IA

1. Créer une classe implémentant `TechrappySEO\Services\AI\ProviderInterface`
2. La placer dans `services/ai/providers/`
3. L'enregistrer dans `AIClient::get_provider()`

```php
// Exemple
class OpenAIProvider implements ProviderInterface {
    public function generate(string $prompt, array $options = []): string {
        // ... appel API OpenAI
    }

    public function get_name(): string {
        return 'openai';
    }
}
```

---

## Ajouter un job à la file d'attente

```php
$queue = new \TechrappySEO\Services\Queue\Queue();
$queue->push([
    'post_id' => $post_id,
    'action'  => 'analyze_seo',
    'payload' => ['focus_keyword' => $keyword],
]);
```

---

## Ajouter un module d'intégration

1. Créer la classe dans `services/integrations/`
2. L'instancier dans `Plugin::define_integrations()`
3. Enregistrer ses hooks via le `Loader`

---

## Débogage

```php
// Logger dans le debug.log WordPress
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[TechrappySEO] ' . print_r($data, true));
}
```

---

## Tests

- Les tests unitaires iront dans `tests/` (PHPUnit + WP Mock)
- Les tests d'intégration via WP-CLI ou un environnement Local/Lando

---

## Roadmap modules post-V1

| Module | Priorité |
|---|---|
| Analyse SEO complète (readability, density, links) | Haute |
| Intégration OpenAI / Anthropic | Haute |
| Bridge Yoast (lecture/écriture champs) | Haute |
| Bulk Processor (traitement masse via queue) | Moyenne |
| Bridge Divi (extraction contenu modules) | Moyenne |
| REST API endpoints | Moyenne |
| Dashboard analytics | Basse |
| Multisite support | Basse |
