<?php
/**
 * Vue : Wizard de génération (container principal — 7 étapes).
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';

$steps_labels = [
    1 => __( '1. Mode',       'techrappy-seo' ),
    2 => __( '2. Template',   'techrappy-seo' ),
    3 => __( '3. Audit',      'techrappy-seo' ),
    4 => __( '4. WordPress',  'techrappy-seo' ),
    5 => __( '5. Résumé',     'techrappy-seo' ),
    6 => __( '6. Génération', 'techrappy-seo' ),
];
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-wizard">

    <?php /* ── Navigation étapes ───────────────────────────────────────────── */ ?>
    <nav class="techrappy-wizard-steps" aria-label="<?php esc_attr_e( 'Étapes du wizard', 'techrappy-seo' ); ?>">
        <?php foreach ( $steps_labels as $n => $label ) : ?>
            <span class="techrappy-wizard-step<?php echo 1 === $n ? ' active' : ''; ?>"
                  data-step="<?php echo esc_attr( $n ); ?>">
                <?php echo esc_html( $label ); ?>
            </span>
        <?php endforeach; ?>
    </nav>

    <div id="techrappy-wizard-notice"></div>

    <?php /* ── Étape 1 : Mode ──────────────────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-1">
        <h2><?php esc_html_e( 'Mode de génération', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Mode', 'techrappy-seo' ); ?></th>
                <td>
                    <label style="margin-right:20px;">
                        <input type="radio" name="wz_mode" value="single" checked>
                        <?php esc_html_e( 'Page unique', 'techrappy-seo' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="wz_mode" value="bulk">
                        <?php esc_html_e( 'Génération en masse (par villes)', 'techrappy-seo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wz_keyword"><?php esc_html_e( 'Mot-clé principal', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="text" id="wz_keyword" name="wz_keyword" class="regular-text"
                           placeholder="<?php esc_attr_e( 'ex : ostéopathe toulouse', 'techrappy-seo' ); ?>">
                    <p class="description" id="wz_keyword_hint">
                        <?php esc_html_e( 'Mode bulk : saisir la base sans ville (ex : ostéopathe)', 'techrappy-seo' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wz_profession"><?php esc_html_e( 'Profession / Secteur', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="text" id="wz_profession" name="wz_profession" class="regular-text"
                           placeholder="<?php esc_attr_e( 'ex : ostéopathe, plombier, kinésithérapeute', 'techrappy-seo' ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wz_type"><?php esc_html_e( 'Type de contenu', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="wz_type" name="wz_type">
                        <option value="page"><?php esc_html_e( 'Page', 'techrappy-seo' ); ?></option>
                        <option value="post"><?php esc_html_e( 'Article', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="wz_city_row">
                <th scope="row"><label for="wz_city"><?php esc_html_e( 'Ville (mode single)', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="text" id="wz_city" name="wz_city" class="regular-text"
                           placeholder="<?php esc_attr_e( 'ex : Toulouse', 'techrappy-seo' ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Intention de recherche', 'techrappy-seo' ); ?></th>
                <td>
                    <label style="display:block;margin-bottom:8px;">
                        <input type="radio" name="wz_intent_mode" value="auto" checked>
                        <?php esc_html_e( 'Analyser via IA (recommandé, +1 appel API)', 'techrappy-seo' ); ?>
                    </label>
                    <label style="display:block;">
                        <input type="radio" name="wz_intent_mode" value="manual">
                        <?php esc_html_e( 'Je la fournis moi-même (plus rapide, économise des tokens)', 'techrappy-seo' ); ?>
                    </label>
                    <div id="wz_user_intent_wrap" style="display:none;margin-top:8px;">
                        <textarea id="wz_user_intent" name="wz_user_intent" rows="3" class="large-text"
                                  placeholder="<?php esc_attr_e( 'ex : intention transactionnelle — l\'utilisateur cherche à prendre rendez-vous avec un psychologue près de chez lui', 'techrappy-seo' ); ?>"></textarea>
                        <p class="description"><?php esc_html_e( 'Décrivez l\'intention : type (transactionnelle, informationnelle…), besoin, contexte.', 'techrappy-seo' ); ?></p>
                    </div>
                </td>
            </tr>
            <?php /* ── Sous-mode bulk : par villes ou par mots-clés ──────────────── */ ?>
            <tr id="wz_bulk_submode_row" style="display:none;">
                <th scope="row"><?php esc_html_e( 'Type de génération en masse', 'techrappy-seo' ); ?></th>
                <td>
                    <label style="display:inline-block;margin-right:24px;">
                        <input type="radio" name="wz_bulk_submode" value="cities" checked>
                        <?php esc_html_e( 'Par villes (base keyword + chaque ville)', 'techrappy-seo' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="wz_bulk_submode" value="keywords">
                        <?php esc_html_e( 'Par mots-clés (un mot-clé par ligne)', 'techrappy-seo' ); ?>
                    </label>
                </td>
            </tr>

            <?php /* ── Zone mots-clés (visible en mode bulk + keywords) ───────────── */ ?>
            <tr id="wz_bulk_keywords_row" style="display:none;">
                <th scope="row"><label for="wz_keywords_list"><?php esc_html_e( 'Liste de mots-clés', 'techrappy-seo' ); ?></label></th>
                <td>
                    <textarea id="wz_keywords_list" name="wz_keywords_list" rows="10"
                              class="large-text" style="font-family:monospace;font-size:13px;"
                              placeholder="<?php esc_attr_e( "montage vidéo professionnel\nmonteur vidéo freelance\nmontage vidéo pour entrepreneur\nmontage vidéo YouTube\ncréation contenu vidéo", 'techrappy-seo' ); ?>"></textarea>
                    <p class="description">
                        <?php esc_html_e( 'Un mot-clé par ligne. Chaque ligne génère une page complète via le pipeline IA.', 'techrappy-seo' ); ?>
                        <span id="wz_keywords_count" style="float:right;font-weight:600;color:#0073aa;"></span>
                    </p>
                </td>
            </tr>

            <tr id="wz_bulk_city_row" style="display:none;">
                <th scope="row"><label for="wz_ville_principale"><?php esc_html_e( 'Code postal de référence', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="text" id="wz_ville_principale" name="wz_ville_principale" class="small-text"
                           placeholder="<?php esc_attr_e( 'ex : 31000', 'techrappy-seo' ); ?>"
                           maxlength="5" pattern="\d{5}" inputmode="numeric">
                    <p class="description"><?php esc_html_e( 'Entrez un code postal français à 5 chiffres (ex : 31000 pour Toulouse).', 'techrappy-seo' ); ?></p>
                    <br>
                    <label><?php esc_html_e( 'Rayon (km) :', 'techrappy-seo' ); ?>
                        <input type="number" id="wz_radius_km" name="wz_radius_km" value="30"
                               min="5" max="100" class="small-text">
                    </label>
                    <button type="button" class="button" id="wz_load_cities" style="margin-left:8px;">
                        <?php esc_html_e( 'Charger les villes', 'techrappy-seo' ); ?>
                    </button>
                    <div id="wz_cities_list" style="margin-top:10px;"></div>
                </td>
            </tr>
        </table>
    </div>

    <?php /* ── Étape 2 : Template ──────────────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-2" style="display:none;">
        <h2><?php esc_html_e( 'Sélection du template Divi', 'techrappy-seo' ); ?></h2>
        <p><?php esc_html_e( 'Choisissez une page existante qui servira de template Divi (avec des tokens {{TOKEN}}).', 'techrappy-seo' ); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wz_template_post_id"><?php esc_html_e( 'Template', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="wz_template_post_id" name="wz_template_post_id" class="regular-text">
                        <option value="0"><?php esc_html_e( '— Aucun template (HTML brut) —', 'techrappy-seo' ); ?></option>
                    </select>
                    <span class="spinner techrappy-spinner" id="wz_template_spinner"></span>
                    <p class="description"><?php esc_html_e( 'Si aucun template : le contenu sera injecté en HTML brut dans post_content.', 'techrappy-seo' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <?php /* ── Étape 3 : Audit ──────────────────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-3" style="display:none;">
        <h2><?php esc_html_e( 'Audit du template', 'techrappy-seo' ); ?></h2>
        <div id="wz_audit_result">
            <p class="description"><?php esc_html_e( 'Sélectionnez un template à l\'étape précédente pour lancer l\'audit.', 'techrappy-seo' ); ?></p>
        </div>
    </div>

    <?php /* ── Étape 4 : Paramètres WordPress ────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-4" style="display:none;">
        <h2><?php esc_html_e( 'Paramètres WordPress', 'techrappy-seo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wz_publish_status"><?php esc_html_e( 'Statut de publication', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="wz_publish_status" name="wz_publish_status">
                        <option value="draft"><?php esc_html_e( 'Brouillon', 'techrappy-seo' ); ?></option>
                        <option value="publish"><?php esc_html_e( 'Publié immédiatement', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wz_parent_id"><?php esc_html_e( 'Page parente', 'techrappy-seo' ); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_pages( [
                        'id'               => 'wz_parent_id',
                        'name'             => 'wz_parent_id',
                        'show_option_none' => __( '— Aucune —', 'techrappy-seo' ),
                        'option_none_value' => '0',
                        'selected'         => 0,
                    ] );
                    ?>
                </td>
            </tr>
            <tr id="wz_category_row">
                <th scope="row"><label for="wz_category_id"><?php esc_html_e( 'Catégorie', 'techrappy-seo' ); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories( [
                        'id'               => 'wz_category_id',
                        'name'             => 'wz_category_id',
                        'show_option_none' => __( '— Aucune —', 'techrappy-seo' ),
                        'option_none_value' => '0',
                        'hide_empty'       => false,
                        'selected'         => 0,
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wz_slug_rule"><?php esc_html_e( 'Règle de slug', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="wz_slug_rule" name="wz_slug_rule">
                        <option value="from_keyword"><?php esc_html_e( 'Depuis le mot-clé', 'techrappy-seo' ); ?></option>
                        <option value="from_h1"><?php esc_html_e( 'Depuis le H1 généré', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>

            <?php /* ── Gestion des menus ─────────────────────────────────────────── */ ?>
            <tr>
                <th scope="row"><label for="bj-menu-action"><?php esc_html_e( 'Ajouter au menu', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="bj-menu-action" name="bj_menu_action">
                        <option value="none"><?php esc_html_e( '— Ne pas ajouter au menu —', 'techrappy-seo' ); ?></option>
                        <option value="add_existing"><?php esc_html_e( 'Ajouter à un menu existant', 'techrappy-seo' ); ?></option>
                        <option value="create_new"><?php esc_html_e( 'Créer un nouveau menu', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="wz_menu_existing_row" style="display:none;">
                <th scope="row"><label for="bj-menu-id"><?php esc_html_e( 'Menu existant', 'techrappy-seo' ); ?></label></th>
                <td>
                    <?php $nav_menus = wp_get_nav_menus(); ?>
                    <?php if ( ! empty( $nav_menus ) ) : ?>
                        <select id="bj-menu-id" name="bj_menu_id">
                            <?php foreach ( $nav_menus as $menu ) : ?>
                                <option value="<?php echo esc_attr( $menu->term_id ); ?>">
                                    <?php echo esc_html( $menu->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <input type="hidden" id="bj-menu-id" name="bj_menu_id" value="0">
                        <p class="description"><?php esc_html_e( 'Aucun menu trouvé. Créez-en un dans Apparence > Menus, ou choisissez "Créer un nouveau menu".', 'techrappy-seo' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="wz_menu_new_row" style="display:none;">
                <th scope="row"><label for="bj-menu-name"><?php esc_html_e( 'Nom du nouveau menu', 'techrappy-seo' ); ?></label></th>
                <td>
                    <input type="text" id="bj-menu-name" name="bj_menu_name" class="regular-text"
                           placeholder="<?php esc_attr_e( 'ex : Menu SEO Marseille', 'techrappy-seo' ); ?>">
                    <?php $registered_menus = get_registered_nav_menus(); ?>
                    <?php if ( ! empty( $registered_menus ) ) : ?>
                        <p class="description" style="margin-top:8px;">
                            <?php esc_html_e( 'Emplacement de thème (optionnel) :', 'techrappy-seo' ); ?>
                        </p>
                        <select id="bj-menu-location" name="bj_menu_location">
                            <option value=""><?php esc_html_e( '— Aucun emplacement —', 'techrappy-seo' ); ?></option>
                            <?php foreach ( $registered_menus as $location => $description ) : ?>
                                <option value="<?php echo esc_attr( $location ); ?>">
                                    <?php echo esc_html( $description ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <input type="hidden" id="bj-menu-location" name="bj_menu_location" value="">
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="wz_menu_label_row" style="display:none;">
                <th scope="row"><label for="bj-label-format"><?php esc_html_e( 'Format du libellé de menu', 'techrappy-seo' ); ?></label></th>
                <td>
                    <select id="bj-label-format" name="bj_label_format">
                        <option value="post_title"><?php esc_html_e( 'Titre du post', 'techrappy-seo' ); ?></option>
                        <option value="keyword_city"><?php esc_html_e( 'Mot-clé + Ville', 'techrappy-seo' ); ?></option>
                        <option value="custom"><?php esc_html_e( 'Personnalisé', 'techrappy-seo' ); ?></option>
                    </select>
                    <div id="wz_menu_custom_label_wrap" style="display:none;margin-top:8px;">
                        <input type="text" id="bj-label-template" name="bj_label_template" class="regular-text"
                               placeholder="<?php esc_attr_e( 'ex : {keyword} – {city}', 'techrappy-seo' ); ?>">
                        <p class="description"><?php esc_html_e( 'Variables : {keyword}, {city}, {title}', 'techrappy-seo' ); ?></p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <?php /* ── Étape 5 : Résumé ─────────────────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-5" style="display:none;">
        <h2><?php esc_html_e( 'Résumé avant génération', 'techrappy-seo' ); ?></h2>
        <div id="wz_summary">
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Mode', 'techrappy-seo' ); ?></th><td id="s_mode">—</td></tr>
                <tr><th><?php esc_html_e( 'Mot-clé', 'techrappy-seo' ); ?></th><td id="s_keyword">—</td></tr>
                <tr><th><?php esc_html_e( 'Profession', 'techrappy-seo' ); ?></th><td id="s_profession">—</td></tr>
                <tr><th><?php esc_html_e( 'Type', 'techrappy-seo' ); ?></th><td id="s_type">—</td></tr>
                <tr><th><?php esc_html_e( 'Template', 'techrappy-seo' ); ?></th><td id="s_template">—</td></tr>
                <tr><th><?php esc_html_e( 'Statut', 'techrappy-seo' ); ?></th><td id="s_status">—</td></tr>
            </table>
        </div>
    </div>

    <?php /* ── Étape 6 : Génération en cours ────────────────────────────────── */ ?>
    <div class="techrappy-wizard-content" id="wz-step-6" style="display:none;">
        <h2 id="wz_step6_title"><?php esc_html_e( 'Génération en cours…', 'techrappy-seo' ); ?></h2>
        <div id="wz_progress" style="display:none;">

            <?php /* Barre de progression globale */ ?>
            <div style="margin-bottom:16px;">
                <div style="background:#e2e3e5;border-radius:6px;height:12px;overflow:hidden;">
                    <div id="wz_progress_bar" style="background:#0073aa;height:12px;width:0%;border-radius:6px;transition:width .4s ease;"></div>
                </div>
                <p style="margin:8px 0 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span class="spinner is-active" id="wz_spinner" style="float:none;margin:0;"></span>
                    <span id="wz_progress_msg"><?php esc_html_e( 'Initialisation…', 'techrappy-seo' ); ?></span>
                    <span id="wz_progress_pct" style="margin-left:auto;font-weight:600;color:#0073aa;font-size:13px;"></span>
                </p>
            </div>

            <?php /* Étapes nommées */ ?>
            <div id="wz_steps_list" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:6px;"></div>

            <?php /* Logs */ ?>
            <div id="wz_log_output" style="max-height:160px;overflow-y:auto;background:#f6f7f7;padding:8px;font-family:monospace;font-size:11px;border:1px solid #ddd;border-radius:4px;"></div>
        </div>
        <div id="wz_done" style="display:none;">
            <div class="notice notice-success" style="padding:10px;">
                <p><strong><?php esc_html_e( '✓ Génération terminée !', 'techrappy-seo' ); ?></strong></p>
                <p>
                    <a id="wz_post_link" href="#" target="_blank" class="button button-primary">
                        <?php esc_html_e( 'Voir le post créé', 'techrappy-seo' ); ?>
                    </a>
                    <a id="wz_edit_link" href="#" class="button" style="margin-left:8px;">
                        <?php esc_html_e( 'Éditer dans WordPress', 'techrappy-seo' ); ?>
                    </a>
                    <button type="button" class="button" id="wz_new_generation" style="margin-left:8px;">
                        <?php esc_html_e( 'Nouvelle génération', 'techrappy-seo' ); ?>
                    </button>
                </p>
            </div>
        </div>
        <div id="wz_failed" style="display:none;">
            <div class="notice notice-error" style="padding:10px;">
                <p><strong><?php esc_html_e( '✗ La génération a échoué.', 'techrappy-seo' ); ?></strong></p>
                <p><button type="button" class="button" id="wz_retry"><?php esc_html_e( 'Réessayer', 'techrappy-seo' ); ?></button></p>
            </div>
        </div>
    </div>

    <?php /* ── Navigation ───────────────────────────────────────────────────── */ ?>
    <div class="techrappy-wizard-nav" style="margin-top:16px;">
        <button type="button" class="button" id="wz-btn-prev" style="display:none;">
            ← <?php esc_html_e( 'Précédent', 'techrappy-seo' ); ?>
        </button>
        <button type="button" class="button button-primary" id="wz-btn-next">
            <?php esc_html_e( 'Suivant', 'techrappy-seo' ); ?> →
        </button>
        <button type="button" class="button button-primary" id="wz-btn-launch" style="display:none;">
            🚀 <?php esc_html_e( 'Lancer la génération', 'techrappy-seo' ); ?>
        </button>
    </div>

</div>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
