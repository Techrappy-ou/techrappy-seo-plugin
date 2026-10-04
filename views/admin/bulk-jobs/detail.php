<?php
/**
 * Vue : détail d'un job.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$job_id = sanitize_text_field( $_GET['job_id'] ?? '' );
$job    = $job_id ? \TechrappySEO\Jobs\JobRepository::find( $job_id ) : null;

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap">

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=techrappy-seo-bulk-jobs' ) ); ?>" class="button">
        ← <?php esc_html_e( 'Retour à la liste', 'techrappy-seo' ); ?>
    </a>

    <?php if ( ! $job ) : ?>
        <div class="notice notice-error"><p><?php esc_html_e( 'Job introuvable.', 'techrappy-seo' ); ?></p></div>
    <?php else :
        $steps  = is_array( $job['steps_data'] )  ? $job['steps_data']  : [];
        $result = is_array( $job['result_data'] )  ? $job['result_data'] : [];
        $logs   = is_array( $job['logs'] )          ? $job['logs']        : [];
    ?>
        <h2><?php echo esc_html( $job['keyword'] . ( $job['city'] ? ' — ' . $job['city'] : '' ) ); ?></h2>
        <p>
            <strong><?php esc_html_e( 'Statut :', 'techrappy-seo' ); ?></strong>
            <?php echo esc_html( $job['status'] ); ?> &nbsp;|&nbsp;
            <strong><?php esc_html_e( 'Mode :', 'techrappy-seo' ); ?></strong>
            <?php echo esc_html( $job['mode'] ); ?> &nbsp;|&nbsp;
            <strong><?php esc_html_e( 'Créé le :', 'techrappy-seo' ); ?></strong>
            <?php echo esc_html( $job['created_at'] ?? '' ); ?>
        </p>

        <?php if ( ! empty( $result['permalink'] ) ) : ?>
            <p>
                <a href="<?php echo esc_url( $result['permalink'] ); ?>" target="_blank" class="button button-primary">
                    <?php esc_html_e( 'Voir le post', 'techrappy-seo' ); ?>
                </a>
            </p>
        <?php endif; ?>

        <h3><?php esc_html_e( 'Étapes', 'techrappy-seo' ); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th><?php esc_html_e( 'Étape', 'techrappy-seo' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'techrappy-seo' ); ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ( $steps as $step_key => $step_data ) :
                    if ( str_starts_with( $step_key, '_' ) ) continue;
                    $s = is_array( $step_data ) ? ( $step_data['status'] ?? '—' ) : '—';
                ?>
                    <tr>
                        <td><code><?php echo esc_html( $step_key ); ?></code></td>
                        <td><?php echo esc_html( $s ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( ! empty( $logs ) ) : ?>
            <h3><?php esc_html_e( 'Logs', 'techrappy-seo' ); ?></h3>
            <div style="max-height:300px;overflow-y:auto;background:#f6f7f7;border:1px solid #ddd;padding:10px;font-family:monospace;font-size:12px;">
                <?php foreach ( $logs as $log ) : ?>
                    <div class="log-line log-<?php echo esc_attr( $log['level'] ?? 'info' ); ?>">
                        [<?php echo esc_html( strtoupper( $log['level'] ?? 'info' ) ); ?>]
                        [<?php echo esc_html( $log['step'] ?? '' ); ?>]
                        <?php echo esc_html( $log['msg'] ?? '' ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <style>
                .log-error { color:#c0392b; }
                .log-warning { color:#e67e22; }
                .log-info { color:#2c3e50; }
                .log-debug { color:#7f8c8d; }
            </style>
        <?php endif; ?>

    <?php endif; ?>

</div>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
