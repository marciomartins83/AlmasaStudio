<?php

namespace App\Tests\Service;

use App\Entity\Pessoas;
use App\Entity\RelacionamentosFamiliares;
use App\Repository\PessoaRepository;
use App\Service\PessoaService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Tests\DatabaseTestCase;

class PessoaServiceTest extends DatabaseTestCase
{
    // private PessoaService $service;
    // private EntityManagerInterface $entityManager;
    // private LoggerInterface $logger;
    // private PessoaRepository $pessoaRepository;

    // protected function setUp(): void
    // {
    //     $this->entityManager = $this->createMock(EntityManagerInterface::class);
    //     $this->logger = $this->createMock(LoggerInterface::class);
    //     $this->pessoaRepository = $this->createMock(PessoaRepository::class);

    //     $this->service = new PessoaService(
    //         $this->entityManager,
    //         $this->logger,
    //         $this->pessoaRepository
    //     );
    // }

    // // ... existing tests ...

    // public function testBuscarConjugePessoaValidCase(): void
    // {
    //     $pessoaId = 1;
    //     $conjugeId = 2;

    //     // Mock RelacionamentosFamiliares repository to return a relationship
    //     $relRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
    //     $rel = $this->createMock(RelacionamentosFamiliares::class);
    //     $rel->method('getIdPessoaDestino')->willReturn($conjugeId);
    //     $relRepo->method('findOneBy')
    //         ->with([
    //             'idPessoaOrigem' => $pessoaId,
    //             'tipoRelacionamento' => 'Cônjuge',
    //             'ativo' => true,
    //         ])
    //         ->willReturn($rel);

    //     // Mock PessoaRepository to return the conjugal person
    //     $conjuge = $this->createMock(Pessoas::class);
    //     $conjuge->method('getNome')->willReturn('Conjuge Teste');
    //     $conjuge->method('getDataNascimento')->willReturn(new \DateTime('1992-02-02'));
    //     $conjuge->method('getEstadoCivil')->willReturn(null);
    //     $conjuge->method('getNacionalidade')->willReturn(null);
    //     $conjuge->method('getNaturalidade')->willReturn(null);
    //     $conjuge->method('getNomePai')->willReturn(null);
    //     $conjuge->method('getNomeMae')->willReturn(null);
    //     $conjuge->method('getRenda')->willReturn(null);
    //     $conjuge->method('getObservacoes')->willReturn(null);

    //     $this->pessoaRepository->method('find')
    //         ->with($conjugeId)
    //         ->willReturn($conjuge);

    //     // Mock getCpfByPessoa to return a CPF
    //     $this->pessoaRepository->method('getCpfByPessoa')
    //         ->with($conjugeId)
    //         ->willReturn('98765432100');

    //     // Mock entityManager to return the relationship repository
    //     $this->entityManager->method('getRepository')
    //         ->willReturnCallback(function ($class) use ($relRepo) {
    //             if ($class === RelacionamentosFamiliares::class) {
    //                 return $relRepo;
    //             }
    //             return $this->createMock(\Doctrine\ORM\EntityRepository::class);
    //         });

    //     $resultado = $this->service->buscarConjugePessoa($pessoaId);

    //     $this->assertIsArray($resultado);
    //     $this->assertArrayHasKey('id', $resultado);
    //     $this->assertArrayHasKey('nome', $resultado);
    //     $this->assertArrayHasKey('cpf', $resultado);
    //     $this->assertArrayHasKey('telefones', $resultado);
    //     $this->assertArrayHasKey('emails', $resultado);
    //     $this->assertArrayHasKey('profissoes', $resultado);
    // }

    // public function testBuscarConjugePessoaNullCase(): void
    // {
    //     $pessoaId = 3;

    //     // Mock RelacionamentosFamiliares repository to return null
    //     $relRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
    //     $relRepo->method('findOneBy')
    //         ->willReturn(null);

    //     // Mock entityManager to return the relationship repository
    //     $this->entityManager->method('getRepository')
    //         ->willReturnCallback(function ($class) use ($relRepo) {
    //             if ($class === RelacionamentosFamiliares::class) {
    //                 return $relRepo;
    //             }
    //             return $this->createMock(\Doctrine\ORM.EntityRepository::class);
    //         });

    //     $resultado = $this->service->buscarConjugePessoa($pessoaId);

    //     $this->assertNull($resultado);
    // }

    public function testBuscarConjugePessoaWithRealData(): void
    {
        // Carrega o container e o serviço real
        $service = $this->getContainer()->get(PessoaService::class);

        // ID 14769 foi criado pela fixture e possui um cônjuge
        $pessoaId = 14769;

        $resultado = $service->buscarConjugePessoa($pessoaId);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('id', $resultado);
        $this->assertArrayHasKey('nome', $resultado);
        $this->assertArrayHasKey('cpf', $resultado);
        $this->assertArrayHasKey('telefones', $resultado);
        $this->assertArrayHasKey('emails', $resultado);
        $this->assertArrayHasKey('profissoes', $resultado);
    }

    public function testBuscarConjugePessoaWithoutConjuge(): void
    {
        // Carrega o container e o serviço real
        $service = $this->getContainer()->get(PessoaService::class);

        // ID 14770 foi criado pela fixture e NÃO possui cônjuge
        $pessoaId = 14770;

        $resultado = $service->buscarConjugePessoa($pessoaId);

        $this->assertNull($resultado);
    }
}
