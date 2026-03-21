<?php
/**
 * Orchestrateur du pipeline de génération de contenu.
 *
 * @package TechrappySEO\AI\Pipeline
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline;

use TechrappySEO\AI\Pipeline\Steps\StepAntiDuplicate;
use TechrappySEO\AI\Pipeline\Steps\StepBlocksList;
use TechrappySEO\AI\Pipeline\Steps\StepConclusion;
use TechrappySEO\AI\Pipeline\Steps\StepFaq;
use TechrappySEO\AI\Pipeline\Steps\StepIntent;
use TechrappySEO\AI\Pipeline\Steps\StepInternalLinks;
use TechrappySEO\AI\Pipeline\Steps\StepIntro;
use TechrappySEO\AI\Pipeline\Steps\StepMeta;
use TechrappySEO\AI\Pipeline\Steps\StepPlan;
use TechrappySEO\AI\Pipeline\Steps\StepQA;
use TechrappySEO\AI\Pipeline\Steps\StepWriteBlock;
use TechrappySEO\Jobs\JobRepository;
use TechrappySEO\SEO\SlugGenerator;
use TechrappySEO\SEO\YoastIntegration;
use TechrappySEO\Settings\SettingsRepository;
use TechrappySEO\Utils\ContentAssembler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PipelineRunner
 *
 * Orchestre l'exécution séquentielle des 10 étapes du pipeline IA :
 *
 * 1. intent       — analyse intention SEO
 * 2. plan         — plan de contenu (H1, slug, sections)
 * 3. blocks_list  — liste des blocs à rédiger
 * 4. intro        — introduction
 * 5. block_write  — rédaction de chaque bloc (boucle)
 * 6. conclusion   — conclusion + CTA
 * 7. meta         — meta title + meta description
 * 8. faq          — FAQ + JSON-LD
 * 9. internal_links — liens internes (optionnel)
 * 10. anti_duplicate — variantes ville (optionnel, bulk)
 * 11. qa          — contrôle qualité (optionnel, si activé)
 *
 * Puis : assemblage HTML → création post WordPress → Yoast meta.
 */
class PipelineRunner {

    /**
     * Données du job en cours.
     *
     * @var array<string, mixed>
     */
    private array $job;

    /**
     * Logger associé au job.
     *
     * @var \TechrappySEO\Utils\Logger
     */
    private \TechrappySEO\Utils\Logger $logger;

    /**
     * Données d'étapes accumulées en mémoire.
     *
     * @var array<string, mixed>
     */
    private array $steps_data = [];

    /**
     * Constructeur.
     *
     * @param array<string, mixed>       $job    Données du job (depuis JobRepository::find).
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     */
    public function __construct( array $job, \TechrappySEO\Utils\Logger $logger ) {
        $this->job        = $job;
        $this->logger     = $logger;
        $this->steps_data = $job['steps_data'] ?? [];
    }

    /**
     * Exécute le pipeline complet et retourne le résultat.
     *
     * @return array{post_id: int, permalink: string, slug: string}|null Résultat ou null en cas d'échec.
     */
    public function run(): ?array {
        $job_id = $this->job['job_id'];

        $this->logger->info( 'pipeline', 'Pipeline démarré pour le job : ' . $job_id );

        // ── Étapes critiques (abort si échec) ──────────────────────────

        // 1. Intent
        if ( ! $this->run_step( new StepIntent(), 'intent' ) ) {
            return $this->fail( 'Étape "intent" échouée.' );
        }

        // 2. Plan
        if ( ! $this->run_step( new StepPlan(), 'plan' ) ) {
            return $this->fail( 'Étape "plan" échouée.' );
        }

        // 3. Blocks list
        if ( ! $this->run_step( new StepBlocksList(), 'blocks_list' ) ) {
            return $this->fail( 'Étape "blocks_list" échouée.' );
        }

        // ── Étapes de contenu (non-bloquantes) ─────────────────────────

        // 4. Intro
        $this->run_step( new StepIntro(), 'intro' );

        // 5. Rédaction des blocs (boucle)
        $this->run_block_loop();

        // 6. Conclusion + CTA
        $this->run_step( new StepConclusion(), 'conclusion' );

        // 7. Meta SEO
        $this->run_step( new StepMeta(), 'meta' );

        // 8. FAQ
        $this->run_step( new StepFaq(), 'faq' );

        // 9. Liens internes (optionnel)
        $this->run_step( new StepInternalLinks(), 'internal_links' );

        // 10. Anti-duplicate (optionnel — uniquement si ville)
        $this->run_step( new StepAntiDuplicate(), 'anti_duplicate' );

        // ── Assemblage du contenu HTML ──────────────────────────────────

        $assembler   = new ContentAssembler();
        $assembled   = $assembler->assemble( $this->steps_data );
        $full_html   = $assembled['raw_html'];

        // 11. QA (optionnel — si qa_gate_enabled)
        if ( SettingsRepository::get( 'qa_gate_enabled', false ) ) {
            $job_with_html = array_merge( $this->job, [
                'steps_data'    => $this->steps_data,
                '_assembled_html' => $full_html,
            ] );
            $qa_result = ( new StepQA() )->run( $job_with_html, $this->logger );
            if ( ! empty( $qa_result ) ) {
                $this->steps_data['qa'] = $qa_result;
                $this->persist_steps();
            }
        }

        // ── Création du post WordPress ──────────────────────────────────

        $result = $this->create_wp_post( $full_html );

        if ( null === $result ) {
            return $this->fail( 'Impossible de créer le post WordPress.' );
        }

        // Persister le résultat final.
        JobRepository::update_result( $job_id, $result );
        $this->logger->info( 'pipeline', sprintf(
            'Pipeline terminé — post_id: %d — %s',
            $result['post_id'],
            $result['permalink']
        ) );

        return $result;
    }

    // ─────────────────────────────────────────
    // Helpers d'exécution
    // ─────────────────────────────────────────

    /**
     * Exécute une étape unique, stocke le résultat et persiste en DB.
     *
     * @param StepInterface $step     Instance de l'étape.
     * @param string        $step_key Clé sous laquelle stocker le résultat dans steps_data.
     *
     * @return bool True si l'étape a produit un résultat non-vide.
     */
    private function run_step( StepInterface $step, string $step_key ): bool {
        $this->logger->info( 'pipeline', "Démarrage étape : {$step_key}" );

        // Passer les steps_data accumulés dans le job pour que chaque étape
        // puisse lire les résultats des étapes précédentes.
        $job_with_context         = $this->job;
        $job_with_context['steps_data'] = $this->steps_data;

        $result = $step->run( $job_with_context, $this->logger );

        if ( empty( $result ) ) {
            $this->logger->warning( 'pipeline', "Étape {$step_key} n'a rien retourné." );
            return false;
        }

        $this->steps_data[ $step_key ] = $result;
        $this->persist_steps();

        $this->logger->info( 'pipeline', "Étape {$step_key} terminée." );

        return true;
    }

    /**
     * Exécute la boucle de rédaction des blocs de contenu.
     *
     * @return void
     */
    private function run_block_loop(): void {
        $blocks_data = $this->steps_data['blocks_list']['blocs'] ?? [];

        if ( empty( $blocks_data ) ) {
            $this->logger->warning( 'pipeline', 'Aucun bloc à rédiger (blocks_list vide).' );
            return;
        }

        $step           = new StepWriteBlock();
        $written_blocks = [];

        foreach ( $blocks_data as $index => $bloc ) {
            $this->logger->info( 'pipeline', sprintf(
                'Rédaction bloc %d/%d : %s',
                $index + 1,
                count( $blocks_data ),
                $bloc['H2'] ?? '?'
            ) );

            // Injecter le bloc courant dans le job.
            $job_with_bloc                   = $this->job;
            $job_with_bloc['steps_data']     = $this->steps_data;
            $job_with_bloc['_current_bloc']  = $bloc;

            $block_result = $step->run( $job_with_bloc, $this->logger );

            if ( ! empty( $block_result ) ) {
                $written_blocks[] = $block_result;
            } else {
                $this->logger->warning( 'pipeline', "Bloc {$index} vide, ignoré." );
            }
        }

        if ( ! empty( $written_blocks ) ) {
            $this->steps_data['written_blocks'] = $written_blocks;
            $this->persist_steps();
        }
    }

    /**
     * Persiste les steps_data et les logs courants en base.
     *
     * @return void
     */
    private function persist_steps(): void {
        JobRepository::update_steps(
            $this->job['job_id'],
            $this->steps_data,
            $this->logger->get_logs()
        );
    }

    /**
     * Marque le job comme échoué, persiste les logs et retourne null.
     *
     * @param string $reason Message d'erreur.
     *
     * @return null
     */
    private function fail( string $reason ): null {
        $this->logger->error( 'pipeline', 'ÉCHEC PIPELINE : ' . $reason );
        JobRepository::update_status( $this->job['job_id'], 'failed' );
        $this->persist_steps();
        return null;
    }

    // ─────────────────────────────────────────
    // Création du post WordPress
    // ─────────────────────────────────────────

    /**
     * Crée le post WordPress à partir du contenu assemblé.
     *
     * @param string $content_html Contenu HTML complet.
     *
     * @return array{post_id: int, permalink: string, slug: string}|null
     */
    private function create_wp_post( string $content_html ): ?array {
        $plan     = $this->steps_data['plan'] ?? [];
        $meta     = $this->steps_data['meta'] ?? [];
        $wp_params = $this->job['wp_params'] ?? [];

        $h1           = $plan['H1'] ?? $this->job['keyword'] ?? '';
        $slug_suggest = $plan['slug_suggere'] ?? '';
        $slug_rule    = $this->job['slug_rule'] ?? 'from_keyword';

        // Génération du slug.
        $slug_gen = new SlugGenerator();
        $source   = ( 'from_h1' === $slug_rule && ! empty( $h1 ) ) ? $h1 : ( $this->job['keyword'] ?? '' );
        $slug     = ! empty( $slug_suggest )
            ? sanitize_title( $slug_suggest )
            : $slug_gen->generate( $source, $this->job['city'] ?? '' );

        // Type de post.
        $post_type   = ( 'article_seo' === ( $this->job['type'] ?? '' ) ) ? 'post' : 'page';
        $post_status = $this->job['publish_status'] ?? SettingsRepository::get( 'default_publish_status', 'draft' );

        // Catégorie (pour les articles).
        $post_category = [];
        if ( 'post' === $post_type && ! empty( $wp_params['category_id'] ) ) {
            $post_category = [ (int) $wp_params['category_id'] ];
        }

        $post_data = [
            'post_title'    => wp_strip_all_tags( $h1 ),
            'post_content'  => $content_html,
            'post_name'     => $slug,
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_category' => $post_category,
        ];

        // Parent de page (optionnel).
        if ( ! empty( $wp_params['parent_page_id'] ) ) {
            $post_data['post_parent'] = (int) $wp_params['parent_page_id'];
        }

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            $this->logger->error( 'pipeline', 'wp_insert_post échoué : ' . $post_id->get_error_message() );
            return null;
        }

        // ── Yoast SEO meta ──────────────────────────────────────────────
        $yoast = new YoastIntegration();
        $yoast->write_meta( $post_id, [
            'keyphrase'   => $this->job['keyword'] ?? '',
            'title'       => $meta['meta_title_1'] ?? '',
            'description' => $meta['meta_desc_1']  ?? '',
        ] );

        return [
            'post_id'   => $post_id,
            'permalink' => (string) get_permalink( $post_id ),
            'slug'      => $slug,
        ];
    }
}
