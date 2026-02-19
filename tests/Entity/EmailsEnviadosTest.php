<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\EmailsEnviados;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class EmailsEnviadosTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $email = new EmailsEnviados();

        // ID is null initially
        $this->assertNull($email->getId());

        // Status default
        $this->assertSame(EmailsEnviados::STATUS_ENVIADO, $email->getStatus());
        $this->assertSame('Enviado', $email->getStatusLabel());
        $this->assertSame('success', $email->getStatusClass());

        // Anexos default
        $this->assertNull($email->getAnexos());
        $this->assertSame(0, $email->getQuantidadeAnexos());
        $this->assertSame(0, $email->getTamanhoTotalAnexos());
        $this->assertSame('0 B', $email->getTamanhoAnexosFormatado());

        // Enviado em default
        $this->assertInstanceOf(DateTimeInterface::class, $email->getEnviadoEm());
        // The default timestamp should be close to now
        $now = new DateTime();
        $diff = abs($now->getTimestamp() - $email->getEnviadoEm()->getTimestamp());
        $this->assertLessThanOrEqual(5, $diff, 'Default enviadoEm should be close to now');
    }

    public function testGettersAndSetters(): void
    {
        $email = new EmailsEnviados();

        $email->setTipoReferencia(EmailsEnviados::TIPO_COBRANCA)
              ->setReferenciaId(123)
              ->setDestinatario('teste@example.com')
              ->setAssunto('Assunto de teste')
              ->setCorpo('Corpo do email')
              ->setAnexos([
                  ['nome' => 'file1.pdf', 'tamanho' => 1024],
                  ['nome' => 'file2.jpg', 'tamanho' => 2048],
              ])
              ->setStatus(EmailsEnviados::STATUS_FALHA)
              ->setErroMensagem('Erro de teste')
              ->setEnviadoEm(new DateTime('2023-01-01 12:00:00'));

        $this->assertSame(EmailsEnviados::TIPO_COBRANCA, $email->getTipoReferencia());
        $this->assertSame(123, $email->getReferenciaId());
        $this->assertSame('teste@example.com', $email->getDestinatario());
        $this->assertSame('Assunto de teste', $email->getAssunto());
        $this->assertSame('Corpo do email', $email->getCorpo());
        $this->assertSame([
            ['nome' => 'file1.pdf', 'tamanho' => 1024],
            ['nome' => 'file2.jpg', 'tamanho' => 2048],
        ], $email->getAnexos());
        $this->assertSame(EmailsEnviados::STATUS_FALHA, $email->getStatus());
        $this->assertSame('Erro de teste', $email->getErroMensagem());
        $this->assertSame('2023-01-01 12:00:00', $email->getEnviadoEm()->format('Y-m-d H:i:s'));
    }

    public function testStatusMethods(): void
    {
        $email = new EmailsEnviados();

        $email->setStatus(EmailsEnviados::STATUS_ENVIADO);
        $this->assertTrue($email->isEnviado());
        $this->assertFalse($email->isFalha());
        $this->assertFalse($email->isBounce());

        $email->setStatus(EmailsEnviados::STATUS_FALHA);
        $this->assertFalse($email->isEnviado());
        $this->assertTrue($email->isFalha());
        $this->assertFalse($email->isBounce());

        $email->setStatus(EmailsEnviados::STATUS_BOUNCE);
        $this->assertFalse($email->isEnviado());
        $this->assertFalse($email->isFalha());
        $this->assertTrue($email->isBounce());
    }

    public function testStatusLabelAndClass(): void
    {
        $email = new EmailsEnviados();

        $email->setStatus(EmailsEnviados::STATUS_ENVIADO);
        $this->assertSame('Enviado', $email->getStatusLabel());
        $this->assertSame('success', $email->getStatusClass());

        $email->setStatus(EmailsEnviados::STATUS_FALHA);
        $this->assertSame('Falha', $email->getStatusLabel());
        $this->assertSame('danger', $email->getStatusClass());

        $email->setStatus(EmailsEnviados::STATUS_BOUNCE);
        $this->assertSame('Bounce', $email->getStatusLabel());
        $this->assertSame('warning', $email->getStatusClass());

        // Unknown status
        $email->setStatus('UNKNOWN');
        $this->assertSame('UNKNOWN', $email->getStatusLabel());
        $this->assertSame('secondary', $email->getStatusClass());
    }

    public function testTipoReferenciaLabel(): void
    {
        $email = new EmailsEnviados();

        $email->setTipoReferencia(EmailsEnviados::TIPO_COBRANCA);
        $this->assertSame('Cobrança', $email->getTipoReferenciaLabel());

        $email->setTipoReferencia(EmailsEnviados::TIPO_BOLETO);
        $this->assertSame('Boleto', $email->getTipoReferenciaLabel());

        $email->setTipoReferencia(EmailsEnviados::TIPO_CONTRATO);
        $this->assertSame('Contrato', $email->getTipoReferenciaLabel());

        $email->setTipoReferencia(EmailsEnviados::TIPO_INFORME);
        $this->assertSame('Informe', $email->getTipoReferenciaLabel());

        $email->setTipoReferencia(EmailsEnviados::TIPO_AVISO);
        $this->assertSame('Aviso', $email->getTipoReferenciaLabel());

        // Unknown tipo
        $email->setTipoReferencia('UNKNOWN');
        $this->assertSame('UNKNOWN', $email->getTipoReferenciaLabel());
    }

    public function testAnexosMethods(): void
    {
        $email = new EmailsEnviados();

        // No anexos
        $this->assertSame(0, $email->getQuantidadeAnexos());
        $this->assertSame(0, $email->getTamanhoTotalAnexos());
        $this->assertSame('0 B', $email->getTamanhoAnexosFormatado());

        // Empty array
        $email->setAnexos([]);
        $this->assertSame(0, $email->getQuantidadeAnexos());
        $this->assertSame(0, $email->getTamanhoTotalAnexos());
        $this->assertSame('0 B', $email->getTamanhoAnexosFormatado());

        // Array with items
        $anexos = [
            ['nome' => 'a.txt', 'tamanho' => 512],
            ['nome' => 'b.txt', 'tamanho' => 1024],
            ['nome' => 'c.txt', 'tamanho' => 2048],
        ];
        $email->setAnexos($anexos);
        $this->assertSame(3, $email->getQuantidadeAnexos());
        $this->assertSame(512 + 1024 + 2048, $email->getTamanhoTotalAnexos());
        $this->assertSame('3.5 KB', $email->getTamanhoAnexosFormatado());
    }

    public function testTamanhoAnexosFormatadoCases(): void
    {
        $email = new EmailsEnviados();

        // 512 bytes
        $email->setAnexos([['nome' => 'a', 'tamanho' => 512]]);
        $this->assertSame('512 B', $email->getTamanhoAnexosFormatado());

        // 1024 bytes
        $email->setAnexos([['nome' => 'a', 'tamanho' => 1024]]);
        $this->assertSame('1 KB', $email->getTamanhoAnexosFormatado());

        // 1536 bytes (1.5 KB)
        $email->setAnexos([['nome' => 'a', 'tamanho' => 1536]]);
        $this->assertSame('1.5 KB', $email->getTamanhoAnexosFormatado());

        // 1048576 bytes (1 MB)
        $email->setAnexos([['nome' => 'a', 'tamanho' => 1048576]]);
        $this->assertSame('1 MB', $email->getTamanhoAnexosFormatado());

        // 1572864 bytes (1.5 MB)
        $email->setAnexos([['nome' => 'a', 'tamanho' => 1572864]]);
        $this->assertSame('1.5 MB', $email->getTamanhoAnexosFormatado());
    }

    public function testGetEnviadoEmAndSetEnviadoEm(): void
    {
        $email = new EmailsEnviados();

        $date = new DateTime('2022-12-31 23:59:59');
        $email->setEnviadoEm($date);
        $this->assertSame($date, $email->getEnviadoEm());
    }

    public function testErroMensagem(): void
    {
        $email = new EmailsEnviados();

        $this->assertNull($email->getErroMensagem());

        $email->setErroMensagem('Mensagem de erro');
        $this->assertSame('Mensagem de erro', $email->getErroMensagem());
    }
}
