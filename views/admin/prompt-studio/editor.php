<?php
/**
 * Vue : Prompt Studio — layout cartes v3.
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

// ── Construire l'index des prompts ─────────────────────────────────────────
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

    <?php /* ── Topbar ──────────────────────────────────────────────────────── */ ?>
    <div class="ps-topbar">
        <div class="ps-topbar-info">
            <span class="ps-topbar-count"><?php echo count( $prompts_index ); ?> prompts</span>
            <span class="ps-topbar-hint"><?php esc_html_e( 'Modifiez et sauvegardez chaque encart indépendamment — Ctrl+S dans un textarea pour sauvegarder', 'techrappy-seo' ); ?></span>
        </div>
        <div class="ps-topbar-actions">
            <button type="button" class="button" id="ps-btn-reset">
                ↺ <?php esc_html_e( 'Remettre TOUS par défaut', 'techrappy-seo' ); ?>
            </button>
            <span class="spinner techrappy-spinner" id="ps-spinner-reset"></span>
        </div>
    </div>

    <div id="ps-notice"></div>

    <?php if ( empty( $prompts_index ) ) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e( 'Aucun prompt en base. Désactivez puis réactivez le plugin.', 'techrappy-seo' ); ?></p></div>
    <?php else : ?>

    <div class="ps-cards-wrap">

        <?php foreach ( $prompts_index as $key => $data ) :
            $is_json    = 'json_object' === $data['format'];
            $fmt_class  = $is_json ? 'ps-fmt-json' : 'ps-fmt-txt';
            $fmt_label  = $is_json ? 'JSON' : 'TEXT';
            $textarea_id = 'ps-textarea-' . esc_attr( $key );
        ?>

        <div class="ps-card" id="ps-card-<?php echo esc_attr( $key ); ?>">

            <?php /* ── En-tête de la carte ──────────────────────────────────── */ ?>
            <div class="ps-card-head">
                <div class="ps-card-head-row">
                    <?php if ( null !== $data['step'] ) : ?>
                        <span class="ps-step-badge"><?php echo esc_html( (string) $data['step'] ); ?></span>
                    <?php else : ?>
                        <span class="ps-step-badge ps-step-sys">S</span>
                    <?php endif; ?>
                    <h3 class="ps-card-title"><?php echo esc_html( $data['label'] ); ?></h3>
                    <span class="ps-fmt-badge <?php echo $fmt_class; ?>"><?php echo $fmt_label; ?></span>
                    <span class="ps-version-tag ps-card-version">v<?php echo esc_html( (string) $data['version'] ); ?></span>
                </div>
                <p class="ps-card-desc"><?php echo esc_html( $data['desc'] ); ?></p>
            </div>

            <?php /* ── Barre variables ───────────────────────────────────────── */ ?>
            <?php if ( ! empty( $data['vars'] ) ) : ?>
            <div class="ps-vars-bar">
                <span class="ps-vars-label"><?php esc_html_e( 'Variables :', 'techrappy-seo' ); ?></span>
                <div class="ps-vars-chips">
                    <?php foreach ( $data['vars'] as $var ) : ?>
                        <span class="ps-var-chip"
                              data-var="<?php echo esc_attr( $var ); ?>"
                              data-target="<?php echo esc_attr( $textarea_id ); ?>">
                            {{<?php echo esc_html( $var ); ?>}}
                        </span>
                    <?php endforeach; ?>
                </div>
                <span class="ps-vars-hint"><?php esc_html_e( 'clic = insérer au curseur', 'techrappy-seo' ); ?></span>
            </div>
            <?php endif; ?>

            <?php /* ── Textarea du prompt ──────────────────────────────────── */ ?>
            <textarea
                id="<?php echo esc_attr( $textarea_id ); ?>"
                class="ps-textarea"
                data-key="<?php echo esc_attr( $key ); ?>"
                spellcheck="false"
            ><?php echo esc_textarea( $data['content'] ); ?></textarea>

            <?php /* ── Footer de la carte ──────────────────────────────────── */ ?>
            <div class="ps-card-footer">
                <div class="ps-card-footer-left">
                    <button type="button" class="button button-primary ps-btn-save" data-key="<?php echo esc_attr( $key ); ?>">
                        <?php esc_html_e( 'Sauvegarder', 'techrappy-seo' ); ?>
                    </button>
                    <span class="spinner techrappy-spinner ps-spinner-save"></span>

                    <button type="button" class="button ps-btn-reset-one"
                            data-key="<?php echo esc_attr( $key ); ?>"
                            title="<?php esc_attr_e( 'Remet ce prompt à sa valeur par défaut', 'techrappy-seo' ); ?>">
                        ↺ <?php esc_html_e( 'Défaut', 'techrappy-seo' ); ?>
                    </button>

                    <div class="ps-toolbar-sep"></div>

                    <button type="button" class="button ps-btn-test-toggle" data-key="<?php echo esc_attr( $key ); ?>">
                        ⚡ <?php esc_html_e( 'Tester', 'techrappy-seo' ); ?>
                    </button>
                </div>
                <div class="ps-card-footer-right">
                    <span class="ps-card-notice" style="display:none;"></span>
                    <span class="ps-char-count">—</span>
                </div>
            </div>

            <?php /* ── Panneau de test (masqué par défaut) ───────────────────── */ ?>
            <div class="ps-test-panel" style="display:none;">
                <div class="ps-test-inner">
                    <p class="ps-test-hint">
                        <?php esc_html_e( 'Renseignez les variables pour tester ce prompt en direct via OpenAI.', 'techrappy-seo' ); ?>
                    </p>

                    <?php if ( ! empty( $data['vars'] ) ) : ?>
                    <div class="ps-test-vars-grid">
                        <?php foreach ( $data['vars'] as $var ) : ?>
                        <div class="ps-test-var-wrap">
                            <label>{{<?php echo esc_html( $var ); ?>}}</label>
                            <input type="text"
                                   class="ps-test-var-input"
                                   data-var="<?php echo esc_attr( $var ); ?>"
                                   placeholder="<?php echo esc_attr( $var ); ?>…">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else : ?>
                    <p style="font-size:12px;color:#6b7280;margin:0 0 14px;">
                        <?php esc_html_e( 'Ce prompt n\'a pas de variables à renseigner.', 'techrappy-seo' ); ?>
                    </p>
                    <?php endif; ?>

                    <div style="display:flex;gap:8px;align-items:center;margin-top:14px;">
                        <button type="button" class="button button-primary ps-btn-run-test"
                                data-key="<?php echo esc_attr( $key ); ?>">
                            <?php esc_html_e( 'Lancer le test', 'techrappy-seo' ); ?>
                        </button>
                        <span class="spinner techrappy-spinner ps-spinner-run-test"></span>
                    </div>

                    <div class="ps-test-output" style="display:none;margin-top:14px;">
                        <div class="ps-test-duration"></div>
                        <pre class="ps-test-result"></pre>
                    </div>
                </div>
            </div>

        </div><!-- .ps-card -->

        <?php endforeach; ?>

    </div><!-- .ps-cards-wrap -->

    <?php endif; ?>

</div><!-- #techrappy-prompt-studio -->
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
