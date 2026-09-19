<?php

namespace App\Service;

use App\Entity\FaixaImpostoRenda;
use App\Repository\FaixaImpostoRendaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar a Tabela de Imposto de Renda (faixas de alíquota) e
 * calcular a retenção de IR sobre um valor de receita.
 */
class TabelaImpostoRendaService
{
    private EntityManagerInterface $entityManager;
    private FaixaImpostoRendaRepository $repository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        FaixaImpostoRendaRepository $repository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function criar(FaixaImpostoRenda $faixa): void
    {
        try {
            $this->entityManager->persist($faixa);
            $this->entityManager->flush();

            $this->logger->info('Faixa de IR criada com sucesso', [
                'id' => $faixa->getId(),
                'data_vigencia' => $faixa->getDataVigencia()->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar faixa de IR', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function atualizar(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar faixa de IR', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(FaixaImpostoRenda $faixa): void
    {
        try {
            $this->entityManager->remove($faixa);
            $this->entityManager->flush();

            $this->logger->info('Faixa de IR deletada com sucesso', [
                'id' => $faixa->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar faixa de IR', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calcula a retenção de IR sobre um valor bruto, usando a tabela progressiva
     * vigente na data de referência (ou hoje, se não informada).
     *
     * retencao = valorBruto * aliquota/100 - parcelaDeduzir (nunca negativo)
     */
    public function calcularRetencao(float $valorBruto, ?\DateTimeInterface $dataReferencia = null): float
    {
        if ($valorBruto <= 0) {
            return 0.0;
        }

        $dataReferencia = $dataReferencia ?? new \DateTime();

        $dataVigente = $this->repository->findDataVigenteMaisRecente($dataReferencia);
        if ($dataVigente === null) {
            $this->logger->warning('Nenhuma tabela de IR vigente cadastrada para a data de referência', [
                'data_referencia' => $dataReferencia->format('Y-m-d'),
            ]);
            return 0.0;
        }

        $faixas = $this->repository->findByDataVigencia($dataVigente);

        foreach ($faixas as $faixa) {
            $inicial = (float) $faixa->getValorInicial();
            $final = $faixa->getValorFinal() !== null ? (float) $faixa->getValorFinal() : null;

            if ($valorBruto >= $inicial && ($final === null || $valorBruto <= $final)) {
                $retencao = ($valorBruto * (float) $faixa->getAliquota() / 100) - (float) $faixa->getParcelaDeduzir();
                return round(max($retencao, 0.0), 2);
            }
        }

        return 0.0;
    }
}
