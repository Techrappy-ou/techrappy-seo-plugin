<?php
/**
 * Vue : Prompt Studio — éditeur principal.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$repo    = new \TechrappySEO\Prompts\PromptRepository();
$prompts = $repo->find_all();

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-prompt-studio">

    <p>
        <button type="button" class="button button-secondary" id="ps-btn-reset">
            <?php esc_html_e( 'Remettre tous les prompts par défaut', 'techrappy-seo' ); ?>
        </button>
        <span class="spinner techrappy-spinner" id="ps-spinner-reset"></span>
    </p>

    <div id="ps-notice"></div>

    <div class="techrappy-prompt-editor">

        <?php /* ── Liste des prompts ──────────────────────────────────────── */ ?>
        <div class="techrappy-prompt-list">
            <?php if ( empty( $prompts ) ) : ?>
                <p style="padding:12px;"><?php esc_html_e( 'Aucun prompt en base. Activez/désactivez le plugin pour seeder les prompts par défaut.', 'techrappy-seo' ); ?></p>
            <?php else : ?>
                <?php foreach ( $prompts as $p ) : ?>
                    <div class="techrappy-prompt-list-item"
                         data-key="<?php echo esc_attr( $p['prompt_key'] ); ?>"
                         data-format="<?php echo esc_attr( $p['response_format'] ); ?>"
                         data-version="<?php echo esc_attr( $p['version'] ?? 1 ); ?>">
                        <strong><?php echo esc_html( $p['prompt_key'] ); ?></strong>
                        <small style="float:right;color:#999;">v<?php echo esc_html( $p['version'] ?? 1 ); ?></small>
                        <br>
                        <small style="color:#777;"><?php echo esc_html( $p['response_format'] ); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php /* ── Éditeur ───────────────────────────────────────────────── */ ?>
        <div id="ps-editor-panel">
            <div id="ps-editor-empty" style="color:#999;padding:20px;">
                <?php esc_html_e( '← Sélectionnez un prompt dans la liste.', 'techrappy-seo' ); ?>
            </div>
            <div id="ps-editor-form" style="display:none;">
                <div style="margin-bottom:8px;">
                    <strong id="ps-current-key"></strong>
                    <span id="ps-current-format" style="margin-left:8px;color:#777;font-size:12px;"></span>
                    <span id="ps-current-version" style="margin-left:8px;color:#999;font-size:12px;"></span>
                </div>
                <textarea id="ps-content" class="techrappy-prompt-textarea"></textarea>
                <div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
                    <button type="button" class="button button-primary" id="ps-btn-save">
                        <?php esc_html_e( 'Sauvegarder', 'techrappy-seo' ); ?>
                    </button>
                    <button type="button" class="button" id="ps-btn-test">
                        <?php esc_html_e( 'Tester ce prompt', 'techrappy-seo' ); ?>
                    </button>
                    <span class="spinner techrappy-spinner" id="ps-spinner"></span>
                </div>

                <?php /* ── Variables connues ────────────────────────────────── */ ?>
                <div id="ps-variables" style="margin-top:16px;">
                    <?php include TECHRAPPY_SEO_VIEWS . 'admin/prompt-studio/variables.php'; ?>
                </div>

                <?php /* ── Panel test ───────────────────────────────────────── */ ?>
                <div id="ps-test-panel" style="display:none;margin-top:16px;border-top:1px solid #ddd;padding-top:16px;">
                    <?php include TECHRAPPY_SEO_VIEWS . 'admin/prompt-studio/tester.php'; ?>
                </div>
            </div>
        </div>

    </div>

</div>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
