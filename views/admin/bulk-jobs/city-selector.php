<?php
/**
 * Vue partielle : sélecteur de villes + options menu pour la génération en masse.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$wp_menus = wp_get_nav_menus();
?>
<div id="bj-city-selector">
    <p class="description"><?php esc_html_e( 'Villes chargées :', 'techrappy-seo' ); ?></p>
    <div id="bj-cities-checkboxes" style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;"></div>
    <p>
        <a href="#" id="bj-select-all"><?php esc_html_e( 'Tout sélectionner', 'techrappy-seo' ); ?></a>
        &nbsp;|&nbsp;
        <a href="#" id="bj-deselect-all"><?php esc_html_e( 'Tout désélectionner', 'techrappy-seo' ); ?></a>
        <span id="bj-cities-count" style="float:right;color:#777;font-size:12px;"></span>
    </p>
</div>

<hr style="margin:20px 0;">

<div id="bj-menu-config">
    <h3 style="margin-top:0;"><?php esc_html_e( 'Gestion automatique des menus', 'techrappy-seo' ); ?></h3>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="bj-menu-action"><?php esc_html_e( 'Action sur les menus', 'techrappy-seo' ); ?></label>
            </th>
            <td>
                <select id="bj-menu-action" name="menu_action">
                    <option value="none"><?php esc_html_e( 'Ne rien faire', 'techrappy-seo' ); ?></option>
                    <option value="add_existing"><?php esc_html_e( 'Ajouter à un menu existant', 'techrappy-seo' ); ?></option>
                    <option value="create_new"><?php esc_html_e( 'Créer un nouveau menu', 'techrappy-seo' ); ?></option>
                </select>
            </td>
        </tr>
    </table>

    <div id="bj-menu-existing" style="display:none;">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bj-menu-id"><?php esc_html_e( 'Menu cible', 'techrappy-seo' ); ?></label>
                </th>
                <td>
                    <select id="bj-menu-id" name="menu_id">
                        <option value=""><?php esc_html_e( '— Sélectionner un menu —', 'techrappy-seo' ); ?></option>
                        <?php foreach ( $wp_menus as $m ) : ?>
                            <option value="<?php echo esc_attr( $m->term_id ); ?>">
                                <?php echo esc_html( $m->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <div id="bj-menu-new" style="display:none;">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bj-menu-name"><?php esc_html_e( 'Nom du menu', 'techrappy-seo' ); ?></label>
                </th>
                <td>
                    <input type="text" id="bj-menu-name" name="menu_name" class="regular-text"
                           placeholder="<?php esc_attr_e( 'Ex : Pages locales', 'techrappy-seo' ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bj-menu-location"><?php esc_html_e( 'Emplacement', 'techrappy-seo' ); ?></label>
                </th>
                <td>
                    <select id="bj-menu-location" name="menu_location">
                        <option value=""><?php esc_html_e( 'Aucun (définir manuellement)', 'techrappy-seo' ); ?></option>
                        <?php foreach ( get_registered_nav_menus() as $location => $description ) : ?>
                            <option value="<?php echo esc_attr( $location ); ?>">
                                <?php echo esc_html( $description ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <div id="bj-menu-label-config" style="display:none;">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bj-label-format"><?php esc_html_e( 'Format du libellé', 'techrappy-seo' ); ?></label>
                </th>
                <td>
                    <select id="bj-label-format" name="label_format">
                        <option value="post_title"><?php esc_html_e( 'Titre du post', 'techrappy-seo' ); ?></option>
                        <option value="keyword_city"><?php esc_html_e( 'Mot-clé + ville', 'techrappy-seo' ); ?></option>
                        <option value="custom"><?php esc_html_e( 'Modèle personnalisé', 'techrappy-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="bj-label-template-row" style="display:none;">
                <th scope="row">
                    <label for="bj-label-template"><?php esc_html_e( 'Modèle de libellé', 'techrappy-seo' ); ?></label>
                </th>
                <td>
                    <input type="text" id="bj-label-template" name="label_template" class="regular-text"
                           placeholder="{keyword} – {city}">
                    <p class="description">
                        <?php esc_html_e( 'Variables : {keyword}, {city}, {title}', 'techrappy-seo' ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div>

<script>
(function($){
    $('#bj-menu-action').on('change', function(){
        var val = $(this).val();
        $('#bj-menu-existing').toggle( val === 'add_existing' );
        $('#bj-menu-new').toggle( val === 'create_new' );
        $('#bj-menu-label-config').toggle( val !== 'none' );
    });
    $('#bj-label-format').on('change', function(){
        $('#bj-label-template-row').toggle( $(this).val() === 'custom' );
    });
})(jQuery);
</script>
