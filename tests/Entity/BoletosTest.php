<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Boletos;
use App\Entity\ConfiguracoesApiBanco;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Pessoas;
use App\Entity\Imoveis;
use App\Entity\BoletosLogApi;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeImmutable;

final class BoletosTest extends TestCase
{
    private function createBoletos(): Boletos
    {
        return new Boletos();
    }

    private function createMockConfiguracoesApiBanco(): ConfiguracoesApiBanco
    {
        return $this->createMock(ConfiguracoesApiBanco::class);
    }

    private function createMockLancamentosFinanceiros(): LancamentosFinanceiros
    {
        return $this->createMock(LancamentosFinanceiros::class);
    }

    private function createMockPessoas(): Pessoas
    {
        return $this->createMock(Pessoas::class);
    }

    private function createMockImoveis(): Imoveis
    {
        return $this->createMock(Imoveis::class);
    }

    private function createMockBoletosLogApi(): BoletosLogApi
    {
        return $this->createMock(BoletosLogApi::class);
    }

    public function testGettersAndSetters(): void
    {
        $boletos = $this->createBoletos();

        // Relacionamentos
        $config = $this->createMockConfiguracoesApiBanco();
        $boletos->setConfiguracaoApi($config);
        $this->assertSame($config, $boletos->getConfiguracaoApi());

        $lancamento = $this->createMockLancamentosFinanceiros();
        $boletos->setLancamentoFinanceiro($lancamento);
        $this->assertSame($lancamento, $boletos->getLancamentoFinanceiro());

        $pessoa = $this->createMockPessoas();
        $boletos->setPessoaPagador($pessoa);
        $this->assertSame($pessoa, $boletos->getPessoaPagador());

        $imovel = $this->createMockImoveis();
        $boletos->setImovel($imovel);
        $this->assertSame($imovel, $boletos->getImovel());

        // Identificação
        $boletos->setNossoNumero('12345678901234567890');
        $this->assertSame('12345678901234567890', $boletos->getNossoNumero());

        $boletos->setSeuNumero('123456789012345');
        $this->assertSame('123456789012345', $boletos->getSeuNumero());

        // Valores
        $boletos->setValorNominal('1234.56');
        $this->assertSame('1234.56', $boletos->getValorNominal());

        $boletos->setValorDesconto('10.00');
        $this->assertSame('10.00', $boletos->getValorDesconto());

        $boletos->setValorMulta('5.00');
        $this->assertSame('5.00', $boletos->getValorMulta());

        $boletos->setValorJurosDia('0.50');
        $this->assertSame('0.50', $boletos->getValorJurosDia());

        $boletos->setValorAbatimento('2.00');
        $this->assertSame('2.00', $boletos->getValorAbatimento());

        // Datas
        $dataEmissao = new DateTimeImmutable('2023-01-01');
        $boletos->setDataEmissao($dataEmissao);
        $this->assertSame($dataEmissao, $boletos->getDataEmissao());

        $dataVencimento = new DateTimeImmutable('2023-02-01');
        $boletos->setDataVencimento($dataVencimento);
        $this->assertSame($dataVencimento, $boletos->getDataVencimento());

        $dataLimitePagamento = new DateTimeImmutable('2023-01-31');
        $boletos->setDataLimitePagamento($dataLimitePagamento);
        $this->assertSame($dataLimitePagamento, $boletos->getDataLimitePagamento());

        // Desconto
        $boletos->setTipoDesconto(Boletos::DESCONTO_VALOR_DATA_FIXA);
        $this->assertSame(Boletos::DESCONTO_VALOR_DATA_FIXA, $boletos->getTipoDesconto());

        $dataDesconto = new DateTimeImmutable('2023-01-15');
        $boletos->setDataDesconto($dataDesconto);
        $this->assertSame($dataDesconto, $boletos->getDataDesconto());

        // Juros e Multa
        $boletos->setTipoJuros(Boletos::JUROS_VALOR_DIA);
        $this->assertSame(Boletos::JUROS_VALOR_DIA, $boletos->getTipoJuros());

        $boletos->setTipoMulta(Boletos::MULTA_PERCENTUAL);
        $this->assertSame(Boletos::MULTA_PERCENTUAL, $boletos->getTipoMulta());

        $dataMulta = new DateTimeImmutable('2023-02-05');
        $boletos->setDataMulta($dataMulta);
        $this->assertSame($dataMulta, $boletos->getDataMulta());

        // Dados retornados pela API
        $boletos->setCodigoBarras('12345678901234567890123456789012345678901234');
        $this->assertSame('12345678901234567890123456789012345678901234', $boletos->getCodigoBarras());

        $boletos->setLinhaDigitavel('12345678901234567890123456789012345678901234');
        $this->assertSame('12345678901234567890123456789012345678901234', $boletos->getLinhaDigitavel());

        $boletos->setTxidPix('txid12345');
        $this->assertSame('txid12345', $boletos->getTxidPix());

        $boletos->setQrcodePix('qrcode12345');
        $this->assertSame('qrcode12345', $boletos->getQrcodePix());

        // Status
        $boletos->setStatus(Boletos::STATUS_REGISTRADO);
        $this->assertSame(Boletos::STATUS_REGISTRADO, $boletos->getStatus());

        // Resposta da API
        $boletos->setIdTituloBanco('id123');
        $this->assertSame('id123', $boletos->getIdTituloBanco());

        $boletos->setConvenioBanco('conv123');
        $this->assertSame('conv123', $boletos->getConvenioBanco());

        // Mensagens
        $boletos->setMensagemPagador('Mensagem de teste');
        $this->assertSame('Mensagem de teste', $boletos->getMensagemPagador());

        // Controle
        $boletos->setTentativasRegistro(3);
        $this->assertSame(3, $boletos->getTentativasRegistro());

        $boletos->setUltimoErro('Erro de teste');
        $this->assertSame('Erro de teste', $boletos->getUltimoErro());

        $dataRegistro = new DateTimeImmutable('2023-01-10 10:00:00');
        $boletos->setDataRegistro($dataRegistro);
        $this->assertSame($dataRegistro, $boletos->getDataRegistro());

        $dataPagamento = new DateTimeImmutable('2023-01-20 12:00:00');
        $boletos->setDataPagamento($dataPagamento);
        $this->assertSame($dataPagamento, $boletos->getDataPagamento());

        $boletos->setValorPago('1234.56');
        $this->assertSame('1234.56', $boletos->getValorPago());

        $dataBaixa = new DateTimeImmutable('2023-01-25 15:00:00');
        $boletos->setDataBaixa($dataBaixa);
        $this->assertSame($dataBaixa, $boletos->getDataBaixa());

        $boletos->setMotivoBaixa('Motivo de teste');
        $this->assertSame('Motivo de teste', $boletos->getMotivoBaixa());

        // Timestamps
        $createdAt = new DateTimeImmutable('2023-01-01 00:00:00');
        $boletos->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $boletos->getCreatedAt());

        $updatedAt = new DateTimeImmutable('2023-01-02 00:00:00');
        $boletos->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $boletos->getUpdatedAt());

        // ID (should be null initially)
        $this->assertNull($boletos->getId());
    }

    public function testCollectionRelationships(): void
    {
        $boletos = $this->createBoletos();

        // Initially empty
        $this->assertInstanceOf(ArrayCollection::class, $boletos->getLogs());
        $this->assertCount(0, $boletos->getLogs());

        $log1 = $this->createMockBoletosLogApi();
        $log2 = $this->createMockBoletosLogApi();

        // Add logs
        $boletos->addLog($log1);
        $boletos->addLog($log2);

        $this->assertCount(2, $boletos->getLogs());
        $this->assertSame($log1, $boletos->getLogs()->first());
        $this->assertSame($log2, $boletos->getLogs()->last());

        // Remove a log
        $boletos->removeLog($log1);
        $this->assertCount(1, $boletos->getLogs());
        $this->assertSame($log2, $boletos->getLogs()->first());

        // Remove remaining log
        $boletos->removeLog($log2);
        $this->assertCount(0, $boletos->getLogs());
    }

    public function testBusinessLogicMethods(): void
    {
        $boletos = $this->createBoletos();

        // Test constants
        $this->assertSame('PENDENTE', Boletos::STATUS_PENDENTE);
        $this->assertSame('REGISTRADO', Boletos::STATUS_REGISTRADO);
        $this->assertSame('PAGO', Boletos::STATUS_PAGO);
        $this->assertSame('VENCIDO', Boletos::STATUS_VENCIDO);
        $this->assertSame('BAIXADO', Boletos::STATUS_BAIXADO);
        $this->assertSame('PROTESTADO', Boletos::STATUS_PROTESTADO);
        $this->assertSame('ERRO', Boletos::STATUS_ERRO);

        // Set dataVencimento to future date for initial status tests
        $futureDate = new DateTimeImmutable('2099-12-31');
        $boletos->setDataVencimento($futureDate);

        // Status methods with future date (not vencido)
        $boletos->setStatus(Boletos::STATUS_REGISTRADO);
        $this->assertTrue($boletos->isRegistrado());
        $this->assertFalse($boletos->isPago());
        $this->assertFalse($boletos->isVencido());

        $boletos->setStatus(Boletos::STATUS_PAGO);
        $this->assertTrue($boletos->isPago());
        $this->assertTrue($boletos->isRegistrado());
        $this->assertFalse($boletos->isVencido()); // False because status PAGO overrides date check

        $boletos->setStatus(Boletos::STATUS_BAIXADO);
        $this->assertTrue($boletos->isRegistrado());
        $this->assertFalse($boletos->isPago());
        $this->assertFalse($boletos->isVencido()); // False because status BAIXADO overrides date check

        $boletos->setStatus(Boletos::STATUS_VENCIDO);
        $this->assertTrue($boletos->isRegistrado());
        $this->assertFalse($boletos->isPago());
        $this->assertFalse($boletos->isVencido()); // False because dataVencimento is still in future

        // Now test with past date for vencido calculation
        $pastDate = new DateTimeImmutable('2000-01-01');
        $boletos->setDataVencimento($pastDate);
        $this->assertTrue($boletos->isVencido()); // True because dataVencimento is in past and status is VENCIDO

        // Dias de atraso
        $now = new DateTimeImmutable('today');
        $diff = $now->diff($pastDate);
        $this->assertSame($diff->days, $boletos->getDiasAtraso());

        // Valor nominal formatado
        $boletos->setValorNominal('1234.56');
        $this->assertSame('R$ 1.234,56', $boletos->getValorNominalFormatado());

        // Data vencimento formatada
        $boletos->setDataVencimento(new DateTimeImmutable('2023-12-25'));
        $this->assertSame('25/12/2023', $boletos->getDataVencimentoFormatada());

        // Status label
        $boletos->setStatus(Boletos::STATUS_PENDENTE);
        $this->assertSame('Pendente', $boletos->getStatusLabel());

        // Status class
        $boletos->setStatus(Boletos::STATUS_PAGO);
        $this->assertSame('success', $boletos->getStatusClass());

        // Unknown status fallback
        $boletos->setStatus('UNKNOWN');
        $this->assertSame('UNKNOWN', $boletos->getStatusLabel());
        $this->assertSame('secondary', $boletos->getStatusClass());
    }
}
