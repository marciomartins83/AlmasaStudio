<?php

namespace App\Tests\Entity;

use App\Entity\PessoasDocumentos;
use App\Entity\Pessoas;
use App\Entity\TiposDocumentos;
use PHPUnit\Framework\TestCase;
use DateTime;

class PessoasDocumentosTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $documento = new PessoasDocumentos();

        // id should be null initially
        $this->assertNull($documento->getId());

        // Pessoa relationship
        $pessoaMock = $this->createMock(Pessoas::class);
        $this->assertSame($documento, $documento->setPessoa($pessoaMock));
        $this->assertSame($pessoaMock, $documento->getPessoa());

        // TipoDocumento relationship
        $tipoMock = $this->createMock(TiposDocumentos::class);
        $this->assertSame($documento, $documento->setTipoDocumento($tipoMock));
        $this->assertSame($tipoMock, $documento->getTipoDocumento());

        // NumeroDocumento
        $numero = '123456789';
        $this->assertSame($documento, $documento->setNumeroDocumento($numero));
        $this->assertSame($numero, $documento->getNumeroDocumento());

        // DataEmissao
        $dataEmissao = new DateTime('2023-01-01');
        $this->assertSame($documento, $documento->setDataEmissao($dataEmissao));
        $this->assertSame($dataEmissao, $documento->getDataEmissao());

        // DataVencimento
        $dataVencimento = new DateTime('2023-12-31');
        $this->assertSame($documento, $documento->setDataVencimento($dataVencimento));
        $this->assertSame($dataVencimento, $documento->getDataVencimento());

        // OrgaoEmissor
        $orgao = 'SSP';
        $this->assertSame($documento, $documento->setOrgaoEmissor($orgao));
        $this->assertSame($orgao, $documento->getOrgaoEmissor());

        // Observacoes
        $obs = 'Observação de teste';
        $this->assertSame($documento, $documento->setObservacoes($obs));
        $this->assertSame($obs, $documento->getObservacoes());

        // Ativo
        $this->assertSame($documento, $documento->setAtivo(true));
        $this->assertTrue($documento->getAtivo());

        $this->assertSame($documento, $documento->setAtivo(false));
        $this->assertFalse($documento->getAtivo());
    }

    public function testNullableFields(): void
    {
        $documento = new PessoasDocumentos();

        // Initially null
        $this->assertNull($documento->getDataEmissao());
        $this->assertNull($documento->getDataVencimento());
        $this->assertNull($documento->getOrgaoEmissor());
        $this->assertNull($documento->getObservacoes());

        // Set to null explicitly
        $this->assertSame($documento, $documento->setDataEmissao(null));
        $this->assertNull($documento->getDataEmissao());

        $this->assertSame($documento, $documento->setDataVencimento(null));
        $this->assertNull($documento->getDataVencimento());

        $this->assertSame($documento, $documento->setOrgaoEmissor(null));
        $this->assertNull($documento->getOrgaoEmissor());

        $this->assertSame($documento, $documento->setObservacoes(null));
        $this->assertNull($documento->getObservacoes());
    }
}
