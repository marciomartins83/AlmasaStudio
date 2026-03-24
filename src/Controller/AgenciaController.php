<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Agencias;
use App\Form\AgenciaType;
use App\Repository\AgenciaRepository;
use App\Service\AgenciaService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/agencia')]
class AgenciaController extends AbstractController
{
    use PaginationRedirectTrait;
    private AgenciaService $agenciaService;

    public function __construct(AgenciaService $agenciaService)
    {
        $this->agenciaService = $agenciaService;
    }
    #[Route('/', name: 'app_agencia_index', methods: ['GET'])]
    public function index(AgenciaRepository $agenciaRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $agenciaRepository->createQueryBuilder('a')
            ->leftJoin('a.banco', 'bk')
            ->orderBy('a.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'a.nome', 'LIKE', [], 'Nome...', 3),
            new SearchFilterDTO('codigo', 'Código', 'text', 'a.codigo', 'LIKE', [], 'Código...', 2),
            new SearchFilterDTO('banco', 'Banco', 'text', 'bk.nome', 'LIKE', [], 'Banco...', 3),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('codigo', 'Código'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['a.codigo', 'a.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('agencia/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_agencia_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $agencia = new Agencias();
        $form = $this->createForm(AgenciaType::class, $agencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->agenciaService->criar($agencia);
                $this->addFlash('success', 'Agência criada com sucesso!');
                return $this->redirectToRoute('app_agencia_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar agência: ' . $e->getMessage());
            }
        }

        return $this->render('agencia/new.html.twig', [
            'agencia' => $agencia,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agencia_show', methods: ['GET'])]
    public function show(Agencias $agencia): Response
    {
        return $this->render('agencia/show.html.twig', [
            'agencia' => $agencia,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agencia_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Agencias $agencia): Response
    {
        $form = $this->createForm(AgenciaType::class, $agencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->agenciaService->atualizar();
                $this->addFlash('success', 'Agência atualizada com sucesso!');
                return $this->redirectToIndex($request, 'app_agencia_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar agência: ' . $e->getMessage());
            }
        }

        return $this->render('agencia/edit.html.twig', [
            'agencia' => $agencia,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agencia_delete', methods: ['POST'])]
    public function delete(Request $request, Agencias $agencia): Response
    {
        if ($this->isCsrfTokenValid('delete'.$agencia->getId(), $request->request->get('_token'))) {
            try {
                $this->agenciaService->deletar($agencia);
                $this->addFlash('success', 'Agência excluída com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir agência: ' . $e->getMessage());
            }
        }

        return $this->redirectToIndex($request, 'app_agencia_index');
    }
} 