<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Entity\Logradouros;
use App\Service\CepService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CepServiceTest extends TestCase
{
    private CepService $service;
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;

    /**
     * Helper method to set private properties on objects
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setValue($object, $value);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service = new CepService($this->entityManager, $this->httpClient);
    }

    /**
     * Test buscarEpersistirEndereco with CEP already in database
     */
    public function testBuscarEpersistirEnderecoRetornaEnderecoCadastrado(): void
    {
        // Arrange
        $cep = '01310-100';
        $estado = new Estados();
        $estado->setUf('SP');
        $estado->setNome('São Paulo');
        $this->setPrivateProperty($estado, 'id', 1);

        $cidade = new Cidades();
        $cidade->setNome('São Paulo');
        $cidade->setEstado($estado);
        $this->setPrivateProperty($cidade, 'id', 1);

        $bairro = new Bairros();
        $bairro->setNome('Centro');
        $bairro->setCidade($cidade);
        $this->setPrivateProperty($bairro, 'id', 1);

        $logradouro = new Logradouros();
        $logradouro->setLogradouro('Avenida Paulista');
        $logradouro->setCep('01310100');
        $logradouro->setBairro($bairro);
        $this->setPrivateProperty($logradouro, 'id', 1);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')
            ->with(['cep' => '01310100'])
            ->willReturn($logradouro);

        $this->entityManager->method('getRepository')
            ->with(Logradouros::class)
            ->willReturn($repository);

        // Act
        $result = $this->service->buscarEpersistirEndereco($cep);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Avenida Paulista', $result['logradouro']);
        $this->assertEquals('Centro', $result['bairro']);
        $this->assertEquals('São Paulo', $result['cidade']);
        $this->assertEquals('SP', $result['estado']);
        $this->assertEquals(1, $result['idLogradouro']);
        $this->assertEquals(1, $result['idBairro']);
        $this->assertEquals(1, $result['idCidade']);
        $this->assertEquals(1, $result['idEstado']);
    }

    /**
     * Test buscarEpersistirEndereco removes non-numeric characters from CEP
     */
    public function testBuscarEpersistirEnderecoRemoveCaracteresNaoNumericos(): void
    {
        // Arrange
        $cep = '01.310-100';

        // Create mocks for all repositories needed
        $logradourosRepo = $this->createMock(EntityRepository::class);
        $estadosRepo = $this->createMock(EntityRepository::class);
        $cidadesRepo = $this->createMock(EntityRepository::class);
        $bairrosRepo = $this->createMock(EntityRepository::class);

        $logradourosRepo->method('findOneBy')
            ->with(['cep' => '01310100'])
            ->willReturn(null);

        // Setup API response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'logradouro' => 'Avenida Paulista',
                'bairro' => 'Centro',
                'localidade' => 'São Paulo',
                'uf' => 'SP'
            ]);

        $this->httpClient->method('request')
            ->with('GET', 'https://viacep.com.br/ws/01310100/json/')
            ->willReturn($response);

        // Setup entities
        $estado = new Estados();
        $estado->setUf('SP');
        $estado->setNome('SP');
        $this->setPrivateProperty($estado, 'id', 1);

        $cidade = new Cidades();
        $cidade->setNome('São Paulo');
        $cidade->setEstado($estado);
        $this->setPrivateProperty($cidade, 'id', 1);

        $bairro = new Bairros();
        $bairro->setNome('Centro');
        $bairro->setCidade($cidade);
        $this->setPrivateProperty($bairro, 'id', 1);

        $estadosRepo->method('findOneBy')->willReturn($estado);
        $cidadesRepo->method('findOneBy')->willReturn($cidade);
        $bairrosRepo->method('findOneBy')->willReturn($bairro);

        // Configure entity manager to return correct repos
        $this->entityManager->method('getRepository')
            ->will($this->returnCallback(function ($class) use (
                $logradourosRepo,
                $estadosRepo,
                $cidadesRepo,
                $bairrosRepo
            ) {
                return match ($class) {
                    Logradouros::class => $logradourosRepo,
                    Estados::class => $estadosRepo,
                    Cidades::class => $cidadesRepo,
                    Bairros::class => $bairrosRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            }));

        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->buscarEpersistirEndereco($cep);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Avenida Paulista', $result['logradouro']);
    }

    /**
     * Test buscarEpersistirEndereco throws exception when CEP not found in ViaCEP
     */
    public function testBuscarEpersistirEnderecoThrowsExceptionWhenCepNotFound(): void
    {
        // Arrange
        $cep = '00000000';
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->with(Logradouros::class)
            ->willReturn($repository);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn(['erro' => true]);

        $this->httpClient->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CEP não encontrado');

        // Act
        $this->service->buscarEpersistirEndereco($cep);
    }

    /**
     * Test buscarEpersistirEndereco throws exception on empty response
     */
    public function testBuscarEpersistirEnderecoThrowsExceptionOnEmptyResponse(): void
    {
        // Arrange
        $cep = '12345678';
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->with(Logradouros::class)
            ->willReturn($repository);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([]);

        $this->httpClient->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CEP não encontrado');

        // Act
        $this->service->buscarEpersistirEndereco($cep);
    }

    /**
     * Test buscarEpersistirEndereco creates new estado when not found
     */
    public function testBuscarEpersistirEnderecoCreateNovoEstado(): void
    {
        // Arrange
        $cep = '12345678';
        $logradourosRepo = $this->createMock(EntityRepository::class);
        $estadosRepo = $this->createMock(EntityRepository::class);
        $cidadesRepo = $this->createMock(EntityRepository::class);
        $bairrosRepo = $this->createMock(EntityRepository::class);

        $logradourosRepo->method('findOneBy')->willReturn(null);
        $estadosRepo->method('findOneBy')->willReturn(null);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'logradouro' => 'Rua Teste',
                'bairro' => 'Bairro Teste',
                'localidade' => 'Cidade Teste',
                'uf' => 'XX'
            ]);

        $this->httpClient->method('request')
            ->willReturn($response);

        $this->entityManager->method('getRepository')
            ->will($this->returnCallback(function ($class) use (
                $logradourosRepo,
                $estadosRepo,
                $cidadesRepo,
                $bairrosRepo
            ) {
                return match ($class) {
                    Logradouros::class => $logradourosRepo,
                    Estados::class => $estadosRepo,
                    Cidades::class => $cidadesRepo,
                    Bairros::class => $bairrosRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            }));

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->service->buscarEpersistirEndereco($cep);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Rua Teste', $result['logradouro']);
    }

    /**
     * Test buscarEpersistirEndereco uses existing estado when found
     */
    public function testBuscarEpersistirEnderecoUsaEstadoExistente(): void
    {
        // Arrange
        $cep = '12345678';
        $logradourosRepo = $this->createMock(EntityRepository::class);
        $estadosRepo = $this->createMock(EntityRepository::class);
        $cidadesRepo = $this->createMock(EntityRepository::class);
        $bairrosRepo = $this->createMock(EntityRepository::class);

        $logradourosRepo->method('findOneBy')->willReturn(null);

        $estado = new Estados();
        $this->setPrivateProperty($estado, 'id', 99);
        $estado->setUf('SP');
        $estado->setNome('São Paulo');

        $estadosRepo->method('findOneBy')
            ->with(['uf' => 'SP'])
            ->willReturn($estado);

        $cidadesRepo->method('findOneBy')->willReturn(null);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')
            ->willReturn([
                'logradouro' => 'Rua Teste',
                'bairro' => 'Bairro Teste',
                'localidade' => 'Cidade Nova',
                'uf' => 'SP'
            ]);

        $this->httpClient->method('request')
            ->willReturn($response);

        $this->entityManager->method('getRepository')
            ->will($this->returnCallback(function ($class) use (
                $logradourosRepo,
                $estadosRepo,
                $cidadesRepo,
                $bairrosRepo
            ) {
                return match ($class) {
                    Logradouros::class => $logradourosRepo,
                    Estados::class => $estadosRepo,
                    Cidades::class => $cidadesRepo,
                    Bairros::class => $bairrosRepo,
                    default => $this->createMock(EntityRepository::class),
                };
            }));

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->service->buscarEpersistirEndereco($cep);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('SP', $result['estado']);
    }

    /**
     * Test buscarEpersistirEndereco with valid CEP format variations
     */
    public function testBuscarEpersistirEnderecoHandlesVariousCepFormats(): void
    {
        $cepFormats = [
            '01310-100',
            '01.310-100',
            '01310100',
            '01-310-100'
        ];

        foreach ($cepFormats as $cepFormat) {
            $this->setUp();

            $logradouro = new Logradouros();
            $logradouro->setLogradouro('Avenida Paulista');
            $logradouro->setCep('01310100');
            $this->setPrivateProperty($logradouro, 'id', 1);

            $bairro = new Bairros();
            $bairro->setNome('Centro');
            $this->setPrivateProperty($bairro, 'id', 1);

            $logradouro->setBairro($bairro);

            $cidade = new Cidades();
            $cidade->setNome('São Paulo');
            $this->setPrivateProperty($cidade, 'id', 1);

            $bairro->setCidade($cidade);

            $estado = new Estados();
            $estado->setUf('SP');
            $this->setPrivateProperty($estado, 'id', 1);

            $cidade->setEstado($estado);

            $repository = $this->createMock(EntityRepository::class);
            $repository->method('findOneBy')
                ->with(['cep' => '01310100'])
                ->willReturn($logradouro);

            $this->entityManager->method('getRepository')
                ->with(Logradouros::class)
                ->willReturn($repository);

            $result = $this->service->buscarEpersistirEndereco($cepFormat);

            $this->assertIsArray($result);
            $this->assertEquals('Avenida Paulista', $result['logradouro']);
        }
    }
}
