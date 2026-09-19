<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\IndiceEconomico;
use App\Form\IndiceEconomicoType;
use App\Service\IndiceEconomicoService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/indice-economico', name: 'app_indice_economico_')]
class IndiceEconomicoController extends AbstractController
{
    use PaginationRedirectTrait;
    private IndiceEconomicoService $indiceEconomicoService;

    public function __construct(IndiceEconomicoService $indiceEconomicoService)
    {
        $this->indiceEconomicoService = $indiceEconomicoService;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(IndiceEconomico::class)->createQueryBuilder('i')
            ->orderBy('i.competencia', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Índice', 'select', 'i.tipo', 'EXACT', [
                'IGPM' => 'IGPM',
                'Tribunal de Justiça' => 'TJ',
            ], 'Todos', 6),
            new SearchFilterDTO('competencia', 'Competência', 'text', 'i.competencia', 'LIKE', [], 'Ex: 2026-04', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('competencia', 'Competência', 'DESC'),
            new SortOptionDTO('tipo', 'Índice'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['i.tipo', 'i.competencia'], null, $filters, $sortOptions, 'competencia', 'DESC');

        return $this->render('indice_economico/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $indice = new IndiceEconomico();
        $form = $this->createForm(IndiceEconomicoType::class, $indice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->indiceEconomicoService->criar($indice);
                $this->addFlash('success', 'Índice econômico criado com sucesso!');
                return $this->redirectToRoute('app_indice_economico_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar índice econômico: ' . $e->getMessage());
            }
        }

        return $this->render('indice_economico/new.html.twig', [
            'indice_economico' => $indice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(IndiceEconomico $indiceEconomico): Response
    {
        return $this->render('indice_economico/show.html.twig', [
            'indice_economico' => $indiceEconomico,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, IndiceEconomico $indiceEconomico): Response
    {
        $form = $this->createForm(IndiceEconomicoType::class, $indiceEconomico);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->indiceEconomicoService->atualizar();
                $this->addFlash('success', 'Índice econômico atualizado com sucesso!');
                return $this->redirectToIndex($request, 'app_indice_economico_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar índice econômico: ' . $e->getMessage());
            }
        }

        return $this->render('indice_economico/edit.html.twig', [
            'indice_economico' => $indiceEconomico,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, IndiceEconomico $indiceEconomico): Response
    {
        if ($this->isCsrfTokenValid('delete'.$indiceEconomico->getId(), $request->request->get('_token'))) {
            try {
                $this->indiceEconomicoService->deletar($indiceEconomico);
                $this->addFlash('success', 'Índice econômico excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir índice econômico: ' . $e->getMessage());
            }
        }

        return $this->redirectToIndex($request, 'app_indice_economico_index');
    }
}
