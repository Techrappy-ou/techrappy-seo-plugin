<?php

declare( strict_types=1 );

namespace TechrappySEO\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use TechrappySEO\Utils\UuidGenerator;

/**
 * @covers \TechrappySEO\Utils\UuidGenerator
 */
class UuidGeneratorTest extends TestCase {

    public function test_generate_returns_string(): void {
        $uuid = UuidGenerator::generate();
        $this->assertIsString( $uuid );
    }

    public function test_generate_has_correct_format(): void {
        $uuid = UuidGenerator::generate();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
            'L\'UUID doit respecter le format UUID v4 RFC 4122.'
        );
    }

    public function test_generate_is_unique(): void {
        $uuids = [];
        for ( $i = 0; $i < 100; $i++ ) {
            $uuids[] = UuidGenerator::generate();
        }
        $this->assertSame(
            count( $uuids ),
            count( array_unique( $uuids ) ),
            '100 UUIDs générés doivent tous être uniques.'
        );
    }

    public function test_generate_version_is_4(): void {
        $uuid = UuidGenerator::generate();
        // Le 13e caractère (index 14) doit être '4'.
        $this->assertSame( '4', $uuid[14] );
    }

    public function test_generate_variant_is_rfc4122(): void {
        $uuid    = UuidGenerator::generate();
        $variant = hexdec( $uuid[19] );
        // Le variant RFC 4122 : les bits de poids fort doivent être 10xx,
        // soit une valeur entre 8 et b inclus.
        $this->assertGreaterThanOrEqual( 8, $variant );
        $this->assertLessThanOrEqual( 11, $variant );
    }
}
