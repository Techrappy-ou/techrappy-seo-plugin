<?php
/**
 * Vue : formulaire de réglages.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$settings = \TechrappySEO\Settings\SettingsRepository::get_all();
$api_key  = \TechrappySEO\Settings\SettingsRepository::get_api_key();
$masked   = $api_key ? str_repeat( '•', max( 0, strlen( $api_key ) - 6 ) ) . substr( $api_key, -6 ) : '';

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap">

    <div id="techrappy-settings-notice"></div>

    <form id="techrappy-settings-form">

        <?php /* ─── Bloc OpenAI ─────────────────────────────────────────────── */ ?>
        <h2><?php esc_html_e( 'API OpenAI', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="openai_api_key"><?php esc_html_e( 'Clé API', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="password" id="openai_api_key" name="openai_api_key"
                           class="regular-text" autocomplete="off"
                           placeholder="<?php echo esc_attr( $masked ?: 'sk-...' ); ?>">
                    <button type="button" class="button" id="techrappy-test-api" style="margin-left:6px;">
                        <?php esc_html_e( 'Tester la connexion', 'techrappy-seo' ); ?>
                    </button>
                    <span id="techrappy-test-result" style="margin-left:8px;font-weight:600;"></span>
                    <p class="description"><?php esc_html_e( 'Laisser vide pour conserver la clé actuelle.', 'techrappy-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_model"><?php esc_html_e( 'Modèle', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="openai_model" name="openai_model">
                        <?php foreach ( [ 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo' ] as $m ) : ?>
                            <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $settings['openai_model'] ?? 'gpt-4o', $m ); ?>>
                                <?php echo esc_html( $m ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_temperature"><?php esc_html_e( 'Température', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="openai_temperature" name="openai_temperature"
                           step="0.1" min="0" max="2"
                           value="<?php echo esc_attr( $settings['openai_temperature'] ?? 0.7 ); ?>"
                           class="small-text">
                    <p class="description"><?php esc_html_e( '0.0 = déterministe, 2.0 = très créatif', 'techrappy-seo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_max_tokens"><?php esc_html_e( 'Max tokens', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="openai_max_tokens" name="openai_max_tokens"
                           min="256" max="16000"
                           value="<?php echo esc_attr( $settings['openai_max_tokens'] ?? 4096 ); ?>"
                           class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_timeout"><?php esc_html_e( 'Timeout (secondes)', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="openai_timeout" name="openai_timeout"
                           min="10" max="300"
                           value="<?php echo esc_attr( $settings['openai_timeout'] ?? 60 ); ?>"
                           class="small-text">
                </td>
            </tr>
        </table>

        <?php /* ─── Bloc Génération ─────────────────────────────────────────── */ ?>
        <h2><?php esc_html_e( 'Comportement de génération', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="default_publish_status"><?php esc_html_e( 'Statut par défaut', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="default_publish_status" name="default_publish_status">
                        <option value="draft" <?php selected( $settings['default_publish_status'] ?? 'draft', 'draft' ); ?>><?php esc_html_e( 'Brouillon', 'techrappy-seo' ); ?></option>
                        <option value="publish" <?php selected( $settings['default_publish_status'] ?? 'draft', 'publish' ); ?>><?php esc_html_e( 'Publié', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="default_slug_rule"><?php esc_html_e( 'Règle de slug', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="default_slug_rule" name="default_slug_rule">
                        <option value="from_keyword" <?php selected( $settings['default_slug_rule'] ?? 'from_keyword', 'from_keyword' ); ?>><?php esc_html_e( 'Depuis le mot-clé', 'techrappy-seo' ); ?></option>
                        <option value="from_h1" <?php selected( $settings['default_slug_rule'] ?? 'from_keyword', 'from_h1' ); ?>><?php esc_html_e( 'Depuis le H1 généré', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Gate QA', 'techrappy-seo' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="qa_gate_enabled" name="qa_gate_enabled"
                               value="1" <?php checked( $settings['qa_gate_enabled'] ?? false ); ?>>
                        <?php esc_html_e( 'Activer l\'étape QA avant publication', 'techrappy-seo' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php /* ─── Bloc Bulk ────────────────────────────────────────────────── */ ?>
        <h2><?php esc_html_e( 'Génération en masse', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="bulk_max_cities"><?php esc_html_e( 'Villes max par batch', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="bulk_max_cities" name="bulk_max_cities"
                           min="1" max="200"
                           value="<?php echo esc_attr( $settings['bulk_max_cities'] ?? 50 ); ?>"
                           class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cost_alert_threshold"><?php esc_html_e( 'Seuil d\'alerte coût ($)', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="cost_alert_threshold" name="cost_alert_threshold"
                           step="0.01" min="0"
                           value="<?php echo esc_attr( $settings['cost_alert_threshold'] ?? 1.00 ); ?>"
                           class="small-text">
                </td>
            </tr>
        </table>

        <?php /* ─── Bloc Debug ──────────────────────────────────────────────── */ ?>
        <h2><?php esc_html_e( 'Debug', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Mode debug', 'techrappy-seo' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="debug_mode" name="debug_mode"
                               value="1" <?php checked( $settings['debug_mode'] ?? false ); ?>>
                        <?php esc_html_e( 'Activer les logs détaillés (error_log WordPress)', 'techrappy-seo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="log_retention_days"><?php esc_html_e( 'Rétention logs (jours)', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="number" id="log_retention_days" name="log_retention_days"
                           min="1" max="365"
                           value="<?php echo esc_attr( $settings['log_retention_days'] ?? 30 ); ?>"
                           class="small-text">
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary" id="techrappy-settings-save">
                <?php esc_html_e( 'Enregistrer les réglages', 'techrappy-seo' ); ?>
            </button>
            <span class="techrappy-spinner spinner" style="float:none;"></span>
        </p>
    </form>

</div>

<script>
jQuery(function($) {
    // Test connexion API.
    $('#techrappy-test-api').on('click', function() {
        var $btn    = $(this);
        var $result = $('#techrappy-test-result');
        $btn.prop('disabled', true);
        $result.text('<?php echo esc_js( __( 'Test en cours…', 'techrappy-seo' ) ); ?>').css('color', '#555');

        TechrappySEOAjax('techrappy_test_api_connection', { nonce: TechrappySEO.nonces.settings },
            function(res) {
                $btn.prop('disabled', false);
                $result.text(res.message || '').css('color', '#155724');
            },
            function(err) {
                $btn.prop('disabled', false);
                $result.text((err && err.message) ? err.message : '<?php echo esc_js( __( 'Erreur inconnue.', 'techrappy-seo' ) ); ?>').css('color', '#b91c1c');
            }
        );
    });

    $('#techrappy-settings-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#techrappy-settings-save');
        var $spinner = $btn.siblings('.spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        var data = { nonce: TechrappySEO.nonces.settings };
        $(this).find(':input[name]').each(function() {
            var $el = $(this);
            var name = $el.attr('name');
            if ($el.attr('type') === 'checkbox') {
                data['settings[' + name + ']'] = $el.is(':checked') ? 1 : 0;
            } else if ($el.val() !== '') {
                data['settings[' + name + ']'] = $el.val();
            }
        });

        TechrappySEOAjax('techrappy_save_settings', data,
            function(res) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                // Utiliser .text() pour éviter toute injection XSS depuis la réponse serveur.
                var $ok = $('<div class="notice notice-success is-dismissible">').append($('<p>').text(res.message || ''));
                $('#techrappy-settings-notice').empty().append($ok);
            },
            function(err) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                var msgText = (err && err.errors) ? JSON.stringify(err.errors) : (err && err.message ? err.message : '<?php echo esc_js( __( 'Erreur inconnue.', 'techrappy-seo' ) ); ?>');
                // Utiliser .text() pour éviter toute injection XSS depuis la réponse serveur.
                var $err = $('<div class="notice notice-error is-dismissible">').append($('<p>').text(msgText));
                $('#techrappy-settings-notice').empty().append($err);
            }
        );
    });
});
</script>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
