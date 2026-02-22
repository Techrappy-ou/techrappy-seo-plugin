# TECHRAPPY SEO – Plugin WordPress (Brief Claude Code)

## Stack technique

- **Environnement** : WordPress (PHP 8+) + Divi Builder
- **Slug plugin** : `techrappy-seo`
- **API IA** : OpenAI (`POST https://api.openai.com/v1/responses`)
- **Queue** : Action Scheduler (ou WP Cron)
- **Rôles** : Admin uniquement

---

## Architecture des fichiers (attendue)

```
techrappy-seo/
├── techrappy-seo.php          # Bootstrap plugin
├── includes/
│   ├── class-ai-client.php    # Client OpenAI centralisé
│   ├── class-generator.php    # Pipeline de génération
│   ├── class-divi-handler.php # Duplication + remplacement tokens
│   ├── class-slug-helper.php  # Génération slug SEO-friendly
│   ├── class-yoast-helper.php # Injection meta Yoast
│   ├── class-menu-manager.php # Gestion menus bulk
│   └── class-queue-handler.php# File d'attente génération masse
├── admin/
│   ├── wizard/                # Étapes du wizard UX
│   ├── prompt-studio/         # Éditeur de prompts
│   └── settings/              # Réglages API
└── assets/
```

---

## Fonctionnalité 1 – Wizard de génération (UX)

### Étape 1 – Mode
- `page` : Générer une page SEO
- `article` : Générer un article de blog SEO
- `bulk` : Génération en masse (pages locales)

### Étape 2 – Template Divi
- Sélection d'une page ou article existant comme template source

### Étape 3 – Audit du template (obligatoire)
Scanner le template Divi et détecter tous les tokens `{{...}}`.

**Tokens standards reconnus :**
```
{{H1}}, {{intro}}, {{metatitle}}, {{metadescription}}, {{slug}},
{{keyword}}, {{city}},
{{h2}}, {{text}}, {{h2_1}}, {{text_1}}, ...
{{internal_links}}, {{faq_block}}, {{cta_block}}
```

Afficher :
- Tokens détectés dans le template
- Tokens recommandés manquants
- Mapping token → source : `IA / WordPress / manuel`

### Étape 4 – Paramètres WordPress
- Articles : catégorie, tags
- Pages : page parente
- Publication : `draft` ou `publish`

### Étape 5 – Paramètres SEO
- Mot-clé principal
- Type de contenu : `page locale` ou `article informatif`
- Règle de slug : basé sur mot-clé ou H1

---

## Fonctionnalité 2 – Templates Divi (duplication + tokens)

### Duplication
1. Dupliquer la page/article template via l'API WordPress
2. Conserver structure, styles, images, CTA
3. Remplacer uniquement les tokens `{{...}}`

### Sections répétables
- Une section "contenu SEO" contient `{{h2}}` + `{{text}}`
- Le plugin duplique cette section N fois selon le nombre de blocs H2 générés
- Supprime les sections répétables en trop

### Import / Export de templates
- Export JSON d'un template Divi (avec liste des tokens détectés)
- Import JSON → analyse automatique des tokens → sélection comme template actif
- Définir un template par défaut
- Prévisualisation avec valeurs d'exemple

---

## Fonctionnalité 3 – Pipeline IA (10 étapes)

Chaque étape : loguée, affichée en preview, relançable en cas d'erreur.

| # | Clé | Description |
|---|-----|-------------|
| 1 | `intent` | Analyse d'intention (SERP) |
| 2 | `plan` | Plan H1/H2/H3 |
| 3 | `blocks_list` | Liste des blocs à rédiger |
| 4 | `intro` | Introduction SEO |
| 5 | `block_write` | Rédaction blocs H2 (un par un) |
| 6 | `conclusion_cta` | Conclusion + CTA |
| 7 | `meta` | Meta title + meta description |
| 8 | `faq` | FAQ HTML + JSON-LD FAQPage |
| 9 | `internal_links` | Maillage interne (3 à 5 liens) |
| 10 | `anti_duplicate` | Variations pour bulk |
| 11 | `qa` | Score SEO + suggestions qualité |

**Format de sortie attendu** : JSON strict pour chaque étape.

---

## Fonctionnalité 4 – SEO technique

### Yoast SEO
Renseigner via `update_post_meta()` :
- `_yoast_wpseo_title`
- `_yoast_wpseo_metadesc`

### Slug
- Génération SEO-friendly (sans accents, espaces → tirets)
- Source configurable : mot-clé principal ou H1 généré
- Gestion des doublons (suffixe numérique)

### Maillage interne (`{{internal_links}}`)
- Suggestion IA de 3 à 5 liens internes
- Injection via token ou placement automatique dans le contenu

### FAQ (`{{faq_block}}`)
Générer :
1. FAQ HTML visible (balises `<details>` ou structure Divi)
2. JSON-LD `FAQPage` injecté en `<script type="application/ld+json">`

---

## Fonctionnalité 5 – Génération en masse (pages locales)

### API villes voisines
```
GET https://www.villes-voisines.fr/getcp.php?cp={CODE_POSTAL}&rayon={RAYON}
GET https://api-adresse.data.gouv.fr/search/?q={CP}   ← CP → nom commune
```

### UX bulk
1. Saisie code postal + rayon (10 / 20 / 30 km)
2. Récupération automatique des communes
3. Liste avec checkboxes + compteur de pages à générer
4. Cache 24h + fallback saisie manuelle

### Anti-duplicate SEO (obligatoire)
- Intro unique par ville (générée par IA, étape `anti_duplicate`)
- Variations lexicales sur le mot-clé
- Bloc "spécificités locales" générique
- **Interdit** : inventer des lieux précis

### File d'attente
- Génération via Action Scheduler
- Preview d'UNE page exemple avant lancement global
- Validation : tout en `draft` ou tout `publish`
- Logs par page générée

---

## Fonctionnalité 6 – Gestion automatique des menus (bulk)

Options proposées à l'admin avant lancement :
- Ne rien faire
- Ajouter les pages à un menu existant (sélection dropdown)
- Créer un nouveau menu (nom libre) + assignation emplacement (`header` / `footer`)

Paramètres :
- Format du libellé : titre de page / `mot-clé + ville` / modèle personnalisé
- Anti-duplication : ne pas ajouter si l'URL est déjà présente dans le menu
- Ajout uniquement pour les pages en statut `publish`
- Erreurs loguées

---

## Fonctionnalité 7 – Prompt Studio (admin)

Menu WP : **Techrappy SEO → Prompts**

### Onglets
1. **Prompts** – Éditeur par étape de pipeline
2. **Variables** – Liste des variables disponibles avec bouton "Insérer"
3. **Tester** – Exécuter un prompt avec variables manuelles
4. **Historique** – Versions précédentes + restauration

### Stockage (V1)
```php
// wp_options, autoload = no
$key = 'techrappy_seo_prompts';

// Structure JSON :
{
  "version": 3,
  "system_prompt": "...",
  "prompts": {
    "intent":        { "template": "...", "response_format": "json_object" },
    "plan":          { "template": "...", "response_format": "json_object" },
    "block_write":   { "template": "...", "response_format": "json_object" }
    // ... toutes les étapes
  }
}
```

### Variables disponibles (à afficher dans l'UI)
```
{{mot_cle}}, {{keyword_base}}, {{city}}
{{intent_json}}, {{plan_json}}
{{bloc_a_rediger_json}}
{{pages_site_liste}}
{{full_content_html}}
{{professions}}
```

### Tester un prompt
- Dropdown sélection prompt
- Champs auto-générés selon variables `{{...}}` détectées
- Bouton "Run test"
- Sortie : JSON brut + validation JSON (OK/erreur) + temps de réponse + tokens consommés

### Sécurité
- Accès admin uniquement
- Bouton "Réinitialiser prompts par défaut"
- Si `response_format = json_object` → valider que le JSON est parsable avant enregistrement

---

## Fonctionnalité 8 – Réglages API OpenAI

Menu WP : **Techrappy SEO → Settings**

Champs :
- Clé API OpenAI (champ `password`, stocké dans `wp_options`, autoload = no)
- Sélecteur de modèle (ex : `gpt-4o`, `gpt-4o-mini`)
- Température (0.0 – 1.0)
- Timeout (secondes)
- Max tokens
- Mode debug (on/off)

### Client PHP centralisé
Tous les appels OpenAI passent par `class-ai-client.php` :
- `POST https://api.openai.com/v1/responses`
- Sortie : JSON strict
- Retry automatique en cas d'échec
- Logs par étape

### Coûts & garde-fous
- Estimation du coût avant génération (basée sur modèle + tokens estimés)
- Confirmation utilisateur au-delà d'un seuil configurable
- Confirmation supplémentaire obligatoire pour la génération en masse

---

## Prévisualisation & Validation

### Génération unitaire
- Preview du plan SEO (éditable avant rédaction)
- Preview HTML finale
- Boutons : `Enregistrer en brouillon` / `Publier`

### Génération en masse
- Preview d'UNE page générée (exemple)
- Validation globale → lancement de la file d'attente
- Statut en temps réel des jobs

---

## Logs & Debug

- Panel de logs dans l'admin (Menu : **Techrappy SEO → Logs**)
- Log par étape de pipeline
- Log par page générée (bulk)
- Possibilité de relancer une étape échouée
- Mode debug : affichage des prompts envoyés + réponses brutes

---

## Contraintes & règles globales

- PHP 8+ strict
- Aucune dépendance frontend lourde (pas de framework JS obligatoire, vanilla ou jQuery WP uniquement)
- Toutes les clés API chiffrées / stockées hors autoload
- Accès admin uniquement (capacité `manage_options`)
- Compatible Divi Builder (ne pas casser le JSON Divi stocké en `post_content`)
- Ne jamais inventer de lieux précis dans le contenu local généré
- Sanitisation et nonce sur tous les formulaires admin
