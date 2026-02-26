<?php
/**
 * Vue : liste des jobs (single + bulk).
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Afficher uniquement les jobs racines (pas les enfants bulk).
$jobs = \TechrappySEO\Jobs\JobRepository::list( [ 'limit' => 50, 'top_level' => true ] );

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';

$status_labels = [
    'pending'          => [ __( 'En attente',  'techrappy-seo' ), 'secondary' ],
    'running'          => [ __( 'En cours',    'techrappy-seo' ), 'warning'   ],
    'done'             => [ __( 'Terminé',     'techrappy-seo' ), 'success'   ],
    'done_with_errors' => [ __( 'Partiel',     'techrappy-seo' ), 'warning'   ],
    'failed'           => [ __( 'Échec',       'techrappy-seo' ), 'error'     ],
];
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-bulk-jobs">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h2 style="margin:0;"><?php esc_html_e( 'Jobs de génération', 'techrappy-seo' ); ?></h2>
        <div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=techrappy-seo' ) ); ?>" class="button button-primary">
                + <?php esc_html_e( 'Nouvelle génération', 'techrappy-seo' ); ?>
            </a>
            <button type="button" class="button" id="bj-btn-refresh" style="margin-left:6px;">
                <?php esc_html_e( 'Rafraîchir', 'techrappy-seo' ); ?>
            </button>
        </div>
    </div>

    <?php if ( empty( $jobs ) ) : ?>
        <p><?php esc_html_e( 'Aucun job trouvé. Lancez votre première génération !', 'techrappy-seo' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:200px;"><?php esc_html_e( 'Mot-clé', 'techrappy-seo' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Mode', 'techrappy-seo' ); ?></th>
                    <th style="width:100px;"><?php esc_html_e( 'Statut', 'techrappy-seo' ); ?></th>
                    <th><?php esc_html_e( 'Résultat', 'techrappy-seo' ); ?></th>
                    <th style="width:140px;"><?php esc_html_e( 'Créé le', 'techrappy-seo' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Actions', 'techrappy-seo' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $jobs as $job ) :
                    $status     = $job['status'] ?? 'pending';
                    $label_info = $status_labels[ $status ] ?? [ $status, 'secondary' ];
                    $steps      = is_array( $job['steps_data'] ) ? $job['steps_data'] : [];
                    $result     = is_array( $job['result_data'] ) ? $job['result_data'] : [];
                    $is_bulk    = 'bulk' === $job['mode'] && empty( $job['parent_job_id'] );
                    $total      = $steps['_bulk_total']    ?? 0;
                    $done       = $steps['_bulk_done']     ?? 0;
                    $percent    = $steps['_bulk_progress'] ?? 0;
                    $is_failed  = in_array( $status, [ 'failed', 'done_with_errors' ], true );

                    // Dernier message d'erreur dans les logs.
                    $last_error = '';
                    if ( $is_failed && is_array( $job['logs'] ) ) {
                        foreach ( array_reverse( $job['logs'] ) as $log_entry ) {
                            if ( ( $log_entry['level'] ?? '' ) === 'error' ) {
                                $last_error = $log_entry['msg'] ?? '';
                                break;
                            }
                        }
                    }
                ?>
                <tr data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                    data-mode="<?php echo esc_attr( $job['mode'] ); ?>"
                    data-status="<?php echo esc_attr( $status ); ?>">
                    <td>
                        <strong><?php echo esc_html( $job['keyword'] ); ?></strong>
                        <?php if ( $job['city'] ) : ?>
                            <br><small><?php echo esc_html( $job['city'] ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( ucfirst( $job['mode'] ) ); ?></td>
                    <td>
                        <span class="bj-status bj-status-<?php echo esc_attr( $label_info[1] ); ?>">
                            <?php echo esc_html( $label_info[0] ); ?>
                        </span>
                        <?php if ( $is_bulk && in_array( $status, [ 'running', 'done', 'done_with_errors' ], true ) ) : ?>
                            <br><small><?php echo esc_html( $done . '/' . $total . ' (' . $percent . '%)' ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( ! empty( $result['permalink'] ) ) : ?>
                            <a href="<?php echo esc_url( $result['permalink'] ); ?>" target="_blank">
                                <?php esc_html_e( 'Voir le post', 'techrappy-seo' ); ?>
                            </a>
                        <?php elseif ( 'done' === $status ) : ?>
                            <em><?php esc_html_e( 'Job parent (voir enfants)', 'techrappy-seo' ); ?></em>
                        <?php elseif ( $is_failed && $last_error ) : ?>
                            <span style="color:#721c24;font-size:12px;" title="<?php echo esc_attr( $last_error ); ?>">
                                ✗ <?php echo esc_html( mb_strimwidth( $last_error, 0, 80, '…' ) ); ?>
                            </span>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( $job['created_at'] ?? '—' ); ?></td>
                    <td>
                        <?php if ( in_array( $status, [ 'running', 'pending' ], true ) ) : ?>
                            <button type="button" class="button bj-btn-status"
                                    data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>">
                                <?php esc_html_e( 'Statut', 'techrappy-seo' ); ?>
                            </button>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=techrappy-seo&resume_job=' . $job['job_id'] ) ); ?>"
                               class="button" style="margin-top:2px;display:block;text-align:center;">
                                <?php esc_html_e( 'Suivre', 'techrappy-seo' ); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( $is_failed ) : ?>
                            <button type="button" class="button bj-btn-retry"
                                    data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"
                                    data-is-bulk="<?php echo esc_attr( $is_bulk ? '1' : '0' ); ?>"
                                    style="color:#b91c1c;border-color:#b91c1c;margin-top:2px;">
                                ↺ <?php esc_html_e( 'Relancer', 'techrappy-seo' ); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div id="bj-status-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
         background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px;min-width:360px;z-index:99999;box-shadow:0 4px 16px rgba(0,0,0,.2);">
        <button type="button" id="bj-modal-close" style="float:right;" class="button">&times;</button>
        <h3 style="margin-top:0;" id="bj-modal-title"><?php esc_html_e( 'Statut du job', 'techrappy-seo' ); ?></h3>
        <div id="bj-modal-content"></div>
    </div>

    <style>
        .bj-status { display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px; }
        .bj-status-success  { background:#d4edda;color:#155724; }
        .bj-status-warning  { background:#fff3cd;color:#856404; }
        .bj-status-error    { background:#f8d7da;color:#721c24; }
        .bj-status-secondary{ background:#e2e3e5;color:#383d41; }
    </style>

</div>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
