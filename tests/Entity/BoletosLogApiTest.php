<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Boletos;
use App\Entity\BoletosLogApi;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class BoletosLogApiTest extends TestCase
{
    private BoletosLogApi $entity;

    protected function setUp(): void
    {
        $this->entity = new BoletosLogApi();
    }

    // --------------------------------------------------------------------
    // Test getters and setters
    // --------------------------------------------------------------------
    public function testGettersAndSetters(): void
    {
        // id is null initially
        $this->assertNull($this->entity->getId());

        // boleto relationship
        $boletoMock = $this->createMock(Boletos::class);
        $this->entity->setBoleto($boletoMock);
        $this->assertSame($boletoMock, $this->entity->getBoleto());

        // set boleto to null
        $this->entity->setBoleto(null);
        $this->assertNull($this->entity->getBoleto());

        // operacao
        $this->entity->setOperacao(BoletosLogApi::OPERACAO_REGISTRO);
        $this->assertSame(BoletosLogApi::OPERACAO_REGISTRO, $this->entity->getOperacao());

        // requestPayload
        $request = '{"foo":"bar"}';
        $this->entity->setRequestPayload($request);
        $this->assertSame($request, $this->entity->getRequestPayload());

        // responsePayload
        $response = '{"status":"ok"}';
        $this->entity->setResponsePayload($response);
        $this->assertSame($response, $this->entity->getResponsePayload());

        // httpCode
        $this->entity->setHttpCode(200);
        $this->assertSame(200, $this->entity->getHttpCode());

        // sucesso
        $this->entity->setSucesso(true);
        $this->assertTrue($this->entity->isSucesso());

        // mensagemErro
        $msg = 'Erro de teste';
        $this->entity->setMensagemErro($msg);
        $this->assertSame($msg, $this->entity->getMensagemErro());

        // createdAt
        $now = new DateTime();
        $this->entity->setCreatedAt($now);
        $this->assertSame($now, $this->entity->getCreatedAt());
    }

    // --------------------------------------------------------------------
    // Test business logic methods
    // --------------------------------------------------------------------
    public function testBusinessLogicMethods(): void
    {
        // Operação label mapping
        $this->entity->setOperacao(BoletosLogApi::OPERACAO_REGISTRO);
        $this->assertSame('Registro', $this->entity->getOperacaoLabel());

        $this->entity->setOperacao(BoletosLogApi::OPERACAO_CONSULTA);
        $this->assertSame('Consulta', $this->entity->getOperacaoLabel());

        $this->entity->setOperacao(BoletosLogApi::OPERACAO_ALTERACAO);
        $this->assertSame('Alteração', $this->entity->getOperacaoLabel());

        $this->entity->setOperacao(BoletosLogApi::OPERACAO_BAIXA);
        $this->assertSame('Baixa', $this->entity->getOperacaoLabel());

        $this->entity->setOperacao(BoletosLogApi::OPERACAO_PROTESTO);
        $this->assertSame('Protesto', $this->entity->getOperacaoLabel());

        // Unknown operation fallback
        $this->entity->setOperacao('UNKNOWN');
        $this->assertSame('UNKNOWN', $this->entity->getOperacaoLabel());

        // Status class
        $this->entity->setSucesso(true);
        $this->assertSame('success', $this->entity->getStatusClass());

        $this->entity->setSucesso(false);
        $this->assertSame('danger', $this->entity->getStatusClass());

        // CreatedAt formatted
        $date = new DateTime('2023-01-01 12:34:56');
        $this->entity->setCreatedAt($date);
        $this->assertSame('01/01/2023 12:34:56', $this->entity->getCreatedAtFormatada());
    }

    // --------------------------------------------------------------------
    // Test payload array conversion
    // --------------------------------------------------------------------
    public function testPayloadArrayConversion(): void
    {
        // Null payloads
        $this->entity->setRequestPayload(null);
        $this->entity->setResponsePayload(null);
        $this->assertNull($this->entity->getRequestPayloadArray());
        $this->assertNull($this->entity->getResponsePayloadArray());

        // Valid JSON payloads
        $json = '{"key":"value","num":123}';
        $this->entity->setRequestPayload($json);
        $this->entity->setResponsePayload($json);

        $expectedArray = ['key' => 'value', 'num' => 123];
        $this->assertSame($expectedArray, $this->entity->getRequestPayloadArray());
        $this->assertSame($expectedArray, $this->entity->getResponsePayloadArray());
    }

    // --------------------------------------------------------------------
    // Test constants
    // --------------------------------------------------------------------
    public function testConstantsExist(): void
    {
        $this->assertSame('REGISTRO', BoletosLogApi::OPERACAO_REGISTRO);
        $this->assertSame('CONSULTA', BoletosLogApi::OPERACAO_CONSULTA);
        $this->assertSame('ALTERACAO', BoletosLogApi::OPERACAO_ALTERACAO);
        $this->assertSame('BAIXA', BoletosLogApi::OPERACAO_BAIXA);
        $this->assertSame('PROTESTO', BoletosLogApi::OPERACAO_PROTESTO);
    }

    // --------------------------------------------------------------------
    // Test createdAt default value
    // --------------------------------------------------------------------
    public function testCreatedAtIsSetInConstructor(): void
    {
        $entity = new BoletosLogApi();
        $createdAt = $entity->getCreatedAt();
        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);

        // The createdAt should be close to now (within 2 seconds)
        $now = new DateTime();
        $diff = abs($now->getTimestamp() - $createdAt->getTimestamp());
        $this->assertLessThanOrEqual(2, $diff);
    }
}
