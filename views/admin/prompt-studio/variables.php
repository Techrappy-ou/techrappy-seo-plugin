<?php
/**
 * Vue partielle : variables disponibles par prompt.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$vars_map = [
    'intent'         => [ '{{mot_cle}}', '{{profession}}', '{{type_contenu}}' ],
    'plan'           => [ '{{mot_cle}}', '{{profession}}', '{{intent_json}}', '{{city}}' ],
    'blocks_list'    => [ '{{plan_json}}' ],
    'intro'          => [ '{{mot_cle}}', '{{H1}}', '{{plan_json}}' ],
    'block_write'    => [ '{{mot_cle}}', '{{profession}}', '{{bloc_json}}' ],
    'conclusion_cta' => [ '{{mot_cle}}', '{{H1}}', '{{plan_json}}' ],
    'meta'           => [ '{{mot_cle}}', '{{H1}}', '{{intent_principale}}' ],
    'faq'            => [ '{{mot_cle}}', '{{plan_json}}' ],
    'internal_links' => [ '{{mot_cle}}', '{{pages_site_json}}' ],
    'anti_duplicate' => [ '{{keyword_base}}', '{{city}}' ],
    'qa'             => [ '{{mot_cle}}', '{{full_content_html}}' ],
    'system'         => [],
];
?>
<div id="ps-vars-container">
    <?php foreach ( $vars_map as $key => $vars ) : ?>
        <div class="ps-vars-group" data-prompt="<?php echo esc_attr( $key ); ?>" style="display:none;">
            <?php if ( ! empty( $vars ) ) : ?>
                <p style="margin:4px 0 6px;font-size:12px;color:#777;"><?php esc_html_e( 'Variables disponibles :', 'techrappy-seo' ); ?></p>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    <?php foreach ( $vars as $v ) : ?>
                        <code style="cursor:pointer;background:#f0f6ff;border:1px solid #c7d9f0;padding:2px 6px;border-radius:3px;font-size:12px;"
                              class="ps-insert-var"><?php echo esc_html( $v ); ?></code>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p style="font-size:12px;color:#999;"><?php esc_html_e( 'Aucune variable pour ce prompt.', 'techrappy-seo' ); ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
