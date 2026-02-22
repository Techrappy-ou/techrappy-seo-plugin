<?php
/**
 * Orchestrateur du pipeline de génération de contenu.
 *
 * @package TechrappySEO\AI\Pipeline
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PipelineRunner
 *
 * Responsabilité : orchestrer l'exécution séquentielle des étapes du pipeline.
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
     * Constructeur.
     *
     * @param array<string, mixed>      $job    Données du job.
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     */
    public function __construct( array $job, \TechrappySEO\Utils\Logger $logger ) {
        $this->job    = $job;
        $this->logger = $logger;
    }

    /**
     * Exécute toutes les étapes du pipeline dans l'ordre.
     *
     * @return array<string, mixed> Job mis à jour avec toutes les données de steps.
     */
    public function run(): array {
        $this->logger->info( 'pipeline', 'Pipeline démarré.' );

        // Charger le system prompt une seule fois pour toutes les étapes.
        $manager                     = new \TechrappySEO\AI\PromptManager();
        $system_data                 = $manager->get( 'system' );
        $this->job['_system_prompt'] = $system_data['content'] ?? '';

        // ── 1. Analyse d'intention ────────────────────────────────────────────
        $this->execute_step( 'intent', new Steps\StepIntent() );

        // ── 2. Plan SEO ───────────────────────────────────────────────────────
        $this->execute_step( 'plan', new Steps\StepPlan() );

        // ── 2B. Liste des blocs ───────────────────────────────────────────────
        $this->execute_step( 'blocks_list', new Steps\StepBlocksList() );

        // ── 3. Introduction ───────────────────────────────────────────────────
        $this->execute_step( 'intro', new Steps\StepIntro() );

        // ── 4. Rédaction des blocs (boucle sur chaque bloc) ───────────────────
        $this->execute_block_write_loop();

        // ── 5. Conclusion + CTA ───────────────────────────────────────────────
        $this->execute_step( 'conclusion_cta', new Steps\StepConclusion() );

        // ── 6. Meta title + meta description ─────────────────────────────────
        $this->execute_step( 'meta', new Steps\StepMeta() );

        // ── 7. FAQ ────────────────────────────────────────────────────────────
        $this->execute_step( 'faq', new Steps\StepFaq() );

        // ── 8. Maillage interne ───────────────────────────────────────────────
        $this->execute_step( 'internal_links', new Steps\StepInternalLinks() );

        // ── 9. Anti-duplicate (bulk uniquement) ───────────────────────────────
        if ( 'bulk' === ( $this->job['mode'] ?? '' ) ) {
            $this->execute_step( 'anti_duplicate', new Steps\StepAntiDuplicate() );
        }

        // ── 10. QA (optionnel, gate avant publication) ────────────────────────
        if ( \TechrappySEO\Settings\SettingsRepository::get( 'qa_gate_enabled', false ) ) {
            $this->execute_step( 'qa', new Steps\StepQA() );
        }

        $this->logger->info( 'pipeline', 'Pipeline terminé.' );

        return $this->job;
    }

    /**
     * Retourne le job mis à jour après exécution.
     *
     * @return array<string, mixed>
     */
    public function get_job(): array {
        return $this->job;
    }

    /**
     * Exécute une étape et accumule son résultat dans le job.
     *
     * @param string        $key  Clé de l'étape (ex: 'intent', 'plan').
     * @param StepInterface $step Instance de l'étape.
     *
     * @return array<string, mixed> Données produites par l'étape.
     */
    private function execute_step( string $key, StepInterface $step ): array {
        $this->logger->info( $key, "Démarrage de l'étape." );
        $this->job['steps'][ $key ] = [ 'status' => 'running', 'data' => [] ];

        try {
            $data = $step->run( $this->job, $this->logger );

            if ( empty( $data ) ) {
                $this->job['steps'][ $key ] = [ 'status' => 'error', 'data' => [] ];
                $this->logger->error( $key, 'Étape échouée : données vides retournées.' );
                return [];
            }

            $this->job['steps'][ $key ] = [ 'status' => 'ok', 'data' => $data ];
            $this->logger->info( $key, 'Étape terminée avec succès.' );
            return $data;

        } catch ( \Throwable $e ) {
            $this->job['steps'][ $key ] = [ 'status' => 'error', 'data' => [] ];
            $this->logger->error( $key, 'Exception : ' . $e->getMessage() );
            return [];
        }
    }

    /**
     * Exécute StepWriteBlock pour chaque bloc de la liste.
     *
     * @return void
     */
    private function execute_block_write_loop(): void {
        $blocs = $this->job['steps']['blocks_list']['data']['blocs'] ?? [];

        if ( empty( $blocs ) ) {
            $this->logger->warning( 'block_write', 'Aucun bloc à rédiger (blocks_list vide ou en erreur).' );
            $this->job['steps']['blocks'] = [ 'status' => 'ok', 'data' => [] ];
            return;
        }

        $step           = new Steps\StepWriteBlock();
        $written_blocks = [];
        $total          = count( $blocs );

        foreach ( $blocs as $index => $bloc ) {
            $n = $index + 1;
            $this->logger->info( 'block_write', "Rédaction bloc {$n}/{$total} : " . ( $bloc['H2'] ?? '' ) );

            // Injecter le bloc courant pour que StepWriteBlock y ait accès.
            $this->job['_current_block'] = $bloc;
            $data = $step->run( $this->job, $this->logger );

            if ( ! empty( $data ) ) {
                $written_blocks[] = $data;
            } else {
                $this->logger->error( 'block_write', "Échec du bloc {$n}." );
            }
        }

        // Nettoyer la clé temporaire.
        unset( $this->job['_current_block'] );

        $this->job['steps']['blocks'] = [
            'status' => ! empty( $written_blocks ) ? 'ok' : 'error',
            'data'   => $written_blocks,
        ];

        $this->logger->info( 'block_write', count( $written_blocks ) . "/{$total} blocs rédigés avec succès." );
    }
}
