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

Objectif : proposer un plan SEO complet, hiérarchisé, supérieur à la SERP.

Règles H1 (OBLIGATOIRES) :
- Si {{city}} est fourni : H1 = EXACTEMENT "{{mot_cle}} {{city}}" — pas de reformulation, pas de créativité.
- Si aucune ville : propose un H1 accrocheur SEO (60 car. max).

Autres contraintes :
- H2 = sujets indispensables, pas de titres vagues.
- Inclure une FAQ (5 questions).
- Prévoir un emplacement CTA.
- Si page locale : inclure un bloc "spécificités locales" sans inventer de lieux précis.
- Fournir un slug suggéré SEO-friendly (dérivé du H1).

FORMAT (JSON strict) :
{
  "H1":"",
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
- 180 à 260 mots
- phrases courtes, une idée par paragraphe
- intégrer keywords naturellement
- terminer par une transition
- ne pas inventer de faits locaux précis

FORMAT (JSON strict) :
{
  "H2":"",
  "html":"<p>...</p>",
  "micro_transition":""
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
