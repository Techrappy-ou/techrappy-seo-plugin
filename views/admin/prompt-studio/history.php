<?php
/**
 * Vue partielle : historique des versions d'un prompt.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div id="ps-history">
    <p class="description"><?php esc_html_e( 'Historique des modifications (10 dernières versions conservées).', 'techrappy-seo' ); ?></p>
    <div id="ps-history-list"></div>
</div>
