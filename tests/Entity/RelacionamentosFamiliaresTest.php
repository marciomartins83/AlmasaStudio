<?php

namespace App\Tests\Entity;

use App\Entity\RelacionamentosFamiliares;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class RelacionamentosFamiliaresTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $entity = new RelacionamentosFamiliares();

        // id is not settable, should be null
        $this->assertNull($entity->getId());

        // Set and get idPessoaOrigem
        $entity->setIdPessoaOrigem(123);
        $this->assertSame(123, $entity->getIdPessoaOrigem());

        // Set and get idPessoaDestino
        $entity->setIdPessoaDestino(456);
        $this->assertSame(456, $entity->getIdPessoaDestino());

        // Set and get tipoRelacionamento
        $entity->setTipoRelacionamento('filho');
        $this->assertSame('filho', $entity->getTipoRelacionamento());

        // Set and get idRegimeCasamento (nullable)
        $entity->setIdRegimeCasamento(789);
        $this->assertSame(789, $entity->getIdRegimeCasamento());

        // Set and get idRegimeCasamento null
        $entity->setIdRegimeCasamento(null);
        $this->assertNull($entity->getIdRegimeCasamento());

        // Set and get dataInicio
        $inicio = new DateTimeImmutable('2023-01-01');
        $entity->setDataInicio($inicio);
        $this->assertSame($inicio, $entity->getDataInicio());

        // Set and get dataFim
        $fim = new DateTimeImmutable('2023-12-31');
        $entity->setDataFim($fim);
        $this->assertSame($fim, $entity->getDataFim());

        // Set and get ativo
        $entity->setAtivo(true);
        $this->assertTrue($entity->getAtivo());

        $entity->setAtivo(false);
        $this->assertFalse($entity->getAtivo());
    }

    public function testCollectionOperations(): void
    {
        $collection = new ArrayCollection();

        $entity1 = new RelacionamentosFamiliares();
        $entity1->setIdPessoaOrigem(1);
        $entity1->setIdPessoaDestino(2);
        $entity1->setTipoRelacionamento('irmao');
        $entity1->setAtivo(true);

        $entity2 = new RelacionamentosFamiliares();
        $entity2->setIdPessoaOrigem(3);
        $entity2->setIdPessoaDestino(4);
        $entity2->setTipoRelacionamento('filha');
        $entity2->setAtivo(false);

        // Add entities
        $collection->add($entity1);
        $collection->add($entity2);

        $this->assertCount(2, $collection);
        $this->assertSame($entity1, $collection->first());
        $this->assertSame($entity2, $collection->last());

        // Remove entity
        $collection->removeElement($entity1);
        $this->assertCount(1, $collection);
        $this->assertSame($entity2, $collection->first());

        // Clear collection
        $collection->clear();
        $this->assertCount(0, $collection);
    }

    public function testBusinessLogicMethods(): void
    {
        // The entity currently has no business logic methods.
        // This test ensures that the class can be instantiated and used without errors.
        $entity = new RelacionamentosFamiliares();
        $this->assertInstanceOf(RelacionamentosFamiliares::class, $entity);
    }
}
