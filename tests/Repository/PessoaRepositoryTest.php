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
    private ?EntityManagerInterface $entityManager = null;
    private ?PessoaRepository $repository = null;

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
            $pessoaDocumento->setPessoa($pessoa);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
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
            ->findBy(['pessoa' => $pessoa]);
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
        // Gerar CPF único
        $cpf = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);

        // Criar pessoa com CPF através de documentos
        $pessoa = $this->createPessoaWithDocument('Teste CPF', 'CPF', $cpf, 'fisica');

        // Testar busca
        $result = $this->repository->findByCpfDocumento($cpf);
        
        $this->assertNotNull($result);
        $this->assertEquals('Teste CPF', $result->getNome());
        $this->assertEquals($pessoa->getIdpessoa(), $result->getIdpessoa());

        // Testar busca inexistente
        $resultNotFound = $this->repository->findByCpfDocumento('99999999999');
        $this->assertNull($resultNotFound);

        // Limpar documentos e pessoa
        $this->cleanupPessoa($pessoa);
    }

    public function testFindByCnpj(): void
    {
        // Gerar CNPJ único
        $cnpj = str_pad((string)(int)(microtime(true) * 1000 % 100000000000000), 14, '0', STR_PAD_LEFT);

        // Criar pessoa jurídica com CNPJ através de documentos
        $pessoa = $this->createPessoaWithDocument('Empresa Teste LTDA', 'CNPJ', $cnpj, 'juridica');

        // Testar busca
        $result = $this->repository->findByCnpj($cnpj);
        
        $this->assertNotNull($result);
        $this->assertEquals('Empresa Teste LTDA', $result->getNome());
        $this->assertEquals($pessoa->getIdpessoa(), $result->getIdpessoa());

        // Testar busca inexistente
        $resultNotFound = $this->repository->findByCnpj('99999999000199');
        $this->assertNull($resultNotFound);

        // Limpar documentos e pessoa
        $this->cleanupPessoa($pessoa);
    }

    public function testFindByNome(): void
    {
        // Gerar CPFs únicos
        $cpf1 = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);
        $cpf2 = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);

        // Criar pessoas de teste sem documentos (busca por nome não depende de documentos)
        $pessoa1 = $this->createPessoaWithDocument('João Silva', 'CPF', $cpf1, 'fisica');
        $pessoa2 = $this->createPessoaWithDocument('João Santos', 'CPF', $cpf2, 'fisica');

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
        // Gerar CPF único
        $cpf = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);

        $pessoa = $this->createPessoaWithDocument('Teste Busca CPF', 'CPF', $cpf, 'fisica');
        $results = $this->repository->searchPessoa($cpf);
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaByCnpj(): void
    {
        // Gerar CNPJ único
        $cnpj = str_pad((string)(int)(microtime(true) * 1000 % 100000000000000), 14, '0', STR_PAD_LEFT);

        $pessoa = $this->createPessoaWithDocument('Empresa Busca LTDA', 'CNPJ', $cnpj, 'juridica');
        $results = $this->repository->searchPessoa($cnpj);
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaById(): void
    {
        // Gerar CPF único
        $cpf = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);

        $pessoa = $this->createPessoaWithDocument('Teste Busca ID', 'CPF', $cpf, 'fisica');
        $id = $pessoa->getIdpessoa();

        $results = $this->repository->searchPessoa((string)$id);
        
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->cleanupPessoa($pessoa);
    }

    public function testSearchPessoaByNome(): void
    {
        // Gerar CPFs únicos
        $cpf1 = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);
        $cpf2 = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);

        $pessoa1 = $this->createPessoaWithDocument('Maria Silva Test', 'CPF', $cpf1, 'fisica');
        $pessoa2 = $this->createPessoaWithDocument('Maria Santos Test', 'CPF', $cpf2, 'fisica');

        $results = $this->repository->searchPessoa('Maria Silva Test');
        
        $this->assertGreaterThanOrEqual(1, count($results));

        $this->cleanupPessoa($pessoa1);
        $this->cleanupPessoa($pessoa2);
    }

    public function testExistsByCpfOrCnpj(): void
    {
        // Gerar CPF e CNPJ únicos
        $cpf = str_pad((string)(int)(microtime(true) * 1000 % 100000000000), 11, '0', STR_PAD_LEFT);
        $cnpj = str_pad((string)(int)(microtime(true) * 1000 % 100000000000000), 14, '0', STR_PAD_LEFT);

        $pessoaFisica = $this->createPessoaWithDocument('Pessoa Física', 'CPF', $cpf, 'fisica');
        $pessoaJuridica = $this->createPessoaWithDocument('Pessoa Jurídica', 'CNPJ', $cnpj, 'juridica');

        // Testar busca por CPF existente
        $resultCpf = $this->repository->existsByCpfOrCnpj($cpf, null);
        $this->assertNotNull($resultCpf);

        // Testar busca por CNPJ existente
        $resultCnpj = $this->repository->existsByCpfOrCnpj(null, $cnpj);
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
