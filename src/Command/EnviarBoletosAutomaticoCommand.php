<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CobrancaContratoService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command para processamento automático de envio de boletos.
 *
 * Deve ser executado via cron diariamente (ex: às 6h da manhã).
 *
 * Uso:
 *   php bin/console app:enviar-boletos-automatico
 *   php bin/console app:enviar-boletos-automatico --dry-run
 *
 * Cron sugerido:
 *   0 6 * * * cd /path/to/projeto && php bin/console app:enviar-boletos-automatico >> /var/log/boletos.log 2>&1
 */
#[AsCommand(
    name: 'app:enviar-boletos-automatico',
    description: 'Processa envio automático de boletos para contratos configurados'
)]
class EnviarBoletosAutomaticoCommand extends Command
{
    public function __construct(
        private CobrancaContratoService $cobrancaService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Simula o processamento sem enviar emails'
            )
            ->setHelp(<<<'HELP'
O comando <info>%command.name%</info> processa o envio automático de boletos
para todos os contratos ativos configurados para cobrança automática.

<info>Critérios de processamento:</info>
- Contrato ativo e vigente
- gera_boleto = true
- envia_email = true
- Dentro do período de antecedência configurado

<info>Comportamento:</info>
- Verifica se já existe cobrança para a competência
- Se não existe, cria nova cobrança
- Gera boleto via API Santander
- Envia email com PDF para o locatário
- Marca tipo_envio como 'AUTOMATICO'

<info>Exemplo de cron (diariamente às 6h):</info>
    0 6 * * * cd /path/to/projeto && php bin/console %command.name%

<info>Uso:</info>
    <comment>php bin/console %command.name%</comment>
        Executa o processamento normal

    <comment>php bin/console %command.name% --dry-run</comment>
        Simula sem enviar (apenas mostra o que seria processado)
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('Processamento Automático de Boletos');

        if ($dryRun) {
            $io->warning('Modo DRY-RUN ativado - nenhum email será enviado');
        }

        $io->text([
            'Data/Hora: ' . (new \DateTime())->format('d/m/Y H:i:s'),
            'Buscando contratos configurados para envio automático...',
            ''
        ]);

        try {
            if ($dryRun) {
                // Modo simulação - apenas conta contratos
                $resultado = $this->simularProcessamento($io);
            } else {
                // Processamento real
                $resultado = $this->cobrancaService->processarEnvioAutomatico();
            }

            // Exibir resumo
            $io->newLine();
            $io->section('Resumo do Processamento');

            $io->definitionList(
                ['Enviados com sucesso' => $resultado['sucesso']],
                ['Falhas' => $resultado['falha']],
                ['Ignorados' => $resultado['ignorados']],
                ['Total processado' => $resultado['sucesso'] + $resultado['falha'] + $resultado['ignorados']]
            );

            // Exibir detalhes se houver
            if (!empty($resultado['detalhes']) && $output->isVerbose()) {
                $io->section('Detalhes');

                $headers = ['Contrato', 'Status', 'Mensagem'];
                $rows = [];

                foreach ($resultado['detalhes'] as $detalhe) {
                    $rows[] = [
                        $detalhe['contrato_id'] ?? '-',
                        $detalhe['status'] ?? '-',
                        $detalhe['mensagem'] ?? $detalhe['motivo'] ?? '-'
                    ];
                }

                $io->table($headers, $rows);
            }

            // Log final
            $this->logger->info('Envio automático processado', [
                'sucesso' => $resultado['sucesso'],
                'falha' => $resultado['falha'],
                'ignorados' => $resultado['ignorados'],
                'dry_run' => $dryRun
            ]);

            // Mensagem final
            if ($resultado['falha'] > 0) {
                $io->warning(sprintf(
                    'Processamento concluído com %d falha(s). Verifique os logs para detalhes.',
                    $resultado['falha']
                ));
                return Command::FAILURE;
            }

            $io->success(sprintf(
                'Processamento concluído: %d boleto(s) enviado(s).',
                $resultado['sucesso']
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erro no processamento: ' . $e->getMessage());

            $this->logger->error('Erro fatal no envio automático', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Simula o processamento sem efetivamente enviar.
     */
    private function simularProcessamento(SymfonyStyle $io): array
    {
        // Aqui podemos adicionar lógica para simular o processamento
        // Por ora, apenas retorna contagem zerada
        $io->note('Simulação não implementada - use sem --dry-run para processar');

        return [
            'sucesso' => 0,
            'falha' => 0,
            'ignorados' => 0,
            'detalhes' => []
        ];
    }
}
