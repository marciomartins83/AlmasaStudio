<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AlmasaPlanoContas;
use App\Service\AlmasaRelatorioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/relatorios/almasa')]
#[IsGranted('ROLE_USER')]
class AlmasaRelatorioController extends AbstractController
{
    public function __construct(
        private AlmasaRelatorioService $relatorioService,
        private EntityManagerInterface $em
    ) {
    }

    // =========================================================================
    // DESPESAS ALMASA
    // =========================================================================

    #[Route('/despesas', name: 'app_relatorios_almasa_despesas', methods: ['GET'])]
    public function despesas(): Response
    {
        return $this->render('relatorios/almasa_despesas.html.twig', [
            'plano_contas' => $this->getPlanoContasAlmasa('despesa'),
        ]);
    }

    #[Route('/despesas/preview', name: 'app_relatorios_almasa_despesas_preview', methods: ['POST'])]
    public function despesasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesas($filtros);
        $totais = $this->relatorioService->getTotalDespesas($filtros);

        $html = $this->renderView('relatorios/preview/almasa_despesas.html.twig', [
            'dados' => $dados,
            'totais' => $totais,
            'filtros' => $filtros,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'totais' => $totais,
        ]);
    }

    #[Route('/despesas/pdf', name: 'app_relatorios_almasa_despesas_pdf', methods: ['GET'])]
    public function despesasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesas($filtros);
        $totais = $this->relatorioService->getTotalDespesas($filtros);

        $pdf = $this->relatorioService->gerarPdf('almasa_despesas', [
            'dados' => $dados,
            'totais' => $totais,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="almasa_despesas.pdf"',
        ]);
    }

    // =========================================================================
    // RECEITAS ALMASA
    // =========================================================================

    #[Route('/receitas', name: 'app_relatorios_almasa_receitas', methods: ['GET'])]
    public function receitas(): Response
    {
        return $this->render('relatorios/almasa_receitas.html.twig', [
            'plano_contas' => $this->getPlanoContasAlmasa('receita'),
        ]);
    }

    #[Route('/receitas/preview', name: 'app_relatorios_almasa_receitas_preview', methods: ['POST'])]
    public function receitasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getReceitas($filtros);
        $totais = $this->relatorioService->getTotalReceitas($filtros);

        $html = $this->renderView('relatorios/preview/almasa_receitas.html.twig', [
            'dados' => $dados,
            'totais' => $totais,
            'filtros' => $filtros,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'totais' => $totais,
        ]);
    }

    #[Route('/receitas/pdf', name: 'app_relatorios_almasa_receitas_pdf', methods: ['GET'])]
    public function receitasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getReceitas($filtros);
        $totais = $this->relatorioService->getTotalReceitas($filtros);

        $pdf = $this->relatorioService->gerarPdf('almasa_receitas', [
            'dados' => $dados,
            'totais' => $totais,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="almasa_receitas.pdf"',
        ]);
    }

    // =========================================================================
    // DESPESAS x RECEITAS ALMASA (COMPARATIVO / DRE)
    // =========================================================================

    #[Route('/despesas-receitas', name: 'app_relatorios_almasa_despesas_receitas', methods: ['GET'])]
    public function despesasReceitas(): Response
    {
        return $this->render('relatorios/almasa_despesas_receitas.html.twig');
    }

    #[Route('/despesas-receitas/preview', name: 'app_relatorios_almasa_despesas_receitas_preview', methods: ['POST'])]
    public function despesasReceitasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesasReceitas($filtros);
        $saldo = $this->relatorioService->getSaldoPeriodo($filtros);
        $totaisReceitas = $this->relatorioService->getTotalReceitas($filtros);
        $totaisDespesas = $this->relatorioService->getTotalDespesas($filtros);

        $html = $this->renderView('relatorios/preview/almasa_despesas_receitas.html.twig', [
            'dados' => $dados,
            'totais_receitas' => $totaisReceitas,
            'totais_despesas' => $totaisDespesas,
            'saldo' => $saldo,
            'filtros' => $filtros,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'saldo' => $saldo,
        ]);
    }

    #[Route('/despesas-receitas/pdf', name: 'app_relatorios_almasa_despesas_receitas_pdf', methods: ['GET'])]
    public function despesasReceitasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesasReceitas($filtros);
        $saldo = $this->relatorioService->getSaldoPeriodo($filtros);
        $totaisReceitas = $this->relatorioService->getTotalReceitas($filtros);
        $totaisDespesas = $this->relatorioService->getTotalDespesas($filtros);

        $pdf = $this->relatorioService->gerarPdf('almasa_despesas_receitas', [
            'dados' => $dados,
            'totais_receitas' => $totaisReceitas,
            'totais_despesas' => $totaisDespesas,
            'saldo' => $saldo,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="almasa_despesas_receitas.pdf"',
        ]);
    }

    // =========================================================================
    // METODOS AUXILIARES
    // =========================================================================

    private function extrairFiltros(Request $request): array
    {
        $content = $request->getContent();
        return json_decode($content, true) ?? [];
    }

    private function extrairFiltrosGet(Request $request): array
    {
        return $request->query->all();
    }

    private function converterDatas(array &$filtros): void
    {
        if (!empty($filtros['data_inicio'])) {
            $filtros['data_inicio'] = new \DateTime($filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $filtros['data_fim'] = new \DateTime($filtros['data_fim']);
        }
    }

    private function getPlanoContasAlmasa(?string $tipo = null): array
    {
        $qb = $this->em->getRepository(AlmasaPlanoContas::class)
            ->createQueryBuilder('pc')
            ->where('pc.ativo = true')
            ->andWhere('pc.aceitaLancamentos = true')
            ->orderBy('pc.codigo', 'ASC');

        if ($tipo !== null) {
            $qb->andWhere('pc.tipo = :tipo')
                ->setParameter('tipo', $tipo);
        }

        return $qb->getQuery()->getResult();
    }
}
