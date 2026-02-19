<?php

namespace App\Tests\Service;

use App\Entity\LancamentosFinanceiros;
use App\Entity\BaixasFinanceiras;
use App\Entity\AcordosFinanceiros;
use App\Entity\ImoveisContratos;
use App\Entity\Pessoas;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\BaixasFinanceirasRepository;
use App\Repository\AcordosFinanceirosRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\PessoaRepository;
use App\Repository\ContasBancariasRepository;
use App\Service\FichaFinanceiraService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FichaFinanceiraServiceTest extends TestCase
{
    private FichaFinanceiraService $service;
    private EntityManagerInterface $em;
    private LancamentosFinanceirosRepository $lancamentoRepo;
    private BaixasFinanceirasRepository $baixaRepo;
    private AcordosFinanceirosRepository $acordoRepo;
    private ImoveisContratosRepository $contratoRepo;
    private PessoaRepository $pessoaRepo;
    private ContasBancariasRepository $contaBancariaRepo;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->lancamentoRepo = $this->createMock(LancamentosFinanceirosRepository::class);
        $this->baixaRepo = $this->createMock(BaixasFinanceirasRepository::class);
        $this->acordoRepo = $this->createMock(AcordosFinanceirosRepository::class);
        $this->contratoRepo = $this->createMock(ImoveisContratosRepository::class);
        $this->pessoaRepo = $this->createMock(PessoaRepository::class);
        $this->contaBancariaRepo = $this->createMock(ContasBancariasRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new FichaFinanceiraService(
            $this->em,
            $this->lancamentoRepo,
            $this->baixaRepo,
            $this->acordoRepo,
            $this->contratoRepo,
            $this->pessoaRepo,
            $this->contaBancariaRepo,
            $this->logger
        );
    }

    /**
     * Test listarLancamentos calls repository with filtros
     */
    public function testListarLancamentosCallsRepository(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('getId')->willReturn(1);
        $lancamento->method('getInquilino')->willReturn(null);
        $lancamento->method('getProprietario')->willReturn(null);
        $lancamento->method('getImovel')->willReturn(null);
        $lancamento->method('getContrato')->willReturn(null);
        $lancamento->method('getCompetencia')->willReturn(new \DateTime('2025-03-01'));
        $lancamento->method('getDataVencimento')->willReturn(new \DateTime('2025-03-10'));
        $lancamento->method('getDataLimite')->willReturn(null);
        $lancamento->method('getValorPrincipal')->willReturn('1000.00');
        $lancamento->method('getValorCondominio')->willReturn('0.00');
        $lancamento->method('getValorIptu')->willReturn('0.00');
        $lancamento->method('getValorAgua')->willReturn('0.00');
        $lancamento->method('getValorLuz')->willReturn('0.00');
        $lancamento->method('getValorGas')->willReturn('0.00');
        $lancamento->method('getValorOutros')->willReturn('0.00');
        $lancamento->method('getValorMulta')->willReturn('0.00');
        $lancamento->method('getValorJuros')->willReturn('0.00');
        $lancamento->method('getValorDesconto')->willReturn('0.00');
        $lancamento->method('getValorTotal')->willReturn('1000.00');
        $lancamento->method('getValorPago')->willReturn('0.00');
        $lancamento->method('getValorSaldo')->willReturn('1000.00');
        $lancamento->method('getSituacao')->willReturn('aberto');
        $lancamento->method('getTipoLancamento')->willReturn('aluguel');
        $lancamento->method('getDescricao')->willReturn('Aluguel 03/2025');
        $lancamento->method('isEmAtraso')->willReturn(false);
        $lancamento->method('getDiasAtraso')->willReturn(0);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('isParcial')->willReturn(false);
        $lancamento->method('getNumeroAcordo')->willReturn(null);
        $lancamento->method('getNumeroParcela')->willReturn(null);
        $lancamento->method('getNumeroRecibo')->willReturn(null);
        $lancamento->method('getNumeroBoleto')->willReturn(null);
        $lancamento->method('getBaixas')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $lancamento->method('getCompetenciaFormatada')->willReturn('03/2025');

        $filtros = ['situacao' => 'aberto'];

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with($filtros)
            ->willReturn([$lancamento]);

        $resultado = $this->service->listarLancamentos($filtros);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    /**
     * Test buscarFichaFinanceira returns ficha data for inquilino
     */
    public function testBuscarFichaFinanceiraReturnsFichaData(): void
    {
        $inquilino = $this->createMock(Pessoas::class);
        $inquilino->method('getIdpessoa')->willReturn(1);
        $inquilino->method('getNome')->willReturn('João Silva');

        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('getId')->willReturn(1);
        $lancamento->method('getInquilino')->willReturn($inquilino);
        $lancamento->method('getProprietario')->willReturn(null);
        $lancamento->method('getImovel')->willReturn(null);
        $lancamento->method('getContrato')->willReturn(null);
        $lancamento->method('getCompetencia')->willReturn(new \DateTime('2025-03-01'));
        $lancamento->method('getDataVencimento')->willReturn(new \DateTime('2025-03-10'));
        $lancamento->method('getDataLimite')->willReturn(null);
        $lancamento->method('getValorPrincipal')->willReturn('1000.00');
        $lancamento->method('getValorCondominio')->willReturn('0.00');
        $lancamento->method('getValorIptu')->willReturn('0.00');
        $lancamento->method('getValorAgua')->willReturn('0.00');
        $lancamento->method('getValorLuz')->willReturn('0.00');
        $lancamento->method('getValorGas')->willReturn('0.00');
        $lancamento->method('getValorOutros')->willReturn('0.00');
        $lancamento->method('getValorMulta')->willReturn('0.00');
        $lancamento->method('getValorJuros')->willReturn('0.00');
        $lancamento->method('getValorDesconto')->willReturn('0.00');
        $lancamento->method('getValorTotal')->willReturn('1000.00');
        $lancamento->method('getValorPago')->willReturn('0.00');
        $lancamento->method('getValorSaldo')->willReturn('1000.00');
        $lancamento->method('getSituacao')->willReturn('aberto');
        $lancamento->method('getTipoLancamento')->willReturn('aluguel');
        $lancamento->method('getDescricao')->willReturn('Aluguel 03/2025');
        $lancamento->method('isEmAtraso')->willReturn(false);
        $lancamento->method('getDiasAtraso')->willReturn(0);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('isParcial')->willReturn(false);
        $lancamento->method('getNumeroAcordo')->willReturn(null);
        $lancamento->method('getNumeroParcela')->willReturn(null);
        $lancamento->method('getNumeroRecibo')->willReturn(null);
        $lancamento->method('getNumeroBoleto')->willReturn(null);
        $lancamento->method('getBaixas')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $lancamento->method('getCompetenciaFormatada')->willReturn('03/2025');

        $this->pessoaRepo
            ->method('find')
            ->with(1)
            ->willReturn($inquilino);

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('findFichaFinanceira')
            ->with(1, null)
            ->willReturn([$lancamento]);

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('calcularTotaisInquilino')
            ->with(1)
            ->willReturn(['total' => 1000.00, 'pago' => 0.00]);

        $resultado = $this->service->buscarFichaFinanceira(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('inquilino', $resultado);
        $this->assertArrayHasKey('lancamentos', $resultado);
        $this->assertArrayHasKey('totais', $resultado);
        $this->assertEquals('João Silva', $resultado['inquilino']['nome']);
    }

    /**
     * Test buscarAbertosInquilino returns array of open lancamentos
     */
    public function testBuscarAbertosInquilinoReturnsArray(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('getId')->willReturn(1);
        $lancamento->method('getInquilino')->willReturn(null);
        $lancamento->method('getProprietario')->willReturn(null);
        $lancamento->method('getImovel')->willReturn(null);
        $lancamento->method('getContrato')->willReturn(null);
        $lancamento->method('getCompetencia')->willReturn(new \DateTime('2025-03-01'));
        $lancamento->method('getDataVencimento')->willReturn(new \DateTime('2025-03-10'));
        $lancamento->method('getDataLimite')->willReturn(null);
        $lancamento->method('getValorPrincipal')->willReturn('1000.00');
        $lancamento->method('getValorCondominio')->willReturn('0.00');
        $lancamento->method('getValorIptu')->willReturn('0.00');
        $lancamento->method('getValorAgua')->willReturn('0.00');
        $lancamento->method('getValorLuz')->willReturn('0.00');
        $lancamento->method('getValorGas')->willReturn('0.00');
        $lancamento->method('getValorOutros')->willReturn('0.00');
        $lancamento->method('getValorMulta')->willReturn('0.00');
        $lancamento->method('getValorJuros')->willReturn('0.00');
        $lancamento->method('getValorDesconto')->willReturn('0.00');
        $lancamento->method('getValorTotal')->willReturn('1000.00');
        $lancamento->method('getValorPago')->willReturn('0.00');
        $lancamento->method('getValorSaldo')->willReturn('1000.00');
        $lancamento->method('getSituacao')->willReturn('aberto');
        $lancamento->method('getTipoLancamento')->willReturn('aluguel');
        $lancamento->method('getDescricao')->willReturn('Aluguel 03/2025');
        $lancamento->method('isEmAtraso')->willReturn(false);
        $lancamento->method('getDiasAtraso')->willReturn(0);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('isParcial')->willReturn(false);
        $lancamento->method('getNumeroAcordo')->willReturn(null);
        $lancamento->method('getNumeroParcela')->willReturn(null);
        $lancamento->method('getNumeroRecibo')->willReturn(null);
        $lancamento->method('getNumeroBoleto')->willReturn(null);
        $lancamento->method('getBaixas')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $lancamento->method('getCompetenciaFormatada')->willReturn('03/2025');

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('findAbertosInquilino')
            ->with(1)
            ->willReturn([$lancamento]);

        $resultado = $this->service->buscarAbertosInquilino(1);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    /**
     * Test buscarLancamentoPorId returns lancamento data
     */
    public function testBuscarLancamentoPorIdReturnsData(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('getId')->willReturn(1);
        $lancamento->method('getInquilino')->willReturn(null);
        $lancamento->method('getProprietario')->willReturn(null);
        $lancamento->method('getImovel')->willReturn(null);
        $lancamento->method('getContrato')->willReturn(null);
        $lancamento->method('getCompetencia')->willReturn(new \DateTime('2025-03-01'));
        $lancamento->method('getDataVencimento')->willReturn(new \DateTime('2025-03-10'));
        $lancamento->method('getDataLimite')->willReturn(null);
        $lancamento->method('getValorPrincipal')->willReturn('1000.00');
        $lancamento->method('getValorCondominio')->willReturn('0.00');
        $lancamento->method('getValorIptu')->willReturn('0.00');
        $lancamento->method('getValorAgua')->willReturn('0.00');
        $lancamento->method('getValorLuz')->willReturn('0.00');
        $lancamento->method('getValorGas')->willReturn('0.00');
        $lancamento->method('getValorOutros')->willReturn('0.00');
        $lancamento->method('getValorMulta')->willReturn('0.00');
        $lancamento->method('getValorJuros')->willReturn('0.00');
        $lancamento->method('getValorDesconto')->willReturn('0.00');
        $lancamento->method('getValorTotal')->willReturn('1000.00');
        $lancamento->method('getValorPago')->willReturn('0.00');
        $lancamento->method('getValorSaldo')->willReturn('1000.00');
        $lancamento->method('getSituacao')->willReturn('aberto');
        $lancamento->method('getTipoLancamento')->willReturn('aluguel');
        $lancamento->method('getDescricao')->willReturn('Aluguel 03/2025');
        $lancamento->method('isEmAtraso')->willReturn(false);
        $lancamento->method('getDiasAtraso')->willReturn(0);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('isParcial')->willReturn(false);
        $lancamento->method('getNumeroAcordo')->willReturn(null);
        $lancamento->method('getNumeroParcela')->willReturn(null);
        $lancamento->method('getNumeroRecibo')->willReturn(null);
        $lancamento->method('getNumeroBoleto')->willReturn(null);
        $lancamento->method('getBaixas')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
        $lancamento->method('getCompetenciaFormatada')->willReturn('03/2025');

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $resultado = $this->service->buscarLancamentoPorId(1);

        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['id']);
    }

    /**
     * Test buscarLancamentoPorId returns null when not found
     */
    public function testBuscarLancamentoPorIdReturnsNullWhenNotFound(): void
    {
        $this->lancamentoRepo
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarLancamentoPorId(999);

        $this->assertNull($resultado);
    }

    /**
     * Test criarLancamento persists new lancamento
     */
    public function testCriarLancamentoPersistsEntity(): void
    {
        $dados = [
            'valor_principal' => 1000.00,
            'data_vencimento' => '2025-03-10'
        ];

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->criarLancamento($dados);

        $this->assertInstanceOf(LancamentosFinanceiros::class, $resultado);
    }

    /**
     * Test realizarBaixa creates baixa entry
     */
    public function testRealizarBaixaCriasBaixaEntry(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('getValorPago')->willReturn('0.00');
        $lancamento->method('getValorSaldo')->willReturn('1000.00');

        $dados = [
            'dataPagamento' => '2025-03-10',
            'valorPago' => '1000.00',
            'formaPagamento' => 'boleto'
        ];

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->realizarBaixa(1, $dados);

        $this->assertInstanceOf(BaixasFinanceiras::class, $resultado);
    }

    /**
     * Test estornarBaixa marks baixa as estornada
     */
    public function testEstornarBaixaMarksAsEstornada(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('getValorPago')->willReturn('1000.00');
        $lancamento->method('isEmAtraso')->willReturn(false);

        $baixa = $this->createMock(BaixasFinanceiras::class);
        $baixa->method('isEstornada')->willReturn(false);
        $baixa->method('getLancamento')->willReturn($lancamento);
        $baixa->method('getValorTotalPago')->willReturn('1000.00');

        $this->baixaRepo
            ->method('find')
            ->with(1)
            ->willReturn($baixa);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $baixa->expects($this->once())
            ->method('setEstornada')
            ->with(true);

        $baixa->expects($this->once())
            ->method('setDataEstorno');

        $baixa->expects($this->once())
            ->method('setMotivoEstorno')
            ->with('Motivo teste');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->estornarBaixa(1, 'Motivo teste');

        $this->assertInstanceOf(BaixasFinanceiras::class, $resultado);
    }

    /**
     * Test cancelarLancamento sets status and ativo to false
     */
    public function testCancelarLancamentoSetsStatusAndAtivo(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('getObservacoes')->willReturn(null);

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $lancamento->expects($this->once())
            ->method('setSituacao')
            ->with('cancelado');

        $lancamento->expects($this->once())
            ->method('setAtivo')
            ->with(false);

        $lancamento->expects($this->once())
            ->method('setObservacoes');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->cancelarLancamento(1, 'Motivo cancelamento');

        $this->assertInstanceOf(LancamentosFinanceiros::class, $resultado);
    }

    /**
     * Test obterEstatisticas calls repository
     */
    public function testObterEstatisticasCallsRepository(): void
    {
        $stats = ['total' => 100, 'valor' => 10000.00];

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('getEstatisticas')
            ->with(null)
            ->willReturn($stats);

        $resultado = $this->service->obterEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertEquals(100, $resultado['total']);
    }
}
