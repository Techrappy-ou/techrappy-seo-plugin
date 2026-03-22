<?php
/**
 * Vue : Panel de logs de génération.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$jobs = \TechrappySEO\Jobs\JobRepository::list( [ 'limit' => 100 ] );
$nonce = wp_create_nonce( 'techrappy_seo_logs' );

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';

$status_labels = [
    'pending'          => [ __( 'En attente',  'techrappy-seo' ), 'secondary' ],
    'running'          => [ __( 'En cours',    'techrappy-seo' ), 'warning'   ],
    'paused'           => [ __( 'En pause',    'techrappy-seo' ), 'secondary' ],
    'done'             => [ __( 'Terminé',     'techrappy-seo' ), 'success'   ],
    'done_with_errors' => [ __( 'Partiel',     'techrappy-seo' ), 'warning'   ],
    'failed'           => [ __( 'Échec',       'techrappy-seo' ), 'error'     ],
];

$level_colors = [
    'info'    => '#17a2b8',
    'warning' => '#ffc107',
    'error'   => '#dc3545',
    'debug'   => '#6c757d',
];
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-logs">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h2 style="margin:0;"><?php esc_html_e( 'Logs de génération', 'techrappy-seo' ); ?></h2>
        <button type="button" class="button" id="logs-btn-clear"
                data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <?php esc_html_e( 'Purger les jobs terminés/échoués', 'techrappy-seo' ); ?>
        </button>
    </div>

    <div id="logs-notice"></div>

    <?php if ( empty( $jobs ) ) : ?>
        <p><?php esc_html_e( 'Aucun job trouvé.', 'techrappy-seo' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped" id="logs-table">
            <thead>
                <tr>
                    <th style="width:200px;"><?php esc_html_e( 'Mot-clé', 'techrappy-seo' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Mode', 'techrappy-seo' ); ?></th>
                    <th style="width:100px;"><?php esc_html_e( 'Statut', 'techrappy-seo' ); ?></th>
                    <th style="width:60px;"><?php esc_html_e( 'Logs', 'techrappy-seo' ); ?></th>
                    <th style="width:140px;"><?php esc_html_e( 'Créé le', 'techrappy-seo' ); ?></th>
                    <th style="width:200px;"><?php esc_html_e( 'Actions', 'techrappy-seo' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $jobs as $job ) :
                    $status      = $job['status'] ?? 'pending';
                    $label_info  = $status_labels[ $status ] ?? [ $status, 'secondary' ];
                    $logs        = is_array( $job['logs'] ) ? $job['logs'] : [];
                    $error_count = count( array_filter( $logs, fn( $l ) => ( $l['level'] ?? '' ) === 'error' ) );
                    $is_bulk_parent = 'bulk' === $job['mode'] && empty( $job['parent_job_id'] );
                ?>
                <tr data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                    data-status="<?php echo esc_attr( $status ); ?>">
                    <td>
                        <strong><?php echo esc_html( $job['keyword'] ); ?></strong>
                        <?php if ( $job['city'] ) : ?>
                            <br><small><?php echo esc_html( $job['city'] ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( ucfirst( $job['mode'] ) ); ?></td>
                    <td>
                        <span class="logs-status logs-status-<?php echo esc_attr( $label_info[1] ); ?>">
                            <?php echo esc_html( $label_info[0] ); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo esc_html( count( $logs ) ); ?>
                        <?php if ( $error_count > 0 ) : ?>
                            <br><span style="color:#dc3545;font-size:11px;">
                                <?php echo esc_html( $error_count . ' erreur(s)' ); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( $job['created_at'] ?? '—' ); ?></td>
                    <td style="white-space:nowrap;">
                        <?php /* Bouton Détail */ ?>
                        <button type="button" class="button button-small logs-btn-detail"
                                data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                data-nonce="<?php echo esc_attr( $nonce ); ?>">
                            <?php esc_html_e( 'Voir', 'techrappy-seo' ); ?>
                        </button>

                        <?php /* Réactualiser (reprendre) — running, failed, done_with_errors, paused */ ?>
                        <?php if ( in_array( $status, [ 'running', 'failed', 'done_with_errors', 'paused' ], true ) ) : ?>
                            <button type="button" class="button button-small logs-btn-resume"
                                    data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                    title="<?php esc_attr_e( 'Reprendre depuis le dernier point de succès', 'techrappy-seo' ); ?>">
                                ↻ <?php esc_html_e( 'Reprendre', 'techrappy-seo' ); ?>
                            </button>
                        <?php endif; ?>

                        <?php /* Pause — pending ou running */ ?>
                        <?php if ( in_array( $status, [ 'pending', 'running' ], true ) ) : ?>
                            <button type="button" class="button button-small logs-btn-pause"
                                    data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                    title="<?php esc_attr_e( 'Mettre en pause', 'techrappy-seo' ); ?>">
                                ⏸ <?php esc_html_e( 'Pause', 'techrappy-seo' ); ?>
                            </button>
                        <?php endif; ?>

                        <?php /* Prioriser — pending ou paused */ ?>
                        <?php if ( in_array( $status, [ 'pending', 'paused' ], true ) ) : ?>
                            <button type="button" class="button button-small logs-btn-prioritize"
                                    data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                    title="<?php esc_attr_e( 'Passer en priorité', 'techrappy-seo' ); ?>">
                                ⬆ <?php esc_html_e( 'Prioriser', 'techrappy-seo' ); ?>
                            </button>
                        <?php endif; ?>

                        <?php /* Supprimer — tous statuts */ ?>
                        <button type="button" class="button button-small logs-btn-delete"
                                data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                data-is-bulk="<?php echo esc_attr( $is_bulk_parent ? '1' : '0' ); ?>"
                                style="color:#b91c1c;border-color:#b91c1c;"
                                title="<?php esc_attr_e( 'Supprimer ce job', 'techrappy-seo' ); ?>">
                            ✕ <?php esc_html_e( 'Supprimer', 'techrappy-seo' ); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php /* ── Modal détail logs ────────────────────────────────────────────── */ ?>
<div id="logs-modal" style="display:none;position:fixed;top:5%;left:50%;transform:translateX(-50%);
     background:#fff;border:1px solid #ddd;border-radius:6px;padding:24px;width:760px;max-height:80vh;
     overflow-y:auto;z-index:99999;box-shadow:0 6px 24px rgba(0,0,0,.25);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;" id="logs-modal-title"><?php esc_html_e( 'Logs du job', 'techrappy-seo' ); ?></h3>
        <button type="button" id="logs-modal-close" class="button">&times;</button>
    </div>
    <div id="logs-modal-body">
        <span class="spinner is-active" style="float:none;"></span>
    </div>
</div>
<div id="logs-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99998;"></div>

<style>
.logs-status { display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px; }
.logs-status-success   { background:#d4edda;color:#155724; }
.logs-status-warning   { background:#fff3cd;color:#856404; }
.logs-status-error     { background:#f8d7da;color:#721c24; }
.logs-status-secondary { background:#e2e3e5;color:#383d41; }
.logs-entry { display:flex;gap:10px;border-bottom:1px solid #f0f0f0;padding:6px 0;font-size:13px;font-family:monospace; }
.logs-entry:last-child { border-bottom:none; }
.logs-level { min-width:60px;font-weight:600; }
.logs-step  { min-width:90px;color:#555; }
.logs-msg   { flex:1;word-break:break-word; }
</style>

<script>
(function($){
    var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

    // Détail des logs.
    $(document).on('click', '.logs-btn-detail', function(){
        var jobId = $(this).data('job-id');
        var nonce = $(this).data('nonce');
        $('#logs-modal-title').text('<?php echo esc_js( __( 'Logs du job', 'techrappy-seo' ) ); ?>');
        $('#logs-modal-body').html('<span class="spinner is-active" style="float:none;"></span>');
        $('#logs-modal, #logs-overlay').show();

        $.post(ajaxUrl, {
            action : 'techrappy_get_job_logs',
            nonce  : nonce,
            job_id : jobId
        }, function(resp){
            if (!resp.success) {
                $('#logs-modal-body').html('<p style="color:red;">' + resp.data.message + '</p>');
                return;
            }
            var d = resp.data;
            var title = d.keyword + (d.city ? ' – ' + d.city : '') + ' [' + d.status + ']';
            $('#logs-modal-title').text(title);

            if (!d.logs || !d.logs.length) {
                $('#logs-modal-body').html('<p><?php echo esc_js( __( 'Aucun log pour ce job.', 'techrappy-seo' ) ); ?></p>');
                return;
            }

            var levelColors = <?php echo wp_json_encode( $level_colors ); ?>;
            var html = '<div>';
            $.each(d.logs, function(i, entry){
                var color = levelColors[entry.level] || '#333';
                html += '<div class="logs-entry">'
                    + '<span class="logs-time" style="min-width:130px;color:#888;">' + (entry.time_human || '') + '</span>'
                    + '<span class="logs-level" style="color:' + color + ';">' + (entry.level || '').toUpperCase() + '</span>'
                    + '<span class="logs-step">[' + (entry.step || '') + ']</span>'
                    + '<span class="logs-msg">' + $('<span>').text(entry.msg || '').html() + '</span>'
                    + '</div>';
            });
            html += '</div>';
            $('#logs-modal-body').html(html);
        });
    });

    // Fermer le modal.
    $('#logs-modal-close, #logs-overlay').on('click', function(){
        $('#logs-modal, #logs-overlay').hide();
    });

    // ── Helper générique pour les actions sur les jobs ─────────────────────
    function jobAction(action, jobId, confirmMsg, successMsg) {
        if (confirmMsg && !confirm(confirmMsg)) { return; }
        $.post(ajaxUrl, {
            action : action,
            nonce  : <?php echo wp_json_encode( wp_create_nonce( 'techrappy_seo_bulk' ) ); ?>,
            job_id : jobId
        }, function(resp){
            if (resp.success) {
                alert(resp.data.message || successMsg);
                location.reload();
            } else {
                alert('Erreur : ' + ((resp.data && resp.data.message) || 'Une erreur est survenue.'));
            }
        });
    }

    // Reprendre un job depuis son dernier point.
    $(document).on('click', '.logs-btn-resume', function(){
        var jobId = $(this).data('job-id');
        jobAction('techrappy_resume_job', jobId,
            '<?php echo esc_js( __( 'Reprendre ce job depuis son dernier point de succès ?', 'techrappy-seo' ) ); ?>',
            '<?php echo esc_js( __( 'Job repris.', 'techrappy-seo' ) ); ?>'
        );
    });

    // Mettre en pause.
    $(document).on('click', '.logs-btn-pause', function(){
        var jobId = $(this).data('job-id');
        jobAction('techrappy_pause_job', jobId,
            '<?php echo esc_js( __( 'Mettre ce job en pause ?', 'techrappy-seo' ) ); ?>',
            '<?php echo esc_js( __( 'Job mis en pause.', 'techrappy-seo' ) ); ?>'
        );
    });

    // Prioriser.
    $(document).on('click', '.logs-btn-prioritize', function(){
        var jobId = $(this).data('job-id');
        jobAction('techrappy_prioritize_job', jobId,
            '<?php echo esc_js( __( 'Passer ce job en priorité ?', 'techrappy-seo' ) ); ?>',
            '<?php echo esc_js( __( 'Job priorisé.', 'techrappy-seo' ) ); ?>'
        );
    });

    // Supprimer.
    $(document).on('click', '.logs-btn-delete', function(){
        var jobId  = $(this).data('job-id');
        var isBulk = $(this).data('is-bulk') === '1' || $(this).data('is-bulk') === 1;
        var msg    = isBulk
            ? '<?php echo esc_js( __( 'Supprimer ce job bulk et tous ses enfants ?', 'techrappy-seo' ) ); ?>'
            : '<?php echo esc_js( __( 'Supprimer ce job ?', 'techrappy-seo' ) ); ?>';
        jobAction('techrappy_delete_job', jobId, msg,
            '<?php echo esc_js( __( 'Job supprimé.', 'techrappy-seo' ) ); ?>'
        );
    });

    // Purger les jobs.
    $('#logs-btn-clear').on('click', function(){
        if (!confirm('<?php echo esc_js( __( 'Supprimer tous les jobs terminés et échoués ?', 'techrappy-seo' ) ); ?>')) {
            return;
        }
        var nonce = $(this).data('nonce');
        $.post(ajaxUrl, {
            action : 'techrappy_clear_logs',
            nonce  : nonce
        }, function(resp){
            if (resp.success) {
                $('#logs-notice').html('<div class="notice notice-success"><p>' + resp.data.message + '</p></div>');
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $('#logs-notice').html('<div class="notice notice-error"><p>' + resp.data.message + '</p></div>');
            }
        });
    });
})(jQuery);
</script>

<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
