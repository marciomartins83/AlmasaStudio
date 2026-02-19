<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ConfiguracoesApiBanco;
use App\Service\SantanderAuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SantanderAuthServiceTest extends TestCase
{
    private SantanderAuthService $service;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->projectDir = sys_get_temp_dir();

        $this->service = new SantanderAuthService(
            $this->em,
            $this->logger,
            $this->projectDir
        );
    }

    /**
     * Test obterAccessToken returns cached token when valid
     */
    public function testObterAccessTokenRetornaCachedTokenValido(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken('cached_token_123');
        $config->setTokenExpiraEm((new \DateTime())->modify('+10 minutes'));

        // Act
        $result = $this->service->obterAccessToken($config);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('cached_token_123', $result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertGreaterThan(0, $result['expires_in']);
    }

    /**
     * Test obterAccessToken throws exception when ClientId is empty
     */
    public function testObterAccessTokenThrowsExceptionWhenClientIdEmpty(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken(null);
        $config->setTokenExpiraEm(null);
        $config->setClientId('');
        $config->setClientSecret('test_secret');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Client ID não configurado');

        // Act
        $this->service->obterAccessToken($config);
    }

    /**
     * Test obterAccessToken throws exception when ClientSecret is empty
     */
    public function testObterAccessTokenThrowsExceptionWhenClientSecretEmpty(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken(null);
        $config->setTokenExpiraEm(null);
        $config->setClientId('test_client');
        $config->setClientSecret('');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Client Secret não configurado');

        // Act
        $this->service->obterAccessToken($config);
    }

    /**
     * Test obterAccessToken throws exception when certificado path is empty
     */
    public function testObterAccessTokenThrowsExceptionWhenCertificadoPathEmpty(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken(null);
        $config->setTokenExpiraEm(null);
        $config->setClientId('test_client');
        $config->setClientSecret('test_secret');
        $config->setCertificadoPath('');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificado não configurado');

        // Act
        $this->service->obterAccessToken($config);
    }

    /**
     * Test obterAccessToken throws exception when certificate file not found
     */
    public function testObterAccessTokenThrowsExceptionWhenCertificadoNotFound(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken(null);
        $config->setTokenExpiraEm(null);
        $config->setClientId('test_client');
        $config->setClientSecret('test_secret');
        $config->setCertificadoPath('/non/existent/path/cert.p12');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arquivo de certificado não encontrado');

        // Act
        $this->service->obterAccessToken($config);
    }

    /**
     * Test testarConexao returns false when configuration validation fails
     */
    public function testTestarConexaoRetornaFalsoWhenConfigurationInvalid(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setClientId('');
        $config->setClientSecret('secret');

        // Act
        $resultado = $this->service->testarConexao($config);

        // Assert
        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('Client ID', $resultado['mensagem']);
        $this->assertArrayHasKey('erro', $resultado['detalhes']);
    }

    /**
     * Test testarConexao returns error when certificate file missing
     */
    public function testTestarConexaoRetornaErroWhenCertificadoMissing(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setClientId('test_client');
        $config->setClientSecret('test_secret');
        $config->setCertificadoPath('/path/that/does/not/exist.p12');

        // Act
        $resultado = $this->service->testarConexao($config);

        // Assert
        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('certificado não encontrado', $resultado['mensagem']);
    }

    /**
     * Test testarConexao catches all exceptions gracefully
     */
    public function testTestarConexaoCatchesAllExceptions(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setClientId(null);

        // Act
        $resultado = $this->service->testarConexao($config);

        // Assert - should not throw exception, but return error array
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['sucesso']);
        $this->assertArrayHasKey('mensagem', $resultado);
        $this->assertArrayHasKey('detalhes', $resultado);
    }

    /**
     * Test testarConexao returns success array structure
     */
    public function testTestarConexaoReturnsCorrectArrayStructure(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setClientId('test');
        // Intentionally leave secret empty to trigger validation error

        // Act
        $resultado = $this->service->testarConexao($config);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('sucesso', $resultado);
        $this->assertArrayHasKey('mensagem', $resultado);
        $this->assertArrayHasKey('detalhes', $resultado);
        $this->assertIsBool($resultado['sucesso']);
        $this->assertIsString($resultado['mensagem']);
        $this->assertIsArray($resultado['detalhes']);
    }

    /**
     * Test getCertificadoPath handles absolute paths
     */
    public function testGetCertificadoPathHandlesAbsolutePaths(): void
    {
        // Arrange - use reflection to test private method
        $reflectionMethod = new \ReflectionMethod($this->service, 'getCertificadoPath');
        $reflectionMethod->setAccessible(true);

        $config = new ConfiguracoesApiBanco();
        $config->setCertificadoPath('/absolute/path/cert.p12');

        // Act
        $result = $reflectionMethod->invoke($this->service, $config);

        // Assert
        $this->assertEquals('/absolute/path/cert.p12', $result);
    }

    /**
     * Test getCertificadoPath concatenates with project dir for relative paths
     */
    public function testGetCertificadoPathConcatenatesProjectDir(): void
    {
        // Arrange
        $reflectionMethod = new \ReflectionMethod($this->service, 'getCertificadoPath');
        $reflectionMethod->setAccessible(true);

        $config = new ConfiguracoesApiBanco();
        $config->setCertificadoPath('var/certificates/cert.p12');

        // Act
        $result = $reflectionMethod->invoke($this->service, $config);

        // Assert
        $this->assertStringContainsString($this->projectDir, $result);
        $this->assertStringEndsWith('cert.p12', $result);
    }

    /**
     * Test token expiration datetime is properly set and retrieved
     */
    public function testTokenExpirationIsProperlyFormatted(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $futureTime = (new \DateTime())->modify('+2 hours');
        $config->setAccessToken('token_123');
        $config->setTokenExpiraEm($futureTime);

        // Act
        $result = $this->service->obterAccessToken($config);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertIsInt($result['expires_in']);
        $this->assertGreaterThan(0, $result['expires_in']);
    }

    /**
     * Test request method requires valid token
     */
    public function testRequestMethodRequiresValidToken(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        // No token, so it will try to get one and fail on missing credentials
        $config->setClientId('');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->request($config, 'GET', '/test');
    }

    /**
     * Test testarConexao returns proper structure on error
     */
    public function testTestarConexaoStructureOnError(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        // Missing all required fields

        // Act
        $resultado = $this->service->testarConexao($config);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('sucesso', $resultado);
        $this->assertArrayHasKey('mensagem', $resultado);
        $this->assertArrayHasKey('detalhes', $resultado);
        $this->assertFalse($resultado['sucesso']);
    }

    /**
     * Test that token refresh triggers when cache expires
     */
    public function testTokenRefreshTriggersWhenCacheExpiresSoon(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        // Token expires in 2 minutes - should be rejected (margin is 5 minutes)
        $config->setAccessToken('old_token_123');
        $config->setTokenExpiraEm((new \DateTime())->modify('+2 minutes'));
        $config->setClientId('');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        // Token will be rejected and it will try to get a new one, failing on missing ClientId
        $this->service->obterAccessToken($config);
    }

    /**
     * Test request method with empty configuration
     */
    public function testRequestMethodWithEmptyConfiguration(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->request($config, 'GET', '/test');
    }

    /**
     * Test obterAccessToken with null token expiration
     */
    public function testObterAccessTokenWithNullTokenExpiration(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $config->setAccessToken('some_token');
        $config->setTokenExpiraEm(null);
        $config->setClientId('');

        // Act & Assert - will fail due to no ClientId
        $this->expectException(\RuntimeException::class);
        $this->service->obterAccessToken($config);
    }

    /**
     * Test response array structure from obterAccessToken
     */
    public function testObterAccessTokenResponseStructure(): void
    {
        // Arrange
        $config = new ConfiguracoesApiBanco();
        $futureTime = (new \DateTime())->modify('+1 hour');
        $config->setAccessToken('test_token');
        $config->setTokenExpiraEm($futureTime);

        // Act
        $result = $this->service->obterAccessToken($config);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);
    }
}
