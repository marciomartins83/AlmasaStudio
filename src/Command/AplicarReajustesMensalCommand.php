<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ContratoService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command para aplicar o reajuste automático (IGPM/TJ) dos contratos vencidos.
 *
 * Por padrão roda em modo simulação (preview) — só grava o reajuste quando
 * chamado com --aplicar. Isso evita reajustar valores de contrato por engano
 * num cron mal configurado.
 *
 * Uso:
 *   php bin/console app:aplicar-reajustes-mensal              (preview, não grava nada)
 *   php bin/console app:aplicar-reajustes-mensal --aplicar     (aplica de verdade)
 *
 * Cron sugerido (dia 1 de cada mês, revisão manual do preview antes de rodar com --aplicar):
 *   0 7 1 * * cd /path/to/projeto && php bin/console app:aplicar-reajustes-mensal >> /var/log/reajustes.log 2>&1
 */
#[AsCommand(
    name: 'app:aplicar-reajustes-mensal',
    description: 'Simula ou aplica o reajuste automático (IGPM/TJ) dos contratos com data de reajuste vencida'
)]
class AplicarReajustesMensalCommand extends Command
{
    public function __construct(
        private ContratoService $contratoService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'aplicar',
                null,
                InputOption::VALUE_NONE,
                'Aplica de fato o reajuste (sem essa opção, apenas simula/mostra o preview)'
            )
            ->setHelp(<<<'HELP'
O comando <info>%command.name%</info> processa o reajuste automático de aluguel
por IGPM ou Tribunal de Justiça, conforme configurado em cada contrato.

<info>Sem --aplicar (padrão):</info> apenas simula e mostra o preview de cada contrato,
não grava nada no banco.

<info>Com --aplicar:</info> aplica de fato — atualiza o valor do contrato, avança a
data do próximo reajuste e registra o histórico. Contratos com erro (ex: índice
não cadastrado para a competência) são pulados sem interromper o restante do lote.

<info>Exemplo de uso:</info>
    <comment>php bin/console %command.name%</comment>
        Mostra o preview de todos os contratos pendentes

    <comment>php bin/console %command.name% --aplicar</comment>
        Aplica o reajuste de fato em todos os contratos pendentes
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $aplicar = $input->getOption('aplicar');

        $io->title('Reajuste Automático de Contratos (IGPM/TJ)');

        if (!$aplicar) {
            $io->warning('Modo SIMULAÇÃO — nenhum valor será gravado. Use --aplicar para persistir.');
        }

        $contratos = $this->contratoService->buscarContratosParaReajuste();

        if (empty($contratos)) {
            $io->success('Nenhum contrato com reajuste pendente.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('%d contrato(s) com reajuste pendente encontrado(s).', count($contratos)));
        $io->newLine();

        $sucesso = 0;
        $falha = 0;
        $rows = [];

        foreach ($contratos as $contrato) {
            $contratoId = $contrato['id'];

            try {
                $resultado = $aplicar
                    ? $this->contratoService->aplicarReajuste($contratoId)
                    : $this->contratoService->simularReajuste($contratoId);

                $rows[] = [
                    $contratoId,
                    $contrato['locatario_nome'] ?? '-',
                    $resultado['indice_tipo'],
                    number_format((float) $resultado['valor_anterior'], 2, ',', '.'),
                    number_format((float) $resultado['valor_novo'], 2, ',', '.'),
                    $aplicar ? 'Aplicado' : 'Simulado',
                ];
                $sucesso++;
            } catch (\Exception $e) {
                $rows[] = [$contratoId, $contrato['locatario_nome'] ?? '-', '-', '-', '-', 'ERRO: ' . $e->getMessage()];
                $falha++;
            }
        }

        $io->table(['Contrato', 'Locatário', 'Índice', 'Valor Anterior', 'Valor Novo', 'Status'], $rows);

        $this->logger->info('Reajuste mensal processado', [
            'aplicar' => $aplicar,
            'sucesso' => $sucesso,
            'falha' => $falha,
        ]);

        if ($falha > 0) {
            $io->warning(sprintf('%d contrato(s) processado(s) com sucesso, %d com erro.', $sucesso, $falha));
        } else {
            $io->success(sprintf('%d contrato(s) processado(s) com sucesso.', $sucesso));
        }

        return Command::SUCCESS;
    }
}
