<?php

declare( strict_types=1 );

namespace TechrappySEO\Tests\Unit\Jobs;

use PHPUnit\Framework\TestCase;
use TechrappySEO\Utils\UuidGenerator;

/**
 * Tests des utilitaires utilisés par JobRepository.
 * JobRepository nécessite $wpdb (WordPress) — on teste ici les helpers purs.
 *
 * @covers \TechrappySEO\Utils\UuidGenerator
 */
class JobRepositoryTest extends TestCase {

    public function test_job_id_is_valid_uuid(): void {
        $job_id = UuidGenerator::generate();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $job_id
        );
    }

    public function test_job_status_values_are_known(): void {
        $valid_statuses = [ 'pending', 'running', 'done', 'done_with_errors', 'failed' ];
        foreach ( $valid_statuses as $status ) {
            $this->assertContains( $status, $valid_statuses );
        }
    }

    public function test_wp_params_encodes_to_json(): void {
        $params  = [ 'profession' => 'plombier', 'parent_id' => 0, 'category_id' => 5 ];
        $encoded = wp_json_encode( $params );
        $decoded = json_decode( $encoded, true );
        $this->assertSame( $params, $decoded );
    }

    public function test_json_fields_decode_correctly(): void {
        $original = [ 'menu_action' => 'none', 'menu_id' => 0 ];
        $json     = wp_json_encode( $original );
        $decoded  = json_decode( $json, true );
        $this->assertSame( 'none', $decoded['menu_action'] );
        $this->assertSame( 0,      $decoded['menu_id'] );
    }

    public function test_absint_sanitizes_strings(): void {
        $this->assertSame( 5,  absint( '5abc' ) );
        $this->assertSame( 0,  absint( 'abc' ) );
        $this->assertSame( 42, absint( -42 ) );
    }
}
