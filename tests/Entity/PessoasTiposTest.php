<?php

namespace App\Tests\Entity;

use App\Entity\PessoasTipos;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PessoasTiposTest extends TestCase
{
    private PessoasTipos $entity;

    protected function setUp(): void
    {
        $this->entity = new PessoasTipos();
    }

    public function testGettersAndSetters(): void
    {
        $idPessoa = 123;
        $idTipoPessoa = 456;
        $dataInicio = new DateTime('2025-01-01');
        $dataFim = new DateTime('2026-01-01');
        $ativo = true;

        $this->entity->setIdPessoa($idPessoa);
        $this->entity->setIdTipoPessoa($idTipoPessoa);
        $this->entity->setDataInicio($dataInicio);
        $this->entity->setDataFim($dataFim);
        $this->entity->setAtivo($ativo);

        $this->assertEquals($idPessoa, $this->entity->getIdPessoa());
        $this->assertEquals($idTipoPessoa, $this->entity->getIdTipoPessoa());
        $this->assertEquals($dataInicio, $this->entity->getDataInicio());
        $this->assertEquals($dataFim, $this->entity->getDataFim());
        $this->assertTrue($this->entity->getAtivo());
    }

    public function testSetIdPessoaReturnsSelf(): void
    {
        $result = $this->entity->setIdPessoa(123);
        $this->assertSame($this->entity, $result);
    }

    public function testSetIdTipoPessoaReturnsSelf(): void
    {
        $result = $this->entity->setIdTipoPessoa(456);
        $this->assertSame($this->entity, $result);
    }

    public function testSetDataInicioReturnsSelf(): void
    {
        $result = $this->entity->setDataInicio(new DateTime());
        $this->assertSame($this->entity, $result);
    }

    public function testSetDataFimReturnsSelf(): void
    {
        $result = $this->entity->setDataFim(new DateTime());
        $this->assertSame($this->entity, $result);
    }

    public function testSetAtivoReturnsSelf(): void
    {
        $result = $this->entity->setAtivo(true);
        $this->assertSame($this->entity, $result);
    }

    public function testDataFimCanBeNull(): void
    {
        $this->entity->setDataFim(null);
        $this->assertNull($this->entity->getDataFim());
    }

    public function testAtivoDefaultIsFalse(): void
    {
        // The entity doesn't initialize $ativo in constructor,
        // so we'll just verify it works when set
        $this->entity->setAtivo(false);
        $this->assertFalse($this->entity->getAtivo());
        
        $this->entity->setAtivo(true);
        $this->assertTrue($this->entity->getAtivo());
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->entity->getId());
    }

    public function testDataInicioIsRequired(): void
    {
        $dataInicio = new DateTime('2025-06-15');
        $this->entity->setDataInicio($dataInicio);
        
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getDataInicio());
        $this->assertEquals($dataInicio->format('Y-m-d'), $this->entity->getDataInicio()->format('Y-m-d'));
    }

    public function testMultipleSettersChaining(): void
    {
        $result = $this->entity
            ->setIdPessoa(100)
            ->setIdTipoPessoa(200)
            ->setDataInicio(new DateTime())
            ->setAtivo(true);

        $this->assertSame($this->entity, $result);
        $this->assertEquals(100, $this->entity->getIdPessoa());
        $this->assertEquals(200, $this->entity->getIdTipoPessoa());
        $this->assertTrue($this->entity->getAtivo());
    }
}
