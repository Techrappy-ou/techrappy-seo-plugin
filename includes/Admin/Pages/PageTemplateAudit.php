<?php
/**
 * Page Admin : Audit de templates.
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PageTemplateAudit
 */
class PageTemplateAudit {

    /**
     * Rendu de la page.
     *
     * @return void
     */
    public function render(): void {
        require_once TECHRAPPY_SEO_PATH . 'views/admin/template-audit/audit-result.php';
    }
}
