<?php
/**
 * Prompts par défaut — seedés en base à l'activation du plugin.
 *
 * @package TechrappySEO\Prompts
 */

declare( strict_types=1 );

namespace TechrappySEO\Prompts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DefaultPrompts
 */
class DefaultPrompts {

    /**
     * Insère les prompts par défaut en base si absents.
     * Appelé à l'activation du plugin via Activator.
     *
     * @return void
     */
    public static function seed(): void {
        $repository = new PromptRepository();

        foreach ( self::get_defaults() as $key => $data ) {
            // Ne pas écraser un prompt existant.
            if ( $repository->exists( $key ) ) {
                continue;
            }
            $repository->insert( $key, $data['content'], $data['response_format'] ?? 'json_object' );
        }
    }

    /**
     * Synchronise les prompts par défaut en base.
     * - Insère les prompts absents.
     * - Met à jour les prompts jamais modifiés par l'utilisateur (version = 1).
     *
     * Appelé lors des mises à jour du plugin pour propager les corrections de prompts.
     *
     * @return void
     */
    public static function sync(): void {
        $repository = new PromptRepository();

        foreach ( self::get_defaults() as $key => $data ) {
            if ( ! $repository->exists( $key ) ) {
                $repository->insert( $key, $data['content'], $data['response_format'] ?? 'json_object' );
            } else {
                $repository->update_if_default( $key, $data['content'] );
            }
        }
    }

    /**
     * Retourne le tableau de tous les prompts par défaut.
     *
     * @return array<string, array{content: string, response_format: string}>
     */
    public static function get_defaults(): array {
        return [
            'system' => [
                'response_format' => 'text',
                'content'         => <<<'PROMPT'
Règles absolues :
- Ne fabrique jamais de faits locaux précis (rues, lieux, chiffres) si non fournis.
- Style : clair, humain, professionnel, accessible.
- Pas de blabla "en tant qu'IA".
- Respect strict du FORMAT demandé (JSON si demandé).
- Évite le contenu générique : chaque section doit apporter une info concrète.
- Français uniquement.
PROMPT,
            ],
            'intent' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Tu es expert SEO depuis 15 ans, spécialiste de la rédaction web pour les sites de {{profession}}.

Mot-clé principal : {{mot_cle}}
Type de contenu : {{type_contenu}}

Mission :
1) Déduis l'intention principale et secondaires.
2) Liste les "must-have topics" (10 max) qui dominent la SERP.
3) Donne 10 questions PAA probables (formulation naturelle).
4) Donne 15 mots-clés secondaires/variantes.
5) Donne le ton recommandé + risques SEO à éviter.

FORMAT (JSON strict) :
{
  "intent_principale":"",
  "intent_secondaires":[],
  "types_contenus_dominants":[],
  "must_have_topics":[],
  "paa_questions":[],
  "keywords_secondaires":[],
  "ton_recommande":"",
  "risques_a_eviter":[]
}
PROMPT,
            ],
            'plan' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Tu es expert SEO et rédacteur web depuis 15 ans pour {{profession}}.

Mot-clé : {{mot_cle}}
Ville : {{city}}
Analyse d'intention : {{intent_json}}

Le H1 est imposé automatiquement par le système (= mot-clé exact).
Recopie-le tel quel dans "H1" sans reformulation.

Objectif : proposer un plan H2/H3 SEO complet, hiérarchisé, supérieur à la SERP.

Contraintes :
- H2 = sujets indispensables à la thématique, pas de titres vagues.
- H3 = sous-angles concrets, 2 à 4 par H2.
- Inclure une FAQ (5 questions PAA pertinentes).
- Prévoir un emplacement CTA.
- Si page locale : inclure un bloc "spécificités locales" sans inventer de lieux précis.
- slug_suggere : SEO-friendly, basé sur le mot-clé + ville si fournie.

FORMAT (JSON strict) :
{
  "H1":"{{mot_cle}}",
  "slug_suggere":"",
  "sections":[{"H2":"","intention":"","type_contenu_attendu":"","keywords_a_integrer":[],"H3":[]}],
  "cta_placement":"",
  "faq_seed_questions":[]
}
PROMPT,
            ],
            'blocks_list' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
À partir de ce plan JSON, produis :
- nb_blocs_repetables : nombre de sections "contenu" à écrire (exclure FAQ/CTA)
- blocs : liste ordonnée des blocs à rédiger

Plan : {{plan_json}}

FORMAT (JSON strict) :
{
  "nb_blocs_repetables": 0,
  "blocs":[{"ordre":1,"H2":"","H3":[],"intention":"","keywords":[],"type_contenu":""}]
}
PROMPT,
            ],
            'intro' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Plan : {{plan_json}}

Rédige une intro 120-180 mots :
- mot-clé dans les 2 premières phrases
- accroche + rassurance + promesse réaliste
- transition vers le 1er H2

FORMAT (JSON strict) :
{
  "intro_longue_html":"<p>...</p>",
  "intro_courte_mobile":"..."
}
PROMPT,
            ],
            'block_write' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Contexte : site de {{profession}}
Mot-clé : {{mot_cle}}
Bloc à rédiger : {{bloc_json}}

Contraintes :
- 180 à 260 mots au total
- Recopier EXACTEMENT le champ "H2" du bloc dans le champ "H2" de la réponse (texte brut, sans balises HTML) — ce champ est obligatoire
- Le champ "html" ne doit JAMAIS contenir le H2 — uniquement le corps du bloc (h3 + paragraphes)
- Si le champ "H3" du bloc contient des sous-titres, utilise-les comme balises <h3> dans le HTML, dans l'ordre indiqué
- Rédige 2 à 3 phrases courtes sous chaque <h3>
- Si "H3" est vide, rédige des <p> structurés sans sous-titres
- Intégrer les keywords naturellement
- Terminer par une micro-transition (1 phrase, pas de point final)
- Ne pas inventer de faits locaux précis

FORMAT (JSON strict) :
{
  "H2":"Titre exact du bloc (obligatoire, texte brut sans balises)",
  "html":"<h3>...</h3><p>...</p><h3>...</h3><p>...</p>",
  "micro_transition":"Phrase de transition sans point final"
}
PROMPT,
            ],
            'conclusion_cta' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Plan : {{plan_json}}

Donne :
- 3 titres H2 de fin (pas "Conclusion")
- 2 variantes de conclusion 150-200 mots (douce / pro)
- 1 CTA simple

FORMAT (JSON strict) :
{
  "h2_fin_suggestions":[],
  "conclusion_douce_html":"<p>...</p>",
  "conclusion_pro_html":"<p>...</p>",
  "cta_html":"<p>...</p>"
}
PROMPT,
            ],
            'meta' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
H1 : {{H1}}
Intention principale : {{intent_principale}}

Contraintes :
- meta title 55-65 caractères
- meta desc 140-160 caractères
- 2 variantes

FORMAT (JSON strict) :
{
  "meta_title_1":"",
  "meta_title_2":"",
  "meta_desc_1":"",
  "meta_desc_2":""
}
PROMPT,
            ],
            'faq' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
Plan : {{plan_json}}

Génère 5 Q/R réellement pertinentes (PAA-like).
Réponses 45-75 mots, concrètes, rassurantes, sans promesse médicale.

FORMAT (JSON strict) :
{
  "faq_visible_html":"<section class='techrappy-faq'>...</section>",
  "faq_jsonld":"<script type='application/ld+json'>{...}<\/script>"
}
PROMPT,
            ],
            'internal_links' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
Pages existantes (Titre + URL) : {{pages_site_json}}

Propose 5 liens max, pertinents UX+SEO.

FORMAT (JSON strict) :
[{"url":"","anchor":"","placement":"","why":""}]
PROMPT,
            ],
            'anti_duplicate' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé base : {{keyword_base}}
Ville : {{city}}

Produis :
- 3 angles d'intro (A/B/C)
- 1 intro finale unique 140-180 mots
- 6 phrases "variantes locales" génériques (sans inventer de lieux précis)

FORMAT (JSON strict) :
{
  "angles":[],
  "intro_finale_html":"<p>...</p>",
  "variantes_locales":[]
}
PROMPT,
            ],
            'qa' => [
                'response_format' => 'json_object',
                'content'         => <<<'PROMPT'
Mot-clé : {{mot_cle}}
Contenu HTML : {{full_content_html}}

Retourne un diagnostic SEO/humain + corrections.

FORMAT (JSON strict) :
{
  "score_seo":0,
  "score_humain":0,
  "problemes":[],
  "fixes_rapides":[],
  "rewrite_intro_suggeree_html":"<p>...</p>"
}
PROMPT,
            ],
        ];
    }
}
