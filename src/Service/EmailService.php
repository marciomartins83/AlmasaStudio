<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContratosCobrancas;
use App\Entity\EmailsEnviados;
use App\Entity\Pessoas;
use App\Repository\EmailsEnviadosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Service para envio de emails.
 *
 * Funcionalidades:
 * - Envio de emails com anexos
 * - Log de todos os emails enviados
 * - Templates Twig para corpo do email
 * - Envio específico de boleto para locatário
 */
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private Environment $twig,
        private LoggerInterface $logger,
        private EmailsEnviadosRepository $emailsRepo,
        private string $emailRemetente,
        private string $nomeRemetente
    ) {}

    /**
     * Envia email com anexos opcionais
     *
     * @param string $destinatario Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $corpoHtml Corpo HTML do email
     * @param array $anexos Array de anexos [{path, nome} ou {conteudo, nome, mime}]
     * @param string|null $tipoReferencia Tipo de referência (COBRANCA, BOLETO, etc.)
     * @param int|null $referenciaId ID da referência
     * @return array{sucesso: bool, id?: int, erro?: string}
     */
    public function enviar(
        string $destinatario,
        string $assunto,
        string $corpoHtml,
        array $anexos = [],
        ?string $tipoReferencia = null,
        ?int $referenciaId = null
    ): array {
        // Criar email
        $email = (new Email())
            ->from($this->nomeRemetente . ' <' . $this->emailRemetente . '>')
            ->to($destinatario)
            ->subject($assunto)
            ->html($corpoHtml);

        // Adicionar anexos
        $anexosLog = [];
        foreach ($anexos as $anexo) {
            if (isset($anexo['path']) && file_exists($anexo['path'])) {
                $email->attachFromPath(
                    $anexo['path'],
                    $anexo['nome'] ?? basename($anexo['path'])
                );
                $anexosLog[] = [
                    'nome' => $anexo['nome'] ?? basename($anexo['path']),
                    'tamanho' => filesize($anexo['path'])
                ];
            } elseif (isset($anexo['conteudo'])) {
                $email->attach(
                    $anexo['conteudo'],
                    $anexo['nome'] ?? 'anexo.pdf',
                    $anexo['mime'] ?? 'application/pdf'
                );
                $anexosLog[] = [
                    'nome' => $anexo['nome'] ?? 'anexo.pdf',
                    'tamanho' => strlen($anexo['conteudo'])
                ];
            }
        }

        // Registrar no banco
        $logEmail = new EmailsEnviados();
        $logEmail->setDestinatario($destinatario);
        $logEmail->setAssunto($assunto);
        $logEmail->setCorpo($corpoHtml);
        $logEmail->setAnexos($anexosLog);

        if ($tipoReferencia && $referenciaId) {
            $logEmail->setTipoReferencia($tipoReferencia);
            $logEmail->setReferenciaId($referenciaId);
        }

        try {
            $this->mailer->send($email);

            $logEmail->setStatus(EmailsEnviados::STATUS_ENVIADO);
            $this->em->persist($logEmail);
            $this->em->flush();

            $this->logger->info('Email enviado com sucesso', [
                'destinatario' => $destinatario,
                'assunto' => $assunto,
                'tipo' => $tipoReferencia,
                'id' => $referenciaId
            ]);

            return [
                'sucesso' => true,
                'id' => $logEmail->getId(),
                'email' => $destinatario
            ];

        } catch (\Exception $e) {
            $logEmail->setStatus(EmailsEnviados::STATUS_FALHA);
            $logEmail->setErroMensagem($e->getMessage());
            $this->em->persist($logEmail);
            $this->em->flush();

            $this->logger->error('Falha ao enviar email', [
                'destinatario' => $destinatario,
                'erro' => $e->getMessage()
            ]);

            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Envia email de cobrança de boleto para o locatário
     *
     * @param ContratosCobrancas $cobranca Cobrança a ser enviada
     * @param string $pdfPath Caminho do PDF do boleto
     * @return array{sucesso: bool, id?: int, email?: string, erro?: string}
     */
    public function enviarBoletoLocatario(
        ContratosCobrancas $cobranca,
        string $pdfPath
    ): array {
        $contrato = $cobranca->getContrato();
        $locatario = $contrato->getPessoaLocatario();
        $imovel = $contrato->getImovel();

        if (!$locatario) {
            return ['sucesso' => false, 'erro' => 'Contrato sem locatário definido'];
        }

        // Buscar email principal do locatário
        $emailDestino = $this->getEmailPrincipal($locatario);

        if (!$emailDestino) {
            return ['sucesso' => false, 'erro' => 'Locatário sem email cadastrado'];
        }

        // Montar corpo do email usando template
        try {
            $corpo = $this->twig->render('emails/boleto_cobranca.html.twig', [
                'locatario' => $locatario,
                'imovel' => $imovel,
                'contrato' => $contrato,
                'cobranca' => $cobranca,
                'competencia' => $this->formatarCompetencia($cobranca->getCompetencia()),
                'vencimento' => $cobranca->getDataVencimento()->format('d/m/Y'),
                'valor' => number_format($cobranca->getValorTotalFloat(), 2, ',', '.'),
                'periodo_inicio' => $cobranca->getPeriodoInicio()->format('d/m/Y'),
                'periodo_fim' => $cobranca->getPeriodoFim()->format('d/m/Y'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao renderizar template de email', [
                'erro' => $e->getMessage()
            ]);

            // Fallback para corpo simples
            $corpo = $this->montarCorpoSimples($cobranca);
        }

        $assunto = sprintf(
            'Boleto de Aluguel - %s - Venc: %s',
            $this->formatarCompetencia($cobranca->getCompetencia()),
            $cobranca->getDataVencimento()->format('d/m/Y')
        );

        return $this->enviar(
            $emailDestino,
            $assunto,
            $corpo,
            [['path' => $pdfPath, 'nome' => 'boleto.pdf']],
            EmailsEnviados::TIPO_COBRANCA,
            $cobranca->getId()
        );
    }

    /**
     * Envia lembrete de vencimento próximo
     */
    public function enviarLembreteVencimento(ContratosCobrancas $cobranca): array
    {
        $contrato = $cobranca->getContrato();
        $locatario = $contrato->getPessoaLocatario();

        if (!$locatario) {
            return ['sucesso' => false, 'erro' => 'Contrato sem locatário'];
        }

        $emailDestino = $this->getEmailPrincipal($locatario);

        if (!$emailDestino) {
            return ['sucesso' => false, 'erro' => 'Locatário sem email'];
        }

        try {
            $corpo = $this->twig->render('emails/lembrete_vencimento.html.twig', [
                'locatario' => $locatario,
                'cobranca' => $cobranca,
                'vencimento' => $cobranca->getDataVencimento()->format('d/m/Y'),
                'valor' => number_format($cobranca->getValorTotalFloat(), 2, ',', '.')
            ]);
        } catch (\Exception $e) {
            $corpo = sprintf(
                'Prezado(a) %s,<br><br>Lembrete: seu boleto de aluguel vence em %s.<br>Valor: R$ %s',
                $locatario->getNome(),
                $cobranca->getDataVencimento()->format('d/m/Y'),
                number_format($cobranca->getValorTotalFloat(), 2, ',', '.')
            );
        }

        $assunto = sprintf(
            'Lembrete: Boleto vence em %s',
            $cobranca->getDataVencimento()->format('d/m/Y')
        );

        return $this->enviar(
            $emailDestino,
            $assunto,
            $corpo,
            [],
            EmailsEnviados::TIPO_AVISO,
            $cobranca->getId()
        );
    }

    /**
     * Busca email principal de uma pessoa
     */
    private function getEmailPrincipal(Pessoas $pessoa): ?string
    {
        // Tentar buscar via método da entidade se existir
        if (method_exists($pessoa, 'getEmailPrincipal')) {
            $email = $pessoa->getEmailPrincipal();
            if ($email) {
                return $email->getEmail();
            }
        }

        // Fallback: buscar emails relacionados
        if (method_exists($pessoa, 'getEmails')) {
            $emails = $pessoa->getEmails();
            foreach ($emails as $email) {
                // Primeiro email encontrado
                if (method_exists($email, 'getEmail')) {
                    return $email->getEmail();
                }
            }
        }

        return null;
    }

    /**
     * Formata competência para exibição
     */
    private function formatarCompetencia(string $competencia): string
    {
        $meses = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        [$ano, $mes] = explode('-', $competencia);
        return ($meses[$mes] ?? $mes) . '/' . $ano;
    }

    /**
     * Monta corpo simples do email (fallback se template falhar)
     */
    private function montarCorpoSimples(ContratosCobrancas $cobranca): string
    {
        $contrato = $cobranca->getContrato();
        $locatario = $contrato->getPessoaLocatario();
        $imovel = $contrato->getImovel();

        return sprintf(
            '<html><body>
                <h2>Boleto de Aluguel</h2>
                <p>Prezado(a) <strong>%s</strong>,</p>
                <p>Segue em anexo o boleto de aluguel referente à competência <strong>%s</strong>.</p>
                <p><strong>Imóvel:</strong> %s</p>
                <p><strong>Período:</strong> %s a %s</p>
                <p><strong>Vencimento:</strong> %s</p>
                <p><strong>Valor:</strong> R$ %s</p>
                <p>Atenciosamente,<br>%s</p>
            </body></html>',
            $locatario ? $locatario->getNome() : 'Locatário',
            $this->formatarCompetencia($cobranca->getCompetencia()),
            $imovel ? $imovel->getCodigoInterno() : '-',
            $cobranca->getPeriodoInicio()->format('d/m/Y'),
            $cobranca->getPeriodoFim()->format('d/m/Y'),
            $cobranca->getDataVencimento()->format('d/m/Y'),
            number_format($cobranca->getValorTotalFloat(), 2, ',', '.'),
            $this->nomeRemetente
        );
    }

    /**
     * Busca histórico de emails por referência
     *
     * @return EmailsEnviados[]
     */
    public function getHistoricoByReferencia(string $tipoReferencia, int $referenciaId): array
    {
        return $this->emailsRepo->findByReferencia($tipoReferencia, $referenciaId);
    }

    /**
     * Retorna estatísticas de envio
     */
    public function getEstatisticas(?\DateTime $inicio = null, ?\DateTime $fim = null): array
    {
        return $this->emailsRepo->getEstatisticas($inicio, $fim);
    }
}
