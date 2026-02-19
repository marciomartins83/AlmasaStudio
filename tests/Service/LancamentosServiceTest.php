<?php

namespace App\Tests\Service;

use App\Entity\Lancamentos;
use App\Entity\PlanoContas;
use App\Entity\ContasBancarias;
use App\Repository\LancamentosRepository;
use App\Repository\PlanoContasRepository;
use App\Repository\PessoaRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\ContasBancariasRepository;
use App\Service\LancamentosService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LancamentosServiceTest extends TestCase
{
    private LancamentosService $service;
    private EntityManagerInterface $em;
    private LancamentosRepository $lancamentoRepo;
    private PlanoContasRepository $planoContaRepo;
    private PessoaRepository $pessoaRepo;
    private ImoveisContratosRepository $contratoRepo;
    private ImoveisRepository $imovelRepo;
    private ContasBancariasRepository $contaBancariaRepo;
    private Security $security;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->lancamentoRepo = $this->createMock(LancamentosRepository::class);
        $this->planoContaRepo = $this->createMock(PlanoContasRepository::class);
        $this->pessoaRepo = $this->createMock(PessoaRepository::class);
        $this->contratoRepo = $this->createMock(ImoveisContratosRepository::class);
        $this->imovelRepo = $this->createMock(ImoveisRepository::class);
        $this->contaBancariaRepo = $this->createMock(ContasBancariasRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->service = new LancamentosService(
            $this->em,
            $this->lancamentoRepo,
            $this->planoContaRepo,
            $this->pessoaRepo,
            $this->contratoRepo,
            $this->imovelRepo,
            $this->contaBancariaRepo,
            $this->security
        );
    }

    /**
     * Test listarLancamentos calls repository with filtros
     */
    public function testListarLancamentosCallsRepository(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $filtros = ['tipo' => 'receita'];

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
     * Test buscarPorId returns lancamento when found
     */
    public function testBuscarPorIdReturnLancamentoWhenFound(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('getId')->willReturn(1);

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $resultado = $this->service->buscarPorId(1);

        $this->assertInstanceOf(Lancamentos::class, $resultado);
        $this->assertEquals(1, $resultado->getId());
    }

    /**
     * Test buscarPorId returns null when not found
     */
    public function testBuscarPorIdReturnsNullWhenNotFound(): void
    {
        $this->lancamentoRepo
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarPorId(999);

        $this->assertNull($resultado);
    }

    /**
     * Test salvarLancamento creates new lancamento
     */
    public function testSalvarLancamentoCriaNovoLancamento(): void
    {
        $dados = [
            'tipo' => 'receita',
            'data_vencimento' => '2025-03-01',
            'valor' => 1000.00,
            'historico' => 'Aluguel'
        ];

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('getProximoNumero')
            ->with('receita')
            ->willReturn(1);

        $this->security
            ->method('getUser')
            ->willReturn(null);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $resultado = $this->service->salvarLancamento($dados);

        $this->assertInstanceOf(Lancamentos::class, $resultado);
    }

    /**
     * Test salvarLancamento throws exception on error
     */
    public function testSalvarLancamentoRollsBackOnError(): void
    {
        $dados = [
            'tipo' => 'receita',
            'data_vencimento' => '2025-03-01',
            'valor' => 1000.00
        ];

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $this->em
            ->expects($this->once())
            ->method('rollback');

        $this->lancamentoRepo
            ->method('getProximoNumero')
            ->willThrowException(new \Exception('DB Error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro ao salvar lançamento');

        $this->service->salvarLancamento($dados);
    }

    /**
     * Test excluirLancamento removes lancamento when not paid
     */
    public function testExcluirLancamentoRemovesWhenNotPaid(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('isPago')->willReturn(false);
        $lancamento->method('isPagoParcial')->willReturn(false);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $this->em
            ->expects($this->once())
            ->method('remove')
            ->with($lancamento);

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $resultado = $this->service->excluirLancamento($lancamento);

        $this->assertTrue($resultado);
    }

    /**
     * Test excluirLancamento throws exception when paid
     */
    public function testExcluirLancamentoThrowsExceptionWhenPaid(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('isPago')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não é possível excluir um lançamento com pagamento');

        $this->service->excluirLancamento($lancamento);
    }

    /**
     * Test getEstatisticas calls repository
     */
    public function testGetEstatisticasCallsRepository(): void
    {
        $stats = ['total' => 100, 'valor' => 10000.00];

        $this->lancamentoRepo
            ->expects($this->once())
            ->method('getEstatisticas')
            ->with(null)
            ->willReturn($stats);

        $resultado = $this->service->getEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertEquals(100, $resultado['total']);
    }

    /**
     * Test listarPlanosContaAtivos returns array of planos conta
     */
    public function testListarPlanosContaAtivosReturnsArray(): void
    {
        $planoConta = $this->createMock(PlanoContas::class);
        $planoConta->method('getId')->willReturn(1);
        $planoConta->method('getCodigo')->willReturn('1.0.0.0');

        $this->planoContaRepo
            ->expects($this->once())
            ->method('findBy')
            ->with(['ativo' => true], ['codigo' => 'ASC'])
            ->willReturn([$planoConta]);

        $resultado = $this->service->listarPlanosContaAtivos();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    /**
     * Test cancelarLancamento sets status to cancelado
     */
    public function testCancelarLancamentoSetsStatusToCancelado(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('isPago')->willReturn(false);

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $lancamento->expects($this->once())
            ->method('setStatus')
            ->with(Lancamentos::STATUS_CANCELADO);

        $lancamento->expects($this->once())
            ->method('setSuspensoMotivo')
            ->with('Cancelado');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $resultado = $this->service->cancelarLancamento(1, 'Cancelado');

        $this->assertInstanceOf(Lancamentos::class, $resultado);
    }

    /**
     * Test suspenderLancamento sets status to suspenso
     */
    public function testSuspenderLancamentoSetsStatusToSuspenso(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('isCancelado')->willReturn(false);
        $lancamento->method('isPago')->willReturn(false);

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $lancamento->expects($this->once())
            ->method('setStatus')
            ->with(Lancamentos::STATUS_SUSPENSO);

        $lancamento->expects($this->once())
            ->method('setSuspensoMotivo')
            ->with('Motivo teste');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $resultado = $this->service->suspenderLancamento(1, 'Motivo teste');

        $this->assertInstanceOf(Lancamentos::class, $resultado);
    }

    /**
     * Test reativarLancamento removes suspenso motivo
     */
    public function testReativarLancamentoRemovesSuspensoMotivo(): void
    {
        $lancamento = $this->createMock(Lancamentos::class);
        $lancamento->method('isSuspenso')->willReturn(true);

        $this->lancamentoRepo
            ->method('find')
            ->with(1)
            ->willReturn($lancamento);

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');

        $lancamento->expects($this->once())
            ->method('setSuspensoMotivo')
            ->with(null);

        $lancamento->expects($this->once())
            ->method('atualizarStatus');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $this->em
            ->expects($this->once())
            ->method('commit');

        $resultado = $this->service->reativarLancamento(1);

        $this->assertInstanceOf(Lancamentos::class, $resultado);
    }

    /**
     * Test gerarNumeroSequencial calls repository
     */
    public function testGerarNumeroSequencialCallsRepository(): void
    {
        $this->lancamentoRepo
            ->expects($this->once())
            ->method('getProximoNumero')
            ->with('receita')
            ->willReturn(123);

        $resultado = $this->service->gerarNumeroSequencial('receita');

        $this->assertEquals(123, $resultado);
    }
}
