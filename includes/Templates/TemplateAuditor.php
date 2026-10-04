<?php
/**
 * Audit des templates Divi pour validation des tokens.
 *
 * @package TechrappySEO\Templates
 */

declare( strict_types=1 );

namespace TechrappySEO\Templates;

use TechrappySEO\Divi\TokenScanner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TemplateAuditor
 *
 * Responsabilité : analyser un template Divi et vérifier que tous
 * les tokens requis sont présents et correctement mappés.
 */
class TemplateAuditor {

    /**
     * Audite un template et retourne les tokens trouvés et manquants.
     *
     * @param int $post_id ID du post template Divi.
     *
     * @return array{
     *   tokens_found: array<string>,
     *   tokens_missing: array<string>,
     *   post_title: string,
     *   has_divi: bool,
     *   is_valid: bool
     * }
     */
    public function audit( int $post_id ): array {
        $base = [
            'tokens_found'   => [],
            'tokens_missing' => TokenScanner::REQUIRED_TOKENS,
            'post_title'     => '',
            'has_divi'       => false,
            'is_valid'       => false,
        ];

        if ( $post_id <= 0 ) {
            return $base;
        }

        $post = get_post( $post_id );

        if ( ! $post || empty( $post->post_content ) ) {
            return $base;
        }

        $scanner        = new TokenScanner();
        $tokens_found   = $scanner->scan( $post->post_content );
        $tokens_missing = $scanner->get_missing_required( $tokens_found );
        $has_divi       = str_contains( $post->post_content, '[et_pb_' );

        return [
            'tokens_found'   => $tokens_found,
            'tokens_missing' => $tokens_missing,
            'post_title'     => get_the_title( $post ),
            'has_divi'       => $has_divi,
            'is_valid'       => $has_divi && empty( $tokens_missing ),
        ];
    }
}
