---
titre: Documentation CTR, meta title et meta description
version: 2026-10-04
usage: base de connaissances pour les outils (Atelier, audits, briefs SEO) des pages Divi / WordPress de praticiens
fichiers_associes: documentation-seo-geo-2026-10.md, schema-jsonld-gabarits.html
---

# CTR, meta title, meta description : documentation de référence

## 0. Mode d'emploi

Ce fichier sert à deux choses : comprendre ce qui est établi (et ce qui ne l'est pas) sur le clic en SERP, puis fournir des règles directement réutilisables par les outils.

**Légende des niveaux de preuve** (même logique que la fiche SEO/GEO) :

- [OFFICIEL] documentation Google ou déclaration sous serment
- [ETUDE] étude chiffrée avec méthode connue
- [EDITEUR] chiffre ou méthode publiés par un outil ou une agence
- [EXPERT] avis de praticien, non mesuré
- [HYPOTHESE] à tester sur nos propres sites

Les exemples de titles et metas sont **illustratifs** (Sophie Baraffe, Lens, 60 € viennent d'un cas fictif). Pour un vrai client, toute donnée (prix, années, délai) vient de lui, sinon marqueur `[À CONFIRMER : ...]`.

---

## 1. Résumé exécutif (12 points)

1. Le title et la meta description **ne servent pas à classer, ils servent à faire cliquer**. Le title garde un rôle de compréhension du sujet, la meta n'en a pas. [OFFICIEL + EXPERT]
2. Google **ne fixe aucune limite** en caractères. Il tronque "pour tenir dans la largeur de l'appareil". Les 50-60 caractères du title et 140-155 de la meta sont des repères en pixels, pas des règles. [OFFICIEL]
3. Google **réécrit beaucoup** : environ 76 % des titles (étude Q1 2025) et environ 63 % des metas (étude Ahrefs, 192 656 pages). Écrire pour le clic ET pour réduire la raison de réécrire. [ETUDE]
4. Un title est réécrit surtout quand il est **vague, trop long, bourré de mots-clés, avec marque envahissante, ou en désaccord avec le H1 et le contenu**. [OFFICIEL + ETUDE]
5. La meta est réécrite **à peu près autant qu'elle soit longue ou courte** (61 % contre 64 %). La longueur n'est donc pas le levier principal : c'est la **correspondance avec la requête** qui compte. [ETUDE]
6. Le CTR **s'est effondré sur les requêtes informationnelles** à cause des AI Overviews (position 1 : -58 % sur 300 000 mots-clés, Ahrefs). Sur les requêtes locales de praticiens (pack local, GBP), le levier est différent. [ETUDE]
7. Les clics alimentent un signal de classement (NavBoost, fenêtre glissante de 13 mois), mais **on ne peut pas le manipuler** et son effet sur une petite page locale est inconnu. Le CTR est surtout un levier de **trafic à position constante**. [OFFICIEL (témoignages) + HYPOTHESE sur l'ampleur]
8. Patterns de title associés à un meilleur CTR dans un test de 423 pages : **chiffres, questions, "vs", badge entre crochets**. Associés à un moins bon CTR : **marque en tête sur requêtes non-marque, année seule en fin**. Test directionnel, pas causal. [EDITEUR]
9. Pour un praticien, le gain le plus rentable est rarement un "titre accrocheur" : c'est un **title qui dit métier + ville + situation**, une meta avec **prix/délai/déroulé** (ce que les annuaires n'affichent pas), et une **fiche Google Business** cohérente.
10. Domaine santé et bien-être (YMYL) : Google préfère l'exactitude à l'optimisation. Zéro promesse de guérison, zéro superlatif invérifiable, sinon réécriture et risque conformité. [ETUDE + OFFICIEL]
11. Se mesurer sur **Search Console par page et par requête, avec un avant/après de 28 jours minimum**. Ne jamais juger un title en 5 jours.
12. L'outil doit **compter les caractères, vérifier l'unicité, la cohérence title / H1, l'absence de bourrage**, et livrer 2 versions de chaque.

---

## 2. Ce que dit Google (officiel)

### 2.1 Title links (liens de titre)

Source : Google Search Central, "Influencing your title links in search results".

- La génération est **entièrement automatique** et tient compte du contenu de la page ET des références web vers elle. [OFFICIEL]
- Sources utilisées : élément `<title>`, titre visuel principal de la page, balises Hn (`<h1>`), `og:title`, contenu mis en valeur par le style, texte de la page, ancres internes, ancres de liens externes, données structurées `WebSite`. [OFFICIEL]
- Bonnes pratiques : un `<title>` sur chaque page, **descriptif et concis**, éviter "Accueil" ou "Sans titre", pas de bourrage, **pas de texte standard répété sur toutes les pages**, marque courte avec séparateur, même langue que le contenu. [OFFICIEL]
- Le titre visible doit être **le plus saillant** de la page (grande taille, premier `<h1>`). Plusieurs titres également saillants brouillent le signal. [OFFICIEL]
- Pas de longueur maximale officielle. Troncature "selon la largeur de l'appareil". [OFFICIEL]
- Si un défaut est détecté, Google "peut générer un meilleur titre" à partir des ancres, du texte de la page ou d'autres sources. [OFFICIEL]
- Délai de prise en compte d'une modification : quelques jours à quelques semaines (demande de réindexation possible dans Search Console). [OFFICIEL]

### 2.2 Snippets et meta description

Source : Google Search Central, "Control your snippets in search results".

- Le snippet est **créé automatiquement à partir du contenu de la page**, pour mettre en avant ce qui répond à la recherche précise. Deux requêtes différentes peuvent afficher deux snippets différents pour la même page. [OFFICIEL]
- Google "utilise parfois" la meta description, **quand elle décrit mieux la page que le contenu**. [OFFICIEL]
- Recommandations : meta **unique par page**, informations précises (auteur, date, prix, éléments clés), pas de suite de mots-clés, lisible comme **un argumentaire qui convainc que la page est exactement ce que la personne cherche**. Génération programmatique acceptable sur gros volumes. [OFFICIEL]
- Contrôles : `nosnippet`, `max-snippet:[n]`, `data-nosnippet` (sur une portion de page). [OFFICIEL]

### 2.3 Ce que Google ne dit pas

- Aucune valeur officielle en caractères ou pixels.
- Aucune déclaration disant "la meta description améliore le classement". Elle n'est pas un facteur de classement. [OFFICIEL + consensus]

---

## 3. Réécriture par Google : les chiffres

| Mesure | Résultat | Source | Niveau |
|---|---|---|---|
| Titles modifiés (top 20, ~30 000 mots-clés) | 76 % (contre ~61 % en 2023) | John McAlpin, Search Engine Land, mai 2025 | [ETUDE] |
| Motifs de réécriture de title | retrait de la marque 63 %, clarté 30 %, longueur 8 % | idem | [ETUDE] |
| Titles réécrits sans le mot-clé cible | 77 % | idem | [ETUDE] |
| Même taux en YMYL | 76 %, avec retrait de mots-clés plus strict | idem | [ETUDE] |
| Metas réécrites par Google | 62,78 % (20 000 mots-clés, 192 656 pages) | Ahrefs | [ETUDE] |
| Requêtes courtes / longue traîne | 59,65 % / 65,62 % réécrites | Ahrefs | [ETUDE] |
| Metas trop longues / bien dimensionnées | 61,46 % / 63,69 % réécrites | Ahrefs | [ETUDE] |
| Pages du top sans meta description | 25,02 % | Ahrefs | [ETUDE] |

**Lecture utile pour nos outils :**

1. Une réécriture n'est pas un échec : c'est le fonctionnement normal. Le but est de donner à Google **un title et une meta si bien alignés avec la page et la requête** qu'il n'ait pas de raison de les remplacer.
2. La **marque est retirée en premier** (63 % des réécritures). Sur une page locale de praticien, la mettre **courte et à la fin**, ou la supprimer si le title dépasse la largeur.
3. Plus la requête est précise (longue traîne), plus la meta est réécrite : **la meta d'une page vise une intention principale, et c'est le corps de page (réponse directe en haut, tarifs, déroulé) qui alimente aussi les snippets alternatifs**. Les premières lignes de la page sont donc "une deuxième meta description".
4. Ne pas écrire de meta = Google choisit un extrait. Ce n'est pas une catastrophe, c'est une perte de contrôle. Écrire la meta pour les pages à enjeu (service, ville, accueil, à propos, articles qui ramènent des patients).

---

## 4. Le CTR : chiffres, contexte, limites

### 4.1 Benchmarks

| Mesure | Résultat | Source | Niveau |
|---|---|---|---|
| Position 1, mots-clés sans AI Overview (desktop) | 7,6 % (déc. 2023) puis 3,9 % (déc. 2025) | Ahrefs, 300 000 mots-clés, données Search Console | [ETUDE] |
| Position 1, mots-clés avec AI Overview (desktop) | 7,3 % puis 1,6 % (soit -58 % vs prévision sans AIO) | Ahrefs, idem | [ETUDE] |
| Affichage AIO | environ 91 % des cas de position 1 sur les requêtes concernées | Ahrefs | [ETUDE] |
| Position 1, toutes SERP (juil. 2026) | 20,02 %, contre 9,69 % avec AIO | Advanced Web Ranking, 1,75 M de SERP par mois | [ETUDE, prudence : sources de calcul non détaillées] |
| Position 1 marque / hors marque | 27,32 % / 19,38 % | Advanced Web Ranking | [ETUDE] |
| Recherches sans clic organique, avec AIO / sans | 74,3 % / 53,6 % | Advanced Web Ranking | [ETUDE] |
| Accumulation de blocs (AIO, pack local, PAA, vidéos) | perte de clics proche de la totalité | Advanced Web Ranking | [ETUDE] |

**Avertissements de lecture :**

- Les chiffres divergent entre études (méthodes, pays, appareils, période). **Ne jamais donner un CTR "normal" absolu à une cliente**, seulement une comparaison avec ses propres pages.
- Les études ci-dessus portent surtout sur des requêtes **informationnelles et anglophones**. Les requêtes locales de praticiens ("sophrologue lens") déclenchent surtout pack local + annuaires : le CTR du résultat organique est structurellement plus bas, mais la **conversion du clic est bien plus haute**.
- Ahrefs note lui-même que le CTR mesuré est "probablement le plus haut qu'il sera" quand l'effet de nouveauté s'estompe. [ETUDE]

### 4.2 CTR et classement : que sait-on ?

- Un ingénieur Google (Eric Lehman) a déclaré sous serment lors du procès antitrust américain que **les clics sont le signal principal de NavBoost**, système qui réordonne les résultats sur une fenêtre glissante de 13 mois. [OFFICIEL, témoignage]
- La fuite de la documentation d'API (mai 2024) mentionne `goodClicks`, `badClicks`, `lastLongestClicks`. [EDITEUR, source : fuite, non confirmée officiellement]
- Ce qui est **le plus probable** : un clic suivi d'une session satisfaite (pas de retour rapide à la SERP) pèse plus qu'un simple clic. Un title qui promet ce que la page ne tient pas **fait perdre sur les deux tableaux**.
- Ce qui est **inconnu** : le poids pour une petite page locale, le seuil d'effet, le rôle exact du CTR relatif à la position. [HYPOTHESE]
- Les tentatives de manipulation de clics ne fonctionnent pas (anti-fraude, moyenne longue) et sont à bannir. [EXPERT]

**Règle d'outil :** présenter le CTR comme un levier de **trafic à position égale** (certain) et de **signal de qualité** (probable), jamais comme une garantie de classement.

### 4.3 Ce qui fait baisser le CTR sans que le title soit en cause

- Pack local en haut (sur les requêtes avec ville) : la fiche Google Business pèse autant que la page.
- AI Overviews sur les questions ("c'est quoi la sophrologie").
- Annuaires (Doctolib, Resalib, Pages Jaunes) positionnés devant.
- Extraits optimisés et "Autres questions posées" qui répondent sans clic.
- Position moyenne trompeuse : une page peut être en position 2 sur 3 requêtes et en position 15 sur 30 autres.

Donc avant de réécrire un title pour "CTR faible", **regarder la SERP réelle** de la requête.

---

## 5. Le meta title

### 5.1 Fonction

Il répond à la question : **"est-ce que cette page est pour moi ?"** en deux secondes, sur mobile. Il doit confirmer l'intention (métier, lieu, situation) et lever une incertitude (prix, durée, format, public).

### 5.2 Règles

1. **Une page, un title unique**, sans texte standard répété sur tout le site. [OFFICIEL]
2. **Mot-clé principal en tête** (métier + ville pour le local, sujet pour l'article), suivi d'un différenciateur concret. [EXPERT, cohérent avec OFFICIEL]
3. **Cohérent avec le H1** sans être identique, avec les mêmes mots-clés et le même sujet. L'écart title / H1 / contenu provoque la réécriture. [OFFICIEL + ETUDE]
4. **Longueur cible 50 à 60 caractères**, limite réelle en pixels (environ 580 pixels sur desktop). Les lettres larges (M, W, majuscules) consomment plus. Au-delà : troncature ou réécriture. [EXPERT / EDITEUR]
5. **Marque courte, à la fin**, avec séparateur ("|" ou "-" ou ":"). Pas de marque en tête sur une requête non-marque. [OFFICIEL + ETUDE + EDITEUR]
6. **Pas de bourrage** ("Sophrologue Lens, sophrologie Lens, cabinet sophrologie Lens"). [OFFICIEL]
7. **Pas de promesse de résultat** ni de superlatif invérifiable ("le meilleur", "résultats garantis", "guérir"). [OFFICIEL (exactitude YMYL) + conformité, voir section 7]
8. **Un chiffre ou une précision vraie** quand elle existe (tarif, durée, nombre de séances). Jamais inventé.
9. **Pas d'année seule en fin de title** ("... 2026") sur une page qui ne change pas. L'année est utile dans un guide réellement mis à jour, placée **au milieu ou en crochets**. [EDITEUR, directionnel]
10. **Même langue et même système d'écriture que la page.** [OFFICIEL]
11. Caractères spéciaux (emoji, symboles) : à éviter par défaut, Google les supprime souvent et ils lisent mal sur certains appareils. [EXPERT]

### 5.3 Ce qui est testé : patterns de title

Source : test contrôlé de 423 pages (28 jours avant/après, groupe témoin simultané, pages en position 1 à 20 avec au moins 150 impressions). **Directionnel, pas causal, aucun pourcentage de gain publié par les auteurs.** [EDITEUR]

| Pattern | Effet observé sur le CTR | Pourquoi probable |
|---|---|---|
| Chiffre ("3 à 6 séances") | plutôt positif | borne la réponse |
| Question qui reprend la formulation de la recherche | plutôt positif | correspond à la requête |
| "A ou B" / "vs" | plutôt positif | attire les comparateurs |
| Badge entre crochets ([Guide], [Tarifs]) | plutôt positif | précise le format |
| Année en milieu de title | neutre | fraîcheur sans friction |
| Année seule en fin | plutôt négatif | signal de page figée |
| Marque dans le title | plutôt négatif sur requêtes non-marque | moins pertinent pour le chercheur |

**Usage :** ces patterns sont des **hypothèses de test**, à valider page par page (section 10.3).

### 5.4 Formules par intention

| Intention | Formule | Exemple illustratif | Car. |
|---|---|---|---|
| Local transactionnelle | Métier à Ville : situation 1, situation 2 | `Hypnothérapeute à Nice : arrêt du tabac, phobies, stress` | 56 |
| Local avec prix | Métier à Ville : type de séance, tarif | `Sophrologue à Lens : séances individuelles, tarif 60 €` | 54 |
| Local avec marque courte | Métier à Ville : situations, puis marque | `Sophrologue à Lens : stress, sommeil ¦ Sophie Baraffe` (¦ = barre verticale) | 53 |
| Informationnelle question | Question reprise de la requête | `Sophrologie : à quoi sert une séance et combien en faut-il ?` | 60 |
| Comparaison | A ou B : différence et choix | `Sophrologie ou hypnose : quelle différence et que choisir ?` | 59 |
| Accueil / marque | Métier à Ville, puis marque | `Sophrologue à Lens ¦ Sophie Baraffe` (¦ = barre verticale) | 35 |

Version à éviter : `Sophrologue à Lens : séances individuelles, tarif 60 € - Sophie Baraffe` (71 car., trop long, la marque sera retirée ou le title tronqué).

---

## 6. La meta description

### 6.1 Fonction

Elle n'influence pas le classement. Elle **argumente le clic** quand Google l'affiche (environ 37 % des cas). Elle doit répondre à : "pourquoi cliquer sur cette page plutôt que sur celles d'à côté ?" [OFFICIEL + ETUDE]

### 6.2 Règles

1. **Unique par page**, jamais copiée-collée sur plusieurs pages. [OFFICIEL]
2. **Longueur cible 120 à 155 caractères**, avec **l'essentiel dans les 110 premiers** (la coupe est en pixels, plus courte sur mobile). [EXPERT / EDITEUR]
3. **Promesse + preuve + action**, en une ou deux phrases. [EXPERT]
4. **Données précises plutôt que slogans** : prix, durée, délai de rendez-vous, nombre de séances, lieu, public. C'est ce que les annuaires et concurrents n'affichent pas. [OFFICIEL (recommande prix, date, auteur) + EXPERT]
5. **Reprendre les mots de la requête** : Google les met en gras et choisit plus volontiers une meta qui matche. [EXPERT]
6. **Résumer la page entière**, pas un commentaire annexe. [OFFICIEL]
7. **Pas de suite de mots-clés.** [OFFICIEL]
8. **Pas de guillemets droits** (`"`) dans le texte (ils coupent la balise ou le JSON dans certains outils). Utiliser « » ou apostrophes.
9. **Un verbe d'action ou une invitation** (prendre rendez-vous, comparer, découvrir) quand l'intention est transactionnelle. Pour l'informationnel : annoncer **ce que la page donne** (déroulé, critères, nombre de séances).
10. Pas de promesse de guérison, pas d'affirmation médicale. [conformité]

### 6.3 Formules et exemples

| Type de page | Formule | Exemple illustratif | Car. |
|---|---|---|---|
| Page de service locale | Métier + lieu + ancienneté + situations + format + délai | `Sophrologue à Lens depuis 8 ans. Stress, sommeil, préparation mentale : séance d'1 h à 60 €, rendez-vous en ligne sous 48 h.` | 124 |
| Page de service (angle situation) | Situations en mots de patient + éléments affichés + délai | `Stress, sommeil, charge mentale : séances de sophrologie à Lens, tarif et déroulé affichés. Premier rendez-vous en ligne sous 48 h.` | 131 |
| Article question | Réponse courte + ce que la page détaille | `En général 3 à 6 séances, selon votre demande. Déroulé d'une séance, tarif, pour qui ce n'est pas adapté : tout est expliqué ici.` | 129 |
| Comparatif | Critères comparés | `Sophrologie ou hypnose ? Comparez le déroulé, la durée, le nombre de séances et les situations où chacune aide le plus.` | 119 |

### 6.4 Si Google réécrit la meta : que faire

1. Chercher dans la page **le passage que Google a choisi**. S'il est meilleur que la meta, le remonter (réponse directe en haut de page) et réécrire la meta pour qu'elle le reprenne.
2. Si Google prend une phrase de menu ou de pied de page : la page manque d'un **paragraphe d'ouverture clair**.
3. Ne pas courir après l'affichage de la meta à tout prix : sur la longue traîne elle est réécrite 65 % du temps. [ETUDE]

---

## 7. Spécificités praticiens, thérapeutes et bien-être

### 7.1 Conformité (priorité sur le CTR)

- Aucune promesse de guérison, pas de "soigner", "guérir", "traiter" une maladie pour un non-professionnel de santé. Préférer "accompagner", "soulager", "mieux gérer" avec un objet concret.
- Titres protégés (psychothérapeute, psychologue, diététicien...) : uniquement si détenu.
- Pas de "meilleur", "numéro 1", "garanti", "résultats rapides".
- Pas de faux sentiment d'urgence ("dernières places") dans un title ou une meta.
- Un signalement DGCCRF coûte plus cher que n'importe quel CTR. Toujours signaler le point à Isabelle quand la conformité limite un angle.

### 7.2 Local

- Sur "métier + ville", le **pack local et la fiche Google Business** captent une part majeure des clics. Le title / meta ne rattrapent pas une fiche pauvre.
- Garder **title, H1, nom de fiche, NAP** cohérents.
- Les annuaires affichent peu de détails : **prix, déroulé, délai, spécificité** dans la meta sont un angle gagnant. [EXPERT]
- Pages villes : title et meta **avec un élément propre à la ville** (accès, communes desservies, permanence), sinon on retombe dans les pages doublons.

### 7.3 Public et ton

- Vouvoiement sur les sites qui s'adressent aux patients (vérifier l'usage du site). Tutoiement uniquement pour les contenus visant les praticiennes (contenus TechRappy).
- Mots de patient, pas de jargon : "stress, sommeil, charge mentale, arrêter de fumer" plutôt que "techniques de relaxation dynamique".
- Éviter : holistique, bienveillant, passion, essence, alignement.

### 7.4 Lien avec le GEO

- Un title et une meta **explicites et factuels** facilitent aussi la compréhension par les moteurs IA, qui lisent les pages en parallèle. Aucune étude ne prouve qu'une meta influence une citation IA. [HYPOTHESE]
- La **réponse directe en haut de page** nourrit à la fois snippet, extrait optimisé et reprise IA.

---

## 8. Gabarits par type de page (title, meta, contrôles)

| Page | Title | Meta | À vérifier |
|---|---|---|---|
| Service locale | Métier à Ville : situations (+ prix si affiché) | Ancienneté + situations + format + délai ou tarif | H1 proche du title, adresse et NAP cohérents |
| Page ville (programmatique) | Métier à Ville : élément local | Même base + élément local propre à la ville | Aucun doublon de meta, page non doublon |
| Article informationnel | Question reprise de la requête | Réponse courte + ce que l'article détaille | Réponse directe dans les 2 premières phrases |
| Comparatif | A ou B : critères | Critères comparés | Tableau présent sur la page |
| À propos | Prénom Nom, métier à Ville | Parcours + formation + méthode | Cohérence avec profil Person |
| Accueil | Métier à Ville, puis marque | Résumé de l'offre | Meta de l'accueil différente des pages de service |
| Page de vente / formation | Résultat concret + format | Public + format + ce qu'on obtient | Mentions légales et prix conformes |
| Pages à ne pas indexer | noindex | sans objet | merci, mentions légales, archives vides |

---

## 9. Mise en oeuvre WordPress / Divi

1. **Extension SEO unique** (Rank Math, Yoast ou SEOPress). Le title et la meta se saisissent dans l'extension, pas dans Divi.
2. **Variables** : utiliser des modèles (`%title% | %sitename%`) uniquement comme **secours**. Les pages à enjeu ont un title et une meta écrits à la main.
3. **H1 Divi** : un seul H1, cohérent avec le title. Vérifier dans le rendu (extension de navigateur ou code source) que le module Divi ne génère pas un second H1.
4. **og:title / og:description** : les renseigner avec le même contenu (Google lit `og:title` parmi ses sources). Pas de texte différent du title.
5. **Contrôle du snippet** : `max-snippet` par défaut ouvert (ne pas le restreindre sans raison). `data-nosnippet` pour masquer un bloc inutile (bandeau, cookies, mention légale longue) des extraits.
6. **Recrawl** : après une modification, demander l'inspection d'URL / réindexation dans Search Console. Délai de prise en compte : jours à semaines.
7. **Séparateur** : "|" ou "-" ou ":" cohérent sur tout le site.
8. **Vérifier la sortie réelle** : view-source, balise `<title>` unique, une seule meta description, pas de doublon généré par un thème ou une seconde extension.
9. **Pas d'ajout de balises meta "keywords"** : inutile.
10. **Multilingue / pages alternatives** : un title et une meta par langue.

---

## 10. Mesure et protocole d'amélioration

### 10.1 Lecture Search Console

- Rapport Performances, onglet Pages puis Requêtes, filtre par page. Période : 28 jours minimum, comparée à la période précédente.
- Repérer : **forte impression, CTR faible pour la position** (comparer aux autres pages du même site à position proche, pas à un benchmark mondial).
- Séparer **requêtes de marque** et hors marque. Les marques ont un CTR bien plus haut.
- Regarder la **position moyenne par requête** : une page positionnée 8 à 15 gagne d'abord par la position, ensuite par le CTR.

### 10.2 Priorisation

1. Pages avec impressions élevées, position 3 à 10, CTR sous la médiane du site : **title et meta en premier**.
2. Pages en position 11 à 20 avec impressions : contenu, maillage et title.
3. Pages à CTR correct mais sans conversion : **title qui promet trop**, vérifier le message.
4. Requête inattendue avec beaucoup d'impressions : intention mal servie, nouvelle section ou page.

### 10.3 Protocole de test (par page)

1. Noter l'état de départ : title, meta, position, impressions, CTR sur 28 jours.
2. Changer **une seule chose** (title ou meta) à la fois.
3. Demander la réindexation, attendre **28 jours**.
4. Comparer au même volume d'impressions et à position similaire.
5. Garder si le CTR monte à position égale, sinon revenir. Noter le résultat dans une table de tests.
6. Ne jamais conclure sur moins de 100 clics ou moins de 28 jours. [EXPERT]
7. Éviter de tester pendant une saison atypique (vacances, rentrée) ou en même temps qu'une refonte.

### 10.4 Registre de tests à tenir

Colonnes : page, date, title avant, title après, meta avant, meta après, position avant/après, impressions avant/après, CTR avant/après, décision, apprentissage.

---

## 11. Contradictions et zones d'incertitude

| Sujet | Position A | Position B | Arbitrage |
|---|---|---|---|
| Longueur du title | "60 caractères max" (repère) | Google tronque en pixels, sans limite officielle | Viser 50-60, vérifier la largeur, ne pas sacrifier le sens |
| Marque dans le title | Utile pour la reconnaissance et la cohérence locale | Retirée par Google (63 %) et associée à un CTR plus bas hors marque | Courte, à la fin, retirée si le title est long |
| Meta et classement | "Aide" (beaucoup d'avis anciens) | Officiellement non | Levier de clic uniquement |
| CTR comme facteur de classement | Signal de clics confirmé en procès | Manipulation impossible, poids inconnu, jamais "un facteur CTR" simple | Optimiser pour la pertinence et le clic, ne pas promettre de classement |
| Année dans le title | Fraîcheur | Page figée si en fin de title | Dans un guide réellement mis à jour, au milieu ou entre crochets |
| Chiffres de CTR | Très divers (20 %, 27 %, 3,9 % en position 1) | Méthodes et périmètres différents | Utiliser les données Search Console du client |

---

## 12. Règles actionnables pour les outils

### 12.1 Principes

1. **Intention d'abord** : lire la SERP avant d'écrire le title. Pas d'écriture sans intention en une ligne.
2. **Concret avant créatif** : prix, délai, durée, lieu, public, nombre de séances.
3. **Cohérence** : title, H1, meta, og:title, contenu parlent du même sujet.
4. **Honnêteté** : tout ce que le title promet est tenu par la page.
5. **Deux versions** de chaque, avec compte de caractères et recommandation motivée.
6. **Données client** : jamais inventées, marqueur `[À CONFIRMER : ...]`.

### 12.2 Interdits

- Bourrage de mots-clés, répétition de la ville ou du métier.
- Promesse de guérison, superlatif invérifiable, "garanti", fausse urgence.
- Marque en tête sur page non-marque, marque trop longue.
- Même title ou même meta sur deux pages.
- Année seule en fin de title sur une page qui ne change pas.
- Majuscules abusives, emojis, symboles décoratifs.
- Tirets cadratins, mots bannis de la liste de style (holistique, bienveillant, passion, essence, alignement).
- Texte de meta qui décrit autre chose que la page (clic déçu).

### 12.3 Procédure de l'outil (ordre fixe)

1. Récupérer : URL, mot-clé principal, intention, type de page, ville, éléments vrais disponibles (prix, durée, délai, ancienneté, formation).
2. Vérifier la SERP (ou demander les 3 premiers résultats) et relever angle dominant et "trou".
3. Choisir la formule (sections 5.4 et 6.3).
4. Écrire 2 titles et 2 metas.
5. **Calculer les longueurs** (title 50-60, meta 120-155), signaler tout dépassement.
6. Contrôler la checklist 12.4.
7. Livrer : tableau des versions avec compte de caractères, recommandation, points `[À CONFIRMER]`, point de conformité éventuel.

### 12.4 Checklist avant livraison

- [ ] Intention identifiée et title adapté.
- [ ] Mot-clé principal en tête de title, une seule fois.
- [ ] Title entre 50 et 60 caractères (ou justification).
- [ ] Meta entre 120 et 155 caractères, essentiel dans les 110 premiers.
- [ ] Title cohérent avec H1 sans être identique.
- [ ] Au moins un élément concret dans la meta (prix, durée, délai, étapes).
- [ ] Marque courte et à la fin, ou absente.
- [ ] Aucun superlatif, aucune promesse de guérison.
- [ ] Aucun guillemet droit, aucun emoji, aucun tiret cadratin.
- [ ] Unique sur le site (pas de doublon).
- [ ] Les données client sont confirmées ou marquées `[À CONFIRMER]`.
- [ ] Vouvoiement ou tutoiement conforme à la cible.

### 12.5 Grille d'audit title + meta (sur 20)

| Critère | Points |
|---|---|
| Intention reconnue (format et angle adaptés à la SERP) | 4 |
| Mot-clé principal en tête, sans bourrage | 3 |
| Longueur correcte (title et meta) | 2 |
| Cohérence title / H1 / contenu | 3 |
| Élément concret différenciant dans la meta (prix, délai, étapes) | 3 |
| Promesse tenue par la page, conformité respectée | 3 |
| Unicité sur le site, marque bien placée | 2 |

Lecture : 16-20 publier, 12-15 corriger les critères faibles, moins de 12 réécrire.

### 12.6 Prompt-type pour l'outil (à adapter)

```
Tu écris le title et la meta description d'une page de [type de page] pour [métier] à [ville].
Entrées : mot-clé principal, intention, éléments vrais (prix, durée, délai, ancienneté), SERP observée.
Règles : title 50-60 caractères, meta 120-155 caractères avec l'essentiel dans les 110 premiers.
Mot-clé principal en tête du title, marque courte à la fin ou absente, cohérent avec le H1.
Meta : promesse + preuve + action, au moins un élément concret, jamais de promesse de guérison.
Interdits : bourrage, superlatifs, fausse urgence, guillemets droits, emojis, tirets cadratins, mots bannis de la liste de style.
Livre 2 versions de chaque avec compte de caractères, une recommandation motivée, et les points [À CONFIRMER].
```

---

## 13. Hypothèses à tester sur nos sites

1. Une meta qui contient **prix + délai** augmente-t-elle le CTR des pages de service locales par rapport à une meta "promesse seule" ?
2. Marque présente vs absente dans le title : effet sur le CTR hors marque pour des pages locales de praticiens.
3. Title en question vs title en affirmation sur les articles informationnels à fort volume.
4. Remonter la réponse directe en haut de page réduit-il la réécriture des metas ?
5. Effet d'une meta qui nomme un public ("jeunes parents épuisés") sur le CTR et sur la qualité des demandes de rendez-vous.
6. Un title mentionnant le prix (si affiché) filtre-t-il les clics non qualifiés sans baisser les rendez-vous ?
7. Quel est le CTR relatif d'une page de service quand le pack local est présent ou absent ?

Chaque test suit le protocole 10.3 et est consigné dans le registre 10.4.

---

## 14. Limites de cette fiche

- Les études citées viennent d'outils et d'agences (conflits d'intérêts possibles), surtout **sur le marché anglophone** et **sur des requêtes informationnelles**. Elles ne se transposent pas telles quelles aux requêtes locales de praticiens en France.
- Les chiffres Advanced Web Ranking (positions et AIO) sont publiés sans détail méthodologique suffisant pour être audités. À utiliser comme ordre de grandeur.
- Le test de 423 pages sur les patterns de title est directionnel et sans pourcentage de gain.
- Les informations sur NavBoost reposent sur des témoignages et des fuites, pas sur une documentation publique Google.
- Les limites en pixels peuvent évoluer avec les changements d'interface de Google : revérifier de temps en temps.
- Aucune donnée spécifique aux praticiens français n'a été trouvée dans ces sources. Les règles de la section 7 sont des règles de méthode, à valider par nos propres tests.

---

## 15. Sources

- Google Search Central, Title links : https://developers.google.com/search/docs/appearance/title-link
- Google Search Central, Snippets et meta description : https://developers.google.com/search/docs/appearance/snippet
- Search Engine Land, "Google changed 76% of title tags in Q1 2025" : https://searchengineland.com/google-changed-76-of-title-tags-in-q1-2025-heres-what-that-means-454847
- Ahrefs, "How often does Google rewrite meta descriptions?" : https://ahrefs.com/blog/meta-description-study/
- Ahrefs, "Update: AI Overviews reduce clicks by 58%" : https://ahrefs.com/blog/ai-overviews-reduce-clicks-update/
- Advanced Web Ranking, Organic CTR : https://www.advancedwebranking.com/seo/organic-ctr
- Test de 423 titles (US Tech Automations) : https://ustechautomations.com/resources/blog/how-we-ab-tested-423-seo-titles-for-clickthrough-rate-2026
- Repères caractères et pixels : https://flaviocopes.com/character-limits-titles-descriptions
- NavBoost (synthèse du procès et de la fuite) : https://blckalpaca.at/en/knowledge-base/seo-geo/on-page-seo/user-engagement-signals-navboost-as-confirmed-ranking-factor

---

## Annexe : glossaire

- **CTR** : clics divisés par impressions.
- **SERP** : page de résultats de recherche.
- **AIO** : AI Overviews, réponses générées en haut de SERP.
- **NavBoost** : système de Google qui ajuste le classement avec les données de clics.
- **Snippet** : extrait affiché sous le title.
- **NAP** : nom, adresse, téléphone.
- **YMYL** : domaines à enjeu pour la santé, l'argent ou la sécurité.
