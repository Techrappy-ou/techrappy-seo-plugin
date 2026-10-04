# Documentation SEO + GEO : synthèse des recherches

Version : 4 octobre 2026
Périmètre : SEO, GEO (Generative Engine Optimization), recherche IA (Google AI Overviews, AI Mode, Gemini, ChatGPT, Perplexity, Claude), pages de classement, persona et personnalisation, schema, UX et vidéo.
Usage prévu : base de connaissances pour améliorer les outils (génération de pages, audits, posts, emails) pour des thérapeutes, praticiens bien-être et coachs, sur WordPress et Divi, avec des pages légères.

---

## 0. Mode d'emploi

### Légende des niveaux de preuve

| Tag | Signification | Comment l'utiliser |
|---|---|---|
| [OFFICIEL] | Documentation ou annonce de Google, OpenAI, web.dev | Règle ferme |
| [ETUDE] | Étude chiffrée avec méthode décrite | Ordre de grandeur, à citer avec prudence |
| [EDITEUR] | Étude ou chiffre publié par un vendeur d'outil ou d'agence | À traiter comme une hypothèse |
| [EXPERT] | Retour de praticien (podcast, post) | Piste de test, pas une preuve |
| [SECOND] | Chiffre repris de seconde main, source primaire non vérifiée | Ne pas citer à une cliente |
| [HYPOTHESE] | Raisonnement ou extrapolation | À tester |

### Règle de base pour tous les outils
Un conseil GEO n'est utilisable que s'il est (1) cohérent avec les règles officielles de Google, (2) bénéfique au lecteur humain, (3) honnête (aucune fabrication d'avis, de mentions ou de preuves). Tout ce qui ne passe pas ces trois filtres est exclu.

---

## 1. Résumé exécutif (les 15 points à retenir)

1. Le GEO se construit sur le SEO, il ne le remplace pas. Google le dit explicitement, les trois experts francophones étudiés aussi. [OFFICIEL] [EXPERT]
2. Pour apparaître dans AI Overviews et AI Mode, une page doit être indexée et éligible à un snippet. Il n'y a pas d'autre exigence technique. [OFFICIEL]
3. Google recommande avant tout un contenu "non commodity" : point de vue propre, expérience de première main, pas une synthèse de ce qui existe déjà. [OFFICIEL]
4. Google déconseille : llms.txt, découpage artificiel du contenu en petits blocs, réécriture spéciale pour l'IA, recherche de mentions inauthentiques, obsession du balisage structuré, et la création de pages pour chaque sous-requête dans le but de manipuler. [OFFICIEL]
5. Les moteurs IA décomposent une question en sous-requêtes (query fan-out) et vont chercher des pages pour chaque sous-requête. Couvrir les sous-questions réelles dans une page de qualité est utile. [OFFICIEL] [EDITEUR]
6. La personnalisation agit avant la recherche : ChatGPT réécrit la requête avec les souvenirs de l'utilisateur, Google utilise l'historique et la mémoire dans AI Mode, et les brevets décrivent des fan-outs qui dépendent de l'utilisateur. [OFFICIEL] [SECOND]
7. Les études de persona montrent que décrire sa situation et ses valeurs change les marques recommandées, alors que l'âge et le sexe changent peu. L'effet est plus fort sur Google AI Overviews et Gemini que sur ChatGPT. [ETUDE] [EDITEUR]
8. Pour une page, cela signifie : écrire dans le vocabulaire, les situations et les contraintes de la cible, et pas seulement poser une étiquette ("tu es une maman"). [HYPOTHESE appuyée par ETUDE]
9. Les pages de classement ("meilleur X") sont le type de page le plus présent dans les sources de ChatGPT, mais l'auto-classement sans preuve est risqué (baisses de visibilité de 30 à 50% observées chez des sites SaaS). [ETUDE] [EXPERT]
10. Les mentions de marque dans des sources tierces fiables pèsent plus que les backlinks pour la visibilité IA, selon plusieurs sources. Mais Google dit que chercher des mentions inauthentiques n'aide pas. [EXPERT] [SECOND] [OFFICIEL]
11. Les sources locales clés en France citées par Poitevin : fiche Google Business Profile, Trustpilot (ou Avis Vérifiés), Wikipédia. LinkedIn est très cité pour les personnes. [EXPERT]
12. Schema.org : utile comme infrastructure de clarté d'entité, pas comme levier de citation prouvé. [OFFICIEL] [ETUDE]
13. UX : Core Web Vitals confirmés (poids modeste, départage), NavBoost confirmé comme signal important, vidéo YouTube très citée sur les surfaces Google, très peu sur ChatGPT. [OFFICIEL] [ETUDE]
14. Les tests faits depuis son propre compte sont biaisés. Il faut tester en chat temporaire, avec plusieurs passages, et avec des préfixes de persona. [ETUDE] [EDITEUR]
15. Les conseils des experts sont crédibles mais intéressés (vente de liens, d'outils, de formations). Les croiser avec la doc officielle. [CONSTAT]

---

## 2. Cadre officiel : Google et Gemini

### 2.1 Guide Google "Optimizing for generative AI features" (mis à jour le 10 juillet 2026) [OFFICIEL]

**Position de principe**
- Le SEO reste pertinent car les fonctions génératives de Google s'appuient sur ses systèmes de ranking et de qualité.
- Techniques citées : RAG (grounding, qui récupère des pages de l'index Google pour produire une réponse avec liens) et query fan-out (requêtes concurrentes générées par le modèle). Exemple donné par Google : pour "comment réparer une pelouse pleine de mauvaises herbes", le fan-out peut inclure "meilleurs herbicides pour pelouse", "supprimer les mauvaises herbes sans produits chimiques", "prévenir les mauvaises herbes".
- AEO et GEO : pour Google, optimiser pour la recherche générative est optimiser pour l'expérience de recherche, donc du SEO.

**Ce que Google recommande**
- Contenu non commodity. Exemple donné : "7 conseils pour acheter sa première maison" (commodity) face à un retour d'expérience détaillé (non commodity).
- Contenu organisé pour le lecteur : paragraphes, sections, titres clairs.
- Images et vidéos de qualité, quand elles ont du sens.
- Se concentrer sur ce que veut l'utilisateur, ne pas créer de contenu séparé pour chaque variante dans le but de manipuler (scaled content abuse).
- Si usage d'outils d'IA générative : respecter les Search Essentials et les spam policies.
- Technique : page indexée et éligible à un snippet, contenu crawlable, HTML sémantique pour la lisibilité (pas besoin d'un code parfait), bonnes pratiques JavaScript, bonne expérience de page (affichage tous appareils, latence, contenu principal distinguable), réduire le contenu dupliqué.
- Condition supplémentaire : le site doit être inclus dans les fonctions génératives dans Search Console (réglage de contrôle).
- Local et e-commerce : fiche Google Business Profile et Merchant Center pour apparaître dans les réponses.

**Mythes listés par Google (à ignorer pour Google)**
- Fichiers llms.txt et autres balisages spéciaux : Google Search ne les utilise pas (aucun effet positif ou négatif).
- "Chunking" : pas d'obligation de découper en petits morceaux ; il n'y a pas de longueur idéale.
- Réécrire pour l'IA : inutile, les systèmes comprennent les synonymes ; pas besoin de couvrir toutes les variantes longue traîne.
- Mentions inauthentiques : chercher des mentions inauthentiques sur le web n'est pas aussi utile qu'il y paraît.
- Surfocalisation sur les données structurées : pas obligatoires, pas de schema spécial pour l'IA, mais à conserver pour les résultats enrichis.

**Mesure**
- Rapport "Generative AI performance" dans Search Console.
- Prudence envers les outils tiers qui prétendent utiliser des métriques internes de Google.

**Agents**
- Les agents IA de navigateur lisent les pages via captures d'écran, DOM et arbre d'accessibilité.
- Recommandation : bonnes pratiques d'un site compatible avec les agents (voir section 12).

### 2.2 Annonces de Google sur les contrôles et mesures [OFFICIEL]
- Juin 2026 : AI Overviews dépasse 2,5 milliards d'utilisateurs mensuels, AI Mode plus d'un milliard.
- Un réglage dans Search Console permet de choisir d'apparaître ou non dans les fonctions génératives (AI Overviews, AI Mode, AI Overviews dans Discover). Un site qui se retire ne reçoit ni trafic ni impressions de ces fonctions. Ce réglage n'est pas utilisé comme signal de ranking hors de ces fonctions.
- Déploiement mondial confirmé au 31 août 2026.
- Les rapports Search Generative AI de Search Console donnent : impressions, pages, pays, appareils, dates, pour Search et Discover. Les données restent aussi comprises dans le rapport de performance global.
- Google a augmenté les liens en ligne dans les réponses, ajouté des aperçus de sites, et intégré "Preferred Sources" et des étiquettes d'abonnement dans AI Overviews et AI Mode.

### 2.3 AI Mode (aide Google) [OFFICIEL]
- Utilise une technique de query fan-out : la question est découpée en sous-thèmes et des requêtes sont lancées simultanément.
- Modèle : Gemini 3 Pro cité dans la page d'aide.
- Personnalisation : voir section 3.4.

### 2.4 Gemini API : grounding avec Google Search [OFFICIEL]
- Le modèle décide s'il doit chercher, génère une ou plusieurs requêtes, exécute la recherche, et renvoie une réponse avec citations en ligne.
- Les métadonnées de grounding contiennent : requêtes utilisées, sources (chunks), liens entre segments de texte et sources.
- Le modèle ne cherche pas pour toutes les questions (un score de pertinence de la recherche est mentionné dans la doc Vertex).
- Sur Gemini 3, facturation par requête de recherche exécutée.
- Implication [HYPOTHESE] : la porte d'entrée reste le résultat Google, donc le SEO classique reste le filtre.

---

## 3. Fonctionnement des moteurs IA

### 3.1 Chaîne de traitement simplifiée
1. Compréhension de la demande (avec contexte et mémoire de l'utilisateur si activés).
2. Décision de chercher ou non (si non : très difficile d'influencer la réponse).
3. Réécriture et éclatement en sous-requêtes (fan-out).
4. Récupération de pages dans un index de recherche.
5. Sélection de passages et synthèse avec citations (2 à 7 sources en moyenne selon certains éditeurs [SECOND]).

### 3.2 Moteur de recherche utilisé : sources en désaccord
- Poitevin et Vengeons décrivent le fonctionnement comme une recherche de type Google. [EXPERT]
- iPullRank indique Bing pour ChatGPT, Brave pour Claude (86,7% de chevauchement entre les résultats cités par Claude et le top organique de Brave, chiffre attribué à Profound). [EDITEUR]
- Ahrefs signale que 28% des pages les plus citées par ChatGPT n'ont aucune visibilité organique, et que de nombreux sites douteux se classent bien sur Bing mais pas sur Google. [ETUDE]
- Conclusion : à tester sur ses propres marchés, ne pas affirmer un moteur unique.

### 3.3 Query fan-out et brevets [EDITEUR] [SECOND]
Brevets cités par des analystes :
- US11663201B2 "Generating Query Variants Using a Trained Generative Model" (déposé en 2018, accordé en 2023). Les attributs utilisateur (localisation, tâche en cours) et temporels peuvent entrer dans la génération des variantes.
- WO2025102041A1 "User Embedding Models for Personalization of Sequence Processing Models" (embeddings de contexte utilisateur).
- US12158907B1 "Thematic Search" (sous-thèmes issus de documents).
- US20240289407A1 "Search with Stateful Chat" (pipeline AI Mode).
- Le brevet décrirait plusieurs types de requêtes synthétiques (huit types sont listés par un analyste), dont des requêtes personnalisées.
- Prudence : ces textes décrivent des possibilités, pas ce que Google fait exactement.

Chiffre à manier avec prudence : les pages qui remontent sur les sous-requêtes seraient 161% plus susceptibles d'être citées (source communautaire, [SECOND]).

### 3.4 Personnalisation par plateforme

| Plateforme | Ce qui est documenté | Niveau |
|---|---|---|
| ChatGPT | La mémoire peut enrichir la requête de recherche. Exemple officiel : "restaurants près de moi" devient "bons restaurants végans, San Francisco" pour un utilisateur dont la mémoire contient ces faits. Les chats temporaires non personnalisés n'utilisent pas la mémoire. | [OFFICIEL] |
| Google AI Mode | Mémoire des recherches et interactions passées si l'historique et les recommandations personnalisées sont activés (adultes, anglais, États-Unis dans l'aide consultée). "Personal Intelligence" relie Gmail et Google Photos (lancé le 22 janvier 2026 pour les abonnés AI Pro et Ultra aux États-Unis, ouvert plus largement ensuite). Ne concerne pas les AI Overviews standard selon une analyse. | [OFFICIEL] + [SECOND] |
| Gemini (application) | "Personal Intelligence" depuis le 14 janvier 2026 : Gmail, Photos, YouTube, historique de recherche, opt-in. Déploiement mondial hors EEE, Suisse, Royaume-Uni selon Thurrott (à revérifier, évolue vite). | [OFFICIEL] + [SECOND] |
| Perplexity | Mémoire de session et préférences explicites, personnalisation plus superficielle. | [EDITEUR] |
| Claude | Mémoire et historique en opt-in selon iPullRank. | [EDITEUR] |
| Copilot (Microsoft 365) | Personnalisation la plus profonde en entreprise via le graphe Microsoft 365. | [EDITEUR] |

Idée centrale d'iPullRank [EDITEUR] : la personnalisation passe de l'aval (réorganiser les résultats) à l'amont (changer les questions posées). Si le contexte déduit rend ta marque non pertinente, ton contenu n'entre pas dans l'ensemble considéré.

Disponibilité pour des clientes françaises : Personal Intelligence de Gemini est exclu de l'EEE dans le déploiement signalé. La mémoire de ChatGPT est la plus pertinente aujourd'hui. À revérifier.

---

## 4. Les experts francophones

### 4.1 Léo Poitevin (Astrak, Linkavista)
Source principale : podcast Le Podcast du Marketing, épisode 326 (avril 2026). [EXPERT]

Idées clés :
- Deux cas : le LLM cherche sur le web ou non. Se concentrer sur les requêtes où il cherche (les seules influençables).
- Le balisage structuré aide mais ne pèse qu'environ 10%.
- Le type de contenu le plus repris : la page "top 10 des meilleurs X", même sur son propre site, avec soi en premier. Il note que ce n'est pas "sexy" mais que c'est le plus réutilisé.
- Si le site est faible, publier des classements sur des médias externes qui citent le client.
- Les mentions sans lien comptent plus que les liens (changement par rapport au SEO classique).
- Une page classée dans le top 15-20 de Google a de bonnes chances d'être prise comme source.
- Sources clés en France : Wikipédia ("saint Graal"), Google Business Profile, Trustpilot (Avis Vérifiés aussi). Reddit et Quora pèsent beaucoup moins qu'aux États-Unis.
- LinkedIn : très cité surtout pour les questions sur des personnes ou entreprises.
- Le trafic indirect (recherche de la marque après avoir été cité) convertit très bien.
- Le SEO reste la base. On peut avoir un bon GEO avec un mauvais SEO grâce aux sites externes.
- Affiliation et RP : leviers de mentions.
- Résumé d'un autre site sur son cours de netlinking : 80 à 90% de chevauchement entre le top 10 Google et les sources de ChatGPT, règle "1 achat = 2 bénéfices" (backlink + mention GEO), technique de "roue de liens" entre listicles. [SECOND]

Réserves : il vend de la mention achetée (Linkavista). L'achat de listicles est proche de ce que Google appelle mentions inauthentiques. Risque réputationnel et de pénalité.

### 4.2 Paul Vengeons (ChatSEO)
Sources : posts X et LinkedIn, podcast MVP. Son site a renvoyé une erreur 503, analyse partielle. [EXPERT]

Idées clés :
- ChatGPT lancerait en silence une recherche Google quand on lui demande une recommandation. Méthode : identifier la requête exacte (schéma "meilleur + offre + ville + année"), ranker n°1, et s'assurer que la page dit littéralement qu'on est les meilleurs. Il mentionne l'année dans les titres et un "hack de fraîcheur".
- Il critique les nouveaux outils de "tracking GEO" et les prompts "bidon".
- Il alerte sur les refontes "pour l'IA" qui détruisent des rankings : reformater en Q&R, fusionner des pages pour être plus "citable", réécrire les titles pour les aligner sur des prompts, restructurer les URL.
- Conclusion répétée : le SEO est la fondation du GEO.

Réserves : il vend un SaaS (ChatSEO). L'auto-recommandation "on est les meilleurs" est à manier avec la prudence décrite en section 8.

### 4.3 Romain Pirotte
Sources : extraits de ses publications LinkedIn, présentation de Geolinka. [EXPERT]

Idées clés :
- "L'IA ne compte pas des liens, elle lit des phrases." Le volume de mentions crée la notoriété, mais ce qui est écrit autour du nom décide de l'association (mots voisins). Un nom associé à "arnaque" ou "remboursement" remonte avec ces mots.
- Il cherche un formateur GEO couvrant : listicles, mentions, différences entre LLM, bonnes pratiques.
- Geolinka (lancé en septembre 2026) : suivi de visibilité dans ChatGPT, scan gratuit (10 questions de marché, note sur 100, rang, pages consultées), appuyé par un réseau de 85 000 sites pour publier.

Réserves : intérêt commercial direct (outil, réseau de sites). Un classement tiers parle de structuration "machine-first" à son sujet, non vérifié.

### 4.4 Classements d'experts
Des articles tiers classent ces experts. Ce sont des sources faibles (méthodes opaques, intérêts croisés). Ne pas s'en servir comme preuve.

---

## 5. Études et chiffres : registre

| Sujet | Chiffre | Source | Niveau |
|---|---|---|---|
| Listicles dans les sources ChatGPT | 43,8% des pages (750 requêtes, 26 283 URL) | Ahrefs | [ETUDE] |
| Listicles : fraîcheur | 79,1% mis à jour en 2025, 26% sur les 2 derniers mois (1 100 URL) | Ahrefs | [ETUDE] |
| Listicles : autorité | 35% sur domaines à faible autorité | Ahrefs | [ETUDE] |
| Auto-promo dans réponses ChatGPT | Présente dans plus d'un tiers des réponses logiciels quand l'éditeur est recommandé | Ahrefs | [ETUDE] |
| Auto-classement en SERP | 169 pages sur 250 (67,6%) avec l'auteur en n°1 sur "meilleur logiciel X" | Allsopp (cité par Ahrefs) | [EXPERT] |
| Pages très citées sans visibilité organique | 28% | Ahrefs (Linehan) | [ETUDE] |
| Listicles auto-promo dans AI Overviews | 323 citations sur 80 prompts, marque non recommandée dans 69% des cas | Lily Ray | [ETUDE] |
| Baisse de visibilité sites SaaS | 30 à 50% | Lily Ray (non confirmé par Google) | [EXPERT] |
| Citations sans mention de marque | Gemini nomme 83,7%, cite 21,4% ; ChatGPT cite 87%, nomme 20,7% | Semrush (115 prompts) | [ETUDE] |
| Requêtes longues contextualisées | 30 à 50 fois moins de mentions de marque, plus de citations | Semrush | [ETUDE] |
| Persona : effet maximal | Jusqu'à 24 points de visibilité | Radyant (17 929 chats) | [EDITEUR] |
| Persona : plateforme la plus sensible | AI Overviews 8,9 points en moyenne, ChatGPT 2,2, Gemini 5,0 | Radyant | [EDITEUR] |
| Persona : combinaisons qui bougent de 5 points ou plus | 28% | Radyant | [EDITEUR] |
| Persona psychographique / sociographique | Part des chaînes nationales de 59% à 42% / 27% (23% combinés) ; démographique 61% | Gander | [EDITEUR] |
| Volatilité des réponses | Environ la moitié des noms changent entre deux passages sur ChatGPT, 7 sur 10 sur Gemini | Gander | [EDITEUR] |
| Persona et marques de milieu de marché | Jusqu'à 75% de l'ensemble recommandé change ; leaders environ 80% stables | arXiv 2605.30207 | [ETUDE] (pré-print, biais de formulation reconnus) |
| Mentions de marque vs backlinks | Corrélation 0,664 vs 0,218 (76 millions d'AI Overviews) | Ahrefs via un tiers | [SECOND] |
| Position dans la page | 44,2% des citations dans le premier tiers du contenu | Growth Memo via des tiers | [SECOND] |
| Longueur | Pages de plus de 20 000 caractères : 10,18 citations en moyenne contre 2,39 sous 500 caractères | Growth Memo via un tiers | [SECOND] |
| Citations de tiers | 82% des citations IA viennent de sources tierces | omnibound.ai via un tiers | [SECOND] |
| Schema et citations | Aucune corrélation (déc. 2024) ; pas de gain majeur sur 1 885 pages (2026) | Search/Atlas ; Ahrefs via un tiers | [ETUDE] / [SECOND] |
| Schema : chiffres "+36%", "+200%" | Non fiable | Éditeurs | [EDITEUR] |
| llms.txt | Présent sur 10,13% des sites, aucune corrélation avec les citations ; score 2 sur 10 dans une synthèse de 54 études | Terolia ; Lepti Digital | [EDITEUR] |
| LinkedIn | Passé de la 11e à la 5e place des domaines cités (Profound, février 2026) ; 3e domaine sur 30 millions de sources (Peec) | Profound, Peec via tiers | [SECOND] |
| LinkedIn : auteurs cités | Contributeurs réguliers, plus de 5 posts par mois, 2 000 à 50 000 abonnés (89 000 URL) | Semrush via tiers | [SECOND] |
| YouTube dans AI Overviews | 21 à 29,5% des citations selon l'étude ; 5,6% de toutes les citations selon Ahrefs | BrightEdge, Surfer, Ahrefs | [ETUDE] (méthodes différentes) |
| YouTube dans ChatGPT | environ 1% | Profound | [ETUDE] |
| Répartition des citations YouTube | Perplexity 38,7%, AI Overviews 36,6%, AI Mode 19,6%, ChatGPT 4,4%, Copilot 0,5%, Gemini 0,2% | OtterlyAI | [ETUDE] |
| Timestamps | Cités uniquement par AI Overviews et AI Mode | OtterlyAI, Rankshift | [ETUDE] |
| Sources dominantes par moteur | Chiffres contradictoires (Reddit n°1 sur 30 M de sources selon Peec ; Wikipédia 48% pour ChatGPT dans une analyse) | Peec, autres | [SECOND] |
| Chevauchement top 10 Google / sources ChatGPT | 80 à 90% | Alhena (résumé de Poitevin) | [SECOND] |
| Délai d'effet | 2 à 8 semaines sur sources récupérées en direct, 3 à 6 mois pour du SEO | Éditeurs | [EDITEUR] |

Règle d'usage : ne jamais présenter un chiffre [SECOND] ou [EDITEUR] à une cliente comme un fait établi.

---

## 6. Intention de recherche et query fan-out : règles d'écriture

### 6.1 Constat
- Les requêtes IA sont longues et conversationnelles (environ 8 mots selon un éditeur). [EDITEUR]
- Le moteur éclate la question en sous-questions : définitions, comparaisons, critères, risques, étapes, chiffres. [OFFICIEL] [EDITEUR]
- Un contenu peut être récupéré via des variantes générées automatiquement. [EDITEUR]

### 6.2 Règles
1. Choisir une intention dominante par page et vérifier le type de SERP (page de service, comparatif, local).
2. Couvrir les intentions latentes dans la même page (pour qui, comment ça se passe, durée, tarif, contre-indications, alternatives) plutôt que créer des pages satellites. Une page par vraie intention distincte, jamais une page par reformulation.
3. Construire une carte d'intentions avant de rédiger (requête tête de cluster, sous-requêtes probables). Une page écrite puis "optimisée GEO" couvre rarement plus de deux branches. [EDITEUR]
4. Pour les SERP comparatives, viser le schéma "meilleur + service + ville + année" avec une vraie page de critères. [EXPERT]
5. Placer la réponse directe dans le premier tiers de page. [SECOND, cohérent avec la lecture humaine]
6. Ne pas créer plusieurs pages "best" sur le même mot-clé (cannibalisation).
7. Ne pas restructurer des pages qui rankent déjà pour les rendre "plus citables" sans test. [EXPERT]
8. Respecter la limite de Google : pas de scaled content pour les variantes de requêtes. [OFFICIEL]

### 6.3 Outils de simulation du fan-out (à utiliser comme aide, pas comme vérité)
Les outils de simulation (DEJAN, Qforia, QueryBurst, GPT personnalisés) génèrent des prédictions, pas les requêtes réelles de ChatGPT ou Gemini. Utile pour repérer des angles manquants. [EDITEUR]

---

## 7. Personnalisation et persona : la théorie et son verdict

### 7.1 Théorie
"Les IA s'inspirent du profil du compte : elles proposent ce qui correspond au profil déduit, donc le contenu SEO doit profiler la cible, par exemple en parlant aux jeunes mamans épuisées."

### 7.2 Verdict
Largement fondée sur le mécanisme, mais à reformuler :
- L'IA déduit le profil de la personne qui cherche, pas celui de la page. Ce profil modifie la requête ou le fan-out. La page est retenue si elle ressemble aux sous-requêtes obtenues.
- Les études (Radyant, Gander, arXiv) montrent que le contexte déclaré change les marques recommandées. Les détails psychographiques (valeurs, méfiances) et sociographiques (appartenances, préférences communautaires) pèsent fort, les détails démographiques (âge, sexe) presque rien. [EDITEUR] [ETUDE]
- Radyant observe que l'effet passe par la proximité sémantique et le vocabulaire fonctionnel des rôles (par exemple "performance marketers", "agencies"), pas par la reprise exacte des intitulés. [EDITEUR]
- Le retrieval reste le filtre : dans l'étude Radyant, 47 URL apparaissent pour toutes les personas.
- L'effet varie : modeste sur ChatGPT, plus marqué sur Google AI Overviews et Gemini.
- Les marques de milieu de marché bougent plus que les leaders (arXiv). Extrapolation [HYPOTHESE] : un praticien local est plus sensible à la persona qu'un leader national, donc a plus à gagner.
- L'effet se combine : les signaux empilés se mélangent plutôt qu'ils ne s'annulent (Gander).
- Le canal de transmission (dans la question, instruction permanente, message antérieur) n'a pas montré de différence dans l'étude Gander.
- Semrush : les requêtes longues et contextualisées produisent beaucoup moins de mentions de marque mais plus de citations. [HYPOTHESE] Avec une cible qui décrit sa situation, être cité (via le contenu) pourrait compter plus qu'être nommé.
- Google ne valide ni ne contredit directement le principe. Il recommande de se concentrer sur ce que veulent les visiteurs et d'éviter les pages multiples par variante.
- "Personal Intelligence" de Gemini n'est pas disponible dans l'EEE dans le déploiement signalé. La mémoire ChatGPT est la plus concernée en France.

### 7.3 Corrections à la théorie
1. "Maman" est une étiquette démographique, faible. Ce qui compte : situation vécue, contraintes, valeurs.
2. Éviter un bloc d'identification isolé ("tu es une maman épuisée"). Préférer une section "Pour qui" qui décrit les situations et critères avec les mots de la cible.
3. Ne pas multiplier les pages par persona. Une page par vraie cible dont le besoin diffère.
4. Mesurer avec des préfixes de persona, pas seulement sans contexte.

### 7.4 Règles d'écriture "profilage de la cible"
- Placer une section "Pour qui" dans le premier tiers : situation, contraintes concrètes (horaires, visio, budget, garde), critères de choix, exclusions explicites ("ce n'est pas pour toi si...").
- Utiliser le vocabulaire de la cible, pas le jargon du métier.
- Rendre explicites les intersections contextuelles (métier + situation + format + budget) dans les pages proches de la conversion. [EDITEUR iPullRank]
- Reprendre les mêmes éléments de positionnement partout (site, fiche Google, LinkedIn, annuaires) pour la cohérence d'entité.
- Obtenir des avis authentiques où le client décrit sa situation avec ses mots : le texte autour du nom compte. Ne jamais scripter les avis. [EXPERT Pirotte]
- Dans les titres H2, formuler les questions comme la cible les pose.
- Attention santé : pas de promesse de résultat, surtout sur les sujets sensibles (post-partum, anxiété, etc.).

Exemple :
- Faible : "Tu es une jeune maman, tu es épuisée, tu as besoin de souffler."
- Plus efficace : "Entre les réveils de nuit, les trajets de crèche et le travail, tu n'as pas une heure à toi : je propose des séances de 45 minutes en visio, entre 12h et 14h ou après le coucher des enfants."

---

## 8. Pages de classement et listicles

### 8.1 Ce que montrent les données
- Les listicles de blog sont le type de page le plus présent dans les sources de ChatGPT, et aussi très présents dans AI Overviews (Ahrefs).
- Les marques bien placées dans les listicles tiers sont plus présentes dans les réponses IA (corrélation).
- Les premières positions d'une liste sont plus souvent reprises (tendance du tiers supérieur). La cause n'est pas établie (Ahrefs ne conclut pas).
- Les listicles auto-promotionnels apparaissent dans les réponses, mais Google peut citer la page sans recommander la marque (69% des cas dans l'étude de Lily Ray).
- Des sites qui s'appuyaient massivement sur des pages "best of" auto-classées ont perdu 30 à 50% de visibilité, notamment lors d'une volatilité de janvier 2026. Google n'a pas confirmé de pénalité. Ce qui tient : test réel, points négatifs honnêtes, expérience de première main. [EXPERT]
- Risque juridique rapporté : la règle américaine FTC sur les avis (en vigueur depuis octobre 2024) vise la présentation de contenus contrôlés comme avis indépendants, les avis de produits non utilisés et les avis attribués à des personnes qui ne les ont pas écrits. Sanctions rapportées jusqu'à 53 088 dollars par infraction. Cela s'applique aux États-Unis, mais l'esprit est transposable. [EXPERT]
- Ahrefs recommande lui-même, s'il en publie : dire clairement qui est recommandé, lier les alternatives, ne pas en faire un volume. Sa part de listicles dans son blog est inférieure à 0,5%.
- Lily Ray alerte aussi sur : pages comparatives et "alternatives" produites à l'échelle, pages "best X" en volume, données structurées abusives, injection de prompt via des boutons "résumer avec l'IA". [EXPERT]

### 8.2 Anatomie d'une page de classement efficace

| Ordre | Bloc | Contenu | Pourquoi |
|---|---|---|---|
| 1 | H1 + réponse directe | "Meilleur [métier] à [ville] : notre sélection [mois année]" puis 40 à 60 mots | Extraction, premier tiers |
| 2 | Transparence | Qui écrit, lien avec les personnes listées, date de mise à jour visible | Réduit le risque Google et FTC |
| 3 | Critères et méthode | 4 à 6 critères concrets (formation, expérience, tarifs, accessibilité, avis vérifiables) | La preuve distingue du classement truqué |
| 4 | Tableau comparatif | Une ligne par praticien, mêmes colonnes | Lecture rapide, extraction |
| 5 | Fiches courtes | Pour qui, points forts, limite honnête, lien direct vers son site | Recommandation d'Ahrefs |
| 6 | Comment choisir | Questions à poser avant de réserver | Intentions latentes |
| 7 | FAQ | 4 à 6 vraies questions | Sous-requêtes du fan-out |
| 8 | Auteur et preuves | Bio, diplômes, historique de mises à jour | Entité, expérience |

### 8.3 Arbitrage recommandé
- Privilégier : page service locale, page "comment choisir", page situation (persona), comparatif d'approches.
- Classement (type T3) : seulement s'il est réel, daté, avec méthode et déclaration de lien. De préférence sur un site neutre (collectif, annuaire) plutôt que sur le site d'une praticienne qui s'y place première.
- Obtenir sa place dans des classements tiers existants reste le levier le plus fort cité (Poitevin), mais l'achat de listicles est risqué.

---

## 9. Gabarits de pages types (pour thérapeutes locaux)

| Gabarit | Intention | H1 type | Blocs dans l'ordre | Schema | Risque |
|---|---|---|---|---|---|
| T1 Page service locale | "[Métier] [ville]" | "Sophrologue à [Ville] : séances pour [cible]" | Bloc réponse, pour qui et situations, déroulé, tarifs, preuves, FAQ, contact et plan | LocalBusiness, Person, Service (audience), WebPage, BreadcrumbList, FAQPage | Faible |
| T2 Guide "Comment choisir" | "Comment choisir un [métier]" | "Comment choisir son [métier] à [ville] : 6 critères" | Réponse directe, critères, questions à poser, erreurs fréquentes, comparaison des approches, FAQ | Article, FAQPage | Faible, le plus sûr |
| T3 Classement local | "Meilleur [métier] [ville]" | "Les meilleurs [métiers] à [ville] : sélection [année]" | Anatomie 8.2 | Article, ItemList | Moyen à élevé si auto-promo |
| T4 Page situation / persona | "[Besoin] + [situation]" | "Sophrologie pour jeunes mamans : [ville]" | Situation vécue, critères, déroulé adapté, exclusions, FAQ | Service avec audience | Faible, une page par vraie cible |
| T5 Comparatif d'approches | "A vs B" | "Sophrologie ou hypnose : laquelle choisir ?" | Définitions courtes, tableau, pour qui, limites | Article | Faible |
| T6 À propos / entité | Marque personnelle | "[Nom], [métier] à [ville]" | Phrase canonique, parcours, méthode, formations, liens vers profils | ProfilePage, Person | Faible |

### 9.1 Éléments récurrents à intégrer
- Bloc réponse de 40 à 60 mots en haut. [SECOND, cohérent avec l'extraction]
- Section "Pour qui" et "Pas adapté si".
- Phrase canonique de 25 mots maximum, identique partout.
- Date de mise à jour visible et vraie.
- Auteur identifié avec diplômes.
- Tableau HTML natif, FAQ en texte visible.
- Liens vers les sources officielles quand une affirmation est factuelle.

---

## 10. Schema.org

### 10.1 Position
- Google : pas de schema spécial pour l'IA, pas obligatoire, à conserver pour les résultats enrichis. [OFFICIEL]
- Études : pas de corrélation établie avec les citations. [ETUDE] [SECOND]
- Rôle réaliste : clarté d'entité, cohérence du nom, de l'adresse, des liens vers profils. [HYPOTHESE raisonnable]

### 10.2 Types utiles par usage

| Type | Où | Utilité | À éviter |
|---|---|---|---|
| LocalBusiness / ProfessionalService | Pages locales, contact | Entité locale, cohérence avec la fiche Google | Données différentes de la fiche Google |
| Person + sameAs | À propos, pages service | Relier la personne à ses profils tiers | Profils inexistants |
| Service avec audience | T1 et T4 | Dit explicitement à qui s'adresse l'offre | Audience trop large |
| FAQPage | Pages où la FAQ est visible | Lecture machine | Texte différent du visible |
| ItemList | Classements réels | Structure de liste | Liste invisible ou fictive |
| BreadcrumbList | Toutes les pages | Hiérarchie | |
| ProfilePage | À propos | Entité personne | |
| Review / AggregateRating sur soi-même | | | À proscrire (connaissance générale : avis auto-publiés non acceptés par Google pour LocalBusiness) |
| HowTo | | | Retiré des résultats enrichis, inutile (connaissance générale) |
| VideoObject | Pages avec vidéo | Métadonnées vidéo | Vidéo absente de la page |

Points à vérifier dans la documentation Google avant de les affirmer à une cliente : restriction des résultats enrichis FAQ depuis 2023 (le billet "Changes to HowTo and FAQ rich results" figure dans le blog Search Central), et interdiction des avis auto-publiés.

### 10.3 Règles de mise en œuvre sur Divi
1. Tout ce qui est balisé doit être visible sur la page.
2. Un seul bloc JSON-LD en @graph par page, dans un module Code ou via l'injection d'en-tête.
3. Si Rank Math ou Yoast génère déjà WebPage ou Organization, fusionner ou désactiver les doublons.
4. Valider dans le Rich Results Test et validator.schema.org.
5. Les dates du schema doivent être identiques aux dates visibles.
6. Pas d'AggregateRating ni de Review sur sa propre activité. Utiliser sameAs vers la fiche Google et Trustpilot.

Fichier associé : `schema-jsonld-gabarits.html` (trois gabarits prêts à coller : page service locale, page classement, page à propos).

---

## 11. Leçons du marché casino (analyse de structure uniquement)

Cadre : en France, les casinos en ligne sont interdits. Seuls le poker, les paris sportifs et les paris hippiques sont autorisés sous agrément ANJ. La publicité pour des sites illégaux est sanctionnée (un communiqué du Groupe Barrière évoque jusqu'à 100 000 euros d'amende). Cette section sert uniquement d'étude de structure sur le marché le plus concurrentiel. Aucun outil ne doit produire de contenu de jeux d'argent.

Limite : les pages françaises de référence ont bloqué l'accès, l'analyse repose sur des extraits de SERP, des pages de méthodologie (Gambling.com, Casino.org, AskGamblers) et des retours de praticiens.

### 11.1 Structure observée

| Élément | Constat |
|---|---|
| Title | "Meilleur [X] [pays] [année] : top N" avec qualificatif de confiance |
| Étiquette par entrée | Un "meilleur pour [critère]" par entrée |
| Blocs | Synthèse en haut, fiches, tableau comparatif, "comment choisir", FAQ, conclusion |
| Fiche | Avantages, licence, paiements, détail de l'offre, note sur 10, boutons |
| Fraîcheur | Année et mois dans le title, mises à jour fréquentes |

### 11.2 Couches de confiance

| Site | Méthode affichée |
|---|---|
| Gambling.com | 10 étapes, score sur 100 converti en note sur 10, un opérateur qui échoue aux prérequis n'est pas noté |
| Casino.org | 25 étapes, test anonyme, liste de sites à éviter |
| AskGamblers | Détails 50%, plaintes 25%, avis joueurs 15% ; mise à jour au moins tous les 6 mois ; liste noire publique |

Observations de praticiens [EXPERT] : une page de revue laissée six mois sans mise à jour est descendue de la page 2 à la page 4 puis remontée après actualisation ; un classement statique sans contributions s'est fait dépasser par un forum ; erreurs courantes : plusieurs pages "best" sur le même mot-clé, pages bonus dupliquées, URL à paramètres qui gaspillent le crawl.

Un site de test critique note que ces plateformes restent des modèles d'affiliation et que des opérateurs avec plaintes ouvertes gardent de bonnes notes. L'écosystème d'avis autour de la catégorie (domaines à noms promotionnels sur Trustpilot) illustre la manipulation que Google et les moteurs IA cherchent à filtrer.

### 11.3 Transposable aux thérapeutes

| Pattern | Transposition | Précaution |
|---|---|---|
| "Meilleur pour [critère]" | "Idéal pour jeunes mamans", "idéal en visio" | Critères réels et vérifiables |
| Méthodologie publique pondérée | "Comment nous sélectionnons les praticiens" | Surtout sur un annuaire ou collectif neutre |
| Filtre éliminatoire | Prérequis (diplôme, assurance) avant inscription | Point fort de confiance |
| Section "à éviter" | "Signaux d'alerte chez un praticien" | Sans citer de personnes |
| Date et cycle de révision | Mise à jour trimestrielle des pages cœur | Ne jamais changer la date sans changer le contenu |
| Une page par marché | Une page par ville, contenu réellement différent | Pas de doublons de mots-clés |
| Plaintes et avis intégrés | Avis vérifiés, médiation | Jamais scénarisés |
| Divulgation d'affiliation | Mention de tout lien d'intérêt | Obligatoire si on classe des confrères |

### 11.4 Non transposable
Bonus et urgence, notes arbitraires de confrères sans protocole, allégations de résultat (règles santé et consommation plus strictes), tout ce qui ressemble à des avis fabriqués.

### 11.5 Enseignement
La mécanique "top N + meilleur pour X" se copie facilement. La crédibilité non : méthode publique, filtre éliminatoire, fraîcheur réelle, voix de vrais utilisateurs.

---

## 12. UX, vidéo, médias, performance, agents

### 12.1 Niveau de preuve par levier

| Levier | Preuve | Détail |
|---|---|---|
| Core Web Vitals (LCP, INP, CLS) | [OFFICIEL] | Composante du signal d'expérience de page, poids modeste, départage entre contenus proches |
| NavBoost (clics, engagement) | [OFFICIEL via procès] + fuite de 2024 | Confirmé comme signal important lors du procès antitrust ; la fuite décrit des types de clics ; dwell time non confirmé comme facteur direct ; poids inconnus |
| Images et vidéo de qualité | [OFFICIEL] | Recommandés par le guide IA de Google |
| Formats extractibles (tableaux, listes, FAQ, résumés) | [ETUDE] corrélé | Google précise qu'un découpage n'est pas requis |
| Utilisabilité pour agents | [OFFICIEL] web.dev | HTML sémantique, pas d'éléments fantômes, mise en page stable |

### 12.2 Vidéo
- YouTube est très cité sur les surfaces Google et Perplexity, très peu sur ChatGPT, Gemini (application) et Copilot.
- Le mécanisme passerait par les transcriptions et descriptions. Seuls AI Overviews et AI Mode citent des timestamps. [ETUDE]
- Recommandation courante : une vidéo + une page jumelle (transcription visible, résumé structuré, VideoObject) pour que les moteurs qui lisent du texte aient quelque chose à citer. [EDITEUR]
- Pour des thérapeutes : vidéo de présentation de 60 à 90 secondes, séance expliquée, réponse à une question fréquente.

### 12.3 Éléments à ajouter par priorité (pages de thérapeutes)

| Élément | Intérêt | Coût Divi | Priorité |
|---|---|---|---|
| Vidéo de présentation courte | Confiance, humanisation, citation possible | Moyen (intégration allégée) | Haute |
| Transcription visible | Texte citable par tous | Faible | Haute |
| Photos réelles légendées, alt descriptif | Signal d'expérience, multimodal | Faible en WebP/AVIF | Haute |
| Tableau HTML comparatif | Extraction | Faible | Haute |
| Bloc "À retenir" | Lecture en diagonale | Faible | Haute |
| Section "Idéal pour / pas adapté si" | Précise la cible | Faible | Haute |
| Sommaire ancré (pages longues) | Navigation | Faible | Moyenne |
| FAQ visible | Sous-requêtes | Faible | Moyenne |
| Bouton de contact non bloquant | Conversion | Faible | Moyenne |
| Chapitres YouTube et lien retour | Timestamps | Faible | Moyenne |
| Outils interactifs | Engagement | Élevé | Faible |
| Parallax, animations, sliders | Aucun gain prouvé | Élevé | À limiter |

### 12.4 Pièges de performance sur Divi
- Intégration YouTube standard : lourde. Préférer une image cliquable qui charge le lecteur au clic.
- Cookie banner et pop-ups qui décalent la page ou masquent le contenu sur mobile : interstitiels intrusifs pénalisés.
- Sliders, parallax, animations au scroll : lourds, sans bénéfice démontré.
- Image principale : format moderne, dimensions déclarées, pas de lazy-load sur l'image au-dessus de la ligne de flottaison.
- DOM trop profond : limiter l'imbrication de sections et de lignes.

### 12.5 Utilisabilité pour agents IA (web.dev, avril 2026) [OFFICIEL]
- Les agents voient le site par captures d'écran, HTML et arbre d'accessibilité. Ils combinent ces canaux.
- Bonnes pratiques : éviter les éléments fantômes (overlays transparents), HTML sémantique (button et a plutôt que div), rôle et tabindex si pas de sémantique, label avec attribut for, curseur pointer, éléments interactifs assez grands, mise en page stable.
- WebMCP : standard proposé, en développement (origin trial).
- Message de fond : ce qui aide les agents aide aussi les humains.

---

## 13. Off-page : mentions, avis, profils

- Mentions sans lien : une mention dans une source crédible et un bon contexte porte du signal pour un LLM. [EXPERT] [SECOND]
- Contexte autour du nom : la co-occurrence avec les bons termes (catégorie, cas d'usage, concurrents établis) apprend au modèle quand recommander. [EXPERT]
- Google dit que chercher des mentions inauthentiques n'est pas aussi utile qu'il y paraît. [OFFICIEL] Privilégier les mentions méritées (presse locale, podcasts, interviews, annuaires sérieux, collectifs).
- Google Business Profile : source clé, vérifiée, à jour, avec avis. Poitevin recommande d'en créer une même sans accueil du public (zone de service). [EXPERT]
- Trustpilot / Avis Vérifiés : source d'avis très utilisée. La fiche est revendiquable gratuitement, offres payantes ensuite. [EXPERT]
- LinkedIn : très cité, surtout pour les personnes ; contributeurs réguliers. [SECOND]
- Wikipédia : source très réutilisée, mais réservée à une notoriété avérée (sources journalistiques multiples). Peu réaliste pour un praticien local.
- Reddit et Quora : moins déterminants en France selon Poitevin ; Peec place Reddit en tête sur 30 millions de sources toutes plateformes. À nuancer par marché et langue.
- Affiliation et RP : cités comme leviers de mentions. [EXPERT]
- Cohérence d'entité : même positionnement partout. [EDITEUR iPullRank]

---

## 14. Mesure et protocole de test

### 14.1 Mesure officielle
- Search Console : rapport Generative AI (impressions, pages, pays, appareils, dates).
- Core Web Vitals dans Search Console.

### 14.2 Protocole de test manuel
1. Définir 3 personas et 8 prompts de marché, avec et sans préfixe de situation.
2. Utiliser un chat temporaire non personnalisé (ou des comptes vierges), car son propre compte porte son contexte.
3. Passer chaque prompt une dizaine de fois : environ la moitié des noms changent entre deux passages sur ChatGPT, davantage sur Gemini. [EDITEUR Gander]
4. Noter : mention de marque, citation de l'URL, source citée, position.
5. Répéter mensuellement et noter les modifications de contenu faites entre-temps.
6. Surveiller la croissance des recherches sur le nom de marque et le trafic direct (effet indirect).

### 14.3 Outils
Des outils de tracking existent (Peec, Otterly, Profound, Geolinka, Qwairy, etc.). Google précise qu'aucun outil tiers n'a accès à ses systèmes internes. Les utiliser comme aide au suivi, pas comme référence.

---

## 15. Registre des contradictions et zones d'incertitude

| Sujet | Position A | Position B | Décision |
|---|---|---|---|
| Moteur de recherche utilisé par ChatGPT | Recherche de type Google (Poitevin, Vengeons) | Bing (iPullRank) | Tester |
| Chunking | Utile (nombreux éditeurs) | Inutile (Google) | Écrire pour l'humain, blocs naturels |
| Mentions | Levier majeur (experts) | Mentions inauthentiques peu utiles (Google) | Mentions méritées uniquement |
| Schema | Levier de citation (éditeurs) | Pas de corrélation (études), non requis (Google) | Infrastructure, pas levier |
| Listicle auto-promo | À publier (Ahrefs, Poitevin) | Risque de baisse et de non-recommandation (Lily Ray) | Prudence, preuve et transparence |
| Persona | Effet fort (Radyant sur AIO) | Effet modeste (ChatGPT) | Prioriser Google AIO, Gemini |
| Poids de YouTube | 21 à 29,5% (AIO) | 5,6% (Ahrefs), 1% sur ChatGPT | Dépend de la surface et du dénominateur |
| Source dominante | Reddit (Peec) | Wikipédia (autres analyses) | Dépend du moteur et de la langue |
| Refonte pour l'IA | Nécessaire (éditeurs GEO) | Risque de perte de rankings (Vengeons) | Tester avant de restructurer |
| Longueur | Pages longues plus citées (SECOND) | Pas de longueur idéale (Google) | Selon le sujet et le public |

---

## 16. Règles actionnables pour les outils

### 16.1 Principes à inscrire dans les prompts ou skills
1. Toujours partir de l'intention de recherche dominante et du type de SERP avant de rédiger.
2. Toujours placer une réponse directe de 40 à 60 mots en haut de page.
3. Toujours produire une section "Pour qui" avec situations, contraintes, critères et exclusions, avec le vocabulaire de la cible.
4. Toujours proposer une FAQ basée sur de vraies questions, en texte visible.
5. Toujours afficher une date de mise à jour vraie et la propager au schema.
6. Toujours inclure auteur, formation, et phrase canonique cohérente avec la fiche Google et LinkedIn.
7. Toujours ajouter, si pertinent, un tableau HTML natif et un bloc "À retenir".
8. Produire du contenu non commodity : demander à l'utilisateur un vécu, un cas, une méthode, des chiffres propres avant de rédiger. Ne pas produire de synthèse générique.
9. Utiliser un schema @graph unique, cohérent avec le contenu visible, sans AggregateRating ni Review sur sa propre activité.
10. Pour une page vidéo : transcription visible, lien retour depuis YouTube, VideoObject.

### 16.2 Interdits à inscrire dans les outils
1. Ne jamais fabriquer d'avis, de témoignages, de mentions ou de classements.
2. Ne jamais produire un classement où l'auteur se place premier sans méthode, preuve et déclaration de lien.
3. Ne jamais créer des pages par variante de requête ou par persona à l'échelle (scaled content).
4. Ne jamais promettre un résultat thérapeutique ni formuler d'allégation de santé non vérifiable.
5. Ne jamais proposer llms.txt comme levier, ni présenter schema comme garantie de citation.
6. Ne jamais restructurer une page qui rank déjà sans proposer un test et une sauvegarde.
7. Ne jamais citer un chiffre [SECOND] ou [EDITEUR] comme un fait.
8. Ne jamais produire de contenu de jeux d'argent ou d'incitation au jeu.
9. Ne jamais injecter d'instructions cachées destinées aux IA dans une page (prompt injection), ni de texte invisible.

### 16.3 Checklist avant publication (page)
- [ ] Intention dominante définie, SERP vérifiée
- [ ] H1 et title alignés sur l'intention, année si intention comparative
- [ ] Bloc réponse de 40 à 60 mots en haut
- [ ] Section "Pour qui" et "Pas adapté si"
- [ ] Contenu non commodity (vécu, méthode, cas, chiffres propres)
- [ ] Vocabulaire de la cible, pas de jargon
- [ ] FAQ visible, réponses de 40 à 80 mots
- [ ] Tableau HTML si comparaison
- [ ] Auteur, formation, preuves
- [ ] Date de mise à jour visible et vraie
- [ ] Phrase canonique identique au site, à la fiche Google et à LinkedIn
- [ ] Schema @graph unique, validé, cohérent avec le visible
- [ ] Texte en HTML réel (pas dans des images ou sliders)
- [ ] H2/H3 logiques, boutons et liens sémantiques, labels associés aux champs
- [ ] Images réelles, alt descriptif, format moderne, dimensions déclarées
- [ ] Vidéo : intégration allégée, transcription, VideoObject
- [ ] Core Web Vitals vérifiés (LCP, INP, CLS), pas de pop-up intrusif
- [ ] Allégations de santé vérifiées, pas de promesse de résultat
- [ ] Aucune page concurrente sur le même mot-clé (cannibalisation)
- [ ] Liens internes vers les pages liées

### 16.4 Grille d'audit d'une page existante (0 à 2 points par critère)
1. Intention claire et conforme à la SERP
2. Réponse directe dans le premier tiers
3. Pertinence pour la cible (situations, contraintes, critères)
4. Contenu non commodity
5. Preuves d'expérience et d'auteur
6. Structure lisible (H2, tableau, FAQ)
7. Fraîcheur réelle
8. Cohérence d'entité (site, fiche Google, profils)
9. Technique (indexable, snippet, HTML, schema cohérent)
10. UX et performance (CWV, mobile, interstitiels)
Interprétation : 0 à 8 refonte ; 9 à 14 améliorations ciblées ; 15 à 20 maintenir et mettre à jour.

### 16.5 Fiche de brief persona (à demander avant de rédiger)
- Situation de vie (qui, quand, quelles contraintes)
- Mots qu'elle utilise pour décrire son problème
- Valeurs et méfiances (ex. méfiance envers les promesses miracles)
- Critères de choix d'une praticienne
- Format souhaité (visio, cabinet), horaires, budget
- Ce qui la ferait fuir
- Questions qu'elle poserait à une IA (en phrases complètes)

### 16.6 Gabarit de bloc "Pour qui" (à générer)
1. Une phrase de situation avec détails concrets
2. 3 contraintes pratiques
3. 3 critères de choix
4. "Ce n'est pas pour toi si..." (2 points)
5. Renvoi vers la FAQ

---

## 17. Hypothèses à tester (backlog d'expériences)

1. Ajouter une section "Pour qui" persona sur 5 pages et suivre l'évolution des mentions en prompts avec préfixe de persona (3 mois).
2. Comparer des pages avec et sans vidéo + transcription sur les surfaces Google et Perplexity.
3. Tester l'effet d'avis clients riches en situation (authentiques) sur les mentions de marque.
4. Tester un classement neutre porté par un collectif, avec méthode publique, face à une absence de classement.
5. Mesurer l'effet de la date de mise à jour réelle (avec vraie révision) tous les trois mois.
6. Mesurer l'effet d'un bloc réponse de 40 à 60 mots sur l'extrait cité.
7. Comparer le comportement d'une même requête dans ChatGPT, Gemini et Perplexity pour une même ville (cohérence ou non des sources).
8. Mesurer l'effet de la cohérence de la phrase canonique sur la mention du praticien dans des questions "qui est [nom]".

---

## 18. Limites et angles morts

- Plusieurs pages de référence (casino, site de Paul Vengeons) n'ont pas pu être ouvertes : analyse partielle.
- Les études sur les personas viennent d'éditeurs d'outils (Radyant, Gander) ou d'un pré-print, avec une seule catégorie testée (Radyant) et des biais reconnus.
- Aucune étude ne teste directement l'effet d'une section de profilage sur la page.
- La plupart des chiffres sur les types de schema viennent d'éditeurs d'outils.
- Les études sur YouTube divergent fortement selon la méthode.
- Les conseils des experts francophones sont intéressés (liens, outils, formations).
- Les résultats sont variables d'un passage à l'autre : aucune mesure ponctuelle n'est fiable.
- Les disponibilités géographiques (Personal Intelligence, AI Mode) évoluent vite et sont à revérifier avant d'affirmer quoi que ce soit à une cliente.
- Pas d'étude centrée sur des thérapeutes locaux : toute transposition (Ahrefs, Lily Ray, casino) est à valider.

---

## 19. Sources

### Google et Gemini
- Optimizing for generative AI features, Google Search Central : https://developers.google.com/search/docs/fundamentals/ai-optimization-guide
- AI features and your website : https://developers.google.com/search/docs/appearance/ai-features
- Top ways to ensure your content performs well in Google's AI experiences : https://developers.google.com/search/blog/2025/05/succeeding-in-ai-search
- New opportunities, control and insights for website owners : https://blog.google/products-and-platforms/products/search/new-controls-website-owners/
- Generative AI performance reports in Search Console : https://developers.google.com/search/blog/2026/06/gen-ai-performance-reports
- AI Mode, aide Google : https://support.google.com/websearch/answer/16011537
- Personal Intelligence dans AI Mode, aide Google : https://support.google.com/websearch/answer/17212611
- Personal Intelligence dans AI Mode, annonce : https://blog.google/products-and-platforms/products/search/personal-intelligence-ai-mode-search/
- Personal Intelligence dans Gemini : https://blog.google/innovation-and-ai/products/gemini-app/personal-intelligence/
- Grounding with Google Search, Gemini API : https://ai.google.dev/gemini-api/docs/google-search
- Build agent-friendly websites, web.dev : https://web.dev/articles/ai-agent-site-ux

### OpenAI
- Memory FAQ : https://help.openai.com/en/articles/8590148-memory-faq
- Notes de version ChatGPT : https://help.openai.com/en/articles/6825453-chatgpt-release-notes
- Memory with Search, Search Engine Land : https://searchengineland.com/chatgpt-releases-memory-with-search-454465

### Experts francophones
- Léo Poitevin, épisode 326, Le Podcast du Marketing : https://lepodcastdumarketing.com/geo-referencement-ia-leo-poitevin-326/
- Résumé du cours de Léo Poitevin (Alhena) : https://www.alhena-conseil.com/comment-faire-recommander-marque-chatgpt-podcast-leo-poitevin-astrak/
- Paul Vengeons sur X : https://x.com/VengeonsP/status/2053738502882197981
- Paul Vengeons sur LinkedIn : https://fr.linkedin.com/in/paul-vengeons
- Romain Pirotte sur LinkedIn : https://www.linkedin.com/in/avis-romain-pirotte/

### Études et analyses
- Ahrefs, listicles et ChatGPT : https://ahrefs.com/fr/blog/articles-listes-chatgpt/
- Lily Ray, AI Overviews et listicles auto-promo (Search Engine Land) : https://searchengineland.com/google-ai-overviews-cite-self-serving-listicles-recommend-competitors-480573
- Lily Ray, répression possible (Search Engine Land) : https://searchengineland.com/google-cracking-down-self-promotional-best-of-listicles-468227
- Listicles et règle FTC (Search Engine Land) : https://searchengineland.com/low-quality-listicles-trend-google-search-473703
- Semrush, citations fantômes : https://www.semrush.com/blog/the-ghost-citations-study/
- Radyant, étude persona : https://www.radyant.io/research/persona-study
- Gander, étude persona : https://takeagander.ai/resources/gander-blog/persona-geo-study-does-telling-ai-who-you-are-change-what-it-recommends/
- Persona Conditioning of Brand Recommendations, arXiv : https://arxiv.org/html/2605.30207v1
- iPullRank, personnalisation du fan-out : https://ipullrank.com/query-fan-out-personalization
- Salespeak, personal context search : https://salespeak.ai/aeo-news/personal-context-search/
- Schema et recherche IA sans le battage, Search Engine Land : https://searchengineland.com/schema-markup-ai-search-no-hype-472339
- Étude GEO sur 54 recherches, Lepti Digital : https://www.leptidigital.fr/webmarketing/seo/etude-geo-facteurs-citations-ia-90013/
- GEO pour les marques personnelles, Terolia : https://terolia.com/blog/geo-marques-personnelles
- Stratégie de contenu GEO, Incremys : https://www.incremys.com/ressources/blog/strategie-de-contenu-geo
- Guide GEO 2026, Agalma : https://agalma-etudes.com/blog/geo-strategy/geo-generative-engine-optimization-guide-2026/
- Query fan-out, NOIISE : https://www.noiise.com/definition/query-fan-out/

### Vidéo et UX
- Étude YouTube, OtterlyAI : https://otterly.ai/blog/youtube-ai-citation-study-2026/
- YouTube et citations IA par moteur : https://www.elmohq.com/blog/youtube-ai-citations
- Étude Rankshift : https://www.rankshift.ai/blog/youtube-ai-citations/
- Radyant, cadre YouTube : https://www.radyant.io/guides/youtube-ai-search-visibility-citation-framework
- GEO pour YouTube : https://www.arfadia.com/blog/geo-for-youtube/
- NavBoost expliqué : https://trapilot.ai/google-leak/coverage/seostack-navboost-unpacked
- Signaux UX et classement : https://www.techcognate.com/ux-signals-and-rankings/

### Casino (analyse de structure et cadre légal)
- Catégorie Casino, Trustpilot France : https://fr.trustpilot.com/categories/casino
- Méthodologie Gambling.com : https://www.gambling.com/us/online-casinos/how-we-review
- Méthodologie Casino.org : https://www.casino.org/how-we-rate/
- CasinoRank, AskGamblers : https://www.askgamblers.com/online-casinos/reviews
- Fiabilité des sites de revue : https://www.netlingo.com/tips/how-reliable-are-online-casino-review-websites.php
- Retours de praticien : https://localseoireland.ie/how-seo-for-online-casinos-made-me-a-better-marketer/
- Guide SEO affilié casino : https://theserpwizards.com/en/affiliate-casino-seo-guide/
- Question parlementaire sur les casinos illégaux : https://www.assemblee-nationale.fr/dyn/16/questions/QANR5L16QE294.pdf
- Plainte du Groupe Barrière : https://www.groupebarriere.com/content/dam/corporate/notre-actualite/communiques-de-presse/pdf-fr/2023/cp-plainte-sites-frauduleux.pdf

---

## Annexe A : glossaire

- **AEO** : Answer Engine Optimization (optimisation pour les réponses directes).
- **GEO** : Generative Engine Optimization (optimisation pour être cité dans les réponses génératives).
- **RAG** : génération augmentée par la recherche (le modèle récupère des pages avant de répondre).
- **Grounding** : ancrage de la réponse dans des sources web, avec citations.
- **Query fan-out** : éclatement d'une requête en sous-requêtes lancées en parallèle.
- **YMYL** : sujets "Your Money or Your Life", où Google exige un niveau de confiance plus élevé (santé, finance).
- **E-E-A-T** : expérience, expertise, autorité, fiabilité.
- **NavBoost** : système de Google qui utilise les clics et l'engagement.
- **Entité** : personne, organisation ou lieu identifié de façon cohérente par les machines.
- **Persona** : profil type de la cible (situation, contraintes, valeurs).
- **Scaled content abuse** : production de pages en volume pour manipuler les résultats (politique anti-spam de Google).

## Annexe B : fichiers associés
- `schema-jsonld-gabarits.html` : trois gabarits JSON-LD (page service locale, page classement, page à propos).
