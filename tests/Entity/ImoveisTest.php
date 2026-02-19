<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Imoveis;
use App\Entity\TiposImoveis;
use App\Entity\Enderecos;
use App\Entity\Condominios;
use App\Entity\Pessoas;
use App\Entity\ImoveisPropriedades;
use App\Entity\ImoveisMedidores;
use App\Entity\ImoveisGarantias;
use App\Entity\ImoveisFotos;
use App\Entity\ImoveisContratos;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use DateTimeInterface;
use DateTime;

final class ImoveisTest extends TestCase
{
    private function createEntity(): Imoveis
    {
        return new Imoveis();
    }

    // ---------- IDENTIFICAÇÃO ----------
    public function testGetSetCodigoInterno(): void
    {
        $entity = $this->createEntity();
        $entity->setCodigoInterno('ABC123');
        $this->assertSame('ABC123', $entity->getCodigoInterno());
    }

    // ---------- RELACIONAMENTOS ----------
    public function testGetSetTipoImovel(): void
    {
        $entity = $this->createEntity();
        $tipo = $this->createMock(TiposImoveis::class);
        $entity->setTipoImovel($tipo);
        $this->assertSame($tipo, $entity->getTipoImovel());
    }

    public function testGetSetEndereco(): void
    {
        $entity = $this->createEntity();
        $endereco = $this->createMock(Enderecos::class);
        $entity->setEndereco($endereco);
        $this->assertSame($endereco, $entity->getEndereco());
    }

    public function testGetSetCondominio(): void
    {
        $entity = $this->createEntity();
        $condominio = $this->createMock(Condominios::class);
        $entity->setCondominio($condominio);
        $this->assertSame($condominio, $entity->getCondominio());
    }

    public function testGetSetPessoaProprietario(): void
    {
        $entity = $this->createEntity();
        $pessoa = $this->createMock(Pessoas::class);
        $entity->setPessoaProprietario($pessoa);
        $this->assertSame($pessoa, $entity->getPessoaProprietario());
    }

    public function testGetSetPessoaFiador(): void
    {
        $entity = $this->createEntity();
        $pessoa = $this->createMock(Pessoas::class);
        $entity->setPessoaFiador($pessoa);
        $this->assertSame($pessoa, $entity->getPessoaFiador());
    }

    public function testGetSetPessoaCorretor(): void
    {
        $entity = $this->createEntity();
        $pessoa = $this->createMock(Pessoas::class);
        $entity->setPessoaCorretor($pessoa);
        $this->assertSame($pessoa, $entity->getPessoaCorretor());
    }

    // ---------- SITUAÇÃO ----------
    public function testGetSetSituacao(): void
    {
        $entity = $this->createEntity();
        $entity->setSituacao('Vendido');
        $this->assertSame('Vendido', $entity->getSituacao());
    }

    public function testGetSetTipoUtilizacao(): void
    {
        $entity = $this->createEntity();
        $entity->setTipoUtilizacao('Residencial');
        $this->assertSame('Residencial', $entity->getTipoUtilizacao());
    }

    public function testGetSetOcupacao(): void
    {
        $entity = $this->createEntity();
        $entity->setOcupacao('Alugado');
        $this->assertSame('Alugado', $entity->getOcupacao());
    }

    public function testGetSetSituacaoFinanceira(): void
    {
        $entity = $this->createEntity();
        $entity->setSituacaoFinanceira('Em dia');
        $this->assertSame('Em dia', $entity->getSituacaoFinanceira());
    }

    public function testIsAluguelGarantido(): void
    {
        $entity = $this->createEntity();
        $entity->setAluguelGarantido(true);
        $this->assertTrue($entity->isAluguelGarantido());
    }

    public function testIsDisponivelAluguel(): void
    {
        $entity = $this->createEntity();
        $entity->setDisponivelAluguel(true);
        $this->assertTrue($entity->isDisponivelAluguel());
    }

    public function testIsDisponivelVenda(): void
    {
        $entity = $this->createEntity();
        $entity->setDisponivelVenda(true);
        $this->assertTrue($entity->isDisponivelVenda());
    }

    public function testIsDisponivelTemporada(): void
    {
        $entity = $this->createEntity();
        $entity->setDisponivelTemporada(true);
        $this->assertTrue($entity->isDisponivelTemporada());
    }

    // ---------- CARACTERÍSTICAS FÍSICAS ----------
    public function testGetSetAreaTotal(): void
    {
        $entity = $this->createEntity();
        $entity->setAreaTotal('120.50');
        $this->assertSame('120.50', $entity->getAreaTotal());
    }

    public function testGetSetAreaConstruida(): void
    {
        $entity = $this->createEntity();
        $entity->setAreaConstruida('110.00');
        $this->assertSame('110.00', $entity->getAreaConstruida());
    }

    public function testGetSetAreaPrivativa(): void
    {
        $entity = $this->createEntity();
        $entity->setAreaPrivativa('90.00');
        $this->assertSame('90.00', $entity->getAreaPrivativa());
    }

    public function testGetSetQtdQuartos(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdQuartos(3);
        $this->assertSame(3, $entity->getQtdQuartos());
    }

    public function testGetSetQtdSuites(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdSuites(1);
        $this->assertSame(1, $entity->getQtdSuites());
    }

    public function testGetSetQtdBanheiros(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdBanheiros(2);
        $this->assertSame(2, $entity->getQtdBanheiros());
    }

    public function testGetSetQtdSalas(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdSalas(2);
        $this->assertSame(2, $entity->getQtdSalas());
    }

    public function testGetSetQtdVagasGaragem(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdVagasGaragem(1);
        $this->assertSame(1, $entity->getQtdVagasGaragem());
    }

    public function testGetSetQtdPavimentos(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdPavimentos(2);
        $this->assertSame(2, $entity->getQtdPavimentos());
    }

    // ---------- CONSTRUÇÃO ----------
    public function testGetSetAnoConstrucao(): void
    {
        $entity = $this->createEntity();
        $entity->setAnoConstrucao(1995);
        $this->assertSame(1995, $entity->getAnoConstrucao());
    }

    public function testGetSetDataFundacao(): void
    {
        $entity = $this->createEntity();
        $date = new DateTime('2000-01-01');
        $entity->setDataFundacao($date);
        $this->assertSame($date, $entity->getDataFundacao());
    }

    public function testGetSetTipoConstrucao(): void
    {
        $entity = $this->createEntity();
        $entity->setTipoConstrucao('Torre');
        $this->assertSame('Torre', $entity->getTipoConstrucao());
    }

    public function testGetSetAptosPorAndar(): void
    {
        $entity = $this->createEntity();
        $entity->setAptosPorAndar(4);
        $this->assertSame(4, $entity->getAptosPorAndar());
    }

    // ---------- VALORES ----------
    public function testGetSetValorAluguel(): void
    {
        $entity = $this->createEntity();
        $entity->setValorAluguel('1500.00');
        $this->assertSame('1500.00', $entity->getValorAluguel());
    }

    public function testGetSetValorVenda(): void
    {
        $entity = $this->createEntity();
        $entity->setValorVenda('250000.00');
        $this->assertSame('250000.00', $entity->getValorVenda());
    }

    public function testGetSetValorTemporada(): void
    {
        $entity = $this->createEntity();
        $entity->setValorTemporada('200.00');
        $this->assertSame('200.00', $entity->getValorTemporada());
    }

    public function testGetSetValorCondominio(): void
    {
        $entity = $this->createEntity();
        $entity->setValorCondominio('300.00');
        $this->assertSame('300.00', $entity->getValorCondominio());
    }

    public function testGetSetValorIptuMensal(): void
    {
        $entity = $this->createEntity();
        $entity->setValorIptuMensal('50.00');
        $this->assertSame('50.00', $entity->getValorIptuMensal());
    }

    public function testGetSetValorTaxaLixo(): void
    {
        $entity = $this->createEntity();
        $entity->setValorTaxaLixo('10.00');
        $this->assertSame('10.00', $entity->getValorTaxaLixo());
    }

    public function testGetSetValorMercado(): void
    {
        $entity = $this->createEntity();
        $entity->setValorMercado('260000.00');
        $this->assertSame('260000.00', $entity->getValorMercado());
    }

    public function testGetSetDiaVencimento(): void
    {
        $entity = $this->createEntity();
        $entity->setDiaVencimento(15);
        $this->assertSame(15, $entity->getDiaVencimento());
    }

    // ---------- COMISSÕES ----------
    public function testGetSetTaxaAdministracao(): void
    {
        $entity = $this->createEntity();
        $entity->setTaxaAdministracao('5.00');
        $this->assertSame('5.00', $entity->getTaxaAdministracao());
    }

    public function testGetSetTaxaMinima(): void
    {
        $entity = $this->createEntity();
        $entity->setTaxaMinima('2.00');
        $this->assertSame('2.00', $entity->getTaxaMinima());
    }

    public function testGetSetComissaoLocacao(): void
    {
        $entity = $this->createEntity();
        $entity->setComissaoLocacao('3.00');
        $this->assertSame('3.00', $entity->getComissaoLocacao());
    }

    public function testGetSetComissaoVenda(): void
    {
        $entity = $this->createEntity();
        $entity->setComissaoVenda('4.00');
        $this->assertSame('4.00', $entity->getComissaoVenda());
    }

    public function testGetSetComissaoAluguel(): void
    {
        $entity = $this->createEntity();
        $entity->setComissaoAluguel('2.50');
        $this->assertSame('2.50', $entity->getComissaoAluguel());
    }

    public function testGetSetTipoRemuneracao(): void
    {
        $entity = $this->createEntity();
        $entity->setTipoRemuneracao('Percentual');
        $this->assertSame('Percentual', $entity->getTipoRemuneracao());
    }

    // ---------- DOCUMENTAÇÃO ----------
    public function testGetSetInscricaoImobiliaria(): void
    {
        $entity = $this->createEntity();
        $entity->setInscricaoImobiliaria('123456789');
        $this->assertSame('123456789', $entity->getInscricaoImobiliaria());
    }

    public function testGetSetMatriculaCartorio(): void
    {
        $entity = $this->createEntity();
        $entity->setMatriculaCartorio('987654321');
        $this->assertSame('987654321', $entity->getMatriculaCartorio());
    }

    public function testGetSetNomeCartorio(): void
    {
        $entity = $this->createEntity();
        $entity->setNomeCartorio('Cartório Central');
        $this->assertSame('Cartório Central', $entity->getNomeCartorio());
    }

    public function testGetSetNomeContribuinteIptu(): void
    {
        $entity = $this->createEntity();
        $entity->setNomeContribuinteIptu('João Silva');
        $this->assertSame('João Silva', $entity->getNomeContribuinteIptu());
    }

    // ---------- DESCRIÇÃO ----------
    public function testGetSetDescricao(): void
    {
        $entity = $this->createEntity();
        $entity->setDescricao('Apartamento com vista para o mar.');
        $this->assertSame('Apartamento com vista para o mar.', $entity->getDescricao());
    }

    public function testGetSetObservacoes(): void
    {
        $entity = $this->createEntity();
        $entity->setObservacoes('Sem reformas.');
        $this->assertSame('Sem reformas.', $entity->getObservacoes());
    }

    public function testGetSetDescricaoImediacoes(): void
    {
        $entity = $this->createEntity();
        $entity->setDescricaoImediacoes('Reparo de telhado.');
        $this->assertSame('Reparo de telhado.', $entity->getDescricaoImediacoes());
    }

    // ---------- CHAVES ----------
    public function testIsTemChaves(): void
    {
        $entity = $this->createEntity();
        $entity->setTemChaves(true);
        $this->assertTrue($entity->isTemChaves());
    }

    public function testGetSetQtdChaves(): void
    {
        $entity = $this->createEntity();
        $entity->setQtdChaves(2);
        $this->assertSame(2, $entity->getQtdChaves());
    }

    public function testGetSetNumeroChave(): void
    {
        $entity = $this->createEntity();
        $entity->setNumeroChave('CHAVE123');
        $this->assertSame('CHAVE123', $entity->getNumeroChave());
    }

    public function testGetSetLocalizacaoChaves(): void
    {
        $entity = $this->createEntity();
        $entity->setLocalizacaoChaves('Sala de entrada');
        $this->assertSame('Sala de entrada', $entity->getLocalizacaoChaves());
    }

    public function testGetSetNumeroControleRemoto(): void
    {
        $entity = $this->createEntity();
        $entity->setNumeroControleRemoto('CTRL-001');
        $this->assertSame('CTRL-001', $entity->getNumeroControleRemoto());
    }

    // ---------- PUBLICAÇÃO ----------
    public function testIsPublicarSite(): void
    {
        $entity = $this->createEntity();
        $entity->setPublicarSite(false);
        $this->assertFalse($entity->isPublicarSite());
    }

    public function testIsPublicarZap(): void
    {
        $entity = $this->createEntity();
        $entity->setPublicarZap(true);
        $this->assertTrue($entity->isPublicarZap());
    }

    public function testIsPublicarVivareal(): void
    {
        $entity = $this->createEntity();
        $entity->setPublicarVivareal(true);
        $this->assertTrue($entity->isPublicarVivareal());
    }

    public function testIsPublicarGruposp(): void
    {
        $entity = $this->createEntity();
        $entity->setPublicarGruposp(true);
        $this->assertTrue($entity->isPublicarGruposp());
    }

    public function testIsOcultarValorSite(): void
    {
        $entity = $this->createEntity();
        $entity->setOcultarValorSite(true);
        $this->assertTrue($entity->isOcultarValorSite());
    }

    public function testIsTemPlaca(): void
    {
        $entity = $this->createEntity();
        $entity->setTemPlaca(true);
        $this->assertTrue($entity->isTemPlaca());
    }

    // ---------- AUDITORIA ----------
    public function testGetDataCadastro(): void
    {
        $entity = $this->createEntity();
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getDataCadastro());
    }

    public function testSetDataCadastro(): void
    {
        $entity = $this->createEntity();
        $date = new DateTime('2025-12-31');
        $entity->setDataCadastro($date);
        $this->assertSame($date, $entity->getDataCadastro());
    }

    public function testGetUpdatedAt(): void
    {
        $entity = $this->createEntity();
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $entity = $this->createEntity();
        $date = new DateTime('2025-12-31');
        $entity->setUpdatedAt($date);
        $this->assertSame($date, $entity->getUpdatedAt());
    }

    // ---------- COLEÇÕES ----------
    public function testAddRemovePropriedade(): void
    {
        $entity = $this->createEntity();
        $propriedade = $this->createMock(ImoveisPropriedades::class);
        $propriedade->expects($this->atLeastOnce())->method('setImovel')->with($this->logicalOr(
            $this->identicalTo($entity),
            $this->identicalTo(null)
        ));
        $entity->addPropriedade($propriedade);
        $this->assertTrue($entity->getPropriedades()->contains($propriedade));

        $entity->removePropriedade($propriedade);
        $this->assertFalse($entity->getPropriedades()->contains($propriedade));
    }

    public function testAddRemoveMedidor(): void
    {
        $entity = $this->createEntity();
        $medidor = $this->createMock(ImoveisMedidores::class);
        $medidor->expects($this->atLeastOnce())->method('setImovel')->with($this->logicalOr(
            $this->identicalTo($entity),
            $this->identicalTo(null)
        ));
        $entity->addMedidor($medidor);
        $this->assertTrue($entity->getMedidores()->contains($medidor));

        $entity->removeMedidor($medidor);
        $this->assertFalse($entity->getMedidores()->contains($medidor));
    }

    public function testAddRemoveGarantia(): void
    {
        $entity = $this->createEntity();
        $garantia = $this->createMock(ImoveisGarantias::class);
        $garantia->expects($this->atLeastOnce())->method('setImovel')->with($this->logicalOr(
            $this->identicalTo($entity),
            $this->identicalTo(null)
        ));
        $entity->addGarantia($garantia);
        $this->assertTrue($entity->getGarantias()->contains($garantia));

        $entity->removeGarantia($garantia);
        $this->assertFalse($entity->getGarantias()->contains($garantia));
    }

    public function testAddRemoveFoto(): void
    {
        $entity = $this->createEntity();
        $foto = $this->createMock(ImoveisFotos::class);
        $foto->expects($this->atLeastOnce())->method('setImovel')->with($this->logicalOr(
            $this->identicalTo($entity),
            $this->identicalTo(null)
        ));
        $entity->addFoto($foto);
        $this->assertTrue($entity->getFotos()->contains($foto));

        $entity->removeFoto($foto);
        $this->assertFalse($entity->getFotos()->contains($foto));
    }

    public function testAddRemoveContrato(): void
    {
        $entity = $this->createEntity();
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->expects($this->atLeastOnce())->method('setImovel')->with($this->logicalOr(
            $this->identicalTo($entity),
            $this->identicalTo(null)
        ));
        $entity->addContrato($contrato);
        $this->assertTrue($entity->getContratos()->contains($contrato));

        $entity->removeContrato($contrato);
        $this->assertFalse($entity->getContratos()->contains($contrato));
    }

    // ---------- RELACIONAMENTOS NULOS ----------
    public function testGetCondominioIsNullByDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getCondominio());
    }

    public function testGetPessoaFiadorIsNullByDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getPessoaFiador());
    }

    public function testGetPessoaCorretorIsNullByDefault(): void
    {
        $entity = $this->createEntity();
        $this->assertNull($entity->getPessoaCorretor());
    }
}
