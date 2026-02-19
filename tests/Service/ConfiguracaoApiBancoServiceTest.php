<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ConfiguracoesApiBanco;
use App\Entity\ContasBancarias;
use App\Repository\ConfiguracoesApiBancoRepository;
use App\Service\ConfiguracaoApiBancoService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfiguracaoApiBancoServiceTest extends TestCase
{
    private ConfiguracaoApiBancoService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ConfiguracoesApiBancoRepository $repository;
    private string $projectDir;

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
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(ConfiguracoesApiBancoRepository::class);
        $this->projectDir = sys_get_temp_dir();

        $this->service = new ConfiguracaoApiBancoService(
            $this->entityManager,
            $this->logger,
            $this->repository,
            $this->projectDir
        );
    }

    /**
     * Test salvar without certificate
     */
    public function testSalvarConfigurationSemCertificado(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setConvenio('123456');
        $config->setClientId('test_client');
        $config->setClientSecret('test_secret');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->expects($this->once())->method('persist')->with($config);
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->salvar($config);

        // Assert
        $this->assertSame($config, $result);
        $this->assertInstanceOf(\DateTime::class, $config->getUpdatedAt());
    }

    /**
     * Test salvar with certificate validates before persisting
     */
    public function testSalvarConfigurationComCertificado(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setClientId('test_client');
        $config->setClientSecret('test_secret');
        $config->setCertificadoSenha('test_password');

        // Create a temporary certificate file for testing
        $certContent = 'fake certificate content';
        $tempCert = tempnam(sys_get_temp_dir(), 'cert_');
        file_put_contents($tempCert, $certContent);

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getPathname')->willReturn($tempCert);
        $uploadedFile->method('guessExtension')->willReturn('p12');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');

        $this->entityManager->method('getConnection')->willReturn($connection);

        // Act & Assert - Certificate validation should fail with invalid certificate
        try {
            $this->service->salvar($config, $uploadedFile);
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('certificado', strtolower($e->getMessage()));
        } finally {
            unlink($tempCert);
        }
    }

    /**
     * Test salvar rolls back transaction on exception
     */
    public function testSalvarRollabackTransactionOnException(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->expects($this->once())->method('persist')->willThrowException(
            new \Exception('Database error')
        );

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->service->salvar($config);
    }

    /**
     * Test listar calls repository method
     */
    public function testListarCallsRepository(): void
    {
        // Arrange
        $configs = [
            new ConfiguracoesApiBanco(),
            new ConfiguracoesApiBanco()
        ];

        $this->repository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn($configs);

        // Act
        $result = $this->service->listar();

        // Assert
        $this->assertEquals($configs, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test buscarPorId calls repository with correct parameter
     */
    public function testBuscarPorIdCallsRepository(): void
    {
        // Arrange
        $id = 123;
        $config = new ConfiguracoesApiBanco();

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($config);

        // Act
        $result = $this->service->buscarPorId($id);

        // Assert
        $this->assertSame($config, $result);
    }

    /**
     * Test buscarPorId returns null when not found
     */
    public function testBuscarPorIdReturnsNullWhenNotFound(): void
    {
        // Arrange
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        // Act
        $result = $this->service->buscarPorId(999);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test buscarPorContaBancaria calls repository correctly
     */
    public function testBuscarPorContaBancariaCallsRepository(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $config = new ConfiguracoesApiBanco();

        $this->repository->expects($this->once())
            ->method('findByContaBancaria')
            ->with($contaBancariaId)
            ->willReturn($config);

        // Act
        $result = $this->service->buscarPorContaBancaria($contaBancariaId);

        // Assert
        $this->assertSame($config, $result);
    }

    /**
     * Test buscarPorContaBancariaEAmbiente calls repository correctly
     */
    public function testBuscarPorContaBancariaEAmbienteCallsRepository(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $ambiente = 'producao';
        $config = new ConfiguracoesApiBanco();

        $this->repository->expects($this->once())
            ->method('findByContaBancariaEAmbiente')
            ->with($contaBancariaId, $ambiente)
            ->willReturn($config);

        // Act
        $result = $this->service->buscarPorContaBancariaEAmbiente($contaBancariaId, $ambiente);

        // Assert
        $this->assertSame($config, $result);
    }

    /**
     * Test deletar removes certificate file and entity
     */
    public function testDeletarRemovesCertificateFileAndEntity(): void
    {
        // Arrange
        $id = 789;
        $certPath = '/tmp/cert_test.p12';

        // Create a temporary cert file
        $tempCert = tempnam(sys_get_temp_dir(), 'cert_delete_');
        file_put_contents($tempCert, 'fake cert');

        $config = new ConfiguracoesApiBanco();
        $config->setCertificadoPath($tempCert);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($config);

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->expects($this->once())->method('remove')->with($config);
        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $result = $this->service->deletar($id);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(file_exists($tempCert));
    }

    /**
     * Test deletar returns false when config not found
     */
    public function testDeletarReturnsFalseWhenNotFound(): void
    {
        // Arrange
        $id = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        // Act
        $result = $this->service->deletar($id);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test deletar rolls back on exception
     */
    public function testDeletarRollabackOnException(): void
    {
        // Arrange
        $id = 789;
        $config = new ConfiguracoesApiBanco();

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($config);

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->expects($this->once())->method('remove')->willThrowException(
            new \Exception('Database error')
        );

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->service->deletar($id);
    }

    /**
     * Test validarCertificado returns error for non-existent file
     */
    public function testValidarCertificadoRetornaErroWhenFileNotFound(): void
    {
        // Arrange
        $path = '/path/that/does/not/exist.pfx';
        $senha = 'password';

        // Act
        $result = $this->service->validarCertificado($path, $senha);

        // Assert
        $this->assertFalse($result['valido']);
        $this->assertStringContainsString('não encontrado', $result['erro']);
        $this->assertNull($result['validade']);
        $this->assertNull($result['titular']);
    }

    /**
     * Test validarCertificado returns expected array structure
     */
    public function testValidarCertificadoReturnsCorrectStructure(): void
    {
        // Arrange
        $path = '/non/existent/file.pfx';

        // Act
        $result = $this->service->validarCertificado($path, 'password');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valido', $result);
        $this->assertArrayHasKey('validade', $result);
        $this->assertArrayHasKey('titular', $result);
        $this->assertArrayHasKey('erro', $result);
        $this->assertIsBool($result['valido']);
    }

    /**
     * Test validarCertificadoUpload uses uploaded file path
     */
    public function testValidarCertificadoUploadUsesUploadedFilePath(): void
    {
        // Arrange
        $tempFile = tempnam(sys_get_temp_dir(), 'cert_upload_');
        file_put_contents($tempFile, 'fake certificate');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        // Act
        $result = $this->service->validarCertificadoUpload($uploadedFile, 'password');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valido', $result);

        unlink($tempFile);
    }

    /**
     * Test buscarCertificadosExpirando calls repository with correct parameter
     */
    public function testBuscarCertificadosExpirandoCallsRepository(): void
    {
        // Arrange
        $dias = 30;
        $configs = [new ConfiguracoesApiBanco()];

        $this->repository->expects($this->once())
            ->method('findCertificadosExpirando')
            ->with($dias)
            ->willReturn($configs);

        // Act
        $result = $this->service->buscarCertificadosExpirando($dias);

        // Assert
        $this->assertEquals($configs, $result);
    }

    /**
     * Test atualizarToken updates config and flushes
     */
    public function testAtualizarTokenUpdatesAndFlushes(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $token = 'new_token_xyz';
        $expiresIn = 3600;

        $this->entityManager->expects($this->once())->method('flush');

        // Act
        $this->service->atualizarToken($config, $token, $expiresIn);

        // Assert
        $this->assertEquals($token, $config->getAccessToken());
        $this->assertInstanceOf(\DateTime::class, $config->getTokenExpiraEm());
        // Token expires in should be close to 1 hour from now
        $tokenExpiresSeconds = intval(($config->getTokenExpiraEm()->getTimestamp() - time()));
        $this->assertGreaterThan(3590, $tokenExpiresSeconds);
        $this->assertLessThan(3610, $tokenExpiresSeconds);
    }

    /**
     * Test existeConfiguracaoDuplicada returns false when not found
     */
    public function testExisteConfiguracaoDuplicadaRetornaFalseWhenNotFound(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $ambiente = 'sandbox';

        $this->repository->expects($this->once())
            ->method('findByContaBancariaEAmbiente')
            ->with($contaBancariaId, $ambiente)
            ->willReturn(null);

        // Act
        $result = $this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test existeConfiguracaoDuplicada returns true when found
     */
    public function testExisteConfiguracaoDuplicadaRetornaTrueWhenFound(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $ambiente = 'sandbox';
        $config = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($config, 'id', 111);

        $this->repository->expects($this->once())
            ->method('findByContaBancariaEAmbiente')
            ->with($contaBancariaId, $ambiente)
            ->willReturn($config);

        // Act
        $result = $this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test existeConfiguracaoDuplicada ignores same ID when excluding
     */
    public function testExisteConfiguracaoDuplicadaIgnoresSameIdWhenExcluding(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $ambiente = 'sandbox';
        $excludeId = 111;
        $config = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($config, 'id', $excludeId);

        $this->repository->expects($this->once())
            ->method('findByContaBancariaEAmbiente')
            ->with($contaBancariaId, $ambiente)
            ->willReturn($config);

        // Act
        $result = $this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente, $excludeId);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test existeConfiguracaoDuplicada returns true for different ID
     */
    public function testExisteConfiguracaoDuplicadaReturnsTrueForDifferentId(): void
    {
        // Arrange
        $contaBancariaId = 456;
        $ambiente = 'sandbox';
        $excludeId = 111;
        $config = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($config, 'id', 222); // Different ID

        $this->repository->expects($this->once())
            ->method('findByContaBancariaEAmbiente')
            ->with($contaBancariaId, $ambiente)
            ->willReturn($config);

        // Act
        $result = $this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente, $excludeId);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test salvar logs success
     */
    public function testSalvarLogsSuccess(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($config, 'id', 123);
        $config->setConvenio('123456');

        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('[ConfiguracaoApiBanco] Configuração salva com sucesso', $this->anything());

        // Act
        $this->service->salvar($config);

        // Assert - logger.info was called
    }

    /**
     * Test deletar logs success
     */
    public function testDeletarLogsSuccess(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();

        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');

        $this->repository->method('find')->willReturn($config);
        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->method('remove');
        $this->entityManager->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('[ConfiguracaoApiBanco] Configuração deletada', $this->anything());

        // Act
        $this->service->deletar(123);

        // Assert - logger.info was called
    }
}
