<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContasBancarias;
use App\Entity\Imoveis;
use App\Entity\ImoveisContratos;
use App\Entity\Pessoas;
use App\Entity\PlanoContas;
use App\Service\RelatorioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * RelatorioController - Thin Controller para Relatórios PDF
 *
 * Delega toda lógica de negócio para RelatorioService
 */
#[Route('/relatorios')]
#[IsGranted('ROLE_USER')]
class RelatorioController extends AbstractController
{
    public function __construct(
        private RelatorioService $relatorioService,
        private EntityManagerInterface $em
    ) {
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    #[Route('/', name: 'app_relatorios_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('relatorios/index.html.twig');
    }

    // =========================================================================
    // INADIMPLENTES
    // =========================================================================

    #[Route('/inadimplentes', name: 'app_relatorios_inadimplentes', methods: ['GET'])]
    public function inadimplentes(): Response
    {
        return $this->render('relatorios/inadimplentes.html.twig', [
            'proprietarios' => $this->getProprietarios(),
            'imoveis' => $this->getImoveis(),
            'inquilinos' => $this->getInquilinos(),
        ]);
    }

    #[Route('/inadimplentes/preview', name: 'app_relatorios_inadimplentes_preview', methods: ['POST'])]
    public function inadimplentesPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $filtros['data_referencia'] = new \DateTime($filtros['data_referencia'] ?? 'now');

        $dados = $this->relatorioService->getInadimplentes($filtros);
        $totais = $this->relatorioService->getTotaisInadimplentes($dados);

        $html = $this->renderView('relatorios/preview/inadimplentes.html.twig', [
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

    #[Route('/inadimplentes/pdf', name: 'app_relatorios_inadimplentes_pdf', methods: ['GET'])]
    public function inadimplentePdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $filtros['data_referencia'] = new \DateTime($filtros['data_referencia'] ?? 'now');

        $dados = $this->relatorioService->getInadimplentes($filtros);
        $totais = $this->relatorioService->getTotaisInadimplentes($dados);

        $pdf = $this->relatorioService->gerarPdf('inadimplentes', [
            'dados' => $dados,
            'totais' => $totais,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="inadimplentes.pdf"',
        ]);
    }

    // =========================================================================
    // DESPESAS
    // =========================================================================

    #[Route('/despesas', name: 'app_relatorios_despesas', methods: ['GET'])]
    public function despesas(): Response
    {
        return $this->render('relatorios/despesas.html.twig', [
            'plano_contas' => $this->getPlanoContas(1), // Tipo despesa
            'fornecedores' => $this->getFornecedores(),
            'imoveis' => $this->getImoveis(),
            'contratos' => $this->getContratos(),
        ]);
    }

    #[Route('/despesas/preview', name: 'app_relatorios_despesas_preview', methods: ['POST'])]
    public function despesasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesas($filtros);
        $totais = $this->relatorioService->getTotalDespesas($filtros);

        $html = $this->renderView('relatorios/preview/despesas.html.twig', [
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

    #[Route('/despesas/pdf', name: 'app_relatorios_despesas_pdf', methods: ['GET'])]
    public function despesasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesas($filtros);
        $totais = $this->relatorioService->getTotalDespesas($filtros);

        $pdf = $this->relatorioService->gerarPdf('despesas', [
            'dados' => $dados,
            'totais' => $totais,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="despesas.pdf"',
        ]);
    }

    // =========================================================================
    // RECEITAS
    // =========================================================================

    #[Route('/receitas', name: 'app_relatorios_receitas', methods: ['GET'])]
    public function receitas(): Response
    {
        return $this->render('relatorios/receitas.html.twig', [
            'plano_contas' => $this->getPlanoContas(0), // Tipo receita
            'pagadores' => $this->getPagadores(),
            'imoveis' => $this->getImoveis(),
            'contratos' => $this->getContratos(),
        ]);
    }

    #[Route('/receitas/preview', name: 'app_relatorios_receitas_preview', methods: ['POST'])]
    public function receitasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getReceitas($filtros);
        $totais = $this->relatorioService->getTotalReceitas($filtros);

        $html = $this->renderView('relatorios/preview/receitas.html.twig', [
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

    #[Route('/receitas/pdf', name: 'app_relatorios_receitas_pdf', methods: ['GET'])]
    public function receitasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getReceitas($filtros);
        $totais = $this->relatorioService->getTotalReceitas($filtros);

        $pdf = $this->relatorioService->gerarPdf('receitas', [
            'dados' => $dados,
            'totais' => $totais,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receitas.pdf"',
        ]);
    }

    // =========================================================================
    // DESPESAS x RECEITAS (COMPARATIVO)
    // =========================================================================

    #[Route('/despesas-receitas', name: 'app_relatorios_despesas_receitas', methods: ['GET'])]
    public function despesasReceitas(): Response
    {
        return $this->render('relatorios/despesas_receitas.html.twig', [
            'imoveis' => $this->getImoveis(),
            'proprietarios' => $this->getProprietarios(),
        ]);
    }

    #[Route('/despesas-receitas/preview', name: 'app_relatorios_despesas_receitas_preview', methods: ['POST'])]
    public function despesasReceitasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesasReceitas($filtros);
        $saldo = $this->relatorioService->getSaldoPeriodo($filtros);
        $totaisReceitas = $this->relatorioService->getTotalReceitas($filtros);
        $totaisDespesas = $this->relatorioService->getTotalDespesas($filtros);

        $html = $this->renderView('relatorios/preview/despesas_receitas.html.twig', [
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

    #[Route('/despesas-receitas/pdf', name: 'app_relatorios_despesas_receitas_pdf', methods: ['GET'])]
    public function despesasReceitasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getDespesasReceitas($filtros);
        $saldo = $this->relatorioService->getSaldoPeriodo($filtros);
        $totaisReceitas = $this->relatorioService->getTotalReceitas($filtros);
        $totaisDespesas = $this->relatorioService->getTotalDespesas($filtros);

        $pdf = $this->relatorioService->gerarPdf('despesas_receitas', [
            'dados' => $dados,
            'totais_receitas' => $totaisReceitas,
            'totais_despesas' => $totaisDespesas,
            'saldo' => $saldo,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="despesas_receitas.pdf"',
        ]);
    }

    // =========================================================================
    // CONTAS BANCÁRIAS
    // =========================================================================

    #[Route('/contas-bancarias', name: 'app_relatorios_contas_bancarias', methods: ['GET'])]
    public function contasBancarias(): Response
    {
        return $this->render('relatorios/contas_bancarias.html.twig', [
            'contas' => $this->getContasBancarias(),
        ]);
    }

    #[Route('/contas-bancarias/preview', name: 'app_relatorios_contas_bancarias_preview', methods: ['POST'])]
    public function contasBancariasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getResumoContas($filtros);

        $html = $this->renderView('relatorios/preview/contas_bancarias.html.twig', [
            'dados' => $dados,
            'filtros' => $filtros,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
        ]);
    }

    #[Route('/contas-bancarias/pdf', name: 'app_relatorios_contas_bancarias_pdf', methods: ['GET'])]
    public function contasBancariasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getResumoContas($filtros);

        $pdf = $this->relatorioService->gerarPdf('contas_bancarias', [
            'dados' => $dados,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contas_bancarias.pdf"',
        ]);
    }

    // =========================================================================
    // PLANO DE CONTAS
    // =========================================================================

    #[Route('/plano-contas', name: 'app_relatorios_plano_contas', methods: ['GET'])]
    public function planoContas(): Response
    {
        return $this->render('relatorios/plano_contas.html.twig');
    }

    #[Route('/plano-contas/preview', name: 'app_relatorios_plano_contas_preview', methods: ['POST'])]
    public function planoContasPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $filtros = $this->extrairFiltros($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getPlanoContas($filtros);

        $html = $this->renderView('relatorios/preview/plano_contas.html.twig', [
            'dados' => $dados,
            'filtros' => $filtros,
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $html,
        ]);
    }

    #[Route('/plano-contas/pdf', name: 'app_relatorios_plano_contas_pdf', methods: ['GET'])]
    public function planoContasPdf(Request $request): Response
    {
        $filtros = $this->extrairFiltrosGet($request);
        $this->converterDatas($filtros);

        $dados = $this->relatorioService->getPlanoContas($filtros);

        $pdf = $this->relatorioService->gerarPdf('plano_contas', [
            'dados' => $dados,
        ], $filtros);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="plano_contas.pdf"',
        ]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
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

    private function getProprietarios(): array
    {
        return $this->em->getRepository(Pessoas::class)
            ->createQueryBuilder('p')
            ->innerJoin('App\Entity\PessoasLocadores', 'pl', 'WITH', 'pl.pessoa = p')
            ->orderBy('p.nome', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getInquilinos(): array
    {
        return $this->em->getRepository(Pessoas::class)
            ->createQueryBuilder('p')
            ->innerJoin('App\Entity\PessoasContratantes', 'pc', 'WITH', 'pc.pessoa = p')
            ->orderBy('p.nome', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getFornecedores(): array
    {
        return $this->em->getRepository(Pessoas::class)
            ->createQueryBuilder('p')
            ->orderBy('p.nome', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    private function getPagadores(): array
    {
        return $this->getFornecedores();
    }

    private function getImoveis(): array
    {
        return $this->em->getRepository(Imoveis::class)
            ->findBy([], ['id' => 'ASC']);
    }

    private function getContratos(): array
    {
        return $this->em->getRepository(ImoveisContratos::class)
            ->findBy(['ativo' => true], ['id' => 'DESC']);
    }

    private function getPlanoContas(?int $tipo = null): array
    {
        $qb = $this->em->getRepository(PlanoContas::class)
            ->createQueryBuilder('pc')
            ->where('pc.ativo = true')
            ->orderBy('pc.codigo', 'ASC');

        if ($tipo !== null) {
            $qb->andWhere('pc.tipo = :tipo')
                ->setParameter('tipo', $tipo);
        }

        return $qb->getQuery()->getResult();
    }

    private function getContasBancarias(): array
    {
        return $this->em->getRepository(ContasBancarias::class)
            ->findBy(['ativo' => true], ['codigo' => 'ASC']);
    }
}
