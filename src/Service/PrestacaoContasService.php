<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PrestacoesContas;
use App\Entity\PrestacoesContasItens;
use App\Entity\Pessoas;
use App\Entity\Imoveis;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Lancamentos;
use App\Repository\PrestacoesContasRepository;
use App\Repository\PrestacoesContasItensRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\LancamentosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\PessoaRepository;
use App\Repository\ContasBancariasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * PrestacaoContasService - Fat Service
 *
 * Responsabilidades:
 * - Geração de prestações de contas
 * - Cálculo de taxas e retenções
 * - Fluxo de aprovação e repasse
 * - Geração de PDF
 */
class PrestacaoContasService
{
    private const UPLOAD_DIR = 'uploads/comprovantes_repasse';
    private string $projectDir;

    public function __construct(
        private EntityManagerInterface $em,
        private PrestacoesContasRepository $prestacaoRepo,
        private PrestacoesContasItensRepository $itemRepo,
        private LancamentosFinanceirosRepository $lancFinanceiroRepo,
        private LancamentosRepository $lancamentoRepo,
        private ImoveisRepository $imovelRepo,
        private ImoveisContratosRepository $contratoRepo,
        private PessoaRepository $pessoaRepo,
        private ContasBancariasRepository $contaBancariaRepo,
        private Security $security,
        ParameterBagInterface $params
    ) {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    /**
     * Lista prestações com filtros
     *
     * @return PrestacoesContas[]
     */
    public function listarPrestacoes(array $filtros = []): array
    {
        return $this->prestacaoRepo->findByFiltros($filtros);
    }

    /**
     * Busca prestação por ID
     */
    public function buscarPorId(int $id): ?PrestacoesContas
    {
        return $this->prestacaoRepo->findByIdComItens($id);
    }

    /**
     * Retorna histórico de prestações por proprietário
     *
     * @return PrestacoesContas[]
     */
    public function getHistoricoPorProprietario(int $idProprietario): array
    {
        return $this->prestacaoRepo->findByProprietario($idProprietario);
    }

    /**
     * Retorna estatísticas gerais
     */
    public function getEstatisticas(?int $ano = null): array
    {
        return $this->prestacaoRepo->getEstatisticas($ano);
    }

    /**
     * Retorna estatísticas do mês atual
     */
    public function getEstatisticasMesAtual(): array
    {
        return $this->prestacaoRepo->getEstatisticasMesAtual();
    }

    /**
     * Calcula período automaticamente baseado no tipo
     *
     * @return array{inicio: \DateTime, fim: \DateTime}
     */
    public function calcularPeriodo(string $tipoPeriodo, ?\DateTime $dataBase = null): array
    {
        $dataBase = $dataBase ?? new \DateTime();

        switch ($tipoPeriodo) {
            case PrestacoesContas::PERIODO_DIARIO:
                return [
                    'inicio' => clone $dataBase,
                    'fim' => clone $dataBase,
                ];

            case PrestacoesContas::PERIODO_SEMANAL:
                $inicio = clone $dataBase;
                $inicio->modify('monday this week');
                $fim = clone $inicio;
                $fim->modify('+6 days');
                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_QUINZENAL:
                $dia = (int) $dataBase->format('d');
                $inicio = clone $dataBase;
                $fim = clone $dataBase;

                if ($dia <= 15) {
                    $inicio->modify('first day of this month');
                    $fim->setDate((int) $fim->format('Y'), (int) $fim->format('m'), 15);
                } else {
                    $inicio->setDate((int) $inicio->format('Y'), (int) $inicio->format('m'), 16);
                    $fim->modify('last day of this month');
                }
                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_MENSAL:
                $inicio = clone $dataBase;
                $inicio->modify('first day of this month');
                $fim = clone $dataBase;
                $fim->modify('last day of this month');
                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_TRIMESTRAL:
                $mes = (int) $dataBase->format('n');
                $trimestre = ceil($mes / 3);
                $mesInicio = ($trimestre - 1) * 3 + 1;

                $inicio = clone $dataBase;
                $inicio->setDate((int) $dataBase->format('Y'), $mesInicio, 1);

                $fim = clone $inicio;
                $fim->modify('+2 months');
                $fim->modify('last day of this month');

                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_SEMESTRAL:
                $mes = (int) $dataBase->format('n');
                $semestre = $mes <= 6 ? 1 : 2;
                $mesInicio = $semestre === 1 ? 1 : 7;

                $inicio = clone $dataBase;
                $inicio->setDate((int) $dataBase->format('Y'), $mesInicio, 1);

                $fim = clone $inicio;
                $fim->modify('+5 months');
                $fim->modify('last day of this month');

                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_ANUAL:
                $inicio = clone $dataBase;
                $inicio->setDate((int) $dataBase->format('Y'), 1, 1);
                $fim = clone $dataBase;
                $fim->setDate((int) $dataBase->format('Y'), 12, 31);
                return ['inicio' => $inicio, 'fim' => $fim];

            case PrestacoesContas::PERIODO_BIENAL:
                $ano = (int) $dataBase->format('Y');
                $anoInicio = $ano % 2 === 0 ? $ano : $ano - 1;

                $inicio = new \DateTime();
                $inicio->setDate($anoInicio, 1, 1);
                $fim = new \DateTime();
                $fim->setDate($anoInicio + 1, 12, 31);

                return ['inicio' => $inicio, 'fim' => $fim];

            default: // personalizado
                return [
                    'inicio' => clone $dataBase,
                    'fim' => clone $dataBase,
                ];
        }
    }

    /**
     * Preview da prestação (sem salvar)
     */
    public function preview(array $filtros): array
    {
        $itens = $this->buscarItensParaPrestacao($filtros);

        $totalReceitas = 0;
        $totalDespesas = 0;
        $totalTaxaAdmin = 0;
        $totalRetencaoIr = 0;

        foreach ($itens as $item) {
            if ($item['tipo'] === PrestacoesContasItens::TIPO_RECEITA) {
                $totalReceitas += $item['valor_bruto'];
                $totalTaxaAdmin += $item['valor_taxa_admin'];
                $totalRetencaoIr += $item['valor_retencao_ir'];
            } else {
                $totalDespesas += $item['valor_bruto'];
            }
        }

        $valorRepasse = $totalReceitas - $totalTaxaAdmin - $totalRetencaoIr - $totalDespesas;

        return [
            'itens' => $itens,
            'resumo' => [
                'total_receitas' => $totalReceitas,
                'total_despesas' => $totalDespesas,
                'total_taxa_admin' => $totalTaxaAdmin,
                'total_retencao_ir' => $totalRetencaoIr,
                'valor_repasse' => $valorRepasse,
                'quantidade_itens' => count($itens),
            ],
        ];
    }

    /**
     * Gera uma nova prestação de contas
     *
     * @throws \Exception
     */
    public function gerarPrestacao(array $filtros): PrestacoesContas
    {
        // Validações
        if (empty($filtros['proprietario'])) {
            throw new \Exception('Proprietário é obrigatório.');
        }

        if (empty($filtros['data_inicio']) || empty($filtros['data_fim'])) {
            throw new \Exception('Período é obrigatório.');
        }

        if (!($filtros['incluir_ficha_financeira'] ?? false) && !($filtros['incluir_lancamentos'] ?? false)) {
            throw new \Exception('Selecione pelo menos uma origem de dados.');
        }

        $proprietario = $this->pessoaRepo->find($filtros['proprietario']);
        if (!$proprietario) {
            throw new \Exception('Proprietário não encontrado.');
        }

        $imovel = null;
        if (!empty($filtros['imovel'])) {
            $imovel = $this->imovelRepo->find($filtros['imovel']);
        }

        // Verificar duplicidade
        if ($this->prestacaoRepo->existePrestacaoDuplicada(
            $proprietario->getIdpessoa(),
            $filtros['data_inicio'],
            $filtros['data_fim'],
            $imovel?->getId()
        )) {
            throw new \Exception('Já existe uma prestação de contas para este proprietário/imóvel/período.');
        }

        $this->em->beginTransaction();

        try {
            $ano = (int) $filtros['data_inicio']->format('Y');

            $prestacao = new PrestacoesContas();
            $prestacao->setNumero($this->prestacaoRepo->getProximoNumero($ano));
            $prestacao->setAno($ano);
            $prestacao->setDataInicio($filtros['data_inicio']);
            $prestacao->setDataFim($filtros['data_fim']);
            $prestacao->setTipoPeriodo($filtros['tipo_periodo'] ?? PrestacoesContas::PERIODO_MENSAL);
            $prestacao->setCompetencia($filtros['competencia'] ?? null);
            $prestacao->setProprietario($proprietario);
            $prestacao->setImovel($imovel);
            $prestacao->setIncluirFichaFinanceira($filtros['incluir_ficha_financeira'] ?? true);
            $prestacao->setIncluirLancamentos($filtros['incluir_lancamentos'] ?? true);

            $user = $this->security->getUser();
            if ($user) {
                $prestacao->setCreatedBy($user);
            }

            // Buscar e criar itens
            $itensData = $this->buscarItensParaPrestacao($filtros);

            foreach ($itensData as $itemData) {
                $item = $this->criarItemPrestacao($itemData);
                $prestacao->addItem($item);
            }

            // Calcular totais
            $prestacao->recalcularTotais();

            $this->em->persist($prestacao);
            $this->em->flush();
            $this->em->commit();

            return $prestacao;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao gerar prestação: ' . $e->getMessage());
        }
    }

    /**
     * Busca itens para compor a prestação
     */
    public function buscarItensParaPrestacao(array $filtros): array
    {
        $itens = [];

        if ($filtros['incluir_ficha_financeira'] ?? true) {
            $itens = array_merge($itens, $this->buscarDadosFichaFinanceira($filtros));
        }

        if ($filtros['incluir_lancamentos'] ?? true) {
            $itens = array_merge($itens, $this->buscarDadosLancamentos($filtros));
        }

        // Ordenar por data
        usort($itens, fn($a, $b) => $a['data_movimento'] <=> $b['data_movimento']);

        return $itens;
    }

    /**
     * Busca dados da Ficha Financeira
     */
    private function buscarDadosFichaFinanceira(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('lf')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->where('lf.proprietario = :proprietario')
            ->andWhere('lf.dataVencimento >= :dataInicio')
            ->andWhere('lf.dataVencimento <= :dataFim')
            ->andWhere('lf.situacao = :situacao')
            ->setParameter('proprietario', $filtros['proprietario'])
            ->setParameter('dataInicio', $filtros['data_inicio'])
            ->setParameter('dataFim', $filtros['data_fim'])
            ->setParameter('situacao', 'pago');

        if (!empty($filtros['imovel'])) {
            $qb->andWhere('lf.imovel = :imovel')
               ->setParameter('imovel', $filtros['imovel']);
        }

        $lancamentos = $qb->getQuery()->getResult();
        $itens = [];

        foreach ($lancamentos as $lf) {
            /** @var LancamentosFinanceiros $lf */
            $valorBruto = (float) $lf->getValorTotal();
            $taxaAdmin = $this->calcularTaxaAdmin($valorBruto, $lf->getContrato()?->getId());
            $retencaoIr = $this->calcularRetencaoIR($valorBruto, $filtros['proprietario']);

            $itens[] = [
                'origem' => PrestacoesContasItens::ORIGEM_FICHA_FINANCEIRA,
                'id_lancamento_financeiro' => $lf->getId(),
                'id_lancamento' => null,
                'data_movimento' => $lf->getDataLancamento(),
                'data_vencimento' => $lf->getDataVencimento(),
                'data_pagamento' => null, // Precisa buscar da baixa
                'tipo' => PrestacoesContasItens::TIPO_RECEITA,
                'id_plano_conta' => $lf->getConta()?->getId(),
                'historico' => $lf->getHistorico() ?? 'Aluguel ' . $lf->getCompetencia()->format('m/Y'),
                'id_imovel' => $lf->getImovel()?->getId(),
                'valor_bruto' => $valorBruto,
                'valor_taxa_admin' => $taxaAdmin,
                'valor_retencao_ir' => $retencaoIr,
                'valor_liquido' => $valorBruto - $taxaAdmin - $retencaoIr,
            ];
        }

        return $itens;
    }

    /**
     * Busca dados dos Lançamentos (Contas a Pagar/Receber)
     */
    private function buscarDadosLancamentos(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Lancamentos::class, 'l')
            ->where('l.proprietario = :proprietario')
            ->andWhere('l.dataVencimento >= :dataInicio')
            ->andWhere('l.dataVencimento <= :dataFim')
            ->andWhere('l.status = :status')
            ->setParameter('proprietario', $filtros['proprietario'])
            ->setParameter('dataInicio', $filtros['data_inicio'])
            ->setParameter('dataFim', $filtros['data_fim'])
            ->setParameter('status', 'pago');

        if (!empty($filtros['imovel'])) {
            $qb->andWhere('l.imovel = :imovel')
               ->setParameter('imovel', $filtros['imovel']);
        }

        $lancamentos = $qb->getQuery()->getResult();
        $itens = [];

        foreach ($lancamentos as $l) {
            /** @var Lancamentos $l */
            $valorBruto = (float) $l->getValor();
            $isReceita = $l->getTipo() === Lancamentos::TIPO_RECEBER;

            if ($isReceita) {
                $taxaAdmin = $this->calcularTaxaAdmin($valorBruto, $l->getContrato()?->getId());
                $retencaoIr = $this->calcularRetencaoIR($valorBruto, $filtros['proprietario']);
                $origem = PrestacoesContasItens::ORIGEM_LANCAMENTO_RECEBER;
            } else {
                $taxaAdmin = 0;
                $retencaoIr = 0;
                $origem = PrestacoesContasItens::ORIGEM_LANCAMENTO_PAGAR;
            }

            $itens[] = [
                'origem' => $origem,
                'id_lancamento_financeiro' => null,
                'id_lancamento' => $l->getId(),
                'data_movimento' => $l->getDataMovimento(),
                'data_vencimento' => $l->getDataVencimento(),
                'data_pagamento' => $l->getDataPagamento(),
                'tipo' => $isReceita ? PrestacoesContasItens::TIPO_RECEITA : PrestacoesContasItens::TIPO_DESPESA,
                'id_plano_conta' => $l->getPlanoConta()?->getId(),
                'historico' => $l->getHistorico(),
                'id_imovel' => $l->getImovel()?->getId(),
                'valor_bruto' => $valorBruto,
                'valor_taxa_admin' => $taxaAdmin,
                'valor_retencao_ir' => $retencaoIr,
                'valor_liquido' => $isReceita ? ($valorBruto - $taxaAdmin - $retencaoIr) : $valorBruto,
            ];
        }

        return $itens;
    }

    /**
     * Cria item de prestação a partir dos dados
     */
    private function criarItemPrestacao(array $dados): PrestacoesContasItens
    {
        $item = new PrestacoesContasItens();
        $item->setOrigem($dados['origem']);
        $item->setDataMovimento($dados['data_movimento']);
        $item->setDataVencimento($dados['data_vencimento']);
        $item->setDataPagamento($dados['data_pagamento']);
        $item->setTipo($dados['tipo']);
        $item->setHistorico($dados['historico']);
        $item->setValorBruto(number_format($dados['valor_bruto'], 2, '.', ''));
        $item->setValorTaxaAdmin(number_format($dados['valor_taxa_admin'], 2, '.', ''));
        $item->setValorRetencaoIr(number_format($dados['valor_retencao_ir'], 2, '.', ''));
        $item->setValorLiquido(number_format($dados['valor_liquido'], 2, '.', ''));

        // Relacionamentos
        if (!empty($dados['id_lancamento_financeiro'])) {
            $lancFinanceiro = $this->lancFinanceiroRepo->find($dados['id_lancamento_financeiro']);
            $item->setLancamentoFinanceiro($lancFinanceiro);
        }

        if (!empty($dados['id_lancamento'])) {
            $lancamento = $this->lancamentoRepo->find($dados['id_lancamento']);
            $item->setLancamento($lancamento);
        }

        if (!empty($dados['id_plano_conta'])) {
            $planoConta = $this->em->getReference('App\\Entity\\PlanoContas', $dados['id_plano_conta']);
            $item->setPlanoConta($planoConta);
        }

        if (!empty($dados['id_imovel'])) {
            $imovel = $this->imovelRepo->find($dados['id_imovel']);
            $item->setImovel($imovel);
        }

        return $item;
    }

    /**
     * Calcula taxa de administração
     */
    public function calcularTaxaAdmin(float $valorReceita, ?int $idContrato): float
    {
        if (!$idContrato || $valorReceita <= 0) {
            return 0;
        }

        $contrato = $this->contratoRepo->find($idContrato);
        if (!$contrato) {
            return 0;
        }

        // Buscar taxa do contrato (assumindo que existe campo taxa_admin ou similar)
        $taxa = 0.10; // 10% padrão - ajustar conforme campo real do contrato

        // Se o contrato tem taxa definida, usar ela
        // $taxa = (float) ($contrato->getTaxaAdmin() ?? 0) / 100;

        return round($valorReceita * $taxa, 2);
    }

    /**
     * Calcula retenção de IR
     */
    public function calcularRetencaoIR(float $valorReceita, int $idProprietario): float
    {
        // Por padrão, não retém IR - implementar lógica conforme necessidade
        // Verificar se proprietário tem retenção configurada
        // Aplicar tabela progressiva se necessário

        return 0;
    }

    /**
     * Aprova prestação de contas
     *
     * @throws \Exception
     */
    public function aprovarPrestacao(int $id): PrestacoesContas
    {
        $prestacao = $this->prestacaoRepo->find($id);
        if (!$prestacao) {
            throw new \Exception('Prestação não encontrada.');
        }

        if (!$prestacao->podeAprovar()) {
            throw new \Exception('Esta prestação não pode ser aprovada.');
        }

        $prestacao->setStatus(PrestacoesContas::STATUS_APROVADO);

        $this->em->flush();

        return $prestacao;
    }

    /**
     * Registra repasse ao proprietário
     *
     * @throws \Exception
     */
    public function registrarRepasse(int $id, array $dadosRepasse): PrestacoesContas
    {
        $prestacao = $this->prestacaoRepo->find($id);
        if (!$prestacao) {
            throw new \Exception('Prestação não encontrada.');
        }

        if (!$prestacao->podeRegistrarRepasse()) {
            throw new \Exception('Esta prestação não está aprovada para repasse.');
        }

        $this->em->beginTransaction();

        try {
            $prestacao->setStatus(PrestacoesContas::STATUS_PAGO);
            $prestacao->setDataRepasse($dadosRepasse['data_repasse']);
            $prestacao->setFormaRepasse($dadosRepasse['forma_repasse'] ?? null);

            if (!empty($dadosRepasse['conta_bancaria'])) {
                $conta = $this->contaBancariaRepo->find($dadosRepasse['conta_bancaria']);
                $prestacao->setContaBancaria($conta);
            }

            if (!empty($dadosRepasse['observacoes'])) {
                $prestacao->setObservacoes($dadosRepasse['observacoes']);
            }

            // Upload do comprovante
            if (!empty($dadosRepasse['comprovante']) && $dadosRepasse['comprovante'] instanceof UploadedFile) {
                $comprovantePath = $this->uploadComprovante($dadosRepasse['comprovante'], $id);
                $prestacao->setComprovanteRepasse($comprovantePath);
            }

            $this->em->flush();
            $this->em->commit();

            return $prestacao;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao registrar repasse: ' . $e->getMessage());
        }
    }

    /**
     * Cancela prestação de contas
     *
     * @throws \Exception
     */
    public function cancelarPrestacao(int $id, ?string $motivo = null): PrestacoesContas
    {
        $prestacao = $this->prestacaoRepo->find($id);
        if (!$prestacao) {
            throw new \Exception('Prestação não encontrada.');
        }

        if (!$prestacao->podeCancelar()) {
            throw new \Exception('Esta prestação não pode ser cancelada.');
        }

        $prestacao->setStatus(PrestacoesContas::STATUS_CANCELADO);

        if ($motivo) {
            $observacoesAtuais = $prestacao->getObservacoes() ?? '';
            $novaObservacao = '[CANCELADO] ' . $motivo;
            $prestacao->setObservacoes(trim($observacoesAtuais . "\n" . $novaObservacao));
        }

        $this->em->flush();

        return $prestacao;
    }

    /**
     * Exclui prestação de contas
     *
     * @throws \Exception
     */
    public function excluirPrestacao(int $id): bool
    {
        $prestacao = $this->prestacaoRepo->find($id);
        if (!$prestacao) {
            throw new \Exception('Prestação não encontrada.');
        }

        if (!$prestacao->podeExcluir()) {
            throw new \Exception('Somente prestações com status "gerado" podem ser excluídas.');
        }

        $this->em->beginTransaction();

        try {
            $this->em->remove($prestacao);
            $this->em->flush();
            $this->em->commit();

            return true;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao excluir prestação: ' . $e->getMessage());
        }
    }

    /**
     * Upload do comprovante de repasse
     */
    private function uploadComprovante(UploadedFile $file, int $idPrestacao): string
    {
        $uploadPath = $this->projectDir . '/public/' . self::UPLOAD_DIR;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fileName = sprintf(
            'comprovante_%d_%s.%s',
            $idPrestacao,
            date('Ymd_His'),
            $file->guessExtension()
        );

        $file->move($uploadPath, $fileName);

        return self::UPLOAD_DIR . '/' . $fileName;
    }

    /**
     * Retorna imóveis do proprietário
     *
     * @return Imoveis[]
     */
    public function getImoveisDoProprietario(int $idProprietario): array
    {
        return $this->imovelRepo->createQueryBuilder('i')
            ->where('i.pessoaProprietario = :proprietario')
            ->setParameter('proprietario', $idProprietario)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retorna anos disponíveis para filtro
     */
    public function getAnosDisponiveis(): array
    {
        return $this->prestacaoRepo->getAnosDisponiveis();
    }
}
