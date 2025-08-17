<?php

namespace App\Tests\Entity;

use App\Entity\FormasRetirada;
use PHPUnit\Framework\TestCase;

class FormasRetiradaTest extends TestCase
{
    public function testCreateFormaRetirada(): void
    {
        $forma = new FormasRetirada();
        $this->assertInstanceOf(FormasRetirada::class, $forma);
    }

    public function testBasicMethods(): void
    {
        $forma = new FormasRetirada();
        $this->assertTrue(method_exists($forma, 'getId'));
    }
}
