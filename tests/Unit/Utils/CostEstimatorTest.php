<?php

declare( strict_types=1 );

namespace TechrappySEO\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use TechrappySEO\Utils\CostEstimator;

/**
 * @covers \TechrappySEO\Utils\CostEstimator
 */
class CostEstimatorTest extends TestCase {

    public function test_estimate_gpt4o_zero_tokens(): void {
        $cost = CostEstimator::estimate( 'gpt-4o', 0, 0 );
        $this->assertSame( 0.0, $cost );
    }

    public function test_estimate_gpt4o_known_values(): void {
        // 1000 tokens input  = 1000/1000 * 0.005 = 0.005
        // 1000 tokens output = 1000/1000 * 0.015 = 0.015
        // total = 0.02
        $cost = CostEstimator::estimate( 'gpt-4o', 1000, 1000 );
        $this->assertSame( 0.02, $cost );
    }

    public function test_estimate_gpt4o_mini_known_values(): void {
        // 2000 input  = 2000/1000 * 0.00015 = 0.0003
        // 500  output = 500/1000  * 0.0006  = 0.0003
        // total = 0.0006
        $cost = CostEstimator::estimate( 'gpt-4o-mini', 2000, 500 );
        $this->assertSame( 0.0006, $cost );
    }

    public function test_estimate_returns_float(): void {
        $cost = CostEstimator::estimate( 'gpt-4o', 500, 200 );
        $this->assertIsFloat( $cost );
    }

    public function test_estimate_rounds_to_6_decimals(): void {
        $cost   = CostEstimator::estimate( 'gpt-4o', 1, 1 );
        $places = strlen( rtrim( substr( (string) $cost, strpos( (string) $cost, '.' ) + 1 ), '0' ) );
        $this->assertLessThanOrEqual( 6, $places );
    }

    public function test_estimate_unknown_model_falls_back_to_gpt4o(): void {
        $cost_unknown = CostEstimator::estimate( 'modele-inexistant', 1000, 1000 );
        $cost_gpt4o   = CostEstimator::estimate( 'gpt-4o', 1000, 1000 );
        $this->assertSame( $cost_gpt4o, $cost_unknown );
    }

    public function test_estimate_gpt4_turbo(): void {
        // 1000 input  = 1000/1000 * 0.01 = 0.01
        // 1000 output = 1000/1000 * 0.03 = 0.03
        // total = 0.04
        $cost = CostEstimator::estimate( 'gpt-4-turbo', 1000, 1000 );
        $this->assertSame( 0.04, $cost );
    }

    public function test_estimate_is_positive(): void {
        $cost = CostEstimator::estimate( 'gpt-4o', 100, 100 );
        $this->assertGreaterThan( 0.0, $cost );
    }
}
