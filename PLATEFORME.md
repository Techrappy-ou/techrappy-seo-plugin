# 🗺️ Carte de la plateforme TechRappy

> **Généré automatiquement le 14/10/2026 à 13:56.** Ce fichier est la source de vérité de toute l'infrastructure.
> Pour le régénérer (après des changements) : `wp eval-file generer-carte.php --allow-root` depuis `/var/www/cabinet-visible`, puis committer ce fichier.

## En bref

- **190 modules** (mu-plugins), **85 647 lignes de code**
- Serveur central **VPS** `89.116.110.43` (accès SSH par clé `~/.ssh/cabinet_visible_ed25519`)
- Les sites des clientes sont chez **o2switch** (pas d'accès serveur) — on leur parle via la **brique « TechRappy Connect »** (plugin posé sur leur site, connexion par clé)

## 🏗️ Architecture (comment tout se connecte)

```
  TA PLATEFORME (VPS cabinet-visible.fr)
   ├─ Les outils (190 modules ci-dessous) : SEO, CRM/Compta, Espace Coach, Sommet...
   ├─ Le MOTEUR IA partagé  cv_le_call_claude()  (clé Anthropic dans /etc/cv/credentials.env)
   ├─ Clés API centralisées : Anthropic, OpenAI, Serper, DataForSEO, Stripe  (/etc/cv/credentials.env)
   └─ Génère du contenu  ─────────────┐
                                      ▼
  BRIQUE « TechRappy Connect »  (plugin WordPress posé sur chaque site cliente, o2switch)
   ├─ Reçoit le contenu via une clé de connexion (route REST /wp-json/techrappy/v1/...)
   ├─ Publie en mise en page Divi native (pages SEO locales + articles de blog)
   ├─ Se met à jour toute seule (update.json hébergé sur cabinet-visible.fr)
   └─ Protège les pages existantes de la cliente (jamais écrasées)
```

**Les 7 sites WordPress du serveur** : `cabinet-visible`, `cabinet-visible-private`, `html`, `plateforme-test`, `regine-staging`, `sommet-cabinet-visible`, `test-techrappy`

## 📦 Les modules, par domaine

### CRM · Compta · Trésorerie  _(21)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-crm-ads-spend.php` | Reçoit la dépense réelle de tous les comptes Google Ads (script MCC quotidien), permet de lier chaque compte à une cliente, et compare au budget avancé. | 121 |
| `cv-crm-clientes.php` | Suivi par cliente (statut V1, RDV, signature, paiement, prochaine relance, notes). Admin, super-admin/manage_cv_sites. | 497 |
| `cv-crm-compta-abos.php` | Vue de TOUS les abonnements par type (hébergement, Google Ads, challenge, premium, site), avec MRR, statut, dernier paiement. Admin only. | 67 |
| `cv-crm-compta-adssuivi.php` | Vue claire de toutes les clientes Google Ads depuis le début : en cours / stoppé / pas commencé, arrangement (service+pub / j'avance / offert), service encai… | 303 |
| `cv-crm-compta-agenda.php` | Synchronise les agendas Google (iCal) : planning global + lives Premium 1/2 + Bootcamp 1/2. Rapproche chaque RDV d'une cliente. Admin only. | 142 |
| `cv-crm-compta-alertes.php` | Liens de paiement Stripe réutilisables (copiables) + relances programmées (date de renvoi, stop quand payé) + surveillance Google Ads. Réservé à Isabelle (ma… | 147 |
| `cv-crm-compta-analyse.php` | Segmente les clientes par offre (Bootcamp éd.1/éd.2, Offre Visible, Hébergement, Google Ads, Premium, Site, autre) et calcule par offre/événement : nb client… | 153 |
| `cv-crm-compta-bank.php` | Rapproche les crédits du relevé LHV (table cv_treso, déjà importée) avec les échéances non payées, via un score de confiance. Payout Stripe ≠ CA. File « à vé… | 304 |
| `cv-crm-compta-cockpit.php` | Tableau de bord : bandeau MRR, indicateurs (encaissé, en attente, impayés, marge), graphique réel/certain par mois, tableau par service. Données réelles. Adm… | 306 |
| `cv-crm-compta-depenses.php` | Dépenses certaines (depuis le relevé LHV/cv_treso) + prévues (manuelles) ; budgets Google Ads clientes (import facture, liaison compte→cliente, rapprochement… | 423 |
| `cv-crm-compta-echeancier.php` | Une page claire : paiements attendus (validables) par catégorie, dépenses récurrentes par catégorie, valeur nette. | 359 |
| `cv-crm-compta-pipeline.php` | Pipeline d'opportunités (pondérée), Demandes/SAV (depuis le système de tickets), relances d'impayés (J+3 email, J+10 email, J+20 appel) via Brevo. Admin only. | 301 |
| `cv-crm-compta-plus.php` | Vue Salariés (heures loggées sur la plateforme + coûts + versé réel) et Répartition en % + prévisionnel (entrées connues des prochains mois → combien de clie… | 154 |
| `cv-crm-compta-projection.php` | Projection de trésorerie 3 scénarios (prudent/réaliste/optimiste), TVA (dès 01/10/2026, paramètres à valider comptable), exports CSV. Admin only. | 203 |
| `cv-crm-compta-sync.php` | Webhook Stripe (signature + anti-doublon) + backfill matin/soir + rattachement des paiements aux échéances + abonnements (MRR). Idempotent. Payout Stripe ≠ CA. | 293 |
| `cv-crm-compta-ui.php` | Transforme la section CRM Compta en application pleine page (barre latérale, sans le décor WordPress), en réutilisant tous les écrans. Admin only. | 153 |
| `cv-crm-compta.php` | CRM comptable (clientes, échéanciers, paiements, MRR, trésorerie). Phase 1 : schéma + écran Clients. Accès réservé à l'admin (Isabelle). Montants stockés en … | 984 |
| `cv-crm-front.php` | Sert le CRM (cv-crm-clientes) comme une page publique plein écran hors dashboard, façon /coach/ et /equipe/. Réutilise cv_crm_page() / cv_crm_fiche_page() vi… | 101 |
| `cv-crmc-finance.php` | CV_CRMC_Finance = LA source unique des chiffres argent (gains, dépenses, net, marge), | 202 |
| `cv-crmc-mois.php` | Vue mensuelle lisible basée sur la source unique CV_CRMC_Finance : gains (Stripe+virements), | 163 |
| `cv-tresorerie.php` | Suivi de trésorerie : import du relevé LHV (CSV), catégorisation entrées/sorties, récap mensuel, prévisions de dépenses récurrentes vs réel. Réservé à la pro… | 646 |

### Clientes · Tâches · Formulaires  _(16)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-admin-clientes-360.php` | Page admin Isabelle. Vue 360 par cliente : workbook, branding, ligne edito, site, atelier posts, newsletter, profil visible, modeles, sessions coach, alertes. | 672 |
| `cv-brief-v1.php` | Questionnaire V1 simplifié à envoyer à une nouvelle cliente (lien tokenisé, autosave, envoi des réponses à Isabelle). Réutilisable pour toute nouvelle V1. | 280 |
| `cv-depot-fichier.php` | Lien privé envoyé à une cliente pour déposer un fichier (zip, PDF, image…) avec une description obligatoire. Le fichier s'ajoute à sa demande de modification… | 133 |
| `cv-outil-site-modifs.php` | Formulaire de demande de modifications du site V1. 2 envois max par participante. Screenshots + vidéo + précisions. | 352 |
| `cv-site-analyze.php` | Quand la cliente a déjà un site, récupère son contenu (accueil + « à propos »), en extrait par IA les infos clés + les réseaux sociaux (regex), et renvoie un… | 174 |
| `cv-site-email.php` | Envoie aux clientes l'invitation a remplir leur brief "Mon site" (style orange, via Brevo SMTP). | 80 |
| `cv-site-marion-notif.php` | Email a Marion quand une cliente soumet son brief "Mon site" (hook cv_site_submitted). Inclut la priorite. | 100 |
| `cv-site-modifs-notif.php` | Email à Marion à chaque demande de modifications envoyée par une cliente (hook cv_site_modifs_submitted). Inclut le texte, les fichiers joints et un lien ver… | 77 |
| `cv-site-prompts.php` | — | 220 |
| `cv-site-raconte.php` | 1re étape pour les clientes site-only : elle dicte librement (offre, goûts, valeurs, ce qu'elle imagine) et/ou dépose des PDF, l'IA en extrait des valeurs po… | 122 |
| `cv-site-v1-front.php` | Sert le panneau « Mon site » / génération V1 (cv_site_admin_page) en plein écran hors dashboard, via le shell front partagé. Réutilise la logique existante (… | 45 |
| `cv-site-v1-hub.php` | Page admin qui liste toutes les V1 clientes (cv_site_responses) avec accès rapide : ouvrir le site + wp-admin + statut. Réservé aux gestionnaires de sites. | 209 |
| `cv-site-v1.php` | Quand Marion marque une V1 terminée, le sous-site de staging est protégé par un mot de passe (verrou de relecture), et la cliente reçoit le lien + le mot de … | 141 |
| `cv-site-worker.php` | — | 255 |
| `cv-taches-marion.php` | Planning de tâches Marion : onglets Créations / Modifications, file FIFO réordonnable (flèches, notif email Isabelle), statuts À faire/En cours/Fait, heures … | 925 |
| `cv-taches-precision.php` | Depuis le planning de Marion, poser une question à la cliente sur une tâche en cours. La cliente reçoit un lien privé, répond et peut joindre des fichiers ; … | 370 |

### Espace Coach · Coaching  _(13)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-coach-bootcamp.php` | Page coach listant les participantes du bootcamp (Espace Entreprise) avec, pour chacune, un topo condensé : workbook entreprise, promesse, headline validée, … | 154 |
| `cv-coach-dashboard.php` | Interface dédiée /coach/ pour Isabelle, Crista, Virginie. Sidebar clientes + main content profil. UI cohérente avec la plateforme cliente. | 499 |
| `cv-coach-files.php` | P1 upload/download + preview PDF (PDF.js inline). Quota 500 Mo/cliente. Historique versions 10 dernières + <30j. Isolation multi-tenant + asymétrie Crista/Vi… | 1783 |
| `cv-coach-sessions.php` | Espace coachs (Crista + Virginie) pour ajouter notes de sessions (vidéo, fichier, topo). Visible côté cliente dans Mon Espace. | 1178 |
| `cv-coach-site-info.php` | Quand un coach (Virginie) coche « Informations pour le site » sur un topo/document de session, une tâche est créée/mise à jour pour Marion (avec le document)… | 80 |
| `cv-coach-topo-validation.php` | Les topos de coaching (PDF) n'alimentent la plateforme qu'une fois VALIDÉS par la cliente. Migration : les topos < 30j sont considérés à jour (auto-validés +… | 177 |
| `cv-coach-topos-helper.php` | Helpers partages pour enrichir les prompts IA avec les topos coach Virginie + les posts deja valides par la cliente. | 370 |
| `cv-espace-coach-fiche.php` | Endpoint AJAX de la fiche coachée (onglets vue/coachings/site/diagnostic/workbook/fichiers) sur données existantes. Droits vérifiés côté serveur. | 843 |
| `cv-espace-coach.php` | Nouvelle interface Espace Coach (Phase 2 : coquille + navigation + liste des coachées). Page /espace-coach/. Permissions côté serveur via cv_coach_slug_for_u… | 737 |
| `cv-topo-front.php` | Sert l'outil Topo → Mail (cv_tm_admin_page) en page plein écran hors dashboard, via le shell front partagé. | 44 |
| `cv-topo-mail.php` | Isabelle colle le transcript d'une réunion, l'IA le met en forme en mail « topo », elle valide/modifie, puis envoie (objet auto « Topo de la réunion du JJ/MM… | 301 |
| `cv-workbook-pdf.php` | — | 575 |
| `cv-workbook-prompts.php` | — | 563 |

### Fiche cliente · Contexte · Entreprise  _(28)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-contexte-plateforme.php` | Agrège TOUTES les données déjà saisies par une cliente (workbook, branding, | 111 |
| `cv-entreprise-access.php` | Connexion sans mot de passe par email pour le bootcamp. Lien signe HMAC + expiry, compte cree a la volee. | 160 |
| `cv-entreprise-assistant.php` | Donnees de la base de connaissance : matrice secteur->problematiques + table de traduction B2C->B2B. | 168 |
| `cv-entreprise-atelier.php` | Génère des posts LinkedIn B2B. Mode sans template (21 ossatures) ou avec template (coller/PDF/Word). Depuis le workbook. Anti-plagiat. | 514 |
| `cv-entreprise-banniere.php` | Génère la bannière LinkedIn B2B (IA depuis le workbook) et la rend via les 5 templates existants, aux couleurs de la cliente. Export PNG. | 268 |
| `cv-entreprise-complement.php` | Page /espace-entreprise/complement accessible par lien tokenisé : la cliente confirme/complète les infos factuelles manquantes ; la réponse s'écrit DIRECTEME… | 135 |
| `cv-entreprise-headline.php` | Constructeur de headline LinkedIn B2B (5 ingrédients, 7 formes, test du décideur). Pré-rempli par l'IA depuis le workbook. | 431 |
| `cv-entreprise-offres.php` | Permet à une liste restreinte de clientes (par email) d'avoir PLUSIEURS offres/workbooks dans l'Espace Entreprise et de basculer entre elles. L'offre « entre… | 122 |
| `cv-entreprise-pagevente-form.php` | Formulaire de la page de vente : sous-domaine, cas clients, photos, tarif, contact. Pre-rempli du workbook. | 447 |
| `cv-entreprise-pagevente-gen.php` | Génère les 15 blocs (2 appels Claude haut/bas) + chiffres sectoriels sourcés + mentions légales & politique de confidentialité. | 414 |
| `cv-entreprise-pagevente-publish.php` | Publie la page générée sur prenom-nom.cabinet-visible.fr (sous-site), couleurs de la cliente, formulaire de contact fonctionnel. | 249 |
| `cv-entreprise-pagevente.php` | Port PHP du generateur 15 blocs (generate_pages.py). Rend une page de vente B2B depuis un dict de donnees. | 358 |
| `cv-entreprise-piliers.php` | Aide les participantes du bootcamp à identifier leurs 3 piliers de contenu B2B (IA à partir du workbook + fiche B2B, OU saisie manuelle). Cohérent avec leur … | 272 |
| `cv-entreprise-prefill.php` | Extrait/adapte les champs transversaux du contexte plateforme (B2C) vers le workbook entreprise (B2B). | 147 |
| `cv-entreprise-profil.php` | Outil d'analyse de profil LinkedIn (texte collé + captures) : qui / cible / offre / diagnostic (headline, bannière /105, photo, bio) / plan d'action, orienté… | 426 |
| `cv-entreprise-recap.php` | Fiche récapitulative (sortie_generee) en lecture seule + export PDF (impression navigateur). | 121 |
| `cv-entreprise-versions.php` | Avant chaque sauvegarde du workbook entreprise qui effacerait un champ déjà rempli, fige l'état actuel dans un historique (20 dernières versions/personne). P… | 182 |
| `cv-entreprise-workbook.php` | Formulaire Workbook Entreprise (6 etapes, 56 champs) rendu depuis le schema JSON + sauvegarde auto. | 479 |
| `cv-entreprise.php` | Hub /espace-entreprise (4 outils B2B) + controle d'acces premium (permanent) / bootcamp (jusqu'au 31/08/2026). | 231 |
| `cv-fiche-cliente-gen.php` | Agrège toutes les sources de la plateforme (workbook, Mon site, branding, ligne édito, profil visible, diagnostics, workbook 21j, J1/J4, audit, topos validés… | 596 |
| `cv-fiche-cliente-pdf.php` | Génère un vrai PDF téléchargeable de la Fiche Cliente Vivante (mise en page adaptée à mPDF). Accès : coach (toute cliente) ou la cliente (sa propre fiche). | 153 |
| `cv-fiche-cliente-render.php` | cv_fiche_render($fiche, $opts) -> HTML autonome (design Cabinet Visible), piloté par les données, n'affiche que les rubriques remplies. Utilisé par la vue co… | 365 |
| `cv-fiche-cliente-ui.php` | Onglet « Fiche » dans le dashboard coach + génération en arrière-plan (la fiche met ~2 min à se construire). Rendu via cv_fiche_render. Opérationnel pour tou… | 457 |
| `cv-fiche-cliente.php` | Le "big mega topo" par cliente : document unique, source de tous les outils. Stockage versionné (chaque MAJ archive l'ancienne version, restauration 1 clic),… | 188 |
| `cv-fiche-entreprise.php` | Fiche factuelle unique par cliente (indexée par email) = carte d'identité de l'activité | 438 |
| `cv-fiche-pratiques.php` | Extrait pour chaque cliente une liste normalisée de ses PRATIQUES/modalités + sa ZONE de travail (ville, périmètre alentours, visio) + des mots-clés référenc… | 210 |
| `cv-fiche-vivante-page.php` | Expose le panneau complet cv_fiche_coach_panel() sur une page front /fiche-vivante/?email=… | 63 |
| `cv-outil-google-ads.php` | MVP Suivi Google Ads pour Isabelle Maestrati (tables, seeds, page WP, shortcode, endpoints REST). | 1006 |

### Mariage · Divers clients  _(2)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-ads-brief.php` | Formulaire tokenisé envoyé au client pour compléter le brief d'une campagne Meta Ads. Pré-rempli, sauvegarde auto, récap envoyé à Isabelle. | 490 |
| `cv-wedding-ln.php` | Page privée (noindex) pour les cadeaux de mariage : IBAN/BIC copier-coller + bouton Revolut (option cv_ln_revolut_url). Servie sur lucie-et-nicolas.cabinet-v… | 159 |

### Moteur · Infra · Technique  _(7)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-404.php` | Remplace la page 404 du thème par défaut (twentytwentyfive + arbre de totara) par une page brandée Cabinet Visible. | 179 |
| `cv-api-usage.php` | Log chaque appel Claude (tokens + coût) et dashboard admin "💰 Coûts API". | 351 |
| `cv-health-check.php` | Endpoint /wp-json/cv/v1/health qui audite l'etat des composants critiques de cabinet-visible.fr (BOM, syntax, endpoints REST, DB). | 281 |
| `cv-le-prompts.php` | — | 481 |
| `cv-le-worker.php` | — | 309 |
| `cv-oauth-google.php` | OAuth 2.0 handler for Google Ads and Google Calendar credentials collection. | 129 |
| `cv-pont.php` | — | 203 |

### Plateforme payante · Accès · Hub  _(5)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-equipe-hub.php` | Porte d'entrée unique de l'équipe, hors dashboard : /equipe/. Chaque membre voit ses outils selon son rôle (Isabelle, Virginie, Marion, Tiffany). Page front … | 244 |
| `cv-pilotage.php` | Page /pilotage (admin uniquement) : acces + stats en direct des 2 plateformes (Cabinet Visible + Sommet SS26). | 196 |
| `cv-plateforme-nocache.php` | Empêche le cache navigateur de la page /mon-espace/ pour éviter les bugs de version obsolète du JS. | 18 |
| `cv-plateforme-tasks-seed.php` | — | 159 |
| `cv-plateforme.php` | La plateforme accompagnement complète : sidebar, dashboard 7 phases, intégration Mon Offre Visible + Mon Site Visible, tâches, ressources, satisfactions. by … | 3584 |

### SEO · Génération · Contenu  _(41)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-ap-kb-instagram.php` | — | 276 |
| `cv-ap-kb-templates-bank.php` | — | 1298 |
| `cv-ap-prompts.php` | — | 1097 |
| `cv-ap-workbook-injection.php` | — | 63 |
| `cv-atelier-analyze.php` | Endpoint d'analyse de posts (LinkedIn/Instagram) selon la grille 10 criteres ponderee par archetype. | 543 |
| `cv-atelier-auth.php` | — | 140 |
| `cv-atelier-prereqs.php` | — | 597 |
| `cv-atelier-suggestions.php` | Projette le ai_plan stocke dans wp_cv_ligne_edito_responses sur le calendrier de l'Atelier V10. | 190 |
| `cv-atelier-v10-antiredundancy.php` | — | 293 |
| `cv-atelier-v10.php` | — | 3480 |
| `cv-newsletter-skill.php` | — | 250 |
| `cv-outil-atelier-post.php` | Crée un post LinkedIn personnalisé via cascade IA. Choix archétype → source (calendrier/thématique/guide) → questions → génération. | 604 |
| `cv-outil-ligne-edito.php` | Cascade IA Situation → Personas → 3 Piliers de contenu → Plan 30 jours LinkedIn. Inspiré du framework d'Amélie Starace. | 653 |
| `cv-outil-newsletter.php` | Generation de newsletters et emails (newsletter, bienvenue, nurture, lancement, relance, post_achat) avec frameworks copywriting et prompt caching Claude. | 859 |
| `cv-seo-audit.php` | Audit SEO on-page du site relié de la cliente (crawl public via sitemap) : titres/meta manquants | 425 |
| `cv-seo-comparateur.php` | Moteur de page comparateur (Bible SEO TechRappy 11/10/2026). Structure SEO complète : | 277 |
| `cv-seo-express.php` | Version non-premium du Rédacteur SEO. Accès par identification (email + prénom + nom + téléphone), sans compte plateforme. Réutilise le moteur de cv-seo-reda… | 212 |
| `cv-seo-gmb.php` | Audit de fiche Google Business pour thérapeutes (grille TechRappy 13 critères /100), données | 558 |
| `cv-seo-locale.php` | Page locale de thérapeute selon la Bible TechRappy (Template A) — POUR LES CLIENTES, SANS concurrents. | 288 |
| `cv-seo-maillage.php` | Carte du maillage interne d'un site (pages + liens entrants/sortants + orphelines) et | 158 |
| `cv-seo-masse.php` | Moteur central : lit le template d'un site connecté, génère un contenu unique par ville (pipeline SEO), assemble dans le Divi et publie via le connecteur. Ga… | 919 |
| `cv-seo-meta.php` | Scanne le site de la cliente, repère les pages sans meta description, génère un | 190 |
| `cv-seo-pont.php` | — | 33 |
| `cv-seo-recherche.php` | À partir du métier/thème de la cliente, lit la vraie page Google (questions des | 146 |
| `cv-seo-redacteur.php` | Outil SEO guidé (méthode seo-parfait) : mot-clé → vraie SERP (Serper.dev) → intention → tableau de mots-clés → plan d'article → rédaction → meta. Espace prem… | 1737 |
| `cv-seo-score-existant.php` | Score « face au top 3 » sur un article EXISTANT (collé ou récupéré par URL), sans passer par | 100 |
| `cv-seo-sites.php` | Le « lieu unique » : tous les sites clientes reliés à TechRappy Connect, leur statut (Divi, connexion), point de départ de la génération de masse. Admin only… | 658 |
| `cv-studio-calendrier.php` | Vue ligne éditoriale (piliers, évolutive) + objectif mensuel (60/30/10) + génération d'un mois équilibré. Chaque post planifié s'ouvre dans l'atelier. Lit la… | 614 |
| `cv-studio-clients.php` | Enregistre le profil de chaque cliente (prénom, nom, email, téléphone, source) quoi qu'il arrive, clé = email. Détecte les doublons par téléphone + prénom. A… | 119 |
| `cv-studio-fiche-pdf.php` | Génère un vrai PDF téléchargeable de la Fiche du studio (Toi, ta cliente, la cascade, ton offre, ta voix, ton rythme, tes profils). Accès : la cliente connec… | 185 |
| `cv-studio-fiche.php` | La Fiche du studio : un seul formulaire sectionné, sauvegarde au fil, source de tous les outils. Brique 1 : Toi + Ta cliente → génération de la promesse (3 v… | 635 |
| `cv-studio-landing.php` | Landing marketing du Studio (hors sommet, 150€), d'après Le Grand Brief. 14 blocs, un seul CTA « Je teste le Studio », SEO, statique. Aucune fausse rareté, a… | 501 |
| `cv-studio-pagevente.php` | Génère une page de vente complète depuis la Fiche, adaptée à la cible (particuliers ou entreprise). Aperçu plein écran + copie du lien. Pas de SEO ajouté. Li… | 1177 |
| `cv-studio-paiement.php` | Écran « continuer » quand l'essai est fini, lien d'abonnement configurable (Stripe/Systeme.io/PayPal…), webhook signé qui réactive l'accès au paiement, prolo… | 280 |
| `cv-studio-posts.php` | Générateur de post (le geste de la semaine). Lit la Fiche (piliers + voix) et écrit hook + corps + CTA selon la méthode TechRappy. Score de viralité du hook … | 717 |
| `cv-studio-profils.php` | Outil du studio. Lit la Fiche (cv_sfiche_context) et génère 3 headlines LinkedIn, une bio Instagram et l'accroche de bannière, dans la voix de la cliente. Vu… | 343 |
| `cv-studio-stats.php` | /studio-techrappy/analyse · saisie manuelle des chiffres par post (impressions, interactions, partages, enregistrements, abonnés) + Top 3 global et par type … | 311 |
| `cv-studio-techrappy.php` | /studio-techrappy · accès freemium 3 mois (code Pass Gold OU whitelist admin), connexion par lien magique, PWA installable (mobile d'abord). Phase 1 : accès … | 947 |
| `cv-studio-visuel.php` | Espace clientes : 4 styles + description → Claude écrit le prompt (branding) → gpt-image-1 génère l'image. Limite/jour + tracker + upload de référence. | 343 |
| `cv-studio-visuels.php` | Depuis un post (ou un thème) et le branding de la Fiche, propose 3 pistes visuelles concrètes : format, modèle, texte NET à poser sur l'image, idée d'image e… | 273 |
| `cv-studio-voix.php` | La fiche de voix qui apprend. Chaque retour ("trop formel", "trop long") ou réécriture de la cliente est distillé en règles de voix versionnées, réinjectées … | 313 |

### Autres  _(57)_

| Fichier | Rôle | Lignes |
|---|---|---:|
| `cv-armelle-paiements.php` | (1) Mail des 3×363€ programmé pour le 01/11. (2) Surveillance de l'abonnement 363€ : | 83 |
| `cv-audrey-hebergement.php` | Mail automatique à Audrey (Orchidée) le 1er janvier 2027 à 10h (Paris) : activation de | 53 |
| `cv-banniere-linkedin-skill.php` | — | 405 |
| `cv-banniere-templates.php` | — | 403 |
| `cv-bidart-video.php` | Page teaser sur bidart.cabinet-visible.fr : la photo en plein écran, entourée de mots intrigants (Qui est-ce ? Où est-elle ? Que fait-elle ?) et le hook « Il… | 85 |
| `cv-challenge-keep.php` | Bouton traçable "Je garde ma page" (opt-in) pour les pages challenge. Route /garder-ma-page. | 47 |
| `cv-challenge-loader.php` | Regroupe les outils du challenge dans le dossier /challenge/ et les charge d'un bloc. | 18 |
| `cv-checklist-livraison.php` | Checklist de livraison (Bible TechRappy) accessible par Marion dans « À faire », cochable | 282 |
| `cv-dictee-vocale.php` | Ajoute un bouton micro à côté de chaque textarea des outils plateforme. Permet la saisie à la voix (Web Speech API). Utile pour cliente malvoyante + toutes l… | 314 |
| `cv-docs-guard.php` | Sert les documents de /modifs-docs (stockés HORS racine web) uniquement aux utilisateurs autorisés (Marion = manage_cv_sites / admin). Bloque tout accès publ… | 75 |
| `cv-email-alias.php` | Permet à une cliente de se connecter avec un email alias qui pointe vers son compte principal (mêmes données). Option cv_email_aliases (alias => canonique). | 26 |
| `cv-embed.php` | — | 23 |
| `cv-export-cliente.php` | Endpoint REST pour exporter en ZIP (ou PDF seul) tout le parcours d'une cliente : Workbook, Branding, Site Visible, Ligne editoriale, Profil Visible, Newslet… | 738 |
| `cv-impersonate.php` | Permet a un super-admin de basculer sur une cliente sans passer par /wp-login.php (Cloudflare WAF bloque switch_to_user). | 117 |
| `cv-menu-cleanup.php` | (1) Cache du menu public les pages internes (outils équipe, staff, admin, page d'exemple). (2) Fusionne les doublons du menu admin (Clientes 360° → CRM, Tâch… | 72 |
| `cv-mpv-skill-about.php` | — | 376 |
| `cv-mpv-skill.php` | — | 523 |
| `cv-offre-cible.php` | Génère, PAR CIBLE (profil de la ligne éditoriale), un document "Mon Offre Visible" | 190 |
| `cv-onepage-template.php` | — | 51 |
| `cv-outil-audit.php` | Audit visibilité 40 questions → profil parmi 4 (Cercle Rouge / Carré Jaune / Losange Vert / Étoile Dorée) | 359 |
| `cv-outil-branding.php` | Formulaire Branding intégré à la plateforme Thérapeute Visible. Couleurs, polices, logo, ton éditorial, réseaux. Auto-valide la tâche associée à la soumission. | 379 |
| `cv-outil-celine-export.php` | Endpoint REST /cv/v1/celine-clientes-csv qui expose un CSV des clientes | 165 |
| `cv-outil-diag-entree-pdf.php` | Genere le PDF resultat du Diagnostic "Les Freins Invisibles" via mPDF. | 557 |
| `cv-outil-diag-entree.php` | Diagnostic 18 questions × 6 profils par Céline Nosliere. Structure calquée sur cv-outil-workbook-celine.php (whitelist, HMAC token session, garde anti-écrase… | 778 |
| `cv-outil-diag-sortie.php` | Diagnostic de sortie 4 étapes pour le live du 29 juillet 2026 avec Céline Nosliere. Pattern calqué sur cv-outil-workbook-celine.php avec VERROU TEMPOREL (unl… | 752 |
| `cv-outil-feedback.php` | Systeme feedback plateforme cliente : demandes auto (replay/quete/formation) + retours + comptes-rendus coach visibles. | 400 |
| `cv-outil-lexique-admin.php` | Interface admin pour gerer les termes du lexique visible (add/edit/delete + import CSV). | 404 |
| `cv-outil-lexique.php` | Lexique visible pour therapeutes et coachs. Table wp_cv_lexique_termes, endpoints REST publics (logged-in) et helpers PHP. CRUD admin gere separement. | 372 |
| `cv-outil-mon-profil-visible.php` | Generation de headlines LinkedIn et bios Instagram pour therapeutes/coachs. 7 variantes par session, affinage iteratif, methode TechRappy secrete cote system… | 1137 |
| `cv-outil-site.php` | Formulaire 14 étapes qui alimente la génération automatique de site WordPress/Divi (4 ambiances × 6 pages). Pré-rempli depuis Workbook si dispo. Accès chiffr… | 1960 |
| `cv-outil-workbook.php` | Outil profond de positionnement & offre pour les acheteuses de l'Accompagnement Thérapeute Visible. 6 parties en cascade avec synthèses personnalisées + PDF … | 1230 |
| `cv-page-accueil.php` | Page d'accueil cabinet-visible.fr — promesse challenge + CTAs outils + footer minimal. | 319 |
| `cv-page-admin.php` | Page admin /cv-staff-9d7f4e/ — accès Isabelle + bras droit. | 340 |
| `cv-page-redirects.php` | Redirige les anciennes pages vers leur remplaçant, sans rien supprimer (réversible : supprimer ce fichier annule tout). Les pages et leurs plugins restent in… | 24 |
| `cv-planning-front.php` | Sert la page « À faire » de Marion (cv_taches_admin_page) en plein écran hors dashboard, via le shell front partagé. | 45 |
| `cv-premium1-hebergement.php` | (1) Ajoute à la liste des paiements en attente les clientes Premium 1 hébergées chez nous. | 112 |
| `cv-security.php` | Couche sécurité custom : bloque énumération users REST, brute-force /admin-reveal-codes, | 288 |
| `cv-team-time.php` | Tracking heures Marion (V1 + modifs @ 15€/h) et Virginie (coaching @ 25€/h, admin @ 13€/h). | 702 |
| `cv-template-avant-apres.php` | — | 811 |
| `cv-template-jai-failli.php` | — | 1260 |
| `cv-template-methode-3etapes.php` | — | 1552 |
| `cv-template-mythe-casser.php` | — | 698 |
| `cv-template-style-lettre.php` | — | 594 |
| `cv-template-style-magazine.php` | — | 558 |
| `cv-template-style-manuscrit.php` | — | 648 |
| `cv-template-style-minimal.php` | — | 644 |
| `cv-template-style-organique.php` | — | 696 |
| `cv-template-style-pop.php` | — | 587 |
| `cv-template-style-tendre.php` | — | 575 |
| `cv-test-replays.php` | Page /test-replays/ qui affiche les vidéos de replay sans cache navigateur | 35 |
| `cv-tickets-equipe-front.php` | Sert le SAV / tickets (cv_tk_admin_router) en plein écran hors dashboard, via le shell front partagé. Pour Tiffany et l'équipe. | 45 |
| `cv-tickets-gmail.php` | Point d'entrée REST pour le script Gmail de tri : reçoit un mail classé et crée un ticket (routé Marion/Virginie/Isabelle). Anti-doublon par messageId. + end… | 100 |
| `cv-tickets.php` | Formulaire de demandes par catégorie + SAV depuis la plateforme (Tiffany répond/attribue, Marion exécute) + accusés auto. Shortcode [cv_tickets]. | 1220 |
| `cv-visuel-context.php` | — | 285 |
| `cv-visuel-preview.php` | — | 3302 |
| `cv-webhook.php` | Pipeline auto-provisioning + formulaire wizard 8 étapes pour le challenge Thérapeute Visible. | 2000 |
| `cv-wp-mail-brevo.php` | Route wp_mail() (notifications WP : reset password, new user, etc.) via Brevo SMTP. | 56 |

## ⏰ Automatisations qui tournent toutes seules

**Crons WordPress :**

- `cv_tk_imap_cron` — cv_tk_5min
- `cv_arm_363_check` — daily
- `cv_coach_files_cleanup` — weekly
- `cv_site_purge_credentials` — daily
- `cv_feedback_daily_live_check` — daily
- `cv_arm_nov_mail` — 
- `cv_p1heb_mail` — 
- `cv_aud_heberg_mail` — 

**Crons serveur (crontab) :**

```
*/10 * * * * /root/cv-health-monitor.sh >/dev/null 2>&1
# Rappel echeances pages de vente entreprise (J-5)
0 7 26 11 * /usr/local/bin/wp eval-file /root/cv-echeance-reminder.php --path=/var/www/cabinet-visible --allow-root >> /root/cv-echeance-reminder.log 2>&1
0 7 27 12 * /usr/local/bin/wp eval-file /root/cv-echeance-reminder.php --path=/var/www/cabinet-visible --allow-root >> /root/cv-echeance-reminder.log 2>&1
# Coupure auto pages challenge non-gardees (1er octobre 22h UTC)
0 22 1 10 * /usr/local/bin/wp eval-file /root/cv-challenge-cut.php --path=/var/www/cabinet-visible --allow-root >> /root/cv-challenge-cut.log 2>&1
0 6 * * 1 cd /var/www/cabinet-visible && /usr/local/bin/wp eval-file /root/cv-taches-weekly.php --allow-root >> /var/log/cv-taches-weekly.log 2>&1
0 7 1 * * cd /var/www/cabinet-visible && /usr/local/bin/wp eval-file /root/cv-virginie-monthly.php --allow-root >> /var/log/cv-virginie-monthly.log 2>&1
15 14-19 * * * cd /var/www/sommet-cabinet-visible && /usr/local/bin/wp eval-file /root/ss26-avis-relance.php --allow-root >> /var/log/ss26-avis-relance.log 2>&1
30 17,19 * * * cd /var/www/sommet-cabinet-visible && /usr/local/bin/wp eval-file /root/ss26-interv-relance.php --allow-root >> /var/log/ss26-interv-relance.log 2>&1
0 16 25 9 * /root/ss26-diag-run.sh
0 4 * * * cd /var/www/cabinet-visible && /usr/local/bin/wp --allow-root eval "cv_crmc_sync(48);" >/dev/null 2>&1
0 16 * * * cd /var/www/cabinet-visible && /usr/local/bin/wp --allow-root eval "cv_crmc_sync(48);" >/dev/null 2>&1
0 5 * * * cd /var/www/cabinet-visible && /usr/local/bin/wp --allow-root eval "cv_crmc_dunning_run();" >/dev/null 2>&1
30 6 * * * cd /var/www/cabinet-visible && /usr/local/bin/wp --allow-root eval "cv_crmc_ical_sync();" >/dev/null 2>&1
*/2 * * * * cd /var/www/cabinet-visible && /usr/local/bin/wp eval-file /root/cv-sm-run.php --allow-root >/dev/null 2>&1
```

## 🧠 Skills (méthodes IA, sur la machine d'Isabelle)

- **chaine-parfaite** — YouTube (stratégie, titres, scripts, miniatures, algo 2026)
- **post-parfait** — le post LinkedIn parfait (copywriting + algo)
- **techrappy-content** — voix de marque TechRappy (accroches, pubs, emails)
- **seo-parfait** / **page-parfaite** / **page-comparateur-seo** — SEO, landing/Ads, comparateurs
- **email-parfait** / **carrousel-parfait** / **infographie-parfaite** — emails, carrousels, visuels
- **o2switch-security** — sécurité/virus sur hébergement o2switch

## 🔑 Infra & accès (OÙ sont les choses — pas les secrets)

- **Serveur** : VPS `89.116.110.43`, SSH par clé `~/.ssh/cabinet_visible_ed25519` (pas de mot de passe)
- **Clés API** : fichier `/etc/cv/credentials.env` sur le VPS (Anthropic, OpenAI, Serper, DataForSEO, Stripe, HMAC…)
- **Sites WordPress** : `/var/www/<site>/` — plugins maison dans `wp-content/mu-plugins/`
- **Brique cliente** : zip + update.json sur `cabinet-visible.fr/wp-content/uploads/techrappy/`
- **Base de données** : tables préfixées `wp_cv_*` et `wp_crmc_*` (clientes, articles, tâches, abonnements, échéances…)
- **GitHub** : dépôt `github.com/Techrappy-ou/techrappy-seo-plugin` (ce fichier + `/docs`)

---
_Fin de la carte. Régénère ce fichier après chaque gros changement pour qu'il reste vrai._
