<?php

namespace App\Tests\Entity;

use App\Entity\ConfiguracoesApiBanco;
use App\Entity\Bancos;
use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class ConfiguracoesApiBancoTest extends TestCase
{
    public function testDefaultValuesAndConstructor()
    {
        $entity = new ConfiguracoesApiBanco();

        // Id should be null initially
        $this->assertNull($entity->getId());

        // Default values
        $this->assertSame('101', $entity->getCarteira());
        $this->assertSame('sandbox', $entity->getAmbiente());
        $this->assertTrue($entity->isAtivo());

        // CreatedAt and UpdatedAt should be set
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());

        // URL base should be set according to sandbox
        $this->assertSame(
            'https://trust-sandbox.api.santander.com.br/collection_bill_management/v2',
            $entity->getUrlBase()
        );
    }

    public function testGettersAndSetters()
    {
        $entity = new ConfiguracoesApiBanco();

        // Banco
        $bancoMock = $this->createMock(Bancos::class);
        $entity->setBanco($bancoMock);
        $this->assertSame($bancoMock, $entity->getBanco());

        // Conta Bancária
        $contaMock = $this->createMock(ContasBancarias::class);
        $entity->setContaBancaria($contaMock);
        $this->assertSame($contaMock, $entity->getContaBancaria());

        // Client ID / Secret
        $entity->setClientId('client-id-123');
        $this->assertSame('client-id-123', $entity->getClientId());

        $entity->setClientSecret('secret-456');
        $this->assertSame('secret-456', $entity->getClientSecret());

        // Workspace ID
        $entity->setWorkspaceId('workspace-789');
        $this->assertSame('workspace-789', $entity->getWorkspaceId());

        // Certificado
        $entity->setCertificadoPath('/path/to/cert.pem');
        $this->assertSame('/path/to/cert.pem', $entity->getCertificadoPath());

        $entity->setCertificadoSenha('cert-pass');
        $this->assertSame('cert-pass', $entity->getCertificadoSenha());

        $certValidade = new DateTimeImmutable('2025-12-31');
        $entity->setCertificadoValidade($certValidade);
        $this->assertSame($certValidade, $entity->getCertificadoValidade());

        // Convenio
        $entity->setConvenio('CONV-001');
        $this->assertSame('CONV-001', $entity->getConvenio());

        // Carteira
        $entity->setCarteira('202');
        $this->assertSame('202', $entity->getCarteira());

        // Ambiente
        $entity->setAmbiente('sandbox');
        $this->assertSame('sandbox', $entity->getAmbiente());

        // URLs
        $entity->setUrlAutenticacao('https://auth.example.com');
        $this->assertSame('https://auth.example.com', $entity->getUrlAutenticacao());

        $entity->setUrlApi('https://api.example.com');
        $this->assertSame('https://api.example.com', $entity->getUrlApi());

        // Access Token
        $entity->setAccessToken('token-abc');
        $this->assertSame('token-abc', $entity->getAccessToken());

        // Token Expiration
        $tokenExpira = new DateTimeImmutable('2025-01-01');
        $entity->setTokenExpiraEm($tokenExpira);
        $this->assertSame($tokenExpira, $entity->getTokenExpiraEm());

        // Ativo
        $entity->setAtivo(false);
        $this->assertFalse($entity->isAtivo());

        // CreatedAt / UpdatedAt
        $created = new DateTimeImmutable('2024-01-01 10:00:00');
        $entity->setCreatedAt($created);
        $this->assertSame($created, $entity->getCreatedAt());

        $updated = new DateTimeImmutable('2024-01-02 12:00:00');
        $entity->setUpdatedAt($updated);
        $this->assertSame($updated, $entity->getUpdatedAt());
    }

    public function testBusinessLogicMethods()
    {
        $entity = new ConfiguracoesApiBanco();

        // isTokenValido
        $entity->setAccessToken(null);
        $entity->setTokenExpiraEm(null);
        $this->assertFalse($entity->isTokenValido());

        $entity->setAccessToken('token');
        $entity->setTokenExpiraEm(new DateTimeImmutable('2000-01-01'));
        $this->assertFalse($entity->isTokenValido());

        $entity->setTokenExpiraEm(new DateTimeImmutable('+1 day'));
        $this->assertTrue($entity->isTokenValido());

        // isCertificadoValido
        $entity->setCertificadoValidade(null);
        $this->assertFalse($entity->isCertificadoValido());

        $entity->setCertificadoValidade(new DateTimeImmutable('2000-01-01'));
        $this->assertFalse($entity->isCertificadoValido());

        $entity->setCertificadoValidade(new DateTimeImmutable('+1 day'));
        $this->assertTrue($entity->isCertificadoValido());

        // getUrlBase
        $entity->setUrlApi('https://api.example.com');
        $this->assertSame('https://api.example.com', $entity->getUrlBase());

        $entity->setUrlApi(null);
        $this->assertSame('', $entity->getUrlBase());
    }

    public function testAmbienteUrlSetting()
    {
        $entity = new ConfiguracoesApiBanco();

        // Default is sandbox
        $this->assertSame(
            'https://trust-sandbox.api.santander.com.br/auth/oauth/v2/token',
            $entity->getUrlAutenticacao()
        );
        $this->assertSame(
            'https://trust-sandbox.api.santander.com.br/collection_bill_management/v2',
            $entity->getUrlApi()
        );

        // Switch to production
        $entity->setAmbiente('producao');
        $this->assertSame(
            'https://trust.api.santander.com.br/auth/oauth/v2/token',
            $entity->getUrlAutenticacao()
        );
        $this->assertSame(
            'https://trust.api.santander.com.br/collection_bill_management/v2',
            $entity->getUrlApi()
        );

        // Switch back to sandbox
        $entity->setAmbiente('sandbox');
        $this->assertSame(
            'https://trust-sandbox.api.santander.com.br/auth/oauth/v2/token',
            $entity->getUrlAutenticacao()
        );
        $this->assertSame(
            'https://trust-sandbox.api.santander.com.br/collection_bill_management/v2',
            $entity->getUrlApi()
        );
    }

    public function testOnPreUpdateUpdatesTimestamp()
    {
        $entity = new ConfiguracoesApiBanco();

        // Capture original updatedAt
        $originalUpdatedAt = $entity->getUpdatedAt();

        // Simulate a delay
        sleep(1);

        // Call the pre-update hook
        $entity->onPreUpdate();

        $newUpdatedAt = $entity->getUpdatedAt();

        $this->assertNotSame($originalUpdatedAt, $newUpdatedAt);
        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
    }
}
