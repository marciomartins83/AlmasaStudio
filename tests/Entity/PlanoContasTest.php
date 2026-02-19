<?php

namespace App\Tests\Entity;

use App\Entity\PlanoContas;
use Doctrine\Common\Collections\Collection;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PlanoContasTest extends TestCase
{
    private PlanoContas $plano;

    protected function setUp(): void
    {
        $this->plano = new PlanoContas();
    }

    public function testConstructor(): void
    {
        $plano = new PlanoContas();
        $this->assertNotNull($plano);
    }

    public function testGettersAndSetters(): void
    {
        $codigo = 'ACC001';
        $descricao = 'Ativa Circulante';
        $tipo = 1;
        $codigoContabil = '1.0.0.00.00';

        $this->plano->setCodigo($codigo);
        $this->plano->setDescricao($descricao);
        $this->plano->setTipo($tipo);
        $this->plano->setCodigoContabil($codigoContabil);

        $this->assertSame($codigo, $this->plano->getCodigo());
        $this->assertSame($descricao, $this->plano->getDescricao());
        $this->assertSame($tipo, $this->plano->getTipo());
        $this->assertSame($codigoContabil, $this->plano->getCodigoContabil());
    }

    public function testTipoAsInteger(): void
    {
        $tipos = [1, 2, 3, 4, 5];

        foreach ($tipos as $tipo) {
            $this->plano->setTipo($tipo);
            $this->assertSame($tipo, $this->plano->getTipo());
        }
    }

    public function testBooleanFields(): void
    {
        $this->plano->setIncideTaxaAdmin(true);
        $this->plano->setIncideIr(true);
        $this->plano->setEntraInforme(true);
        $this->plano->setEntraDesconto(true);
        $this->plano->setEntraMulta(true);
        $this->plano->setAtivo(true);

        // Just verify setters return self (no getters for these)
        $result = $this->plano->setIncideTaxaAdmin(false);
        $this->assertSame($this->plano, $result);
    }

    public function testToString(): void
    {
        $codigo = 'ACC001';
        $descricao = 'Ativa Circulante';

        $this->plano->setCodigo($codigo);
        $this->plano->setDescricao($descricao);

        $this->assertStringContainsString($codigo, (string)$this->plano);
        $this->assertStringContainsString($descricao, (string)$this->plano);
    }

    public function testLancamentosCollection(): void
    {
        $lancamentos = $this->plano->getLancamentos();
        $this->assertInstanceOf(Collection::class, $lancamentos);
        $this->assertCount(0, $lancamentos);
    }

    public function testInformesRendimentosCollection(): void
    {
        $informes = $this->plano->getInformesRendimentos();
        $this->assertInstanceOf(Collection::class, $informes);
        $this->assertCount(0, $informes);
    }

    public function testSettersReturnSelf(): void
    {
        $result = $this->plano->setCodigo('ACC001');
        $this->assertSame($this->plano, $result);

        $result = $this->plano->setDescricao('Ativa Circulante');
        $this->assertSame($this->plano, $result);

        $result = $this->plano->setTipo(1);
        $this->assertSame($this->plano, $result);

        $result = $this->plano->setCodigoContabil('1.0.0.00.00');
        $this->assertSame($this->plano, $result);
    }

    public function testGetId(): void
    {
        $this->assertNull($this->plano->getId());
    }

    public function testCodigoContabilNullable(): void
    {
        $this->plano->setCodigoContabil(null);
        $this->assertNull($this->plano->getCodigoContabil());

        $this->plano->setCodigoContabil('1.0.0.00.00');
        $this->assertSame('1.0.0.00.00', $this->plano->getCodigoContabil());
    }

    public function testTimestampFields(): void
    {
        $createdAt = new DateTime();
        $updatedAt = new DateTime();

        $this->plano->setCreatedAt($createdAt);
        $this->plano->setUpdatedAt($updatedAt);

        $this->assertInstanceOf(DateTimeInterface::class, $this->plano->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->plano->getUpdatedAt());
    }

    public function testGetTipoDescricao(): void
    {
        // getTipoDescricao() returns description of tipo
        // (without database, we just verify it doesn't error)
        $this->plano->setTipo(1);
        $descricao = $this->plano->getTipoDescricao();
        $this->assertIsString($descricao);
    }
}
