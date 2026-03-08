<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\AlmasaLancamento;
use App\Form\AlmasaLancamentoType;
use App\Repository\AlmasaLancamentoRepository;
use App\Service\AlmasaLancamentoService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/almasa-lancamentos', name: 'app_almasa_lancamentos_')]
class AlmasaLancamentoController extends AbstractController
{
    public function __construct(
        private AlmasaLancamentoService $service,
        private AlmasaLancamentoRepository $repository,
        private PaginationService $paginator
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $qb = $this->repository->createQueryBuilder('a')
            ->leftJoin('a.almasaPlanoConta', 'pc')
            ->orderBy('a.dataCompetencia', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'select', 'a.tipo', 'EXACT', [
                AlmasaLancamento::TIPO_RECEITA => 'Receita',
                AlmasaLancamento::TIPO_DESPESA => 'Despesa',
            ], null, 2),
            new SearchFilterDTO('status', 'Status', 'select', 'a.status', 'EXACT', [
                AlmasaLancamento::STATUS_ABERTO => 'Aberto',
                AlmasaLancamento::STATUS_PAGO => 'Pago',
                AlmasaLancamento::STATUS_CANCELADO => 'Cancelado',
            ], null, 2),
            new SearchFilterDTO('competenciaDe', 'Comp. De', 'date', 'a.dataCompetencia', 'GTE', [], null, 2),
            new SearchFilterDTO('competenciaAte', 'Comp. Até', 'date', 'a.dataCompetencia', 'LTE', [], null, 2),
        ];

        $sortOptions = [
            new SortOptionDTO('dataCompetencia', 'Competência', 'DESC'),
            new SortOptionDTO('valor', 'Valor', 'DESC'),
            new SortOptionDTO('status', 'Status', 'ASC'),
        ];

        $pagination = $this->paginator->paginate($qb, $request, null, [], 'a.id', $filters, $sortOptions, 'dataCompetencia', 'DESC');

        return $this->render('almasa_lancamentos/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $lancamento = new AlmasaLancamento();
        $form = $this->createForm(AlmasaLancamentoType::class, $lancamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->criar($lancamento);
                $this->addFlash('success', 'Lançamento Almasa criado com sucesso!');
                return $this->redirectToRoute('app_almasa_lancamentos_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_lancamentos/new.html.twig', [
            'lancamento' => $lancamento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AlmasaLancamento $lancamento): Response
    {
        return $this->render('almasa_lancamentos/show.html.twig', [
            'lancamento' => $lancamento,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AlmasaLancamento $lancamento): Response
    {
        $form = $this->createForm(AlmasaLancamentoType::class, $lancamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->atualizar($lancamento);
                $this->addFlash('success', 'Lançamento Almasa atualizado com sucesso!');
                return $this->redirectToRoute('app_almasa_lancamentos_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_lancamentos/edit.html.twig', [
            'lancamento' => $lancamento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, AlmasaLancamento $lancamento): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lancamento->getId(), $request->request->get('_token'))) {
            try {
                $this->service->deletar($lancamento);
                $this->addFlash('success', 'Lançamento Almasa excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_almasa_lancamentos_index');
    }
}
