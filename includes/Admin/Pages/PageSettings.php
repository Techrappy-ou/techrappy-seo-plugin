<?php
/**
 * Page Admin : Réglages (Settings).
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PageSettings
 */
class PageSettings {

    /**
     * Rendu de la page.
     *
     * @return void
     */
    public function render(): void {
        require_once TECHRAPPY_SEO_PATH . 'views/admin/settings/settings-form.php';
    }
}
