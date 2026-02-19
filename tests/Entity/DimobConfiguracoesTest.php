<?php

namespace App\Tests\Entity;

use App\Entity\DimobConfiguracoes;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class DimobConfiguracoesTest extends TestCase
{
    private DimobConfiguracoes $entity;

    protected function setUp(): void
    {
        $this->entity = new DimobConfiguracoes();
    }

    public function testConstructorDefaults(): void
    {
        $entity = new DimobConfiguracoes();
        $this->assertNotNull($entity);
    }

    public function testGettersAndSetters(): void
    {
        $ano = 2025;
        $cnpjDeclarante = '12.345.678/0001-90';
        $cpfResponsavel = '123.456.789-00';
        $codigoCidade = '3550308';
        $declaracaoRetificadora = true;
        $situacaoEspecial = true;
        $dataGeracao = new DateTime();

        $this->entity->setAno($ano);
        $this->entity->setCnpjDeclarante($cnpjDeclarante);
        $this->entity->setCpfResponsavel($cpfResponsavel);
        $this->entity->setCodigoCidade($codigoCidade);
        $this->entity->setDeclaracaoRetificadora($declaracaoRetificadora);
        $this->entity->setSituacaoEspecial($situacaoEspecial);
        $this->entity->setDataGeracao($dataGeracao);

        $this->assertSame($ano, $this->entity->getAno());
        $this->assertSame($cnpjDeclarante, $this->entity->getCnpjDeclarante());
        $this->assertSame($cpfResponsavel, $this->entity->getCpfResponsavel());
        $this->assertSame($codigoCidade, $this->entity->getCodigoCidade());
        $this->assertTrue($this->entity->isDeclaracaoRetificadora());
        $this->assertTrue($this->entity->isSituacaoEspecial());
        $this->assertEquals($dataGeracao, $this->entity->getDataGeracao());
    }

    public function testBusinessLogicMethods(): void
    {
        $this->entity->setSituacaoEspecial(false);
        $this->assertSame('N', $this->entity->getIndicadorSituacaoEspecial());

        $this->entity->setSituacaoEspecial(true);
        $this->assertSame('S', $this->entity->getIndicadorSituacaoEspecial());
    }

    public function testNumericExtractionMethods(): void
    {
        $this->entity->setCnpjDeclarante('12.345.678/0001-90');
        $this->assertSame('12345678000190', $this->entity->getCnpjDeclaranteNumeros());

        $this->entity->setCpfResponsavel('123.456.789-00');
        $this->assertSame('12345678900', $this->entity->getCpfResponsavelNumeros());
    }

    public function testSettersReturnSelf(): void
    {
        $result = $this->entity->setAno(2025);
        $this->assertSame($this->entity, $result);

        $result = $this->entity->setCnpjDeclarante('12.345.678/0001-90');
        $this->assertSame($this->entity, $result);

        $result = $this->entity->setSituacaoEspecial(true);
        $this->assertSame($this->entity, $result);
    }

    public function testTimestampFields(): void
    {
        $createdAt = new DateTime();
        $updatedAt = new DateTime();

        $this->entity->setCreatedAt($createdAt);
        $this->entity->setUpdatedAt($updatedAt);

        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getUpdatedAt());
    }

    public function testDataGeracaoNullable(): void
    {
        $this->entity->setDataGeracao(null);
        $this->assertNull($this->entity->getDataGeracao());

        $dataGeracao = new DateTime();
        $this->entity->setDataGeracao($dataGeracao);
        $this->assertEquals($dataGeracao, $this->entity->getDataGeracao());
    }
}
