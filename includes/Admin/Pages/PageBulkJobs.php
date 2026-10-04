<?php
/**
 * Page Admin : Jobs en masse (Bulk Jobs).
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PageBulkJobs
 */
class PageBulkJobs {

    /**
     * Rendu de la page.
     *
     * @return void
     */
    public function render(): void {
        require_once TECHRAPPY_SEO_PATH . 'views/admin/bulk-jobs/list.php';
    }
}
