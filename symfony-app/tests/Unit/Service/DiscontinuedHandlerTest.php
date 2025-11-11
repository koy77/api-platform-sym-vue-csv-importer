<?php

namespace App\Tests\Unit\Service;

use App\Service\DiscontinuedHandler;
use App\Tests\TestCase;

class DiscontinuedHandlerTest extends TestCase
{
    private DiscontinuedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new DiscontinuedHandler();
    }

    public function testHandleDiscontinuedYes(): void
    {
        $rowData = ['Discontinued' => 'yes'];

        $result = $this->handler->handleDiscontinued($rowData);

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
    }

    public function testHandleDiscontinuedEmpty(): void
    {
        $rowData = ['Discontinued' => ''];

        $result = $this->handler->handleDiscontinued($rowData);

        $this->assertNull($result);
    }

    public function testHandleDiscontinuedCaseInsensitive(): void
    {
        $rowData = ['Discontinued' => 'YES'];

        $result = $this->handler->handleDiscontinued($rowData);

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
    }
}

