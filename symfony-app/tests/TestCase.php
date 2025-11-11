<?php

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TestCase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}

