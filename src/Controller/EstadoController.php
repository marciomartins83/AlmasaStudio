<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Estados;
use App\Form\EstadoType;
use App\Repository\EstadosRepository;
use App\Service\EstadoService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/estado', name: 'app_estado_')]
class EstadoController extends AbstractController
{
    private EstadoService $estadoService;

    public function __construct(EstadoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EstadosRepository $estadosRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $estadosRepository->createQueryBuilder('e');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'e.nome', 'LIKE', [], 'Nome...', 4),
            new SearchFilterDTO('uf', 'UF', 'text', 'e.uf', 'LIKE', [], 'UF...', 2),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('uf', 'UF'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['e.uf', 'e.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('estado/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $estado = new Estados();
        $form = $this->createForm(EstadoType::class, $estado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->estadoService->criar($estado);
                $this->addFlash('success', 'Estado criado com sucesso!');
                return $this->redirectToRoute('app_estado_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar estado: ' . $e->getMessage());
            }
        }

        return $this->render('estado/new.html.twig', [
            'estado' => $estado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Estados $estado): Response
    {
        return $this->render('estado/show.html.twig', [
            'estado' => $estado,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Estados $estado): Response
    {
        $form = $this->createForm(EstadoType::class, $estado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->estadoService->atualizar();
                $this->addFlash('success', 'Estado atualizado com sucesso!');
                return $this->redirectToRoute('app_estado_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar estado: ' . $e->getMessage());
            }
        }

        return $this->render('estado/edit.html.twig', [
            'estado' => $estado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Estados $estado): Response
    {
        if ($this->isCsrfTokenValid('delete'.$estado->getId(), $request->request->get('_token'))) {
            try {
                $this->estadoService->deletar($estado);
                $this->addFlash('success', 'Estado excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir estado: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_estado_index');
    }
} 