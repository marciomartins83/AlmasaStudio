<?php

namespace App\Tests\Entity;

use App\Entity\ContasVinculadas;
use PHPUnit\Framework\TestCase;

class ContasVinculadasTest extends TestCase
{
    public function testCreateContaVinculada(): void
    {
        $conta = new ContasVinculadas();
        $this->assertInstanceOf(ContasVinculadas::class, $conta);
    }

    public function testBasicMethods(): void
    {
        $conta = new ContasVinculadas();
        $this->assertTrue(method_exists($conta, 'getId'));
    }
}
