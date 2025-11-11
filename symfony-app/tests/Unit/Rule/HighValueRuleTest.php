<?php

namespace App\Tests\Unit\Rule;

use App\Rule\HighValueRule;
use App\Tests\TestCase;

class HighValueRuleTest extends TestCase
{
    private HighValueRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new HighValueRule();
    }

    public function testShouldSkipHighValue(): void
    {
        $productData = [
            'price' => '1200.00',
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertTrue($result['should_skip']);
        $this->assertEquals('Cost > $1000', $result['reason']);
    }

    public function testShouldNotSkipLowValue(): void
    {
        $productData = [
            'price' => '500.00',
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertFalse($result['should_skip']);
    }

    public function testBoundaryCondition(): void
    {
        // Exactly $1000 should not skip
        $productData = [
            'price' => '1000.00',
        ];

        $result = $this->rule->shouldSkip($productData);

        $this->assertFalse($result['should_skip']);
    }
}

