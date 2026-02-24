<?php

declare( strict_types=1 );

namespace TechrappySEO\Tests\Unit\SEO;

use PHPUnit\Framework\TestCase;
use TechrappySEO\SEO\SlugGenerator;

/**
 * @covers \TechrappySEO\SEO\SlugGenerator
 */
class SlugGeneratorTest extends TestCase {

    private SlugGenerator $generator;

    protected function setUp(): void {
        $this->generator = new SlugGenerator();
    }

    public function test_generate_basic_keyword(): void {
        $slug = $this->generator->generate( 'plombier paris' );
        $this->assertSame( 'plombier-paris', $slug );
    }

    public function test_generate_with_city(): void {
        $slug = $this->generator->generate( 'plombier', 'Lyon' );
        $this->assertSame( 'plombier-lyon', $slug );
    }

    public function test_generate_removes_accents(): void {
        $slug = $this->generator->generate( 'Électricien à Nîmes' );
        $this->assertSame( 'electricien-a-nimes', $slug );
    }

    public function test_generate_empty_city_not_appended(): void {
        $slug = $this->generator->generate( 'menuisier', '' );
        $this->assertSame( 'menuisier', $slug );
    }

    public function test_generate_returns_string(): void {
        $slug = $this->generator->generate( 'test' );
        $this->assertIsString( $slug );
    }

    public function test_generate_no_uppercase(): void {
        $slug = $this->generator->generate( 'PLOMBIER URGENCE' );
        $this->assertSame( strtolower( $slug ), $slug );
    }

    public function test_generate_with_accented_city(): void {
        $slug = $this->generator->generate( 'serrurier', 'Île-de-France' );
        $this->assertSame( 'serrurier-ile-de-france', $slug );
    }

    public function test_generate_special_chars_become_dashes(): void {
        $slug = $this->generator->generate( 'service & maintenance' );
        // Les caractères spéciaux sont remplacés par des tirets
        $this->assertStringNotContainsString( '&', $slug );
        $this->assertStringNotContainsString( ' ', $slug );
    }
}
