<?php
/**
 * Vue partielle : panel de test d'un prompt.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div id="ps-tester">
    <h4><?php esc_html_e( 'Tester le prompt', 'techrappy-seo' ); ?></h4>
    <p class="description"><?php esc_html_e( 'Saisissez les valeurs des variables pour tester l\'appel OpenAI.', 'techrappy-seo' ); ?></p>
    <div id="ps-test-vars-inputs" style="margin-bottom:10px;"></div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="button button-primary" id="ps-btn-run-test">
            <?php esc_html_e( 'Lancer le test', 'techrappy-seo' ); ?>
        </button>
        <span class="spinner techrappy-spinner" id="ps-spinner-test"></span>
    </div>
    <div id="ps-test-output" style="display:none;margin-top:12px;">
        <p style="font-size:12px;color:#777;" id="ps-test-duration"></p>
        <pre id="ps-test-result"
             style="background:#f6f7f7;border:1px solid #ddd;padding:12px;overflow:auto;max-height:400px;font-size:12px;"></pre>
    </div>
</div>
