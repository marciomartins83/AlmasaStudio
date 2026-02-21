<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposEnderecos;
use App\Form\TipoEnderecoType;
use App\Repository\TiposEnderecosRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TipoEnderecoController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TiposEnderecosRepository $tipoEnderecoRepository;

    public function __construct(EntityManagerInterface $entityManager, TiposEnderecosRepository $tipoEnderecoRepository)
    {
        $this->entityManager = $entityManager;
        $this->tipoEnderecoRepository = $tipoEnderecoRepository;
    }

    #[Route('/tipo/endereco', name: 'app_tipo_endereco_index', methods: ['GET'])]
    public function index(Request $request, PaginationService $paginator): Response
    {
        $qb = $this->tipoEnderecoRepository->createQueryBuilder('t')
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
            $this->entityManager->persist($tipoEndereco);
            $this->entityManager->flush();

            // Redirect to the index page or a show page after successful creation
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
            $this->entityManager->flush();

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
        // Consider adding CSRF protection here if not already handled by Symfony forms
        if ($this->isCsrfTokenValid('delete' . $tipoEndereco->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($tipoEndereco);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_tipo_endereco_index');
    }
}
