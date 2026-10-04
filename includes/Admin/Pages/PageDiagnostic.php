<?php
/**
 * Contrôleur de la page Diagnostic.
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PageDiagnostic
 */
class PageDiagnostic {

    /**
     * Affiche la page de diagnostic.
     *
     * @return void
     */
    public function render(): void {
        include TECHRAPPY_SEO_VIEWS . 'admin/diagnostic/dashboard.php';
    }
}
