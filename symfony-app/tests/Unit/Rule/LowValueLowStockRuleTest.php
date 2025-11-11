<?php

namespace App\Tests\Unit\Rule;

use App\Rule\LowValueLowStockRule;
use App\Validator\ProductValidator;
use App\Tests\TestCase;

class LowValueLowStockRuleTest extends TestCase
{
    private LowValueLowStockRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new LowValueLowStockRule(new ProductValidator());
    }

    public function testShouldSkipLowValueAndLowStock(): void
    {
        $productData = [
            'price' => '4.00',
            'stock' => 5,
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertTrue($result['should_skip']);
        $this->assertEquals('Cost < $5 and Stock < 10', $result['reason']);
    }

    public function testShouldNotSkipHighValue(): void
    {
        $productData = [
            'price' => '10.00',
            'stock' => 5,
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertFalse($result['should_skip']);
    }

    public function testShouldNotSkipHighStock(): void
    {
        $productData = [
            'price' => '4.00',
            'stock' => 15,
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertFalse($result['should_skip']);
    }

    public function testBoundaryConditions(): void
    {
        // Exactly $5 should not skip
        $productData = [
            'price' => '5.00',
            'stock' => 5,
        ];
        $result = $this->rule->shouldSkip($productData);
        $this->assertFalse($result['should_skip']);

        // Exactly 10 stock should not skip
        $productData = [
            'price' => '4.00',
            'stock' => 10,
        ];
        $result = $this->rule->shouldSkip($productData);
        $this->assertFalse($result['should_skip']);
    }
}

