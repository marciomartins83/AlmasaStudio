<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\EstadoCivil;
use App\Form\EstadoCivilType;
use App\Service\EstadoCivilService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/estado-civil', name: 'app_estado_civil_')]
class EstadoCivilController extends AbstractController
{
    private EstadoCivilService $estadoCivilService;

    public function __construct(EstadoCivilService $estadoCivilService)
    {
        $this->estadoCivilService = $estadoCivilService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(EstadoCivil::class)->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'e.nome', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['e.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('estado_civil/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $estadoCivil = new EstadoCivil();
        $form = $this->createForm(EstadoCivilType::class, $estadoCivil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->estadoCivilService->criar($estadoCivil);
                $this->addFlash('success', 'Estado Civil criado com sucesso!');
                return $this->redirectToRoute('app_estado_civil_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Estado Civil: ' . $e->getMessage());
            }
        }

        return $this->render('estado_civil/new.html.twig', [
            'estado_civil' => $estadoCivil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(EstadoCivil $estadoCivil): Response
    {
        return $this->render('estado_civil/show.html.twig', [
            'estado_civil' => $estadoCivil,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EstadoCivil $estadoCivil): Response
    {
        $form = $this->createForm(EstadoCivilType::class, $estadoCivil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->estadoCivilService->atualizar();
                $this->addFlash('success', 'Estado Civil atualizado com sucesso!');
                return $this->redirectToRoute('app_estado_civil_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar Estado Civil: ' . $e->getMessage());
            }
        }

        return $this->render('estado_civil/edit.html.twig', [
            'estado_civil' => $estadoCivil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, EstadoCivil $estadoCivil): Response
    {
        if ($this->isCsrfTokenValid('delete'.$estadoCivil->getId(), $request->request->get('_token'))) {
            try {
                $this->estadoCivilService->deletar($estadoCivil);
                $this->addFlash('success', 'Estado Civil excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir Estado Civil: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_estado_civil_index');
    }
}
