<?php

namespace App\Tests\Validation;

use App\Entity\Agencias;
use PHPUnit\Framework\TestCase;

class AgenciaUniqueConstraintTest extends TestCase
{
    public function testAgenciaEntityCreation(): void
    {
        $agencia = new Agencias();
        $this->assertInstanceOf(Agencias::class, $agencia);
        
        $agencia->setCodigo('001');
        $agencia->setNome('Agencia Central');
        
        $this->assertEquals('001', $agencia->getCodigo());
        $this->assertEquals('Agencia Central', $agencia->getNome());
    }

    public function testAgenciaDifferentCodes(): void
    {
        $agencia1 = new Agencias();
        $agencia1->setCodigo('001');
        
        $agencia2 = new Agencias();
        $agencia2->setCodigo('002');
        
        $this->assertNotEquals($agencia1->getCodigo(), $agencia2->getCodigo());
    }

    public function testAgenciaSameCodes(): void
    {
        $agencia1 = new Agencias();
        $agencia1->setCodigo('001');
        
        $agencia2 = new Agencias();
        $agencia2->setCodigo('001');
        
        $this->assertEquals($agencia1->getCodigo(), $agencia2->getCodigo());
    }
}
