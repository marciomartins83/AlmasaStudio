<?php

namespace App\Tests\Entity;

use App\Entity\Pessoas;
use App\Entity\PessoasCorretoras;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PessoasCorretorasTest extends TestCase
{
    private PessoasCorretoras $entity;

    protected function setUp(): void
    {
        $this->entity = new PessoasCorretoras();
    }

    public function testGettersAndSetters(): void
    {
        $pessoa = new Pessoas();
        $reflection = new \ReflectionClass($pessoa);
        $idProperty = $reflection->getProperty('idpessoa');
        $idProperty->setAccessible(true);
        $idProperty->setValue($pessoa, 123);

        $this->entity->setPessoa($pessoa);

        $this->assertEquals($pessoa, $this->entity->getPessoa());
        $this->assertSame($pessoa, $this->entity->getPessoa());
    }

    public function testSetPessoaNull(): void
    {
        $this->entity->setPessoa(null);
        $this->assertNull($this->entity->getPessoa());
    }

    public function testDefaultValuesBeforePersist(): void
    {
        $this->assertNull($this->entity->getCreatedAt(), 'createdAt should be null before persist');
        $this->assertNull($this->entity->getUpdatedAt(), 'updatedAt should be null before persist');
    }

    public function testOnPrePersist(): void
    {
        $this->entity->onPrePersist();

        $createdAt = $this->entity->getCreatedAt();
        $updatedAt = $this->entity->getUpdatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);
        $this->assertInstanceOf(DateTimeInterface::class, $updatedAt);
        // Compare to second precision since there can be microsecond differences
        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $updatedAt->format('Y-m-d H:i:s'));
    }

    public function testOnPreUpdate(): void
    {
        $this->entity->onPrePersist();
        $originalUpdatedAt = $this->entity->getUpdatedAt();

        sleep(1);

        $this->entity->onPreUpdate();
        $newUpdatedAt = $this->entity->getUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
        $this->assertInstanceOf(DateTimeInterface::class, $newUpdatedAt);
    }

    public function testSetPessoaReturnsSelf(): void
    {
        $pessoa = new Pessoas();
        $result = $this->entity->setPessoa($pessoa);

        $this->assertSame($this->entity, $result);
    }
}
