# SPEC — TechRappy SEO : génération de masse centralisée + refonte

Version : 4 octobre 2026 · Statut : à valider section par section
Périmètre : intégrer le moteur de l'ancien plugin dans le Rédacteur SEO central, refaire le front/back, ajouter la génération de masse par villes, le support Divi 5 et le GEO.

---

## 0. Objectif en une phrase
Depuis **un seul endroit** (ta centrale), générer **des pages SEO locales uniques par ville** sur **n'importe quel site connecté**, pendant que **chaque cliente** garde son **Rédacteur SEO guidé** — le tout aux standards **GEO** et compatible **Divi 4 et Divi 5**.

---

## 1. Architecture cible (modèle A — centralisé)

```
   TA CENTRALE (cabinet-visible.fr)                 SITE CLIENTE (Divi 4 ou 5)
   ┌─────────────────────────────────────┐          ┌───────────────────────────┐
   │  Moteur de génération (pipeline IA)  │          │  Brique « TechRappy       │
   │  Tes clés API (chiffrées)            │  ──────▶ │  Connect » (légère)        │
   │  Tableau de bord multi-site          │  publish │  + liaison par CODE        │
   │  Génération de masse (toi)           │          │  (INCHANGÉ — vidéo valable)│
   │  Rédacteur guidé (cliente)           │          │  Template(s) Divi          │
   │  Module GEO (audit + mesure)         │          └───────────────────────────┘
   └─────────────────────────────────────┘
```

- **Sur le site** : uniquement la brique Connect + le code de liaison (**inchangé**).
- **Sur la centrale** : tout le cerveau, tes clés, deux portes (toi = masse, cliente = guidé).

---

## 2. Principes qualité (GEO) — non négociables
Issus de `docs/documentation-seo-geo-2026-10.md`. Câblés dans l'outil :
1. **Une page par vraie intention / vraie ville**, au **contenu réellement unique** (jamais du `{{ville}}` recopié → risque « scaled content »).
2. **Réponse directe de 40-60 mots** en haut de page.
3. Section **« Pour qui / Pas adapté si »** avec le vocabulaire de la cible.
4. **FAQ de vraies questions** (sous-requêtes), en texte visible.
5. Contenu **non-commodity** (vécu, méthode, cas, chiffres propres).
6. **Jamais d'allégation de santé / promesse de résultat.**
7. **Schema @graph unique**, cohérent avec le visible, **sans avis/notes sur soi-même**.
8. Date de mise à jour **vraie**, phrase canonique cohérente (site, fiche Google, LinkedIn).
9. **Maillage interne** entre les pages générées.

---

## 3. Modules (back-end)

Légende : 🟢 garde (de l'ancien plugin) · 🟡 garde + améliore · 🔵 refait · 🆕 nouveau

| Module | Statut | Rôle | Données / API |
|---|---|---|---|
| **Pipeline génération** (Intent→Plan→Intro→Blocks→WriteBlock→FAQ→QA→Conclusion→Meta→AntiDuplicate→InternalLinks) | 🟡 | Génère le **contenu unique par page** | Claude Sonnet · prompts (méthode PDF + règles GEO) |
| **AntiDuplicate** | 🟡 | Évite la similarité entre pages villes | — |
| **Villes voisines** (BAN + villes-voisines.fr + cache) | 🟢 | Liste des villes dans un rayon | API geo (gratuit) |
| **Volumes mots-clés** | 🟡 | Filtrer les villes/mots-clés à **vraie demande** | DataForSEO |
| **SERP réelle** | 🟡 | Intention + concurrents (angle unique) | Serper.dev |
| **Couche Divi** (Detector, TokenScanner, TokenReplacer, RepeatableSection) | 🔵 | Injecter le contenu dans ton template, dupliquer les sections | — |
| **Divi 5 (blocs)** | 🆕 | Parser/cloner `wp:divi/section` via `parse_blocks()` + régénérer les IDs | WP core |
| **Jobs bulk** (file, runner, scheduler, PostWriter) | 🟡 | Générer N pages en file, suivi, reprise | cron WP |
| **MenuManager** | 🟢 | Ranger les pages dans un menu / pied de page (créer si absent) | — |
| **Liens internes / Slugs / Schema** | 🟡 | Maillage, URLs propres, @graph | — |
| **Connecteur multi-site** (brique Connect + liaison code) | 🟢 | Publier sur le site cible | REST `techrappy/v1` |
| **Tableau de bord multi-site** | 🆕 | Lister tous les sites reliés (statut Divi, dernière génération) | table centrale |
| **Prompt Studio** (prompts éditables + versionnés) | 🟡 | Modifier les prompts sans coder | — |
| **CostEstimator** | 🟡 | Estimer le coût avant de lancer | — |
| **Module GEO — Audit** | 🆕 | Noter une page sur la grille 0-20 | Claude |
| **Module GEO — Mesure visibilité IA** | 🆕 | Interroger ChatGPT/Gemini/Claude (personas, chats temporaires) → mentions/citations/score | APIs IA |
| **Clés & secrets** | 🔵 | Centralisées, chiffrées (jamais en dur ni par site) | credentials.env |
| **Logs / Diagnostic** | 🟢 | Traçabilité | — |

---

## 4. Écrans (front — refonte UX, inspiration « Rédiger sans migraine »)

| # | Écran | Pour qui | Contenu / actions |
|---|---|---|---|
| E1 | **Tableau de bord sites** | Toi | Liste des sites reliés · statut Divi (4/5) · dernière génération · bouton « Générer » |
| E2 | **Nouvelle génération de masse** | Toi | Site cible → mot-clé(s) → ville de base → rayon → gabarit Divi → **aperçu du coût** → lancer |
| E3 | **Sélecteur de villes** | Toi | Villes trouvées + **volume** par ville · cocher/filtrer (seuil de demande) |
| E4 | **Suivi de la file (jobs)** | Toi | Progression, pages créées (brouillon), erreurs, reprise |
| E5 | **Rédacteur guidé** | Cliente | Mot-clé → indice de difficulté → plan (score live) → rédaction (score /100) → publication |
| E6 | **Idée de contenu** | Cliente | Suggestions de mots-clés (volume, difficulté, CPC) triées « facile → difficile » |
| E7 | **Audit GEO d'une page** | Toi / cliente | Note 0-20 + recommandations concrètes |
| E8 | **Visibilité IA (GEO)** | Toi / cliente | Score IA, mentions, citations, par moteur, rapport hebdo |
| E9 | **Gabarits Divi (bibliothèque)** | Toi | Voir/choisir les templates (T1-T6), analyser les tokens |
| E10 | **Réglages** | Toi | Clés API (chiffrées), prompts (Prompt Studio), quotas/coûts |

---

## 5. Flux « génération de masse » (bout en bout)
1. Tu choisis un **site connecté** (E1) et un **mot-clé** + **ville de base** + **rayon** (E2).
2. L'outil récupère les **villes voisines** (BAN) et leur **volume** (DataForSEO) → tu filtres (E3).
3. Pour chaque ville retenue → **job** en file (E4).
4. Chaque job lance le **pipeline** : intention → plan → contenu **unique** (intro, H2, FAQ) → meta → anti-duplication → liens internes.
5. Le contenu est **injecté dans ton gabarit Divi** (tokens + section répétable), adapté **couleurs/images du site**.
6. La page est **publiée en brouillon** sur le site via la brique Connect, **rangée dans le menu/pied de page**.
7. Tu relis, tu publies. Option : **audit GEO** automatique sur chaque page.

---

## 6. Divi 5 — approche technique
- Détection : `ET_BUILDER_VERSION` ≥ 5 → mode « blocs », sinon mode « shortcodes » (double compat).
- Tokens simples : `str_replace` (identique aux deux formats).
- Section répétable Divi 5 : **`parse_blocks()`** pour isoler le `wp:divi/section` marqué, le cloner N fois, **régénérer les IDs de modules**, réinjecter, puis `serialize_blocks()`.
- Métas Divi copiées à la duplication (layout, couleurs) — liste déjà connue.
- **Validation obligatoire sur `test-techrappy.cabinet-visible.fr` (Divi 5.13.1)** avant generalisation.

---

## 7. API & coûts (ordre de grandeur)
| Service | Usage | Modèle de coût | État |
|---|---|---|---|
| **Claude Sonnet** | Rédaction unique + audit | à l'usage (tokens) | clé OK |
| **DataForSEO** | Volumes mots-clés/villes | **prépayé** (~0,05-0,10 $/1000 kw) | **0,82 $ — à recharger** |
| **Serper.dev** | SERP réelle | à l'usage | clé OK |
| **geo (BAN / villes-voisines)** | Villes voisines | **gratuit** | OK |
| **OpenAI (gpt-image-1)** | Visuels (option) | à l'usage | ⚠️ **clé exposée → révoquer** |
| **Mesure GEO** | Requêtes ChatGPT/Gemini/Claude | à l'usage (× passages × personas) | à cadrer (peut coûter) |

---

## 8. Sécurité & conformité
- **Clés centralisées et chiffrées** (jamais dans un doc, jamais par site). → **révoquer la clé OpenAI du docx**.
- **Santé** : aucun outil ne produit d'allégation de résultat (filtre en dur).
- **Anti-spam Google** : pas de pages « par variante » en volume → unicité réelle + filtre par demande.
- **Pas d'avis/mentions/classements fabriqués** (interdits GEO 16.2).
- Accès masse **réservé à toi** (`manage_options`).

---

## 9. Phasage & estimation (t-shirt : S ≈ petit, M ≈ moyen, L ≈ gros)

| Phase | Contenu | Taille |
|---|---|---|
| **P0** ✅ | Base GitHub versionnée (fait) | — |
| **P1** | **Tableau de bord multi-site** + mécanique brique/code (inchangée) | M |
| **P2** | **Porter le moteur** dans la centrale (pipeline + villes + volumes + SERP, tes clés) | L |
| **P3** | **Couche Divi 5** (blocs) + double compat + test sur test-techrappy | M |
| **P4** | **Génération de masse** de bout en bout (E2→E4) sur 1 site test | L |
| **P5** | **Refonte UX** complète (E5-E6 guidé + E1-E4 masse) + indice difficulté + score live | L |
| **P6** | **Qualité GEO** : prompts (méthode PDF + règles GEO), gabarits T1-T6, audit (E7) | M |
| **P7** | **Mesure GEO** (E8) + rapports hebdo + Search Console (plus tard) | M |

Ordre recommandé : P1 → P2 → P3 → P4 (démo masse réelle) → P5 → P6 → P7.

---

## 10. Décisions à valider
1. Phasage ci-dessus OK, ou tu veux voir **le tableau de bord (P1) en premier** vite ?
2. Génération de masse : d'abord **tes sites TechRappy** ou directement **sites clientes** ?
3. Double compatibilité Divi 4 **ET** 5, ou **Divi 5 seulement** (plus simple si tes nouveaux sites sont tous en 5) ?
4. Mesure GEO : on la cadre maintenant (coût API) ou on la garde pour la fin ?
