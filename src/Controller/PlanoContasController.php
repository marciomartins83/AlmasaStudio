<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\PlanoContas;
use App\Form\PlanoContasType;
use App\Service\PlanoContasService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/plano-contas', name: 'app_plano_contas_')]
class PlanoContasController extends AbstractController
{
    private PlanoContasService $planoContasService;

    public function __construct(PlanoContasService $planoContasService)
    {
        $this->planoContasService = $planoContasService;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(PlanoContas::class)->createQueryBuilder('p')
            ->orderBy('p.codigo', 'ASC');

        $filters = [
            new SearchFilterDTO('codigo', 'Código', 'text', 'p.codigo', 'LIKE', [], 'Buscar...', 3),
            new SearchFilterDTO('descricao', 'Descrição', 'text', 'p.descricao', 'LIKE', [], 'Buscar...', 6),
            new SearchFilterDTO('tipo', 'Tipo', 'select', 'p.tipo', 'EXACT', [
                '' => 'Todos',
                '0' => 'Receita',
                '1' => 'Despesa',
                '2' => 'Transitória',
                '3' => 'Caixa',
            ], '', 3),
        ];

        $sortOptions = [
            new SortOptionDTO('codigo', 'Código', 'ASC'),
            new SortOptionDTO('descricao', 'Descrição'),
            new SortOptionDTO('tipo', 'Tipo'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['p.codigo', 'p.descricao'], null, $filters, $sortOptions, 'codigo', 'ASC');

        return $this->render('plano_contas/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $planoContas = new PlanoContas();
        $form = $this->createForm(PlanoContasType::class, $planoContas);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->planoContasService->criar($planoContas);
                $this->addFlash('success', 'Plano de Contas criado com sucesso!');
                return $this->redirectToRoute('app_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Plano de Contas: ' . $e->getMessage());
            }
        }

        return $this->render('plano_contas/new.html.twig', [
            'plano_contas' => $planoContas,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PlanoContas $planoContas): Response
    {
        return $this->render('plano_contas/show.html.twig', [
            'plano_contas' => $planoContas,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PlanoContas $planoContas): Response
    {
        $form = $this->createForm(PlanoContasType::class, $planoContas);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->planoContasService->atualizar($planoContas);
                $this->addFlash('success', 'Plano de Contas atualizado com sucesso!');
                return $this->redirectToRoute('app_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar Plano de Contas: ' . $e->getMessage());
            }
        }

        return $this->render('plano_contas/edit.html.twig', [
            'plano_contas' => $planoContas,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PlanoContas $planoContas): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planoContas->getId(), $request->request->get('_token'))) {
            try {
                $this->planoContasService->deletar($planoContas);
                $this->addFlash('success', 'Plano de Contas excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir Plano de Contas: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_plano_contas_index');
    }
}
