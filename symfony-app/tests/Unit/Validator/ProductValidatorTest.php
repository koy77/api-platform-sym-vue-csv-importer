<?php

namespace App\Tests\Unit\Validator;

use App\Validator\ProductValidator;
use App\Tests\TestCase;

class ProductValidatorTest extends TestCase
{
    private ProductValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductValidator();
    }

    public function testValidateValidProduct(): void
    {
        $rowData = [
            'Product Code' => 'P0001',
            'Product Name' => 'TV',
            'Product Description' => '32" TV',
            'Stock' => '10',
            'Cost in GBP' => '399.99',
            'Discontinued' => '',
        ];

        $result = $this->validator->validate($rowData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateMissingRequiredFields(): void
    {
        $rowData = [
            'Product Code' => '',
            'Product Name' => '',
            'Product Description' => '',
        ];

        $result = $this->validator->validate($rowData);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testCleanPriceWithCurrencySymbol(): void
    {
        $cleaned = $this->validator->cleanPrice('$4.33');
        $this->assertEquals('4.33', $cleaned);

        $cleaned = $this->validator->cleanPrice('£399.99');
        $this->assertEquals('399.99', $cleaned);

        $cleaned = $this->validator->cleanPrice('1,234.56');
        $this->assertEquals('1234.56', $cleaned);
    }

    public function testCleanPriceInvalid(): void
    {
        $cleaned = $this->validator->cleanPrice('invalid');
        $this->assertNull($cleaned);
    }
}

