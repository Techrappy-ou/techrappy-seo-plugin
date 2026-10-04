<?php
/**
 * Vue : page de diagnostic du plugin.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Données : API ──────────────────────────────────────────────────────────
$api_key    = \TechrappySEO\Settings\SettingsRepository::get_api_key();
$api_masked = $api_key ? str_repeat( '•', max( 0, strlen( $api_key ) - 6 ) ) . substr( $api_key, -6 ) : '';
$api_ok     = ! empty( $api_key ) && str_starts_with( $api_key, 'sk-' );

// ── Données : Prompts ──────────────────────────────────────────────────────
$required_prompts = [
    'system', 'intent', 'plan', 'blocks_list', 'intro',
    'block_write', 'conclusion_cta', 'meta', 'faq',
    'internal_links', 'anti_duplicate',
];
$repo             = new \TechrappySEO\Prompts\PromptRepository();
$prompt_status    = [];
foreach ( $required_prompts as $key ) {
    $prompt_status[ $key ] = $repo->exists( $key );
}
$prompts_ok    = ! in_array( false, $prompt_status, true );
$prompts_count = count( array_filter( $prompt_status ) );

// ── Données : Base de données ──────────────────────────────────────────────
$db_version_stored   = get_option( 'techrappy_seo_db_version', '0.0.0' );
$db_version_expected = \TechrappySEO\Core\Installer::DB_VERSION;
$db_ok               = ! \TechrappySEO\Core\Installer::needs_upgrade();

// ── Données : Queue ────────────────────────────────────────────────────────
$as_available = function_exists( 'as_schedule_single_action' );

// ── Données : Dernières erreurs ────────────────────────────────────────────
$recent_errors = [];
$all_jobs      = \TechrappySEO\Jobs\JobRepository::list( [ 'limit' => 200 ] );
foreach ( $all_jobs as $job ) {
    if ( ! in_array( $job['status'] ?? '', [ 'failed', 'done_with_errors' ], true ) ) {
        continue;
    }
    if ( empty( $job['parent_job_id'] ) && 'bulk' === $job['mode'] ) {
        continue; // Ignorer les parents bulk, afficher leurs enfants.
    }
    $logs = is_array( $job['logs'] ) ? $job['logs'] : [];
    $last_error = '';
    foreach ( array_reverse( $logs ) as $log ) {
        if ( ( $log['level'] ?? '' ) === 'error' ) {
            $last_error = '[' . ( $log['step'] ?? '' ) . '] ' . ( $log['msg'] ?? '' );
            break;
        }
    }
    $recent_errors[] = [
        'keyword'    => $job['keyword']    ?? '',
        'city'       => $job['city']       ?? '',
        'status'     => $job['status']     ?? '',
        'created_at' => $job['created_at'] ?? '',
        'last_error' => $last_error ?: __( 'Aucune ligne d\'erreur dans les logs.', 'techrappy-seo' ),
    ];
    if ( count( $recent_errors ) >= 10 ) {
        break;
    }
}

$nonce_settings    = wp_create_nonce( 'techrappy_seo_settings' );
$nonce_diagnostic  = wp_create_nonce( 'techrappy_seo_diagnostic' );

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-diagnostic">

    <h2 style="margin-bottom:20px;"><?php esc_html_e( 'Diagnostic', 'techrappy-seo' ); ?></h2>

    <style>
        .diag-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px; }
        .diag-card { background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 20px; }
        .diag-card h3 { margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#666;border-bottom:1px solid #eee;padding-bottom:8px; }
        .diag-row { display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px; }
        .diag-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
        .diag-dot.ok  { background:#22c55e; }
        .diag-dot.err { background:#ef4444; }
        .diag-dot.warn{ background:#f59e0b; }
        .diag-prompt-grid { display:flex;flex-wrap:wrap;gap:6px; }
        .diag-prompt-tag { padding:2px 8px;border-radius:3px;font-size:11px;font-family:monospace; }
        .diag-prompt-tag.ok  { background:#dcfce7;color:#166534; }
        .diag-prompt-tag.err { background:#fee2e2;color:#991b1b; }
        .diag-error-table { width:100%;border-collapse:collapse;font-size:12px; }
        .diag-error-table th { background:#f9fafb;text-align:left;padding:6px 8px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;color:#6b7280; }
        .diag-error-table td { padding:6px 8px;border-bottom:1px solid #f0f0f0;vertical-align:top; }
        .diag-error-table tr:last-child td { border-bottom:none; }
        .diag-error-msg { font-family:monospace;color:#991b1b;word-break:break-all; }
        #diag-api-result { font-size:13px;font-weight:600; }
    </style>

    <div class="diag-grid">

        <?php /* ── Carte : API OpenAI ───────────────────────────────────── */ ?>
        <div class="diag-card">
            <h3><?php esc_html_e( 'API OpenAI', 'techrappy-seo' ); ?></h3>

            <div class="diag-row">
                <span class="diag-dot <?php echo $api_ok ? 'ok' : 'err'; ?>"></span>
                <span>
                    <?php if ( $api_ok ) : ?>
                        <?php esc_html_e( 'Clé configurée :', 'techrappy-seo' ); ?>
                        <code style="margin-left:4px;"><?php echo esc_html( $api_masked ); ?></code>
                    <?php else : ?>
                        <strong style="color:#991b1b;"><?php esc_html_e( 'Clé API non configurée', 'techrappy-seo' ); ?></strong>
                    <?php endif; ?>
                </span>
            </div>

            <div class="diag-row" style="margin-top:6px;flex-wrap:wrap;gap:8px;">
                <button type="button" class="button" id="diag-btn-test-api"
                        data-nonce="<?php echo esc_attr( $nonce_settings ); ?>">
                    <?php esc_html_e( 'Tester la connexion', 'techrappy-seo' ); ?>
                </button>
                <span id="diag-api-result"></span>
            </div>

            <div class="diag-row" style="margin-top:8px;color:#555;">
                <?php
                $model   = \TechrappySEO\Settings\SettingsRepository::get( 'openai_model', 'gpt-4o' );
                $timeout = \TechrappySEO\Settings\SettingsRepository::get( 'openai_timeout', 60 );
                ?>
                <?php esc_html_e( 'Modèle :', 'techrappy-seo' ); ?> <code><?php echo esc_html( $model ); ?></code>
                &nbsp;·&nbsp;
                <?php esc_html_e( 'Timeout :', 'techrappy-seo' ); ?> <?php echo esc_html( $timeout ); ?>s
            </div>

            <?php if ( ! $api_ok ) : ?>
                <div style="margin-top:10px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=techrappy-seo-settings' ) ); ?>"
                       class="button button-primary">
                        ⚙ <?php esc_html_e( 'Configurer la clé API', 'techrappy-seo' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php /* ── Carte : Prompts ──────────────────────────────────────── */ ?>
        <div class="diag-card">
            <h3>
                <?php esc_html_e( 'Prompts IA', 'techrappy-seo' ); ?>
                <span style="font-size:12px;font-weight:normal;margin-left:6px;color:<?php echo $prompts_ok ? '#166534' : '#991b1b'; ?>;">
                    <?php echo esc_html( $prompts_count . '/' . count( $required_prompts ) ); ?>
                </span>
            </h3>

            <div class="diag-prompt-grid">
                <?php foreach ( $prompt_status as $key => $exists ) : ?>
                    <span class="diag-prompt-tag <?php echo $exists ? 'ok' : 'err'; ?>"
                          title="<?php echo $exists ? esc_attr__( 'Présent', 'techrappy-seo' ) : esc_attr__( 'Manquant', 'techrappy-seo' ); ?>">
                        <?php echo $exists ? '✓' : '✗'; ?> <?php echo esc_html( $key ); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <?php if ( ! $prompts_ok ) : ?>
                <div style="margin-top:12px;padding:8px;background:#fee2e2;border-radius:4px;font-size:12px;color:#991b1b;">
                    <?php esc_html_e( 'Des prompts sont manquants. Désactivez et réactivez le plugin pour les régénérer.', 'techrappy-seo' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php /* ── Carte : Base de données ──────────────────────────────── */ ?>
        <div class="diag-card">
            <h3><?php esc_html_e( 'Base de données', 'techrappy-seo' ); ?></h3>

            <div class="diag-row">
                <span class="diag-dot <?php echo $db_ok ? 'ok' : 'err'; ?>"></span>
                <span>
                    <?php esc_html_e( 'Version schéma :', 'techrappy-seo' ); ?>
                    <code><?php echo esc_html( $db_version_stored ); ?></code>
                    <?php if ( ! $db_ok ) : ?>
                        <span style="color:#991b1b;margin-left:4px;">
                            (<?php esc_html_e( 'migration requise vers', 'techrappy-seo' ); ?> <?php echo esc_html( $db_version_expected ); ?>)
                        </span>
                    <?php else : ?>
                        <span style="color:#166534;margin-left:4px;">(<?php esc_html_e( 'à jour', 'techrappy-seo' ); ?>)</span>
                    <?php endif; ?>
                </span>
            </div>

            <?php
            global $wpdb;
            $tables = [
                $wpdb->prefix . 'techrappy_seo_jobs',
                $wpdb->prefix . 'techrappy_prompts',
            ];
            foreach ( $tables as $table ) :
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $exists = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
            ?>
                <div class="diag-row">
                    <span class="diag-dot <?php echo $exists ? 'ok' : 'err'; ?>"></span>
                    <code><?php echo esc_html( $table ); ?></code>
                    <span style="color:<?php echo $exists ? '#166534' : '#991b1b'; ?>;">
                        <?php echo $exists ? esc_html__( 'OK', 'techrappy-seo' ) : esc_html__( 'Absente', 'techrappy-seo' ); ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="diag-row" style="margin-top:10px;flex-wrap:wrap;gap:8px;">
                <button type="button" class="button" id="diag-btn-repair-db"
                        data-nonce="<?php echo esc_attr( $nonce_diagnostic ); ?>">
                    <?php esc_html_e( 'Réparer la BDD', 'techrappy-seo' ); ?>
                </button>
                <span id="diag-repair-result"></span>
            </div>
        </div>

        <?php /* ── Carte : Queue ─────────────────────────────────────────── */ ?>
        <div class="diag-card">
            <h3><?php esc_html_e( 'Système de queue', 'techrappy-seo' ); ?></h3>

            <div class="diag-row">
                <span class="diag-dot <?php echo $as_available ? 'ok' : 'warn'; ?>"></span>
                <span>
                    <?php if ( $as_available ) : ?>
                        <strong><?php esc_html_e( 'Action Scheduler', 'techrappy-seo' ); ?></strong>
                        — <?php esc_html_e( 'disponible', 'techrappy-seo' ); ?>
                    <?php else : ?>
                        <strong><?php esc_html_e( 'WP-Cron', 'techrappy-seo' ); ?></strong>
                        — <?php esc_html_e( 'fallback (Action Scheduler non installé)', 'techrappy-seo' ); ?>
                    <?php endif; ?>
                </span>
            </div>

            <?php
            $cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
            ?>
            <div class="diag-row">
                <span class="diag-dot <?php echo $cron_disabled ? 'err' : 'ok'; ?>"></span>
                <span>
                    WP-Cron :
                    <?php if ( $cron_disabled ) : ?>
                        <strong style="color:#991b1b;"><?php esc_html_e( 'DÉSACTIVÉ (DISABLE_WP_CRON = true)', 'techrappy-seo' ); ?></strong>
                    <?php else : ?>
                        <?php esc_html_e( 'Actif', 'techrappy-seo' ); ?>
                    <?php endif; ?>
                </span>
            </div>

            <?php
            // Nombre de jobs en attente / en cours.
            $pending_count = count( array_filter( $all_jobs, fn( $j ) => in_array( $j['status'] ?? '', [ 'pending', 'running' ], true ) ) );
            ?>
            <div class="diag-row" style="margin-top:6px;color:#555;">
                <?php esc_html_e( 'Jobs en file :', 'techrappy-seo' ); ?>
                <strong><?php echo esc_html( $pending_count ); ?></strong>
            </div>
        </div>

    </div>

    <?php /* ── Section : Dernières erreurs ──────────────────────────────── */ ?>
    <div class="diag-card" style="margin-bottom:24px;">
        <h3>
            <?php esc_html_e( 'Dernières erreurs de génération', 'techrappy-seo' ); ?>
            <span style="font-size:12px;font-weight:normal;margin-left:6px;color:#6b7280;">
                (<?php echo esc_html( count( $recent_errors ) ); ?> <?php esc_html_e( 'jobs en échec affichés', 'techrappy-seo' ); ?>)
            </span>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=techrappy-seo-logs' ) ); ?>"
               style="float:right;font-size:12px;font-weight:normal;">
                <?php esc_html_e( 'Voir tous les logs →', 'techrappy-seo' ); ?>
            </a>
        </h3>

        <?php if ( empty( $recent_errors ) ) : ?>
            <p style="color:#166534;margin:0;">
                ✓ <?php esc_html_e( 'Aucune erreur récente.', 'techrappy-seo' ); ?>
            </p>
        <?php else : ?>
            <table class="diag-error-table">
                <thead>
                    <tr>
                        <th style="width:200px;"><?php esc_html_e( 'Mot-clé', 'techrappy-seo' ); ?></th>
                        <th style="width:120px;"><?php esc_html_e( 'Ville', 'techrappy-seo' ); ?></th>
                        <th style="width:140px;"><?php esc_html_e( 'Date', 'techrappy-seo' ); ?></th>
                        <th><?php esc_html_e( 'Dernière erreur', 'techrappy-seo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_errors as $err ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $err['keyword'] ); ?></strong></td>
                            <td><?php echo esc_html( $err['city'] ?: '—' ); ?></td>
                            <td style="color:#6b7280;font-size:11px;"><?php echo esc_html( $err['created_at'] ); ?></td>
                            <td class="diag-error-msg"><?php echo esc_html( $err['last_error'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<script>
jQuery(function($) {
    $('#diag-btn-repair-db').on('click', function() {
        var $btn    = $(this);
        var $result = $('#diag-repair-result');
        var nonce   = $btn.data('nonce');

        $btn.prop('disabled', true);
        $result.text('<?php echo esc_js( __( 'Réparation en cours…', 'techrappy-seo' ) ); ?>').css('color', '#555');

        $.post(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
            action : 'techrappy_repair_db',
            nonce  : nonce
        }, function(resp) {
            $btn.prop('disabled', false);
            if (resp.success) {
                $result.text(resp.data.message || '').css('color', '#166534');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                $result.text((resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Erreur inconnue.', 'techrappy-seo' ) ); ?>').css('color', '#991b1b');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $result.text('<?php echo esc_js( __( 'Erreur réseau.', 'techrappy-seo' ) ); ?>').css('color', '#991b1b');
        });
    });

    $('#diag-btn-test-api').on('click', function() {
        var $btn    = $(this);
        var $result = $('#diag-api-result');
        var nonce   = $btn.data('nonce');

        $btn.prop('disabled', true);
        $result.text('<?php echo esc_js( __( 'Test en cours…', 'techrappy-seo' ) ); ?>').css('color', '#555');

        $.post(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
            action : 'techrappy_test_api_connection',
            nonce  : nonce
        }, function(resp) {
            $btn.prop('disabled', false);
            if (resp.success) {
                $result.text(resp.data.message || '').css('color', '#166534');
            } else {
                $result.text((resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Erreur inconnue.', 'techrappy-seo' ) ); ?>').css('color', '#991b1b');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $result.text('<?php echo esc_js( __( 'Erreur réseau.', 'techrappy-seo' ) ); ?>').css('color', '#991b1b');
        });
    });
});
</script>

<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
