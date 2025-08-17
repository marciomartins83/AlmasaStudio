<?php

namespace App\Tests\Controller;

use App\Entity\Pessoas;
use App\Entity\PessoasFiadores;
use App\Entity\EstadoCivil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PessoaFiadorControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testControllerClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Controller\\PessoaFiadorController'));
    }

    public function testEntityClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Entity\\PessoasFiadores'));
    }

    public function testFormTypeExists(): void
    {
        $this->assertTrue(class_exists('App\\Form\\PessoaFiadorFormType'));
    }

    public function testIndexPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pessoa-fiador/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Fiadores');
    }

    public function testNewFormPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/pessoa-fiador/new');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Novo Fiador');
        
        // Verificar se os campos de busca estão presentes
        $this->assertSelectorExists('#search-term');
        $this->assertSelectorExists('#pessoa-cpf');
        $this->assertSelectorExists('#pessoa-cnpj');
        $this->assertSelectorExists('#pessoa-nome');
        
        // Verificar se os campos específicos para pessoa física estão presentes
        $this->assertSelectorExists('#pessoa-data-nascimento');
        $this->assertSelectorExists('#pessoa-nome-pai');
        $this->assertSelectorExists('#pessoa-nome-mae');
        
        // Verificar se o campo de busca de cônjuge está presente
        $this->assertSelectorExists('#search-conjuge');
    }

    public function testSearchPessoaByCpf(): void
    {
        $client = static::createClient();
        
        // Criar uma pessoa de teste
        $pessoa = new Pessoas();
        $pessoa->setNome('João Silva');
        $pessoa->setCpf('12345678901');
        $pessoa->setDtCadastro(new \DateTime());
        $pessoa->setTipoPessoa(1);
        $pessoa->setStatus(true);
        $pessoa->setFisicaJuridica('F');
        
        $this->entityManager->persist($pessoa);
        $this->entityManager->flush();
        
        // Testar busca por CPF
        $crawler = $client->request('POST', '/pessoa-fiador/search-pessoa', [
            'searchTerm' => '12345678901'
        ]);
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('pessoas', $response);
        $this->assertCount(1, $response['pessoas']);
        $this->assertEquals('João Silva', $response['pessoas'][0]['nome']);
        $this->assertEquals('12345678901', $response['pessoas'][0]['cpf']);
        
        // Limpar
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testSearchPessoaByCnpj(): void
    {
        $client = static::createClient();
        
        // Criar uma pessoa jurídica de teste
        $pessoa = new Pessoas();
        $pessoa->setNome('Empresa Silva LTDA');
        $pessoa->setCnpj('12345678000190');
        $pessoa->setDtCadastro(new \DateTime());
        $pessoa->setTipoPessoa(1);
        $pessoa->setStatus(true);
        $pessoa->setFisicaJuridica('J');
        
        $this->entityManager->persist($pessoa);
        $this->entityManager->flush();
        
        // Testar busca por CNPJ
        $crawler = $client->request('POST', '/pessoa-fiador/search-pessoa', [
            'searchTerm' => '12345678000190'
        ]);
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('pessoas', $response);
        $this->assertCount(1, $response['pessoas']);
        $this->assertEquals('Empresa Silva LTDA', $response['pessoas'][0]['nome']);
        $this->assertEquals('12345678000190', $response['pessoas'][0]['cnpj']);
        
        // Limpar
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testSearchPessoaByNome(): void
    {
        $client = static::createClient();
        
        // Criar pessoas de teste
        $pessoa1 = new Pessoas();
        $pessoa1->setNome('Maria Silva');
        $pessoa1->setCpf('11111111111');
        $pessoa1->setDtCadastro(new \DateTime());
        $pessoa1->setTipoPessoa(1);
        $pessoa1->setStatus(true);
        $pessoa1->setFisicaJuridica('F');
        
        $pessoa2 = new Pessoas();
        $pessoa2->setNome('Maria Santos');
        $pessoa2->setCpf('22222222222');
        $pessoa2->setDtCadastro(new \DateTime());
        $pessoa2->setTipoPessoa(1);
        $pessoa2->setStatus(true);
        $pessoa2->setFisicaJuridica('F');
        
        $this->entityManager->persist($pessoa1);
        $this->entityManager->persist($pessoa2);
        $this->entityManager->flush();
        
        // Testar busca por nome
        $crawler = $client->request('POST', '/pessoa-fiador/search-pessoa', [
            'searchTerm' => 'Maria'
        ]);
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('pessoas', $response);
        $this->assertCount(2, $response['pessoas']);
        
        // Limpar
        $this->entityManager->remove($pessoa1);
        $this->entityManager->remove($pessoa2);
        $this->entityManager->flush();
    }

    public function testCreateFiadorWithNewPessoa(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/pessoa-fiador/new');
        
        // Preencher e submeter o formulário
        $form = $crawler->selectButton('Salvar Fiador')->form([
            'pessoa_fiador_form[nome]' => 'José da Silva',
            'pessoa_fiador_form[cpf]' => '33333333333',
            'pessoa_fiador_form[motivoFianca]' => 'Teste de fiança',
            'pessoa_fiador_form[jaFoiFiador]' => false,
            'pessoa_fiador_form[conjugeTrabalha]' => false,
        ]);
        
        $client->submit($form);
        
        $this->assertResponseRedirects('/pessoa-fiador/');
        
        // Verificar se a pessoa foi criada
        $pessoa = $this->entityManager->getRepository(Pessoas::class)
            ->findByCpf('33333333333');
        $this->assertNotNull($pessoa);
        $this->assertEquals('José da Silva', $pessoa->getNome());
        $this->assertTrue($pessoa->isPessoaFisica());
        
        // Verificar se o fiador foi criado
        $fiador = $this->entityManager->getRepository(PessoasFiadores::class)
            ->findOneBy(['idPessoa' => $pessoa->getIdpessoa()]);
        $this->assertNotNull($fiador);
        $this->assertEquals('Teste de fiança', $fiador->getMotivoFianca());
        
        // Limpar
        $this->entityManager->remove($fiador);
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    public function testValidationCpfOrCnpjRequired(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/pessoa-fiador/new');
        
        // Tentar submeter formulário sem CPF nem CNPJ
        $form = $crawler->selectButton('Salvar Fiador')->form([
            'pessoa_fiador_form[nome]' => 'Pessoa Sem Documento',
            'pessoa_fiador_form[motivoFianca]' => 'Teste',
        ]);
        
        $crawler = $client->submit($form);
        
        // Deve mostrar erro de validação
        $this->assertSelectorTextContains('.alert-danger', 'É obrigatório informar CPF ou CNPJ');
    }

    public function testValidationBothCpfAndCnpj(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/pessoa-fiador/new');
        
        // Tentar submeter formulário com CPF e CNPJ
        $form = $crawler->selectButton('Salvar Fiador')->form([
            'pessoa_fiador_form[nome]' => 'Pessoa Com Ambos Documentos',
            'pessoa_fiador_form[cpf]' => '44444444444',
            'pessoa_fiador_form[cnpj]' => '12345678000190',
            'pessoa_fiador_form[motivoFianca]' => 'Teste',
        ]);
        
        $crawler = $client->submit($form);
        
        // Deve mostrar erro de validação
        $this->assertSelectorTextContains('.alert-danger', 'Informe apenas CPF ou CNPJ, não ambos');
    }

    public function testPreventDuplicateFiador(): void
    {
        $client = static::createClient();
        
        // Criar pessoa e fiador existentes
        $pessoa = new Pessoas();
        $pessoa->setNome('José Existente');
        $pessoa->setCpf('55555555555');
        $pessoa->setDtCadastro(new \DateTime());
        $pessoa->setTipoPessoa(4);
        $pessoa->setStatus(true);
        $pessoa->setFisicaJuridica('F');
        
        $this->entityManager->persist($pessoa);
        $this->entityManager->flush();
        
        $fiador = new PessoasFiadores();
        $fiador->setIdPessoa($pessoa->getIdpessoa());
        $fiador->setMotivoFianca('Fiador existente');
        $fiador->setJaFoiFiador(false);
        $fiador->setConjugeTrabalha(false);
        
        $this->entityManager->persist($fiador);
        $this->entityManager->flush();
        
        // Tentar criar fiador duplicado
        $crawler = $client->request('GET', '/pessoa-fiador/new');
        
        $form = $crawler->selectButton('Salvar Fiador')->form([
            'pessoa_fiador_form[nome]' => 'José Existente',
            'pessoa_fiador_form[cpf]' => '55555555555',
            'pessoa_fiador_form[motivoFianca]' => 'Tentativa duplicada',
        ]);
        
        $crawler = $client->submit($form);
        
        // Deve mostrar erro
        $this->assertSelectorTextContains('.alert-danger', 'Esta pessoa já está cadastrada como fiador');
        
        // Limpar
        $this->entityManager->remove($fiador);
        $this->entityManager->remove($pessoa);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}