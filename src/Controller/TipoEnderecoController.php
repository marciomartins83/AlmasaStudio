<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposEnderecos;
use App\Form\TipoEnderecoType;
use App\Service\GenericTipoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TipoEnderecoController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private GenericTipoService $tipoService;

    public function __construct(EntityManagerInterface $entityManager, GenericTipoService $tipoService)
    {
        $this->entityManager = $entityManager;
        $this->tipoService = $tipoService;
    }

    #[Route('/tipo/endereco', name: 'app_tipo_endereco_index', methods: ['GET'])]
    public function index(Request $request, PaginationService $paginator): Response
    {
        $qb = $this->entityManager->getRepository(TiposEnderecos::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_endereco/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/tipo/endereco/new', name: 'app_tipo_endereco_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tipoEndereco = new TiposEnderecos();
        $form = $this->createForm(TipoEnderecoType::class, $tipoEndereco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tipoService->criar($tipoEndereco);
            return $this->redirectToRoute('app_tipo_endereco_index');
        }

        return $this->render('tipo_endereco/new.html.twig', [
            'tipoEndereco' => $tipoEndereco,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tipo/endereco/{id}', name: 'app_tipo_endereco_show', methods: ['GET'])]
    public function show(TiposEnderecos $tipoEndereco): Response
    {
        return $this->render('tipo_endereco/show.html.twig', [
            'tipo_endereco' => $tipoEndereco,
        ]);
    }

    #[Route('/tipo/endereco/{id}/edit', name: 'app_tipo_endereco_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposEnderecos $tipoEndereco): Response
    {
        $form = $this->createForm(TipoEnderecoType::class, $tipoEndereco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tipoService->atualizar($tipoEndereco);
            return $this->redirectToRoute('app_tipo_endereco_index');
        }

        return $this->render('tipo_endereco/edit.html.twig', [
            'tipo_endereco' => $tipoEndereco,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tipo/endereco/{id}', name: 'app_tipo_endereco_delete', methods: ['POST'])]
    public function delete(Request $request, TiposEnderecos $tipoEndereco): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tipoEndereco->getId(), $request->request->get('_token'))) {
            $this->tipoService->deletar($tipoEndereco);
        }

        return $this->redirectToRoute('app_tipo_endereco_index');
    }
}
