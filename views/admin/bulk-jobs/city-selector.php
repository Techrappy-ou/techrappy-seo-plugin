<?php
/**
 * Vue partielle : sélecteur de villes pour la génération en masse.
 * Inclus dans l'étape bulk du wizard si nécessaire.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
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
