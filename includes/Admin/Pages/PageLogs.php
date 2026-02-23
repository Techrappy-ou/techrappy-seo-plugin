<?php
/**
 * Page Admin : Logs de génération.
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PageLogs
 */
class PageLogs {

    /**
     * Rendu de la page.
     *
     * @return void
     */
    public function render(): void {
        require_once TECHRAPPY_SEO_PATH . 'views/admin/logs/list.php';
    }
}
