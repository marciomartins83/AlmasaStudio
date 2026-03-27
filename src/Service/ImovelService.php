<?php

namespace App\Service;

use App\Entity\Imoveis;
use App\Entity\ImoveisFotos;
use App\Entity\ImoveisMedidores;
use App\Entity\ImoveisPropriedades;
use App\Entity\ImoveisGarantias;
use App\Entity\ImoveisContratos;
use App\Entity\PropriedadesCatalogo;
use App\Entity\Condominios;
use App\Entity\TiposImoveis;
use App\Entity\Pessoas;
use App\Entity\Enderecos;
use App\Repository\ImoveisRepository;
use App\Repository\PropriedadesCatalogoRepository;
use App\Repository\TiposImoveisRepository;
use App\Repository\PessoaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ImovelService - Fat Service
 * Contém TODA a lógica de negócio do módulo de imóveis
 *
 * Responsabilidades:
 * - Gerenciamento de transações
 * - Validações de negócio
 * - Operações de persistência (persist, flush, remove)
 * - Relacionamentos complexos (propriedades, fotos, medidores, garantias)
 */
class ImovelService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ImoveisRepository $imoveisRepository;
    private PropriedadesCatalogoRepository $propriedadesCatalogoRepository;
    private TiposImoveisRepository $tiposImoveisRepository;
    private PessoaRepository $pessoaRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ImoveisRepository $imoveisRepository,
        PropriedadesCatalogoRepository $propriedadesCatalogoRepository,
        TiposImoveisRepository $tiposImoveisRepository,
        PessoaRepository $pessoaRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->imoveisRepository = $imoveisRepository;
        $this->propriedadesCatalogoRepository = $propriedadesCatalogoRepository;
        $this->tiposImoveisRepository = $tiposImoveisRepository;
        $this->pessoaRepository = $pessoaRepository;
    }

    /**
     * Lista imóveis com dados enriquecidos para exibição
     *
     * @return array
     */
    public function listarImoveisEnriquecidos(): array
    {
        $imoveis = $this->imoveisRepository->findAll();
        $imoveisEnriquecidos = [];

        foreach ($imoveis as $imovel) {
            $imoveisEnriquecidos[] = [
                'id' => $imovel->getId(),
                'codigo_interno' => $imovel->getCodigoInterno(),
                'tipo' => $imovel->getTipoImovel()?->getTipo(),
                'situacao' => $imovel->getSituacao(),
                'endereco' => $this->formatarEndereco($imovel->getEndereco()),
                'proprietario' => $imovel->getPessoaProprietario()?->getNome(),
                'valor_aluguel' => $imovel->getValorAluguel(),
                'valor_venda' => $imovel->getValorVenda(),
                'disponivel_aluguel' => $imovel->isAluguelGarantido(),
                'disponivel_venda' => $imovel->isDisponivelVenda(),
                'qtd_quartos' => $imovel->getQtdQuartos(),
                'qtd_banheiros' => $imovel->getQtdBanheiros(),
                'area_total' => $imovel->getAreaTotal(),
            ];
        }

        return $imoveisEnriquecidos;
    }

    /**
     * Salva novo imóvel com todas as entidades relacionadas
     *
     * @param Imoveis $imovel
     * @param array $requestData
     * @param array $autocompleteIds IDs dos campos autocomplete (pessoaProprietario, pessoaFiador, pessoaCorretor, endereco, condominio)
     * @return void
     * @throws \Exception
     */
    public function salvarImovel(Imoveis $imovel, array $requestData, array $autocompleteIds = []): void
    {
        // Validação de código interno único
        if ($imovel->getCodigoInterno()) {
            $existente = $this->imoveisRepository->findOneBy([
                'codigoInterno' => $imovel->getCodigoInterno()
            ]);
            if ($existente) {
                throw new \RuntimeException('Código interno já cadastrado.');
            }
        }

        // Resolve campos autocomplete (IDs -> entidades)
        $this->resolverAutocompleteIds($imovel, $autocompleteIds);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Persiste imóvel principal
            $this->entityManager->persist($imovel);
            $this->entityManager->flush();

            $imovelId = $imovel->getId();
            $this->logger->info('[IMÓVEL SALVO - ID] ' . $imovelId);

            // Salva entidades relacionadas
            $this->salvarPropriedades($imovel, $requestData['propriedades'] ?? []);
            $this->salvarMedidores($imovel, $requestData['medidores'] ?? []);
            $this->salvarFotos($imovel, $requestData['fotos'] ?? []);
            $this->salvarGarantias($imovel, $requestData['garantias'] ?? []);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('🔴 ERRO ao salvar imóvel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza imóvel existente
     *
     * @param Imoveis $imovel
     * @param array $requestData
     * @param array $autocompleteIds IDs dos campos autocomplete (pessoaProprietario, pessoaFiador, pessoaCorretor, endereco, condominio)
     * @return void
     * @throws \Exception
     */
    public function atualizarImovel(Imoveis $imovel, array $requestData, array $autocompleteIds = []): void
    {
        // Resolve campos autocomplete (IDs -> entidades)
        $this->resolverAutocompleteIds($imovel, $autocompleteIds);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Atualiza entidades relacionadas
            $this->salvarPropriedades($imovel, $requestData['propriedades'] ?? []);
            $this->salvarMedidores($imovel, $requestData['medidores'] ?? []);
            $this->salvarFotos($imovel, $requestData['fotos'] ?? []);
            $this->salvarGarantias($imovel, $requestData['garantias'] ?? []);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('[IMÓVEL ATUALIZADO - ID] ' . $imovel->getId());

        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('🔴 ERRO ao atualizar imóvel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Carrega dados completos do imóvel para edição
     *
     * @param int $imovelId
     * @return array
     */
    public function carregarDadosCompletos(int $imovelId): array
    {
        $imovel = $this->imoveisRepository->find($imovelId);

        if (!$imovel) {
            throw new \RuntimeException('Imóvel não encontrado.');
        }

        return [
            'propriedades' => $this->carregarPropriedades($imovel),
            'medidores' => $this->carregarMedidores($imovel),
            'fotos' => $this->carregarFotos($imovel),
            'garantias' => $this->carregarGarantias($imovel),
        ];
    }

    /**
     * Busca imóvel por código interno
     *
     * @param string $codigoInterno
     * @return array|null
     */
    public function buscarPorCodigoInterno(string $codigoInterno): ?array
    {
        $imovel = $this->imoveisRepository->findOneBy([
            'codigoInterno' => $codigoInterno
        ]);

        if (!$imovel) {
            return null;
        }

        return [
            'id' => $imovel->getId(),
            'codigo_interno' => $imovel->getCodigoInterno(),
            'tipo_imovel_id' => $imovel->getTipoImovel()?->getId(),
            'situacao' => $imovel->getSituacao(),
            'endereco' => $this->formatarEndereco($imovel->getEndereco()),
            'proprietario_id' => $imovel->getPessoaProprietario()?->getIdpessoa(),
            'proprietario_nome' => $imovel->getPessoaProprietario()?->getNome(),
            'valor_aluguel' => $imovel->getValorAluguel(),
            'valor_venda' => $imovel->getValorVenda(),
            'area_total' => $imovel->getAreaTotal(),
            'qtd_quartos' => $imovel->getQtdQuartos(),
            'qtd_banheiros' => $imovel->getQtdBanheiros(),
            'descricao' => $imovel->getDescricao(),
        ];
    }

    /**
     * Deleta foto do imóvel
     *
     * @param int $fotoId
     * @return void
     * @throws \Exception
     */
    public function deletarFoto(int $fotoId): void
    {
        $foto = $this->entityManager->getRepository(ImoveisFotos::class)->find($fotoId);

        if (!$foto) {
            throw new \RuntimeException('Foto não encontrada.');
        }

        $this->entityManager->remove($foto);
        $this->entityManager->flush();

        $this->logger->info('[FOTO DELETADA - ID] ' . $fotoId);
    }

    /**
     * Deleta medidor do imóvel
     *
     * @param int $medidorId
     * @return void
     * @throws \Exception
     */
    public function deletarMedidor(int $medidorId): void
    {
        $medidor = $this->entityManager->getRepository(ImoveisMedidores::class)->find($medidorId);

        if (!$medidor) {
            throw new \RuntimeException('Medidor não encontrado.');
        }

        $this->entityManager->remove($medidor);
        $this->entityManager->flush();

        $this->logger->info('[MEDIDOR DELETADO - ID] ' . $medidorId);
    }

    /**
     * Deleta propriedade do imóvel (relacionamento N:N)
     *
     * @param int $idImovel
     * @param int $idPropriedade
     * @return void
     * @throws \Exception
     */
    public function deletarPropriedade(int $idImovel, int $idPropriedade): void
    {
        $relacionamento = $this->entityManager->getRepository(ImoveisPropriedades::class)
            ->findOneBy([
                'imovel' => $idImovel,
                'propriedade' => $idPropriedade
            ]);

        if (!$relacionamento) {
            throw new \RuntimeException('Propriedade não encontrada neste imóvel.');
        }

        $this->entityManager->remove($relacionamento);
        $this->entityManager->flush();

        $this->logger->info("[PROPRIEDADE REMOVIDA] Imóvel: $idImovel, Propriedade: $idPropriedade");
    }

    /**
     * Lista todas as propriedades do catálogo
     *
     * @return array
     */
    public function listarPropriedadesCatalogo(): array
    {
        $propriedades = $this->propriedadesCatalogoRepository->findBy(['ativo' => true]);
        $resultado = [];

        foreach ($propriedades as $propriedade) {
            $resultado[] = [
                'id' => $propriedade->getId(),
                'nome' => $propriedade->getNome(),
                'categoria' => $propriedade->getCategoria(),
                'icone' => $propriedade->getIcone(),
            ];
        }

        return $resultado;
    }

    /**
     * Formata endereço para label de autocomplete
     *
     * @param Enderecos|null $endereco
     * @return string
     */
    public function formatarEnderecoLabel(?Enderecos $endereco): string
    {
        return $this->formatarEndereco($endereco);
    }

    // ========================================================================
    // MÉTODOS PRIVADOS (Auxiliares)
    // ========================================================================

    /**
     * Resolve IDs de autocomplete para entidades e seta no imóvel
     *
     * @param Imoveis $imovel
     * @param array $autocompleteIds
     * @return void
     */
    private function resolverAutocompleteIds(Imoveis $imovel, array $autocompleteIds): void
    {
        if (!empty($autocompleteIds['pessoaProprietario'])) {
            $pessoa = $this->pessoaRepository->find((int) $autocompleteIds['pessoaProprietario']);
            if ($pessoa) {
                $imovel->setPessoaProprietario($pessoa);
            }
        }

        if (!empty($autocompleteIds['pessoaFiador'])) {
            $pessoa = $this->pessoaRepository->find((int) $autocompleteIds['pessoaFiador']);
            $imovel->setPessoaFiador($pessoa);
        } else {
            $imovel->setPessoaFiador(null);
        }

        if (!empty($autocompleteIds['pessoaCorretor'])) {
            $pessoa = $this->pessoaRepository->find((int) $autocompleteIds['pessoaCorretor']);
            $imovel->setPessoaCorretor($pessoa);
        } else {
            $imovel->setPessoaCorretor(null);
        }

        if (!empty($autocompleteIds['endereco'])) {
            $endereco = $this->entityManager->getRepository(Enderecos::class)->find((int) $autocompleteIds['endereco']);
            if ($endereco) {
                $imovel->setEndereco($endereco);
            }
        }

        if (!empty($autocompleteIds['condominio'])) {
            $condominio = $this->entityManager->getRepository(Condominios::class)->find((int) $autocompleteIds['condominio']);
            $imovel->setCondominio($condominio);
        } else {
            $imovel->setCondominio(null);
        }
    }

    /**
     * Salva propriedades do imóvel (N:N)
     *
     * @param Imoveis $imovel
     * @param array $propriedadesIds
     * @return void
     */
    private function salvarPropriedades(Imoveis $imovel, array $propriedadesIds): void
    {
        if (empty($propriedadesIds)) {
            return;
        }

        foreach ($propriedadesIds as $propriedadeId) {
            $propriedade = $this->propriedadesCatalogoRepository->find($propriedadeId);

            if (!$propriedade) {
                continue;
            }

            // Verifica se já existe
            $existente = $this->entityManager->getRepository(ImoveisPropriedades::class)
                ->findOneBy([
                    'imovel' => $imovel,
                    'propriedade' => $propriedade
                ]);

            if ($existente) {
                continue;
            }

            $imovelPropriedade = new ImoveisPropriedades();
            $imovelPropriedade->setImovel($imovel);
            $imovelPropriedade->setPropriedade($propriedade);

            $this->entityManager->persist($imovelPropriedade);
        }
    }

    /**
     * Salva medidores do imóvel
     *
     * @param Imoveis $imovel
     * @param array $medidores
     * @return void
     */
    private function salvarMedidores(Imoveis $imovel, array $medidores): void
    {
        if (empty($medidores)) {
            return;
        }

        foreach ($medidores as $medidorData) {
            if (empty($medidorData['tipo_medidor']) || empty($medidorData['numero_medidor'])) {
                continue;
            }

            $medidor = new ImoveisMedidores();
            $medidor->setImovel($imovel);
            $medidor->setTipoMedidor($medidorData['tipo_medidor']);
            $medidor->setNumeroMedidor($medidorData['numero_medidor']);
            $medidor->setConcessionaria($medidorData['concessionaria'] ?? null);
            $medidor->setObservacoes($medidorData['observacoes'] ?? null);

            $this->entityManager->persist($medidor);
        }
    }

    /**
     * Salva fotos do imóvel
     *
     * @param Imoveis $imovel
     * @param array $fotos
     * @return void
     */
    private function salvarFotos(Imoveis $imovel, array $fotos): void
    {
        if (empty($fotos)) {
            return;
        }

        foreach ($fotos as $fotoData) {
            if (empty($fotoData['arquivo']) || empty($fotoData['caminho'])) {
                continue;
            }

            $foto = new ImoveisFotos();
            $foto->setImovel($imovel);
            $foto->setArquivo($fotoData['arquivo']);
            $foto->setCaminho($fotoData['caminho']);
            $foto->setLegenda($fotoData['legenda'] ?? null);
            $foto->setOrdem($fotoData['ordem'] ?? 0);
            $foto->setCapa($fotoData['capa'] ?? false);

            $this->entityManager->persist($foto);
        }
    }

    /**
     * Salva garantias do imóvel (1:1)
     *
     * @param Imoveis $imovel
     * @param array $garantiasData
     * @return void
     */
    private function salvarGarantias(Imoveis $imovel, array $garantiasData): void
    {
        if (empty($garantiasData)) {
            return;
        }

        // Busca garantia existente ou cria nova
        $garantia = $this->entityManager->getRepository(ImoveisGarantias::class)
            ->findOneBy(['imovel' => $imovel]);

        if (!$garantia) {
            $garantia = new ImoveisGarantias();
            $garantia->setImovel($imovel);
        }

        $garantia->setAceitaCaucao($garantiasData['aceita_caucao'] ?? false);
        $garantia->setAceitaFiador($garantiasData['aceita_fiador'] ?? false);
        $garantia->setAceitaSeguroFianca($garantiasData['aceita_seguro_fianca'] ?? false);
        $garantia->setAceitaOutras($garantiasData['aceita_outras'] ?? false);
        $garantia->setValorCaucao($garantiasData['valor_caucao'] ?? null);
        $garantia->setQtdMesesCaucao($garantiasData['qtd_meses_caucao'] ?? null);
        $garantia->setSeguradora($garantiasData['seguradora'] ?? null);
        $garantia->setNumeroApolice($garantiasData['numero_apolice'] ?? null);
        $garantia->setValorSeguro($garantiasData['valor_seguro'] ?? null);
        $garantia->setObservacoes($garantiasData['observacoes'] ?? null);

        $this->entityManager->persist($garantia);
    }

    /**
     * Carrega propriedades do imóvel
     *
     * @param Imoveis $imovel
     * @return array
     */
    private function carregarPropriedades(Imoveis $imovel): array
    {
        $propriedadesRelacionamento = $this->entityManager
            ->getRepository(ImoveisPropriedades::class)
            ->findBy(['imovel' => $imovel]);

        $resultado = [];
        foreach ($propriedadesRelacionamento as $rel) {
            $prop = $rel->getPropriedade();
            $resultado[] = [
                'id' => $prop->getId(),
                'nome' => $prop->getNome(),
                'categoria' => $prop->getCategoria(),
            ];
        }

        return $resultado;
    }

    /**
     * Carrega medidores do imóvel
     *
     * @param Imoveis $imovel
     * @return array
     */
    private function carregarMedidores(Imoveis $imovel): array
    {
        $medidores = $this->entityManager
            ->getRepository(ImoveisMedidores::class)
            ->findBy(['imovel' => $imovel, 'ativo' => true]);

        $resultado = [];
        foreach ($medidores as $medidor) {
            $resultado[] = [
                'id' => $medidor->getId(),
                'tipo_medidor' => $medidor->getTipoMedidor(),
                'numero_medidor' => $medidor->getNumeroMedidor(),
                'concessionaria' => $medidor->getConcessionaria(),
                'observacoes' => $medidor->getObservacoes(),
            ];
        }

        return $resultado;
    }

    /**
     * Carrega fotos do imóvel
     *
     * @param Imoveis $imovel
     * @return array
     */
    private function carregarFotos(Imoveis $imovel): array
    {
        $fotos = $this->entityManager
            ->getRepository(ImoveisFotos::class)
            ->findBy(['imovel' => $imovel], ['ordem' => 'ASC']);

        $resultado = [];
        foreach ($fotos as $foto) {
            $resultado[] = [
                'id' => $foto->getId(),
                'arquivo' => $foto->getArquivo(),
                'caminho' => $foto->getCaminho(),
                'legenda' => $foto->getLegenda(),
                'ordem' => $foto->getOrdem(),
                'capa' => $foto->getCapa(),
            ];
        }

        return $resultado;
    }

    /**
     * Carrega garantias do imóvel
     *
     * @param Imoveis $imovel
     * @return array|null
     */
    private function carregarGarantias(Imoveis $imovel): ?array
    {
        $garantia = $this->entityManager
            ->getRepository(ImoveisGarantias::class)
            ->findOneBy(['imovel' => $imovel]);

        if (!$garantia) {
            return null;
        }

        return [
            'aceita_caucao' => $garantia->getAceitaCaucao(),
            'aceita_fiador' => $garantia->getAceitaFiador(),
            'aceita_seguro_fianca' => $garantia->getAceitaSeguroFianca(),
            'aceita_outras' => $garantia->getAceitaOutras(),
            'valor_caucao' => $garantia->getValorCaucao(),
            'qtd_meses_caucao' => $garantia->getQtdMesesCaucao(),
            'seguradora' => $garantia->getSeguradora(),
            'numero_apolice' => $garantia->getNumeroApolice(),
            'valor_seguro' => $garantia->getValorSeguro(),
            'observacoes' => $garantia->getObservacoes(),
        ];
    }

    /**
     * Formata endereço para exibição
     *
     * @param Enderecos|null $endereco
     * @return string
     */
    private function formatarEndereco(?Enderecos $endereco): string
    {
        if (!$endereco) {
            return '';
        }

        $logradouro = $endereco->getLogradouro();
        if (!$logradouro) {
            return '';
        }

        $partes = [
            $logradouro->getLogradouro(),
            $endereco->getEndNumero() ? ', ' . $endereco->getEndNumero() : '',
            $logradouro->getBairro()?->getNome() ? ' - ' . $logradouro->getBairro()->getNome() : '',
            $logradouro->getBairro()?->getCidade()?->getNome() ? ', ' . $logradouro->getBairro()->getCidade()->getNome() : '',
        ];

        return implode('', $partes);
    }
}
