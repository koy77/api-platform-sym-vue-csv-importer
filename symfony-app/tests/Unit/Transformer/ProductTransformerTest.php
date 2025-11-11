<?php

namespace App\Tests\Unit\Transformer;

use App\Transformer\ProductTransformer;
use App\Service\DiscontinuedHandler;
use App\Validator\ProductValidator;
use App\Tests\TestCase;

class ProductTransformerTest extends TestCase
{
    private ProductTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new ProductTransformer(
            new ProductValidator(),
            new DiscontinuedHandler()
        );
    }

    public function testTransformValidProduct(): void
    {
        $rowData = [
            'Product Code' => 'P0001',
            'Product Name' => 'TV',
            'Product Description' => '32" TV',
            'Stock' => '10',
            'Cost in GBP' => '399.99',
            'Discontinued' => '',
        ];

        $result = $this->transformer->transform($rowData);

        $this->assertNotNull($result['product']);
        $this->assertEquals('P0001', $result['product']->getProductCode());
        $this->assertEquals('TV', $result['product']->getProductName());
        $this->assertEquals(10, $result['product']->getStock());
        $this->assertEquals('399.99', $result['product']->getPrice());
        $this->assertNull($result['product']->getDiscontinued());
    }

    public function testTransformDiscontinuedProduct(): void
    {
        $rowData = [
            'Product Code' => 'P0002',
            'Product Name' => 'CD Player',
            'Product Description' => 'Nice CD player',
            'Stock' => '11',
            'Cost in GBP' => '50.12',
            'Discontinued' => 'yes',
        ];

        $result = $this->transformer->transform($rowData);

        $this->assertNotNull($result['product']->getDiscontinued());
    }

    public function testTransformPriceWithCurrencySymbol(): void
    {
        $rowData = [
            'Product Code' => 'P0003',
            'Product Name' => 'Bluray Player',
            'Product Description' => 'Watch it in HD',
            'Stock' => '32',
            'Cost in GBP' => '$4.33',
            'Discontinued' => '',
        ];

        $result = $this->transformer->transform($rowData);

        $this->assertEquals('4.33', $result['product']->getPrice());
    }
}

