<?php
/**
 * Vue : Prompt Studio — éditeur principal v2.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$repo    = new \TechrappySEO\Prompts\PromptRepository();
$prompts = $repo->find_all();

// ── Métadonnées de chaque prompt ───────────────────────────────────────────
$prompt_meta = [
    'system'         => [ 'label' => 'Prompt Système',    'desc' => 'Instructions de base envoyées à chaque appel IA',            'step' => null ],
    'intent'         => [ 'label' => 'Intention SEO',     'desc' => 'Analyse l\'intention et les topics SERP du mot-clé',         'step' => 1    ],
    'plan'           => [ 'label' => 'Plan de page',      'desc' => 'Génère le H1, les sections H2/H3 et le slug',                'step' => 2    ],
    'blocks_list'    => [ 'label' => 'Liste des blocs',   'desc' => 'Extrait la liste ordonnée des blocs à rédiger',              'step' => 3    ],
    'intro'          => [ 'label' => 'Introduction',      'desc' => 'Rédige l\'introduction de la page (120–180 mots)',           'step' => 4    ],
    'block_write'    => [ 'label' => 'Rédaction de bloc', 'desc' => 'Rédige chaque section H2 — appelé une fois par bloc',       'step' => 5    ],
    'conclusion_cta' => [ 'label' => 'Conclusion & CTA',  'desc' => 'Génère la conclusion et l\'appel à l\'action',              'step' => 6    ],
    'meta'           => [ 'label' => 'Balises Meta',      'desc' => 'Title SEO et meta description (2 variantes chacun)',         'step' => 7    ],
    'faq'            => [ 'label' => 'FAQ',               'desc' => '5 questions/réponses + schema JSON-LD',                     'step' => 8    ],
    'internal_links' => [ 'label' => 'Liens internes',   'desc' => 'Suggère des liens vers vos pages WordPress existantes',      'step' => 9    ],
    'anti_duplicate' => [ 'label' => 'Anti-duplication', 'desc' => 'Intro unique par ville — utilisé uniquement en mode bulk',   'step' => 10   ],
    'qa'             => [ 'label' => 'Contrôle qualité', 'desc' => 'Score SEO/humain et corrections du contenu généré',          'step' => 11   ],
];

// ── Variables disponibles par prompt ──────────────────────────────────────
$vars_map = [
    'system'         => [],
    'intent'         => [ 'mot_cle', 'profession', 'type_contenu' ],
    'plan'           => [ 'mot_cle', 'profession', 'intent_json', 'city' ],
    'blocks_list'    => [ 'plan_json' ],
    'intro'          => [ 'mot_cle', 'H1', 'plan_json' ],
    'block_write'    => [ 'mot_cle', 'profession', 'bloc_json' ],
    'conclusion_cta' => [ 'mot_cle', 'H1', 'plan_json' ],
    'meta'           => [ 'mot_cle', 'H1', 'intent_principale' ],
    'faq'            => [ 'mot_cle', 'plan_json' ],
    'internal_links' => [ 'mot_cle', 'pages_site_json' ],
    'anti_duplicate' => [ 'keyword_base', 'city' ],
    'qa'             => [ 'mot_cle', 'full_content_html' ],
];

// ── Index des prompts pour le JS ───────────────────────────────────────────
$prompts_index = [];
foreach ( $prompts as $p ) {
    $key  = $p['prompt_key'];
    $meta = $prompt_meta[ $key ] ?? [ 'label' => $key, 'desc' => '', 'step' => null ];
    $prompts_index[ $key ] = [
        'label'   => $meta['label'],
        'desc'    => $meta['desc'],
        'step'    => $meta['step'],
        'content' => $p['content'] ?? '',
        'format'  => $p['response_format'] ?? 'json_object',
        'version' => (int) ( $p['version'] ?? 1 ),
        'vars'    => $vars_map[ $key ] ?? [],
    ];
}

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-prompt-studio">

    <div class="ps-topbar">
        <button type="button" class="button" id="ps-btn-reset">
            ↺ <?php esc_html_e( 'Remettre TOUS les prompts par défaut', 'techrappy-seo' ); ?>
        </button>
        <span class="spinner techrappy-spinner" id="ps-spinner-reset"></span>
    </div>

    <div id="ps-notice"></div>

    <div class="ps-layout">

        <?php /* ── Sidebar ─────────────────────────────────────────────── */ ?>
        <div class="ps-sidebar">
            <?php if ( empty( $prompts ) ) : ?>
                <p style="padding:16px;color:#777;"><?php esc_html_e( 'Aucun prompt en base. Désactivez puis réactivez le plugin.', 'techrappy-seo' ); ?></p>
            <?php else : ?>
                <?php foreach ( $prompts_index as $key => $data ) : ?>
                    <div class="ps-item ps-prompt-item"
                         data-key="<?php echo esc_attr( $key ); ?>"
                         data-label="<?php echo esc_attr( $data['label'] ); ?>"
                         data-desc="<?php echo esc_attr( $data['desc'] ); ?>"
                         data-content="<?php echo esc_attr( $data['content'] ); ?>"
                         data-format="<?php echo esc_attr( $data['format'] ); ?>"
                         data-version="<?php echo esc_attr( (string) $data['version'] ); ?>"
                         data-vars="<?php echo esc_attr( wp_json_encode( $data['vars'] ) ); ?>">
                        <div class="ps-item-header">
                            <?php if ( null !== $data['step'] ) : ?>
                                <span class="ps-step-badge"><?php echo esc_html( (string) $data['step'] ); ?></span>
                            <?php else : ?>
                                <span class="ps-step-badge ps-step-sys">S</span>
                            <?php endif; ?>
                            <span class="ps-item-label"><?php echo esc_html( $data['label'] ); ?></span>
                            <span class="ps-fmt-badge ps-fmt-<?php echo 'json_object' === $data['format'] ? 'json' : 'txt'; ?>">
                                <?php echo 'json_object' === $data['format'] ? 'JSON' : 'TEXT'; ?>
                            </span>
                        </div>
                        <div class="ps-item-desc"><?php echo esc_html( $data['desc'] ); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php /* ── Panneau éditeur ──────────────────────────────────────── */ ?>
        <div class="ps-editor-panel" id="ps-editor-panel">

            <div id="ps-editor-empty">
                <span class="dashicons dashicons-edit" style="font-size:32px;height:32px;width:32px;color:#ccc;"></span>
                <p><?php esc_html_e( 'Sélectionnez un prompt dans la liste pour l\'éditer.', 'techrappy-seo' ); ?></p>
            </div>

            <div id="ps-editor" style="display:none;">

                <?php /* ── En-tête de l'éditeur ─────────────────────────── */ ?>
                <div class="ps-editor-head">
                    <div class="ps-editor-head-left">
                        <div class="ps-editor-title" id="ps-editor-title"></div>
                        <div class="ps-editor-desc-text" id="ps-editor-desc"></div>
                    </div>
                    <div class="ps-editor-head-right">
                        <span class="ps-fmt-badge" id="ps-current-format"></span>
                        <span class="ps-version-tag" id="ps-current-version"></span>
                    </div>
                </div>

                <?php /* ── Variables cliquables ──────────────────────────── */ ?>
                <div class="ps-vars-bar" id="ps-vars-bar" style="display:none;">
                    <span class="ps-vars-label"><?php esc_html_e( 'Variables :', 'techrappy-seo' ); ?></span>
                    <div class="ps-vars-chips" id="ps-vars-chips"></div>
                    <span class="ps-vars-hint"><?php esc_html_e( 'clic = insérer au curseur', 'techrappy-seo' ); ?></span>
                </div>

                <?php /* ── Textarea ─────────────────────────────────────── */ ?>
                <input type="hidden" id="ps-prompt-key" name="ps-prompt-key">
                <textarea id="ps-prompt-content" class="ps-textarea" spellcheck="false"></textarea>

                <?php /* ── Barre d'outils ───────────────────────────────── */ ?>
                <div class="ps-toolbar">
                    <button type="button" class="button button-primary" id="ps-btn-save">
                        <?php esc_html_e( 'Sauvegarder', 'techrappy-seo' ); ?>
                    </button>
                    <span class="spinner techrappy-spinner" id="ps-spinner-save"></span>

                    <button type="button" class="button" id="ps-btn-reset-one" title="Remet ce prompt à sa valeur par défaut">
                        <?php esc_html_e( 'Défaut', 'techrappy-seo' ); ?>
                    </button>

                    <div class="ps-toolbar-sep"></div>

                    <button type="button" class="button" id="ps-btn-test">
                        <?php esc_html_e( 'Tester avec l\'API', 'techrappy-seo' ); ?>
                    </button>
                    <span class="spinner techrappy-spinner" id="ps-spinner-test"></span>

                    <span class="ps-char-count" id="ps-char-count"></span>
                </div>

                <?php /* ── Panneau test ─────────────────────────────────── */ ?>
                <div id="ps-test-panel" style="display:none;">
                    <div class="ps-test-inner">
                        <p class="ps-test-hint"><?php esc_html_e( 'Renseignez les variables pour tester ce prompt en direct via OpenAI.', 'techrappy-seo' ); ?></p>
                        <div class="ps-test-vars-grid" id="ps-test-vars-grid"></div>
                        <div style="display:flex;gap:8px;align-items:center;margin-top:12px;">
                            <button type="button" class="button button-primary" id="ps-btn-run-test">
                                <?php esc_html_e( 'Lancer le test', 'techrappy-seo' ); ?>
                            </button>
                            <span class="spinner techrappy-spinner" id="ps-spinner-run-test"></span>
                        </div>
                        <div id="ps-test-output" style="display:none;margin-top:12px;">
                            <div class="ps-test-meta" id="ps-test-duration"></div>
                            <pre id="ps-test-result" class="ps-test-result"></pre>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- .ps-layout -->

</div><!-- #techrappy-prompt-studio -->
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
