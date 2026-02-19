<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Boletos;
use App\Entity\ConfiguracoesApiBanco;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Pessoas;
use App\Repository\BoletosRepository;
use App\Service\BoletoSantanderService;
use App\Service\SantanderAuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BoletoSantanderServiceTest extends TestCase
{
    private BoletoSantanderService $service;
    private EntityManagerInterface $em;
    private SantanderAuthService $authService;
    private BoletosRepository $boletosRepository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->authService = $this->createMock(SantanderAuthService::class);
        $this->boletosRepository = $this->createMock(BoletosRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new BoletoSantanderService(
            $this->em,
            $this->authService,
            $this->boletosRepository,
            $this->logger
        );
    }

    public function testGerarNossoNumeroFirstTime(): void
    {
        $config = new ConfiguracoesApiBanco();
        $config->setConvenio('0000123');
        $this->setPrivateProperty($config, 'id', 1);

        $this->boletosRepository
            ->expects($this->once())
            ->method('findUltimoNossoNumero')
            ->with(1)
            ->willReturn(null);

        $nossoNumero = $this->service->gerarNossoNumero($config);

        $this->assertStringStartsWith('0000123', $nossoNumero);
        $this->assertEquals(20, strlen($nossoNumero));
        $this->assertStringEndsWith('0000001', $nossoNumero);
    }

    public function testGerarNossoNumeroSequential(): void
    {
        $config = new ConfiguracoesApiBanco();
        $config->setConvenio('0000123');
        $this->setPrivateProperty($config, 'id', 1);

        $this->boletosRepository
            ->expects($this->once())
            ->method('findUltimoNossoNumero')
            ->with(1)
            ->willReturn('00001230000000005');

        $nossoNumero = $this->service->gerarNossoNumero($config);

        $this->assertStringStartsWith('0000123', $nossoNumero);
        $this->assertEquals(20, strlen($nossoNumero));
        $this->assertStringEndsWith('0000006', $nossoNumero);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    public function testCriarBoletoBasic(): void
    {
        $config = new ConfiguracoesApiBanco();
        $config->setConvenio('0000123');
        $this->setPrivateProperty($config, 'id', 1);

        $pagador = new Pessoas();
        $pagador->setNome('João Silva');

        $dataVencimento = new \DateTime('2025-12-10');

        $this->boletosRepository
            ->expects($this->once())
            ->method('findUltimoNossoNumero')
            ->willReturn(null);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $boleto = $this->service->criarBoleto(
            $config,
            $pagador,
            1000.00,
            $dataVencimento
        );

        $this->assertInstanceOf(Boletos::class, $boleto);
        $this->assertEquals('1000.00', $boleto->getValorNominal());
        $this->assertEquals($dataVencimento, $boleto->getDataVencimento());
        $this->assertEquals(Boletos::STATUS_PENDENTE, $boleto->getStatus());
    }

    public function testCriarBoletoComOpcoes(): void
    {
        $config = new ConfiguracoesApiBanco();
        $config->setConvenio('0000123');
        $this->setPrivateProperty($config, 'id', 1);

        $pagador = new Pessoas();
        $dataVencimento = new \DateTime('2025-12-10');

        $this->boletosRepository
            ->expects($this->once())
            ->method('findUltimoNossoNumero')
            ->willReturn(null);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $opcoes = [
            'mensagem_pagador' => 'Pagamento de aluguel',
            'valor_multa' => 50.00,
            'tipo_multa' => Boletos::MULTA_PERCENTUAL,
            'valor_juros_dia' => 1.50,
            'tipo_juros' => Boletos::JUROS_VALOR_DIA,
        ];

        $boleto = $this->service->criarBoleto(
            $config,
            $pagador,
            1000.00,
            $dataVencimento,
            null,
            $opcoes
        );

        $this->assertEquals('50.00', $boleto->getValorMulta());
        $this->assertEquals('1.50', $boleto->getValorJurosDia());
        $this->assertEquals('Pagamento de aluguel', $boleto->getMensagemPagador());
    }

    public function testListarBoletosComFiltros(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();
        $mockQb->expects($this->any())->method('addOrderBy')->willReturnSelf();
        $mockQb->expects($this->any())->method('setMaxResults')->willReturnSelf();
        $mockQb->expects($this->any())->method('setFirstResult')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getSingleScalarResult')->willReturn(5);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->listarBoletos(['status' => 'PENDENTE']);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('boletos', $resultado);
        $this->assertArrayHasKey('total', $resultado);
    }

    public function testBuscarPorId(): void
    {
        $boleto = new Boletos();
        $this->setPrivateProperty($boleto, 'id', 1);
        $boleto->setNossoNumero('00001230000000001');

        $pagador = new Pessoas();
        $this->setPrivateProperty($pagador, 'idpessoa', 1);
        $pagador->setNome('João Silva');

        $boleto->setPessoaPagador($pagador);

        $config = new ConfiguracoesApiBanco();
        $banco = $this->createMock(\App\Entity\Bancos::class);
        $banco->expects($this->any())->method('getNome')->willReturn('Santander');
        $config->setBanco($banco);
        $config->setConvenio('0000123');
        $boleto->setConfiguracaoApi($config);

        $this->boletosRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($boleto);

        $mockRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepo
            ->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $resultado = $this->service->buscarPorId(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('boleto', $resultado);
        $this->assertArrayHasKey('pagador', $resultado);
        $this->assertArrayHasKey('configuracao', $resultado);
    }

    public function testGetEstatisticas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery
            ->expects($this->once())
            ->method('getSingleResult')
            ->willReturn([
                'total' => 100,
                'pendentes' => 30,
                'registrados' => 20,
                'pagos' => 40,
                'vencidos' => 5,
                'baixados' => 3,
                'erros' => 2,
                'valor_total_aberto' => 5000.00,
                'valor_total_pago' => 8000.00,
            ]);

        $mockQb
            ->expects($this->any())
            ->method('getQuery')
            ->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertEquals(100, $resultado['total']);
        $this->assertEquals(30, $resultado['pendentes']);
        $this->assertEquals(40, $resultado['pagos']);
    }

    public function testDeletarBoletoSuccess(): void
    {
        $boleto = new Boletos();
        $this->setPrivateProperty($boleto, 'id', 1);
        $boleto->setStatus(Boletos::STATUS_PENDENTE);
        $boleto->setNossoNumero('00001230000000001');

        $this->boletosRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($boleto);

        $this->em
            ->expects($this->once())
            ->method('remove');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $resultado = $this->service->deletarBoleto(1);

        $this->assertTrue($resultado['sucesso']);
        $this->assertStringContainsString('sucesso', strtolower($resultado['mensagem']));
    }

    public function testDeletarBoletoNotFound(): void
    {
        $this->boletosRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->deletarBoleto(999);

        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('não encontrado', strtolower($resultado['mensagem']));
    }

    public function testDeletarBoletoNotPendente(): void
    {
        $boleto = new Boletos();
        $this->setPrivateProperty($boleto, 'id', 1);
        $boleto->setStatus(Boletos::STATUS_REGISTRADO);

        $this->boletosRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($boleto);

        $resultado = $this->service->deletarBoleto(1);

        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('pendentes', strtolower($resultado['mensagem']));
    }

    public function testRegistrarLote(): void
    {
        $boletos = [];

        $resultado = $this->service->registrarLote($boletos);

        $this->assertIsArray($resultado);
        $this->assertEquals(0, $resultado['total']);
        $this->assertArrayHasKey('sucesso', $resultado);
        $this->assertArrayHasKey('erro', $resultado);
        $this->assertArrayHasKey('detalhes', $resultado);
    }

    public function testAtualizarStatusBoletos(): void
    {
        $this->boletosRepository
            ->expects($this->once())
            ->method('findParaConsultaStatus')
            ->willReturn([]);

        $resultado = $this->service->atualizarStatusBoletos(10);

        $this->assertIsArray($resultado);
        $this->assertEquals(0, $resultado['total']);
        $this->assertEquals(0, $resultado['atualizados']);
    }
}
