# TECHRAPPY SEO — Brief complet Claude Code (v2.0)

> Plugin WordPress interne, évolutif vers produit commercialisable.  
> **Accès : admin uniquement (`manage_options`)**

---

## A. Stack & contraintes

| Élément | Détail |
|---|---|
| Langage | PHP 8+ strict |
| Environnement | WordPress + Divi Builder |
| SEO | Yoast SEO |
| Queue | Action Scheduler (obligatoire pour bulk) |
| APIs externes | OpenAI, Villes-Voisines, BAN/API Adresse Gouv |
| Stockage clés API | `wp_options` autoload=no, admin uniquement |
| Frontend admin | Vanilla JS ou jQuery WP — pas de framework lourd |
| Compatibilité Divi | Ne jamais corrompre le JSON/shortcodes Divi dans `post_content` |

---

## B. Structure du plugin

```
techrappy-seo/
├── techrappy-seo.php              # Bootstrap, hooks, menu
├── includes/
│   ├── class-ai-client.php        # Client OpenAI centralisé (tous les appels passent ici)
│   ├── class-pipeline.php         # Orchestration des 10 étapes IA
│   ├── class-divi-handler.php     # Duplication template + remplacement tokens + sections répétables
│   ├── class-slug-helper.php      # Slug SEO-friendly + gestion doublons
│   ├── class-yoast-helper.php     # update_post_meta Yoast
│   ├── class-menu-manager.php     # Gestion menus (bulk)
│   ├── class-queue-handler.php    # Action Scheduler — jobs bulk
│   ├── class-cities-api.php       # Villes-Voisines + BAN (cache transients)
│   └── class-template-manager.php # Import/export JSON templates
├── admin/
│   ├── page-wizard.php            # Wizard de génération (étapes 1→5)
│   ├── page-bulk-jobs.php         # Liste jobs, statut, logs
│   ├── page-prompt-studio.php     # Éditeur prompts + tester
│   ├── page-templates.php         # Import/export templates
│   └── page-settings.php          # Clé API, modèle, debug
└── assets/
    ├── admin.js
    └── admin.css
```

---

## C. Menu WordPress Admin

```
Techrappy SEO
├── New Generation    (wizard)
├── Bulk Jobs         (liste jobs, statuts, logs, erreurs)
├── Templates         (import/export, audit tokens)
├── Prompts           (Prompt Studio)
└── Settings          (API, defaults)
```

---

## D. Modèles de données (JSON)

### D1 — Token spec

**Pattern de détection :** `/\{\{([a-zA-Z0-9_\-]+)\}\}/`

**Tokens standards :**
```
H1, intro, metatitle, metadescription, slug
keyword, city
internal_links, faq_block, cta_block
h2_1, text_1, h2_2, text_2, ...       ← sections nommées
h2, text                               ← dans section répétable
```

### D2 — Mapping token → source

Stockage : `wp_options` clé `techrappy_seo_template_map_{POST_ID}` (autoload=no)

```json
{
  "template_post_id": 123,
  "builder": "divi",
  "tokens_found": ["H1","intro","faq_block","h2","text"],
  "tokens_missing_recommended": ["internal_links"],
  "mapping": {
    "H1":              {"source":"ai",  "step":"plan.H1"},
    "intro":           {"source":"ai",  "step":"intro.intro_longue"},
    "metatitle":       {"source":"ai",  "step":"meta.meta_title_1"},
    "metadescription": {"source":"ai",  "step":"meta.meta_desc_1"},
    "slug":            {"source":"wp",  "step":"slug"},
    "faq_block":       {"source":"ai",  "step":"faq.faq_visible_html+faq.faq_jsonld"},
    "internal_links":  {"source":"ai",  "step":"links"},
    "cta_block":       {"source":"ai",  "step":"conclusion.cta_html"},
    "h2":              {"source":"ai",  "step":"blocks[i].H2"},
    "text":            {"source":"ai",  "step":"blocks[i].html"}
  }
}
```

### D3 — Job unitaire

```json
{
  "job_id": "uuid",
  "mode": "single",
  "type": "page|post",
  "template_post_id": 123,
  "keyword": "osteopathe beauzelle",
  "city": "Beauzelle",
  "publish_status": "draft|publish",
  "wp_params": {"parent_id": 0, "category_id": 12, "tags": [1,2]},
  "slug_rule": "from_keyword|from_h1",
  "steps": {
    "intent":     {"status": "pending|ok|error", "data": {}},
    "plan":       {"status": "...", "data": {}},
    "blocks_list":{"status": "...", "data": {}},
    "intro":      {"status": "...", "data": {}},
    "blocks":     {"status": "...", "data": []},
    "conclusion": {"status": "...", "data": {}},
    "meta":       {"status": "...", "data": {}},
    "faq":        {"status": "...", "data": {}},
    "links":      {"status": "...", "data": []},
    "qa":         {"status": "...", "data": {}}
  },
  "result": {"post_id": 0, "permalink": "", "slug": ""},
  "logs": [{"t": 0, "step": "", "msg": ""}]
}
```

### D4 — Job bulk

```json
{
  "job_id": "uuid",
  "mode": "bulk",
  "type": "page",
  "template_post_id": 123,
  "keyword_base": "osteopathe",
  "start_cp": "31700",
  "radius_km": 20,
  "cities": [{"city":"Blagnac","cp":"31700"}, {"city":"...","cp":"..."}],
  "publish_status": "draft|publish",
  "preview_city": "Blagnac",
  "anti_duplicate": true,
  "menu_action": "none|add_existing|create_new",
  "menu_options": {
    "menu_id": 0,
    "new_menu_name": "",
    "location": "header|footer|none",
    "label_format": "title|keyword_city|custom",
    "label_custom": "",
    "skip_if_url_exists": true,
    "published_only": true
  },
  "queue": {"total": 0, "done": 0, "errors": 0},
  "children": [{"child_id":"uuid","city":"...","keyword":"...","status":"pending"}]
}
```

**Stockage jobs :** table custom `wp_techrappy_seo_jobs` (recommandé) ou CPT `techrappy_job` + meta (acceptable V1).

---

## E. Wizard UX (5 étapes)

### Étape 1 — Mode
- `page` : Générer une page SEO
- `post` : Générer un article de blog SEO
- `bulk` : Génération en masse (pages locales)

### Étape 2 — Template Divi
Sélection d'une page ou article existant comme template source.

### Étape 3 — Audit du template (obligatoire)
1. Scanner `post_content` avec le pattern `/\{\{([a-zA-Z0-9_\-]+)\}\}/`
2. Afficher : tokens détectés / tokens recommandés manquants
3. Proposer le mapping token → source (IA / WordPress / manuel)

### Étape 4 — Paramètres WordPress
- Articles : catégorie, tags
- Pages : page parente
- Publication : `draft` ou `publish`

### Étape 5 — Paramètres SEO
- Mot-clé principal
- Type de contenu : `page_seo` ou `article_blog`
- Règle de slug : `from_keyword` ou `from_h1`

---

## F. Gestion Divi

### F1 — Dupliquer le template
```php
// 1. Lire le post template
$template = get_post($template_post_id);

// 2. Créer le nouveau post
$new_id = wp_insert_post([
    'post_type'    => 'page|post',
    'post_status'  => 'draft',
    'post_title'   => $keyword,
    'post_content' => $template->post_content,
]);

// 3. Copier TOUTES les metas Divi (sauf identifiants uniques)
$divi_metas = ['et_pb_use_builder','_et_pb_use_builder','et_pb_old_content'];
foreach ($divi_metas as $key) {
    $val = get_post_meta($template_post_id, $key, true);
    if ($val) update_post_meta($new_id, $key, $val);
}
```

### F2 — Remplacer tokens simples
```php
// Remplacer {{TOKEN}} par sa valeur
// Token absent dans le contenu → ignoré
// Token présent mais sans valeur → vide + log warning
$content = preg_replace('/\{\{' . $token . '\}\}/', $value, $content);
```

### F3 — Sections répétables

**Principe :** Une section Divi contient `{{repeatable_section}}` pour la marquer.

**Algorithme :**
1. Parser `post_content` et extraire le bloc `[et_pb_section ...]...[/et_pb_section]` contenant `{{repeatable_section}}`
2. Ce bloc = template de section
3. Dupliquer N fois (N = nombre de blocs H2 générés)
4. Dans chaque copie :
   - Retirer `{{repeatable_section}}`
   - Remplacer `{{h2}}` et `{{text}}` (ou `{{h2_i}}`/`{{text_i}}`)
5. Dans le contenu final : remplacer la section repeatable d'origine par les N copies

**Fallback** si aucune section répétable trouvée : mode `{{text_1}}`, `{{h2_1}}`, etc.

---

## G. Intégrations SEO

### Yoast
```php
update_post_meta($post_id, '_yoast_wpseo_title',    $meta_title);
update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
```

### Slug
```php
// Générer slug à partir du mot-clé ou du H1
$slug = sanitize_title($source_string); // retire accents, espaces → tirets
// Gérer doublons : ajouter suffixe numérique si slug existe déjà
```

---

## H. APIs Villes

### Villes-Voisines
```
GET https://www.villes-voisines.fr/getcp.php?cp={CP}&rayon={0..50}
Retour : JSON tableau de codes postaux
```

### BAN — CP → Commune
```
GET https://api-adresse.data.gouv.fr/search/?q={cp}&type=municipality
Parser : best match → label + postcode
Cache : set_transient("techrappy_city_{cp}", $data, 7 * DAY_IN_SECONDS)
```

### UX bulk
1. Saisie CP + rayon (10 / 20 / 30 km)
2. Récupération + déduplication des communes
3. Table avec checkboxes + compteur de pages
4. Preview : générer 1 ville → afficher → confirmation avant lancement

---

## I. Queue — Action Scheduler

```php
// Enqueue une action par ville
as_enqueue_async_action(
    'techrappy_generate_city_page',
    ['job_id' => $bulk_job_id, 'child_id' => $child_id],
    'techrappy-seo'
);

// Handler
add_action('techrappy_generate_city_page', function($job_id, $child_id) {
    // Exécuter pipeline single pour cette ville
    // Mettre à jour queue.done / queue.errors
});
```

Bulk continue même si une ville échoue.

---

## J. Gestion des menus (bulk)

Options avant lancement :
- `none` : ne rien faire
- `add_existing` : ajouter au menu sélectionné (dropdown)
- `create_new` : créer un menu (nom libre) + assigner emplacement (`header`/`footer`)

Paramètres :
- Format libellé : `title` / `keyword + ville` / modèle personnalisé
- Anti-duplication : skip si URL déjà présente dans le menu
- Ajout uniquement pour pages `publish`
- Erreurs loguées dans le job

---

## K. Pipeline IA — 10 étapes

Chaque étape : **loguée + affichée en preview + relançable individuellement**.

### System prompt (préfixe tous les appels)

```
Règles absolues :
- Ne fabrique jamais de faits locaux précis (rues, lieux, chiffres) si non fournis.
- Style : clair, humain, professionnel, accessible.
- Pas de blabla "en tant qu'IA".
- Respect strict du FORMAT demandé (JSON si demandé).
- Évite le contenu générique : chaque section doit apporter une info concrète.
- Français uniquement.
```

---

### Étape 1 — INTENT
**Inputs :** `professions`, `mot_cle`, `type_contenu`

```
Tu es expert SEO depuis 15 ans, spécialiste de la rédaction web pour les sites de {{professions}}.
Mot-clé principal : {{mot_cle}}
Type de contenu : {{type_contenu}} (page_seo ou article_blog)

Mission :
1) Déduis l'intention principale et secondaires.
2) Liste les "must-have topics" (10 max) qui dominent la SERP.
3) Donne 10 questions PAA probables (formulation naturelle).
4) Donne 15 mots-clés secondaires/variantes.
5) Donne le ton recommandé + risques SEO à éviter.
```

**Output JSON :**
```json
{
  "intent_principale": "",
  "intent_secondaires": [],
  "types_contenus_dominants": [],
  "must_have_topics": [],
  "paa_questions": [],
  "keywords_secondaires": [],
  "ton_recommande": "",
  "risques_a_eviter": []
}
```

---

### Étape 2 — PLAN
**Inputs :** `mot_cle`, `intent_json`, `type_contenu`, `city` (optionnel)

```
Tu es expert SEO et rédacteur web depuis 15 ans pour {{professions}}.
Mot-clé : {{mot_cle}}
Voici l'analyse d'intention (JSON) :
{{intent_json}}

Objectif : proposer un plan SEO complet, hiérarchisé, supérieur à la SERP.

Contraintes :
- H2 = sujets indispensables, pas de titres vagues.
- Inclure une FAQ (5 questions).
- Prévoir un emplacement CTA.
- Si page locale : inclure un bloc "spécificités locales" sans inventer de lieux précis.
- Fournir un slug suggéré SEO-friendly.
```

**Output JSON :**
```json
{
  "H1": "",
  "slug_suggere": "",
  "sections": [
    {
      "H2": "",
      "intention": "",
      "type_contenu_attendu": "",
      "keywords_a_integrer": [],
      "H3": []
    }
  ],
  "cta_placement": "après quelle section",
  "faq_seed_questions": []
}
```

> ⚠️ Afficher le plan en UI éditable avant de continuer. Utiliser le plan (éventuellement modifié) pour les étapes suivantes.

---

### Étape 2B — BLOCKS LIST
**Inputs :** `plan_json`

```
À partir de ce plan JSON, produis :
- nb_blocs_repetables : nombre de sections "contenu" à écrire (exclure FAQ/CTA si gérés ailleurs)
- blocs : liste ordonnée des blocs à rédiger

Entrée :
{{plan_json}}
```

**Output JSON :**
```json
{
  "nb_blocs_repetables": 0,
  "blocs": [
    {"ordre": 1, "H2": "", "H3": [], "intention": "", "keywords": [], "type_contenu": ""}
  ]
}
```

---

### Étape 3 — INTRO
**Inputs :** `mot_cle`, `H1`, `plan_json`

```
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Plan : {{plan_json}}

Rédige une intro 120-180 mots :
- mot-clé dans les 2 premières phrases
- accroche + rassurance + promesse réaliste
- transition vers le 1er H2
```

**Output JSON :**
```json
{
  "intro_longue_html": "<p>...</p>",
  "intro_courte_mobile": "..."
}
```

---

### Étape 4 — WRITE BLOCK (boucle sur chaque bloc)
**Inputs :** `mot_cle`, `bloc_a_rediger_json`, `professions`

```
Contexte : site de {{professions}}
Mot-clé : {{mot_cle}}

Bloc à rédiger (JSON) :
{{bloc_a_rediger_json}}

Contraintes :
- 180 à 260 mots (si liste : autoriser <ul><li>)
- phrases courtes, une idée par paragraphe
- intégrer keywords naturellement
- terminer par une transition
- ne pas inventer de faits locaux précis
```

**Output JSON :**
```json
{
  "H2": "(reprendre le titre exact)",
  "html": "<p>...</p>",
  "micro_transition": ""
}
```

---

### Étape 5 — CONCLUSION + CTA
**Inputs :** `mot_cle`, `H1`, `plan_json`

```
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Plan : {{plan_json}}

Donne :
- 3 titres H2 de fin (pas "Conclusion")
- 2 variantes de conclusion 150-200 mots (douce / pro)
- 1 CTA simple
```

**Output JSON :**
```json
{
  "h2_fin_suggestions": [],
  "conclusion_douce_html": "<p>...</p>",
  "conclusion_pro_html": "<p>...</p>",
  "cta_html": "<p>...</p>"
}
```

---

### Étape 6 — META
**Inputs :** `mot_cle`, `H1`, `intent_principale`

```
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Intention : {{intent_principale}}

Contraintes :
- meta title 55-65 caractères
- meta desc 140-160 caractères
- 2 variantes de chaque
```

**Output JSON :**
```json
{
  "meta_title_1": "",
  "meta_title_2": "",
  "meta_desc_1": "",
  "meta_desc_2": ""
}
```

---

### Étape 7 — FAQ BLOCK (pour `{{faq_block}}`)
**Inputs :** `mot_cle`, `plan_json`

```
Mot-clé : {{mot_cle}}
Plan : {{plan_json}}

Génère 5 Q/R réellement pertinentes (PAA-like).
Réponses 45-75 mots, concrètes, rassurantes, sans promesse médicale.
```

**Output JSON :**
```json
{
  "faq_visible_html": "<section class='techrappy-faq'>...</section>",
  "faq_jsonld": "<script type='application/ld+json'>{...}</script>"
}
```

---

### Étape 8 — INTERNAL LINKS (pour `{{internal_links}}`)
**Inputs :** `mot_cle`, `pages_site_liste`

```
Mot-clé : {{mot_cle}}
Pages existantes (Titre + URL) :
{{pages_site_liste}}

Propose 5 liens max, pertinents UX+SEO.
```

**Output JSON :**
```json
[
  {"url": "", "anchor": "", "placement": "", "why": ""}
]
```

---

### Étape 9 — ANTI DUPLICATE (bulk uniquement)
**Inputs :** `keyword_base`, `city`

```
Mot-clé base : {{keyword_base}}
Ville : {{city}}

Produis :
- 3 angles d'intro (A/B/C)
- 1 intro finale unique 140-180 mots
- 6 phrases "variantes locales" génériques (sans inventer de lieux précis)
```

**Output JSON :**
```json
{
  "angles": [],
  "intro_finale_html": "<p>...</p>",
  "variantes_locales": []
}
```

---

### Étape 10 — QA (optionnel, gate avant publication)
**Inputs :** `mot_cle`, `full_content_html`

```
Mot-clé : {{mot_cle}}
Contenu HTML :
{{full_content_html}}

Retourne un diagnostic SEO/humain + corrections.
```

**Output JSON :**
```json
{
  "score_seo": 0,
  "score_humain": 0,
  "problemes": [],
  "fixes_rapides": [],
  "rewrite_intro_suggeree_html": "<p>...</p>"
}
```

---

## L. Pseudo-code du pipeline

### L1 — Génération unitaire

```
1.  Charger template + mapping
2.  Prompt 1  → intent_json
3.  Prompt 2  → plan_json
4.  [UI] Afficher plan éditable → attendre confirmation
5.  Prompt 2B → blocks_list
6.  Prompt 3  → intro_html
7.  Pour chaque bloc : Prompt 4 → block_html
8.  Prompt 5  → conclusion + CTA
9.  Prompt 6  → meta title + meta desc
10. Prompt 7  → faq_block (HTML + JSON-LD)
11. Prompt 8  → internal_links
12. Assembler contenu = duplication template + sections répétables + remplacement tokens
13. [Optionnel] Prompt 10 QA → gate avant publication
14. wp_insert_post / wp_update_post
15. update_post_meta Yoast
16. Définir slug + taxonomies + parent
17. Retourner preview + permalink
```

### L2 — Génération en masse

```
1. Appel Villes-Voisines → liste CP
2. CP → communes via BAN (avec cache)
3. [UI] Afficher table checkboxes
4. Générer preview pour 1 ville sélectionnée (pipeline single)
5. [UI] Confirmer publish_status + options menu
6. Pour chaque ville cochée : as_enqueue_async_action(...)
7. Afficher progression en temps réel (done/total/errors)
8. Post-traitement menus selon menu_action
```

---

## M. Client OpenAI centralisé

```php
// Endpoint
POST https://api.openai.com/v1/responses

// Tous les appels passent par class-ai-client.php
// Retry : 2 tentatives max, puis status = error
// Format sortie : JSON strict
// Log : step + raw response + timestamp + tokens consommés

// Réglages (wp_options, autoload=no) :
techrappy_seo_openai_key        // champ password
techrappy_seo_model             // ex: gpt-4o
techrappy_seo_temperature
techrappy_seo_timeout
techrappy_seo_max_tokens
techrappy_seo_debug             // bool
techrappy_seo_cost_threshold    // seuil confirmation utilisateur (en $)
```

---

## N. Prompt Studio

Menu : **Techrappy SEO → Prompts**

**Onglets :** Prompts | Variables | Tester | Historique

### Stockage (V1)
```php
// wp_options, autoload = no
$key = 'techrappy_seo_prompts';

// Structure :
{
  "version": 1,
  "system_prompt": "...",
  "prompts": {
    "intent":       {"template": "...", "response_format": "json_object"},
    "plan":         {"template": "...", "response_format": "json_object"},
    "blocks_list":  {"template": "...", "response_format": "json_object"},
    "intro":        {"template": "...", "response_format": "json_object"},
    "block_write":  {"template": "...", "response_format": "json_object"},
    "conclusion":   {"template": "...", "response_format": "json_object"},
    "meta":         {"template": "...", "response_format": "json_object"},
    "faq":          {"template": "...", "response_format": "json_object"},
    "internal_links":{"template": "...", "response_format": "json_object"},
    "anti_duplicate":{"template": "...", "response_format": "json_object"},
    "qa":           {"template": "...", "response_format": "json_object"}
  }
}
```

### Variables disponibles (à afficher avec bouton "Insérer")
```
{{mot_cle}}  {{keyword_base}}  {{city}}  {{professions}}
{{intent_json}}  {{plan_json}}  {{bloc_a_rediger_json}}
{{pages_site_liste}}  {{full_content_html}}
{{H1}}  {{intent_principale}}
```

### Onglet Tester
- Dropdown sélection prompt
- Champs auto-générés selon variables `{{...}}` détectées dans le template
- Bouton "Run test"
- Sortie : JSON brut + validation (OK/erreur) + temps de réponse + tokens

### Sécurité
- Accès `manage_options` uniquement
- Bouton "Réinitialiser prompts par défaut"
- Si `response_format = json_object` → valider que le JSON est parsable avant enregistrement

---

## O. Import / Export templates

- **Export** : JSON d'un template Divi (post_content + tokens détectés + mapping)
- **Import** : parse automatique des tokens → afficher liste → sélectionner comme template actif
- **Template par défaut** : configurable en Settings
- **Preview** : rendu avec valeurs d'exemple avant validation

---

## P. Gestion des erreurs

| Situation | Comportement |
|---|---|
| Token présent, valeur manquante | Remplacer par vide + log warning |
| Token absent du template | Ignorer silencieusement |
| Section répétable absente | Fallback mode `{{text_1}}`, `{{h2_1}}` |
| Échec API OpenAI | Retry 2× → status `error` → relançable manuellement |
| Échec d'une ville (bulk) | Logger + continuer les autres villes |
| `response_format=json_object` non parsable | Retry avec instruction renforcée |

---

## Q. Règles globales (checklist finale)

- [ ] Nonce sur tous les formulaires admin
- [ ] Sanitisation de toutes les entrées (`sanitize_text_field`, `wp_kses_post`, etc.)
- [ ] Clés API jamais exposées en frontend
- [ ] Aucune dépendance frontend lourde
- [ ] Ne jamais inventer de lieux précis dans le contenu local
- [ ] Compatible Divi : ne jamais corrompre les shortcodes Divi dans `post_content`
- [ ] Action Scheduler requis pour tout bulk (pas de timeout HTTP)
- [ ] Logs persistants consultables dans l'admin
