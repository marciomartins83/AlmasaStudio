<?php

namespace App\Tests\Entity;

use App\Entity\FiadoresInquilinos;
use PHPUnit\Framework\TestCase;

class FiadoresInquilinosTest extends TestCase
{
    public function testCreateFiadorInquilino(): void
    {
        $fiadorInquilino = new FiadoresInquilinos();
        $this->assertInstanceOf(FiadoresInquilinos::class, $fiadorInquilino);
    }

    public function testBasicMethods(): void
    {
        $fiadorInquilino = new FiadoresInquilinos();
        $this->assertTrue(method_exists($fiadorInquilino, 'getId'));
    }
}
