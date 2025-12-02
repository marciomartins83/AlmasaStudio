<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DimobConfiguracoes;
use App\Entity\InformesRendimentos;
use App\Entity\InformesRendimentosValores;
use App\Entity\Pessoas;
use App\Repository\DimobConfiguracoesRepository;
use App\Repository\InformesRendimentosRepository;
use App\Repository\InformesRendimentosValoresRepository;
use App\Repository\LancamentosRepository;
use App\Repository\PessoaRepository;
use App\Repository\PlanoContasRepository;
use App\Repository\ImoveisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsável pela lógica de negócio de Informes de Rendimentos e DIMOB
 * Padrão: Fat Service
 */
class InformeRendimentoService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InformesRendimentosRepository $informesRepo,
        private InformesRendimentosValoresRepository $valoresRepo,
        private LancamentosRepository $lancamentosRepo,
        private PlanoContasRepository $planoContasRepo,
        private DimobConfiguracoesRepository $dimobRepo,
        private PessoaRepository $pessoasRepo,
        private ImoveisRepository $imoveisRepo,
        private LoggerInterface $logger
    ) {}

    /**
     * Processa informes de rendimentos para um ano
     * Varre lançamentos e gera/atualiza registros de informe
     *
     * @return array{processados: int, criados: int, atualizados: int, erros: int}
     */
    public function processarInformesAno(
        int $ano,
        ?int $proprietarioInicial = null,
        ?int $proprietarioFinal = null,
        bool $reprocessar = false
    ): array {
        $this->logger->info('Iniciando processamento de informes', [
            'ano' => $ano,
            'proprietarioInicial' => $proprietarioInicial,
            'proprietarioFinal' => $proprietarioFinal,
            'reprocessar' => $reprocessar
        ]);

        $resultado = [
            'processados' => 0,
            'criados' => 0,
            'atualizados' => 0,
            'erros' => 0
        ];

        try {
            // Buscar lançamentos agrupados
            $lancamentosAgrupados = $this->lancamentosRepo->findParaProcessamentoInforme(
                $ano,
                $proprietarioInicial,
                $proprietarioFinal
            );

            if (empty($lancamentosAgrupados)) {
                $this->logger->warning('Nenhum lançamento encontrado para processamento');
                return $resultado;
            }

            // Agrupar por chave única (proprietario+imovel+inquilino+conta)
            $agrupados = [];
            foreach ($lancamentosAgrupados as $lanc) {
                $chave = sprintf(
                    '%d_%d_%d_%d',
                    $lanc['id_proprietario'],
                    $lanc['id_imovel'],
                    $lanc['id_inquilino'],
                    $lanc['id_plano_conta']
                );

                if (!isset($agrupados[$chave])) {
                    $agrupados[$chave] = [
                        'id_proprietario' => $lanc['id_proprietario'],
                        'id_imovel' => $lanc['id_imovel'],
                        'id_inquilino' => $lanc['id_inquilino'],
                        'id_plano_conta' => $lanc['id_plano_conta'],
                        'valores' => []
                    ];
                }

                $agrupados[$chave]['valores'][$lanc['mes']] = (float) $lanc['total'];
            }

            $this->em->beginTransaction();

            foreach ($agrupados as $dados) {
                try {
                    $informe = $this->processarInforme($ano, $dados, $reprocessar);

                    if ($informe !== null) {
                        $resultado['processados']++;
                        if ($informe->getId() === null) {
                            $resultado['criados']++;
                        } else {
                            $resultado['atualizados']++;
                        }
                    }
                } catch (\Exception $e) {
                    $resultado['erros']++;
                    $this->logger->error('Erro ao processar informe', [
                        'dados' => $dados,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Processamento concluído', $resultado);

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro crítico no processamento', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $resultado;
    }

    /**
     * Processa um único informe
     */
    private function processarInforme(int $ano, array $dados, bool $reprocessar): ?InformesRendimentos
    {
        // Buscar informe existente
        $informe = $this->informesRepo->findByChaveUnica(
            $ano,
            $dados['id_proprietario'],
            $dados['id_imovel'],
            $dados['id_inquilino'],
            $dados['id_plano_conta']
        );

        // Se existe e não é reprocessamento, pular
        if ($informe !== null && !$reprocessar) {
            return null;
        }

        // Buscar entidades relacionadas
        $proprietario = $this->pessoasRepo->find($dados['id_proprietario']);
        $imovel = $this->imoveisRepo->find($dados['id_imovel']);
        $inquilino = $this->pessoasRepo->find($dados['id_inquilino']);
        $planoConta = $this->planoContasRepo->find($dados['id_plano_conta']);

        if (!$proprietario || !$imovel || !$inquilino || !$planoConta) {
            throw new \RuntimeException('Entidade relacionada não encontrada');
        }

        // Criar novo informe se não existe
        if ($informe === null) {
            $informe = new InformesRendimentos();
            $informe->setAno($ano);
            $informe->setProprietario($proprietario);
            $informe->setImovel($imovel);
            $informe->setInquilino($inquilino);
            $informe->setPlanoConta($planoConta);
        }

        // Atualizar status e data
        $informe->setStatus(InformesRendimentos::STATUS_PROCESSADO);
        $informe->setDataProcessamento(new \DateTime());
        $informe->setUpdatedAt(new \DateTime());

        // Atualizar valores mensais
        foreach ($dados['valores'] as $mes => $valor) {
            $informe->setValorMes((int) $mes, $valor);
        }

        $this->em->persist($informe);

        return $informe;
    }

    /**
     * Busca informes com filtros para aba Manutenção
     */
    public function buscarInformesComFiltros(array $filtros): array
    {
        $informes = $this->informesRepo->findByFiltros($filtros);
        $resultado = [];

        foreach ($informes as $informe) {
            $resultado[] = $this->serializarInforme($informe);
        }

        return $resultado;
    }

    /**
     * Serializa um informe para JSON
     */
    private function serializarInforme(InformesRendimentos $informe): array
    {
        return [
            'id' => $informe->getId(),
            'ano' => $informe->getAno(),
            'proprietarioId' => $informe->getProprietario()->getIdpessoa(),
            'proprietarioNome' => $informe->getProprietario()->getNome(),
            'imovelId' => $informe->getImovel()->getId(),
            'imovelCodigo' => $informe->getImovel()->getCodigoInterno(),
            'inquilinoId' => $informe->getInquilino()->getIdpessoa(),
            'inquilinoNome' => $informe->getInquilino()->getNome(),
            'contaId' => $informe->getPlanoConta()->getId(),
            'contaCodigo' => $informe->getPlanoConta()->getCodigo(),
            'contaDescricao' => $informe->getPlanoConta()->getDescricao(),
            'status' => $informe->getStatus(),
            'valores' => $informe->getValoresArray(),
            'total' => $informe->getTotalAnual()
        ];
    }

    /**
     * Busca um informe por ID
     */
    public function buscarInformePorId(int $id): ?InformesRendimentos
    {
        return $this->informesRepo->find($id);
    }

    /**
     * Atualiza um informe manualmente
     */
    public function atualizarInforme(int $id, array $dados): InformesRendimentos
    {
        $informe = $this->informesRepo->find($id);

        if ($informe === null) {
            throw new \RuntimeException('Informe não encontrado');
        }

        if (!$informe->isEditavel()) {
            throw new \RuntimeException('Informe já finalizado, não pode ser editado');
        }

        $this->em->beginTransaction();

        try {
            // Atualizar valores mensais se fornecidos
            if (isset($dados['valores']) && is_array($dados['valores'])) {
                foreach ($dados['valores'] as $mes => $valor) {
                    $informe->setValorMes((int) $mes, (float) $valor);
                }
            }

            // Atualizar status se fornecido
            if (isset($dados['status'])) {
                $informe->setStatus($dados['status']);
            }

            $informe->setUpdatedAt(new \DateTime());

            $this->em->flush();
            $this->em->commit();

            return $informe;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Salva configurações DIMOB
     */
    public function salvarDimobConfiguracao(array $dados): DimobConfiguracoes
    {
        $ano = $dados['ano'] ?? (int) date('Y');

        $config = $this->dimobRepo->findByAno($ano);

        if ($config === null) {
            $config = new DimobConfiguracoes();
            $config->setAno($ano);
        }

        $config->setCnpjDeclarante($dados['cnpjDeclarante'] ?? '');
        $config->setCpfResponsavel($dados['cpfResponsavel'] ?? '');
        $config->setCodigoCidade($dados['codigoCidade'] ?? '');
        $config->setDeclaracaoRetificadora($dados['declaracaoRetificadora'] ?? false);
        $config->setSituacaoEspecial($dados['situacaoEspecial'] ?? false);
        $config->setUpdatedAt(new \DateTime());

        $this->em->persist($config);
        $this->em->flush();

        return $config;
    }

    /**
     * Busca configuração DIMOB por ano
     */
    public function buscarDimobPorAno(int $ano): ?DimobConfiguracoes
    {
        return $this->dimobRepo->findByAno($ano);
    }

    /**
     * Gera arquivo DIMOB no formato da Receita Federal
     *
     * @return string Conteúdo do arquivo
     */
    public function gerarArquivoDimob(
        int $ano,
        ?int $proprietarioInicial = null,
        ?int $proprietarioFinal = null
    ): string {
        $config = $this->dimobRepo->findByAno($ano);

        if ($config === null) {
            throw new \RuntimeException('Configuração DIMOB não encontrada para o ano ' . $ano);
        }

        $informes = $this->informesRepo->findParaDimob($ano, $proprietarioInicial, $proprietarioFinal);

        if (empty($informes)) {
            throw new \RuntimeException('Nenhum informe encontrado para geração do DIMOB');
        }

        $linhas = [];

        // Registro 0 - Cabeçalho
        $linhas[] = $this->gerarRegistro0($config, count($informes));

        // Registros de informes agrupados por proprietário
        $proprietarioAtual = null;
        $sequencia = 0;

        foreach ($informes as $informe) {
            $propId = $informe->getProprietario()->getIdpessoa();

            // Se mudou de proprietário, gerar registro R01
            if ($proprietarioAtual !== $propId) {
                $proprietarioAtual = $propId;
                $sequencia++;
                $linhas[] = $this->gerarRegistroR01($informe, $sequencia);
            }

            // Registro R02 - Detalhe
            $linhas[] = $this->gerarRegistroR02($informe);
        }

        // Registro 9 - Rodapé
        $linhas[] = $this->gerarRegistro9(count($linhas) + 1);

        // Atualizar data de geração
        $config->setDataGeracao(new \DateTime());
        $this->em->flush();

        return implode("\r\n", $linhas);
    }

    /**
     * Gera registro tipo 0 (cabeçalho)
     */
    private function gerarRegistro0(DimobConfiguracoes $config, int $qtdRegistros): string
    {
        return sprintf(
            '0%s%s%04d%s%s%s%s',
            str_pad($config->getCnpjDeclaranteNumeros(), 14, '0', STR_PAD_LEFT),
            str_pad($config->getCpfResponsavelNumeros(), 11, '0', STR_PAD_LEFT),
            $config->getAno(),
            $config->getTipoDeclaracao(),
            $config->getIndicadorSituacaoEspecial(),
            str_pad($config->getCodigoCidade(), 7, '0', STR_PAD_LEFT),
            str_repeat(' ', 200) // Preencher até tamanho fixo
        );
    }

    /**
     * Gera registro tipo R01 (proprietário)
     */
    private function gerarRegistroR01(InformesRendimentos $informe, int $sequencia): string
    {
        $proprietario = $informe->getProprietario();
        $cpfCnpj = $this->obterCpfCnpjPessoa($proprietario);

        return sprintf(
            'R01%05d%s%s%s',
            $sequencia,
            str_pad($cpfCnpj, 14, '0', STR_PAD_LEFT),
            str_pad(substr($proprietario->getNome(), 0, 60), 60),
            str_repeat(' ', 150)
        );
    }

    /**
     * Gera registro tipo R02 (detalhe)
     */
    private function gerarRegistroR02(InformesRendimentos $informe): string
    {
        $imovel = $informe->getImovel();
        $inquilino = $informe->getInquilino();
        $cpfCnpjInquilino = $this->obterCpfCnpjPessoa($inquilino);

        $valores = $informe->getValoresArray();
        $valoresFormatados = '';

        for ($mes = 1; $mes <= 12; $mes++) {
            $valoresFormatados .= str_pad(
                number_format($valores[$mes], 2, '', ''),
                15,
                '0',
                STR_PAD_LEFT
            );
        }

        return sprintf(
            'R02%s%s%s%s%s',
            str_pad($imovel->getCodigoInterno() ?? '', 20),
            str_pad($cpfCnpjInquilino, 14, '0', STR_PAD_LEFT),
            str_pad(substr($inquilino->getNome(), 0, 60), 60),
            $valoresFormatados,
            str_repeat(' ', 50)
        );
    }

    /**
     * Gera registro tipo 9 (rodapé)
     */
    private function gerarRegistro9(int $totalLinhas): string
    {
        return sprintf(
            '9%08d%s',
            $totalLinhas,
            str_repeat(' ', 240)
        );
    }

    /**
     * Obtém CPF ou CNPJ de uma pessoa
     */
    private function obterCpfCnpjPessoa(Pessoas $pessoa): string
    {
        $documentos = $pessoa->getDocumentos();

        foreach ($documentos as $doc) {
            $tipo = $doc->getTipo();
            if ($tipo && in_array(strtoupper($tipo->getDescricao()), ['CPF', 'CNPJ'])) {
                return preg_replace('/\D/', '', $doc->getNumero());
            }
        }

        return '';
    }

    /**
     * Lista proprietários (pessoas que possuem imóveis)
     *
     * @return array
     */
    public function listarProprietarios(): array
    {
        // Buscar pessoas que são proprietárias de imóveis
        $qb = $this->em->createQueryBuilder();
        $qb->select('DISTINCT p.idpessoa as id, p.nome')
            ->from(Pessoas::class, 'p')
            ->innerJoin('App\Entity\Imoveis', 'i', 'WITH', 'i.pessoaProprietario = p')
            ->orderBy('p.nome', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Lista anos disponíveis (anos com lançamentos ou informes)
     *
     * @return int[]
     */
    public function listarAnosDisponiveis(): array
    {
        $anosLancamentos = $this->lancamentosRepo->findAnosComLancamentos();
        $anosInformes = $this->informesRepo->findAnosComInformes();

        $anos = array_unique(array_merge($anosLancamentos, $anosInformes));
        rsort($anos);

        // Se não há anos, retornar ano atual e anterior
        if (empty($anos)) {
            $anoAtual = (int) date('Y');
            $anos = [$anoAtual, $anoAtual - 1];
        }

        return $anos;
    }

    /**
     * Gera dados para PDF - Modelo 1
     */
    public function gerarDadosPdfModelo1(int $ano, ?int $idProprietario, bool $abaterTaxaAdmin): array
    {
        $filtros = ['ano' => $ano];
        if ($idProprietario !== null) {
            $filtros['idProprietario'] = $idProprietario;
        }

        $informes = $this->informesRepo->findByFiltros($filtros);

        $dados = [];
        foreach ($informes as $informe) {
            $valores = $informe->getValoresArray();
            $total = $informe->getTotalAnual();

            // Se deve abater taxa de administração
            if ($abaterTaxaAdmin && $informe->getPlanoConta()->isIncideTaxaAdmin()) {
                $imovel = $informe->getImovel();
                $taxaAdmin = (float) $imovel->getTaxaAdministracao();
                if ($taxaAdmin > 0) {
                    $desconto = $total * ($taxaAdmin / 100);
                    $total -= $desconto;
                }
            }

            $dados[] = [
                'proprietario' => $informe->getProprietario()->getNome(),
                'imovel' => $informe->getImovel()->getCodigoInterno(),
                'inquilino' => $informe->getInquilino()->getNome(),
                'conta' => $informe->getPlanoConta()->getDescricao(),
                'valores' => $valores,
                'total' => $total
            ];
        }

        return $dados;
    }

    /**
     * Gera dados para PDF - Modelo 2 (agrupado por proprietário)
     */
    public function gerarDadosPdfModelo2(int $ano, ?int $idProprietario, bool $abaterTaxaAdmin): array
    {
        $dados = $this->gerarDadosPdfModelo1($ano, $idProprietario, $abaterTaxaAdmin);

        // Agrupar por proprietário
        $agrupado = [];
        foreach ($dados as $item) {
            $prop = $item['proprietario'];
            if (!isset($agrupado[$prop])) {
                $agrupado[$prop] = [
                    'proprietario' => $prop,
                    'imoveis' => [],
                    'totalGeral' => 0
                ];
            }

            $agrupado[$prop]['imoveis'][] = $item;
            $agrupado[$prop]['totalGeral'] += $item['total'];
        }

        return array_values($agrupado);
    }
}
