<?php
/**
 * Page Admin : Prompt Studio.
 *
 * @package TechrappySEO\Admin\Pages
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PagePromptStudio
 */
class PagePromptStudio {

    /**
     * Rendu de la page.
     *
     * @return void
     */
    public function render(): void {
        require_once TECHRAPPY_SEO_PATH . 'views/admin/prompt-studio/editor.php';
    }
}
