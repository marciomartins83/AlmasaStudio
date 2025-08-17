<?php

namespace App\Tests\Repository;

use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use App\Repository\PessoaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PessoaRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PessoaRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->repository = $this->entityManager->getRepository(Pessoas::class);
    }

    private function createPessoaWithDocument(string $nome, string $tipoDocumento, string $numeroDocumento, string $fisicaJuridica): Pessoas
    {
        // Criar pessoa
        $pessoa = new Pessoas();
        $pessoa->setNome($nome);
        $pessoa->setDtCadastro(new \DateTime());
        $pessoa->setTipoPessoa(1);
        $pessoa->setStatus(true);
        $pessoa->setFisicaJuridica($fisicaJuridica);

        $this->entityManager->persist($pessoa);
        $this->entityManager->flush();

        // Buscar tipo de documento
        $tipoDocumentoEntity = $this->entityManager->getRepository(TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipoDocumento]);

        if ($tipoDocumentoEntity) {
            // Criar documento
            $pessoaDocumento = new PessoasDocumentos();
            $pessoaDocumento->setIdPessoa($pessoa->getIdpessoa());
            $pessoaDocumento->setIdTipoDocumento($tipoDocumentoEntity->getId());
            $pessoaDocumento->setNumeroDocumento($numeroDocumento);
            $pessoaDocumento->setAtivo(true);

            $this->entityManager->persist($pessoaDocumento);
            $this->entityManager->flush();
        }

        return $pessoa;
    }

    private function cleanupPessoa(Pessoas $pessoa): void
    {
        // Limpar documentos
        $documentos = $this->entityManager->getRepository(PessoasDocumentos::class)
            ->findBy(['idPessoa' => $pessoa->getIdpessoa()]);
        foreach ($documentos as $documento) {
            $this->entityManager->remove($documento);
        }
        
        // Limpar pessoa
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(PessoaRepository::class, $this->repository);
    }

    public function testFindByCpf(): void
    {
        // Criar pessoa com CPF através de documentos
        $pessoa = $this->createPessoaWithDocument('Teste CPF', 'CPF', '12345678901', 'fisica');

        // Testar busca
        $result = $this->repository->findByCpf('12345678901');
        
        $this->assertNotNull($result);
        $this->assertEquals('Teste CPF', $result->getNome());
        $this->assertEquals($pessoa->getIdpessoa(), $result->getIdpessoa());

        // Testar busca inexistente
        $resultNotFound = $this->repository->findByCpf('99999999999');
        $this->assertNull($resultNotFound);

        // Limpar documentos e pessoa
        $documentos = $this->entityManager->getRepository(PessoasDocumentos::class)
            ->findBy(['idPessoa' => $pessoa->getIdpessoa()]);
        foreach ($documentos as $documento) {
            $this->entityManager->remove($documento);
        }
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testFindByCnpj(): void
    {
        // Criar pessoa jurídica com CNPJ através de documentos
        $pessoa = $this->createPessoaWithDocument('Empresa Teste LTDA', 'CNPJ', '12345678000190', 'juridica');

        // Testar busca
        $result = $this->repository->findByCnpj('12345678000190');
        
        $this->assertNotNull($result);
        $this->assertEquals('Empresa Teste LTDA', $result->getNome());
        $this->assertEquals($pessoa->getIdpessoa(), $result->getIdpessoa());

        // Testar busca inexistente
        $resultNotFound = $this->repository->findByCnpj('99999999000199');
        $this->assertNull($resultNotFound);

        // Limpar documentos e pessoa
        $documentos = $this->entityManager->getRepository(PessoasDocumentos::class)
            ->findBy(['idPessoa' => $pessoa->getIdpessoa()]);
        foreach ($documentos as $documento) {
            $this->entityManager->remove($documento);
        }
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testFindByNome(): void
    {
        // Criar pessoas de teste sem documentos (busca por nome não depende de documentos)
        $pessoa1 = $this->createPessoaWithDocument('João Silva', 'CPF', '11111111111', 'fisica');
        $pessoa2 = $this->createPessoaWithDocument('João Santos', 'CPF', '22222222222', 'fisica');

        // Testar busca parcial
        $results = $this->repository->findByNome('João');
        $this->assertGreaterThanOrEqual(2, count($results)); // Pode ter outras pessoas João no banco
        
        // Testar busca mais específica
        $resultsSpecific = $this->repository->findByNome('Silva');
        $this->assertGreaterThanOrEqual(1, count($resultsSpecific));

        // Testar busca inexistente
        $resultsNotFound = $this->repository->findByNome('NomeInexistenteCompleto123');
        $this->assertCount(0, $resultsNotFound);

        // Limpar
        $this->cleanupPessoa($pessoa1);
        $this->cleanupPessoa($pessoa2);
    }

    public function testSearchPessoaByCpf(): void
    {
        $pessoa = $this->createPessoaWithDocument('Teste Busca CPF', 'CPF', '33333333333', 'fisica');
        $results = $this->repository->searchPessoa('33333333333');
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaByCnpj(): void
    {
        $pessoa = $this->createPessoaWithDocument('Empresa Busca LTDA', 'CNPJ', '44444444000144', 'juridica');
        $results = $this->repository->searchPessoa('44444444000144');
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaById(): void
    {
        $pessoa = $this->createPessoaWithDocument('Teste Busca ID', 'CPF', '55555555555', 'fisica');
        $id = $pessoa->getIdpessoa();

        $results = $this->repository->searchPessoa((string)$id);
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaByNome(): void
    {
        $pessoa1 = $this->createPessoaWithDocument('Maria Silva Test', 'CPF', '66666666666', 'fisica');
        $pessoa2 = $this->createPessoaWithDocument('Maria Santos Test', 'CPF', '77777777777', 'fisica');

        $results = $this->repository->searchPessoa('Maria Silva Test');
        
        $this->assertGreaterThanOrEqual(1, count($results));

        $this->cleanupPessoa($pessoa1);
        $this->cleanupPessoa($pessoa2);
    }

    public function testExistsByCpfOrCnpj(): void
    {
        $pessoaFisica = $this->createPessoaWithDocument('Pessoa Física', 'CPF', '88888888888', 'fisica');
        $pessoaJuridica = $this->createPessoaWithDocument('Pessoa Jurídica', 'CNPJ', '88888888000188', 'juridica');

        // Testar busca por CPF existente
        $resultCpf = $this->repository->existsByCpfOrCnpj('88888888888', null);
        $this->assertNotNull($resultCpf);

        // Testar busca por CNPJ existente
        $resultCnpj = $this->repository->existsByCpfOrCnpj(null, '88888888000188');
        $this->assertNotNull($resultCnpj);

        // Testar busca por CPF inexistente
        $resultNotFound = $this->repository->existsByCpfOrCnpj('99999999999', null);
        $this->assertNull($resultNotFound);

        // Testar busca sem parâmetros
        $resultNoParams = $this->repository->existsByCpfOrCnpj(null, null);
        $this->assertNull($resultNoParams);

        $this->cleanupPessoa($pessoaFisica);
        $this->cleanupPessoa($pessoaJuridica);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
