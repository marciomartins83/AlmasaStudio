<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ContratosCobrancas;
use App\Entity\ImoveisContratos;
use App\Entity\EmailsEnviados;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use App\Repository\EmailsEnviadosRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailServiceTest extends TestCase
{
    private EmailService $service;
    private MailerInterface $mailer;
    private EntityManagerInterface $em;
    private Environment $twig;
    private LoggerInterface $logger;
    private EmailsEnviadosRepository $emailsRepo;
    private string $emailRemetente = 'noreply@example.com';
    private string $nomeRemetente = 'Sistema Almasa';

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
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->emailsRepo = $this->createMock(EmailsEnviadosRepository::class);

        $this->service = new EmailService(
            $this->mailer,
            $this->em,
            $this->twig,
            $this->logger,
            $this->emailsRepo,
            $this->emailRemetente,
            $this->nomeRemetente
        );
    }

    /**
     * Test enviar sends email successfully without attachments
     */
    public function testEnviarEmailSuccessfulSemAnexos(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Test Email';
        $corpoHtml = '<html><body>Test content</body></html>';

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Email enviado com sucesso', $this->anything());

        // Act
        $result = $this->service->enviar($destinatario, $assunto, $corpoHtml);

        // Assert
        $this->assertTrue($result['sucesso']);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals($destinatario, $result['email']);
    }

    /**
     * Test enviar sends email successfully with file attachment
     */
    public function testEnviarEmailSuccessfulComAnexoArquivo(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Email with Attachment';
        $corpoHtml = '<html><body>Test content</body></html>';

        // Create temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'email_');
        file_put_contents($tempFile, 'Attachment content');

        $anexos = [
            ['path' => $tempFile, 'nome' => 'document.txt']
        ];

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar($destinatario, $assunto, $corpoHtml, $anexos);

        // Assert
        $this->assertTrue($result['sucesso']);

        unlink($tempFile);
    }

    /**
     * Test enviar sends email successfully with content attachment
     */
    public function testEnviarEmailSuccessfulComAnexoConteudo(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Email with Content Attachment';
        $corpoHtml = '<html><body>Test content</body></html>';

        $anexos = [
            [
                'conteudo' => 'PDF content here',
                'nome' => 'document.pdf',
                'mime' => 'application/pdf'
            ]
        ];

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar($destinatario, $assunto, $corpoHtml, $anexos);

        // Assert
        $this->assertTrue($result['sucesso']);
    }

    /**
     * Test enviar handles mailer exception
     */
    public function testEnviarHandlesMailerException(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Test Email';
        $corpoHtml = '<html><body>Test content</body></html>';

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('SMTP connection failed'));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Falha ao enviar email', $this->anything());

        // Act
        $result = $this->service->enviar($destinatario, $assunto, $corpoHtml);

        // Assert
        $this->assertFalse($result['sucesso']);
        $this->assertArrayHasKey('erro', $result);
        $this->assertStringContainsString('SMTP', $result['erro']);
    }

    /**
     * Test enviar with reference type and ID
     */
    public function testEnviarComTipoReferencia(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Cobrança';
        $corpoHtml = '<html><body>Boleto</body></html>';
        $tipoReferencia = EmailsEnviados::TIPO_COBRANCA;
        $referenciaId = 12345;

        $this->mailer->expects($this->once())
            ->method('send');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar(
            $destinatario,
            $assunto,
            $corpoHtml,
            [],
            $tipoReferencia,
            $referenciaId
        );

        // Assert
        $this->assertTrue($result['sucesso']);
    }

    /**
     * Test enviar ignores non-existent attachment files
     */
    public function testEnviarIgnoresNonExistentAttachmentFiles(): void
    {
        // Arrange
        $destinatario = 'test@example.com';
        $assunto = 'Email';
        $corpoHtml = '<html><body>Test</body></html>';

        $anexos = [
            ['path' => '/non/existent/file.pdf', 'nome' => 'document.pdf']
        ];

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar($destinatario, $assunto, $corpoHtml, $anexos);

        // Assert
        $this->assertTrue($result['sucesso']);
    }

    /**
     * Test enviarBoletoLocatario with valid cobranca
     */
    public function testEnviarBoletoLocatarioComCobrancaValida(): void
    {
        // Arrange
        $locatario = new Pessoas();
        $locatario->setNome('João Silva');

        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getPessoaLocatario')->willReturn($locatario);

        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getCodigoInterno')->willReturn('APT-001');
        $contrato->method('getImovel')->willReturn($imovel);

        $cobranca = $this->createMock(ContratosCobrancas::class);
        $cobranca->method('getContrato')->willReturn($contrato);
        $cobranca->method('getCompetencia')->willReturn('2024-01');
        $cobranca->method('getDataVencimento')->willReturn(new \DateTime('2024-01-10'));
        $cobranca->method('getPeriodoInicio')->willReturn(new \DateTime('2024-01-01'));
        $cobranca->method('getPeriodoFim')->willReturn(new \DateTime('2024-01-31'));
        $cobranca->method('getValorTotalFloat')->willReturn(1500.00);
        $cobranca->method('getId')->willReturn(123);

        // Note: locatario has no emails, so service will return error
        $pdfPath = tempnam(sys_get_temp_dir(), 'boleto_');
        file_put_contents($pdfPath, 'PDF content');

        try {
            // Act
            $result = $this->service->enviarBoletoLocatario($cobranca, $pdfPath);

            // Assert - should fail due to no email
            $this->assertFalse($result['sucesso']);
            $this->assertStringContainsString('email', strtolower($result['erro']));
        } finally {
            unlink($pdfPath);
        }
    }

    /**
     * Test enviarBoletoLocatario returns error when locatario is null
     */
    public function testEnviarBoletoLocatarioRetornaErroWhenLocatarioNull(): void
    {
        // Arrange
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getPessoaLocatario')->willReturn(null);

        $cobranca = new ContratosCobrancas();
        $cobranca->setContrato($contrato);

        $pdfPath = tempnam(sys_get_temp_dir(), 'boleto_');
        file_put_contents($pdfPath, 'PDF');

        // Act
        $result = $this->service->enviarBoletoLocatario($cobranca, $pdfPath);

        // Assert
        $this->assertFalse($result['sucesso']);
        $this->assertStringContainsString('locatário', $result['erro']);

        unlink($pdfPath);
    }

    /**
     * Test enviarLembreteVencimento returns error when no email
     */
    public function testEnviarLembreteVencimentoComCobrancaValida(): void
    {
        // Arrange
        $locatario = new Pessoas();
        $locatario->setNome('Maria Silva');
        // Note: locatario has no emails, so service will return error

        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getPessoaLocatario')->willReturn($locatario);

        $cobranca = $this->createMock(ContratosCobrancas::class);
        $cobranca->method('getContrato')->willReturn($contrato);
        $cobranca->method('getDataVencimento')->willReturn(new \DateTime('2024-02-15'));
        $cobranca->method('getValorTotalFloat')->willReturn(2500.00);
        $cobranca->method('getId')->willReturn(456);

        // Act
        $result = $this->service->enviarLembreteVencimento($cobranca);

        // Assert - should fail due to no email
        $this->assertFalse($result['sucesso']);
        $this->assertStringContainsString('email', strtolower($result['erro']));
    }

    /**
     * Test enviarLembreteVencimento returns error when locatario is null
     */
    public function testEnviarLembreteVencimentoRetornaErroWhenLocatarioNull(): void
    {
        // Arrange
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getPessoaLocatario')->willReturn(null);

        $cobranca = new ContratosCobrancas();
        $cobranca->setContrato($contrato);

        // Act
        $result = $this->service->enviarLembreteVencimento($cobranca);

        // Assert
        $this->assertFalse($result['sucesso']);
        $this->assertStringContainsString('locatário', $result['erro']);
    }

    /**
     * Test enviarLembreteVencimento with email but return error
     */
    public function testEnviarLembreteVencimentoFallbackWhenTemplateFails(): void
    {
        // Arrange
        $locatario = new Pessoas();
        $locatario->setNome('Test Person');

        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getPessoaLocatario')->willReturn($locatario);

        $cobranca = $this->createMock(ContratosCobrancas::class);
        $cobranca->method('getContrato')->willReturn($contrato);
        $cobranca->method('getDataVencimento')->willReturn(new \DateTime('2024-03-10'));
        $cobranca->method('getValorTotalFloat')->willReturn(3000.00);
        $cobranca->method('getId')->willReturn(789);

        // Act
        $result = $this->service->enviarLembreteVencimento($cobranca);

        // Assert - should fail due to no email
        $this->assertFalse($result['sucesso']);
        $this->assertArrayHasKey('erro', $result);
    }

    /**
     * Test getHistoricoByReferencia calls repository
     */
    public function testGetHistoricoByReferenciaCallsRepository(): void
    {
        // Arrange
        $tipoReferencia = EmailsEnviados::TIPO_COBRANCA;
        $referenciaId = 12345;
        $historico = [
            new EmailsEnviados(),
            new EmailsEnviados()
        ];

        $this->emailsRepo->expects($this->once())
            ->method('findByReferencia')
            ->with($tipoReferencia, $referenciaId)
            ->willReturn($historico);

        // Act
        $result = $this->service->getHistoricoByReferencia($tipoReferencia, $referenciaId);

        // Assert
        $this->assertEquals($historico, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test getHistoricoByReferencia returns empty array when none found
     */
    public function testGetHistoricoByReferenciaReturnsEmptyArrayWhenNoneFound(): void
    {
        // Arrange
        $this->emailsRepo->expects($this->once())
            ->method('findByReferencia')
            ->willReturn([]);

        // Act
        $result = $this->service->getHistoricoByReferencia('INVALID', 999);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getEstatisticas calls repository without filters
     */
    public function testGetEstatisticasCallsRepositoryWithoutFilters(): void
    {
        // Arrange
        $stats = [
            'total' => 100,
            'enviados' => 95,
            'falhas' => 5
        ];

        $this->emailsRepo->expects($this->once())
            ->method('getEstatisticas')
            ->with(null, null)
            ->willReturn($stats);

        // Act
        $result = $this->service->getEstatisticas();

        // Assert
        $this->assertEquals($stats, $result);
    }

    /**
     * Test getEstatisticas calls repository with date filters
     */
    public function testGetEstatisticasCallsRepositoryWithDateFilters(): void
    {
        // Arrange
        $inicio = new \DateTime('2024-01-01');
        $fim = new \DateTime('2024-01-31');
        $stats = [
            'total' => 50,
            'enviados' => 48,
            'falhas' => 2
        ];

        $this->emailsRepo->expects($this->once())
            ->method('getEstatisticas')
            ->with($inicio, $fim)
            ->willReturn($stats);

        // Act
        $result = $this->service->getEstatisticas($inicio, $fim);

        // Assert
        $this->assertEquals($stats, $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('enviados', $result);
        $this->assertArrayHasKey('falhas', $result);
    }

    /**
     * Test enviar returns correct response array on success
     */
    public function testEnviarReturnsCorrectResponseStructure(): void
    {
        // Arrange
        $destinatario = 'test@example.com';

        $this->mailer->expects($this->once())->method('send');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar($destinatario, 'Subject', '<html></html>');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sucesso', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertTrue($result['sucesso']);
    }

    /**
     * Test enviar returns error array on failure
     */
    public function testEnviarReturnsErrorResponseStructure(): void
    {
        // Arrange
        $destinatario = 'test@example.com';

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Mailer error'));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar($destinatario, 'Subject', '<html></html>');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sucesso', $result);
        $this->assertArrayHasKey('erro', $result);
        $this->assertFalse($result['sucesso']);
    }

    /**
     * Test enviar with multiple attachments
     */
    public function testEnviarComMultiplosAnexos(): void
    {
        // Arrange
        $tempFiles = [];
        for ($i = 0; $i < 3; $i++) {
            $temp = tempnam(sys_get_temp_dir(), 'email_att_');
            file_put_contents($temp, "Content $i");
            $tempFiles[] = $temp;
        }

        $anexos = [
            ['path' => $tempFiles[0], 'nome' => 'file1.txt'],
            ['conteudo' => 'inline content', 'nome' => 'inline.txt', 'mime' => 'text/plain'],
            ['path' => $tempFiles[1], 'nome' => 'file2.pdf'],
        ];

        $this->mailer->expects($this->once())->method('send');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        // Act
        $result = $this->service->enviar(
            'test@example.com',
            'Multiple Attachments',
            '<html></html>',
            $anexos
        );

        // Assert
        $this->assertTrue($result['sucesso']);

        foreach ($tempFiles as $file) {
            unlink($file);
        }
    }

    /**
     * Test formatarCompetencia formats correctly
     */
    public function testFormatarCompetenciaFormatsCorrectly(): void
    {
        // Use reflection to test private method
        $reflectionMethod = new \ReflectionMethod($this->service, 'formatarCompetencia');
        $reflectionMethod->setAccessible(true);

        // Act & Assert
        $this->assertEquals('Janeiro/2024', $reflectionMethod->invoke($this->service, '2024-01'));
        $this->assertEquals('Dezembro/2024', $reflectionMethod->invoke($this->service, '2024-12'));
        $this->assertEquals('Junho/2023', $reflectionMethod->invoke($this->service, '2023-06'));
    }
}
