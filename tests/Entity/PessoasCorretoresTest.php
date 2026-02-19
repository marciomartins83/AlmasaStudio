<?php

namespace App\Tests\Entity;

use App\Entity\PessoasCorretores;
use App\Entity\Pessoas;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeInterface;

class PessoasCorretoresTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $entity = new PessoasCorretores();

        // id should be null
        $this->assertNull($entity->getId(), 'ID should be null on new entity');

        // pessoa should be null
        $this->assertNull($entity->getPessoa(), 'Pessoa should be null on new entity');

        // creci, usuario, status should be null
        $this->assertNull($entity->getCreci(), 'Creci should be null on new entity');
        $this->assertNull($entity->getUsuario(), 'Usuario should be null on new entity');
        $this->assertNull($entity->getStatus(), 'Status should be null on new entity');

        // dataCadastro should be a DateTimeInterface instance
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getDataCadastro(), 'dataCadastro should be a DateTimeInterface');

        // ativo should be true
        $this->assertTrue($entity->isAtivo(), 'ativo should be true by default');
    }

    public function testSetGetPessoa(): void
    {
        $entity = new PessoasCorretores();
        $pessoaMock = $this->createMock(Pessoas::class);

        $return = $entity->setPessoa($pessoaMock);
        $this->assertSame($entity, $return, 'setPessoa should return $this');

        $this->assertSame($pessoaMock, $entity->getPessoa(), 'getPessoa should return the set object');
    }

    public function testSetGetCreci(): void
    {
        $entity = new PessoasCorretores();
        $creci = 'CRECI-12345';

        $return = $entity->setCreci($creci);
        $this->assertSame($entity, $return, 'setCreci should return $this');

        $this->assertSame($creci, $entity->getCreci(), 'getCreci should return the set value');
    }

    public function testSetGetUsuario(): void
    {
        $entity = new PessoasCorretores();
        $usuario = 'usuario_test';

        $return = $entity->setUsuario($usuario);
        $this->assertSame($entity, $return, 'setUsuario should return $this');

        $this->assertSame($usuario, $entity->getUsuario(), 'getUsuario should return the set value');
    }

    public function testSetGetStatus(): void
    {
        $entity = new PessoasCorretores();
        $status = 'active';

        $return = $entity->setStatus($status);
        $this->assertSame($entity, $return, 'setStatus should return $this');

        $this->assertSame($status, $entity->getStatus(), 'getStatus should return the set value');
    }

    public function testSetGetDataCadastro(): void
    {
        $entity = new PessoasCorretores();
        $date = new DateTime('2023-01-01 12:00:00');

        $return = $entity->setDataCadastro($date);
        $this->assertSame($entity, $return, 'setDataCadastro should return $this');

        $this->assertSame($date, $entity->getDataCadastro(), 'getDataCadastro should return the set date');
    }

    public function testSetGetAtivo(): void
    {
        $entity = new PessoasCorretores();

        // default is true
        $this->assertTrue($entity->isAtivo(), 'ativo should be true by default');

        $return = $entity->setAtivo(false);
        $this->assertSame($entity, $return, 'setAtivo should return $this');

        $this->assertFalse($entity->isAtivo(), 'ativo should be false after setAtivo(false)');
    }

    public function testGetIdAlwaysNull(): void
    {
        $entity = new PessoasCorretores();

        $this->assertNull($entity->getId(), 'ID should be null initially');

        // Set other fields
        $entity->setCreci('CRECI-123');
        $entity->setUsuario('user');
        $entity->setStatus('active');
        $entity->setDataCadastro(new DateTime());
        $entity->setAtivo(false);
        $pessoaMock = $this->createMock(Pessoas::class);
        $entity->setPessoa($pessoaMock);

        // ID should still be null
        $this->assertNull($entity->getId(), 'ID should remain null after setting other fields');
    }
}
