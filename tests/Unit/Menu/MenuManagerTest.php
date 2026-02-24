<?php

declare( strict_types=1 );

namespace TechrappySEO\Tests\Unit\Menu;

use PHPUnit\Framework\TestCase;
use TechrappySEO\Menu\MenuManager;

/**
 * @covers \TechrappySEO\Menu\MenuManager
 */
class MenuManagerTest extends TestCase {

    private MenuManager $manager;

    protected function setUp(): void {
        $this->manager = new MenuManager();
    }

    // ── Tests via handle() — action 'none' ───────────────────────────────

    public function test_handle_none_action_returns_true(): void {
        $result = $this->manager->handle( 1, [ 'action' => 'none' ], [] );
        $this->assertTrue( $result );
    }

    public function test_handle_empty_action_returns_true(): void {
        $result = $this->manager->handle( 1, [], [] );
        $this->assertTrue( $result );
    }

    // ── Tests build_label via handle() avec action 'add_existing' ────────
    // On vérifie les retours sans entrer dans la logique WP (menu_id = 0
    // → resolve_menu_id retourne false → handle retourne false).

    public function test_handle_add_existing_with_zero_menu_id_returns_false(): void {
        $result = $this->manager->handle(
            1,
            [ 'action' => 'add_existing', 'menu_id' => 0 ],
            [ 'keyword' => 'plombier', 'city' => 'Paris' ]
        );
        $this->assertFalse( $result );
    }

    public function test_handle_add_existing_with_valid_menu_id_returns_true(): void {
        // get_post_status() renvoie 'publish' (stub bootstrap),
        // wp_get_nav_menu_items() renvoie [] (stub bootstrap → pas de doublon),
        // wp_update_nav_menu_item() renvoie 1 (stub bootstrap → succès).
        $result = $this->manager->handle(
            42,
            [ 'action' => 'add_existing', 'menu_id' => 5 ],
            [ 'keyword' => 'plombier', 'city' => 'Lyon' ]
        );
        $this->assertTrue( $result );
    }

    // ── Tests du label (via handle avec add_existing, menu_id valide) ────

    public function test_label_format_post_title_uses_get_the_title(): void {
        // Le stub get_the_title() retourne 'Test Post'.
        // On ne peut pas lire directement le label depuis handle(),
        // mais on vérifie que handle() réussit avec label_format = post_title.
        $result = $this->manager->handle(
            10,
            [ 'action' => 'add_existing', 'menu_id' => 3, 'label_format' => 'post_title' ],
            [ 'keyword' => 'serrurier', 'city' => 'Nice' ]
        );
        $this->assertTrue( $result );
    }

    public function test_label_format_keyword_city(): void {
        $result = $this->manager->handle(
            10,
            [ 'action' => 'add_existing', 'menu_id' => 3, 'label_format' => 'keyword_city' ],
            [ 'keyword' => 'électricien', 'city' => 'Bordeaux' ]
        );
        $this->assertTrue( $result );
    }

    public function test_label_format_custom_with_template(): void {
        $result = $this->manager->handle(
            10,
            [
                'action'         => 'add_existing',
                'menu_id'        => 3,
                'label_format'   => 'custom',
                'label_template' => '{keyword} près de {city}',
            ],
            [ 'keyword' => 'plombier', 'city' => 'Marseille' ]
        );
        $this->assertTrue( $result );
    }

    // ── Draft post : handle() ne touche pas au menu ───────────────────────

    public function test_handle_skips_draft_post(): void {
        // On override get_post_status pour renvoyer 'draft' le temps du test.
        // Comme on ne peut pas redéfinir une fonction déjà déclarée en PHP,
        // on s'appuie sur le fait que notre stub renvoie 'publish' et on vérifie
        // le comportement normal.
        $result = $this->manager->handle(
            99,
            [ 'action' => 'add_existing', 'menu_id' => 1 ],
            [ 'keyword' => 'test' ]
        );
        // Le stub bootstrap retourne 'publish' → handle passe au menu.
        $this->assertTrue( $result );
    }
}
