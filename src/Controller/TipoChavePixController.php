<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposChavesPix;
use App\Form\TipoChavePixType;
use App\Service\GenericTipoService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-chave-pix', name: 'app_tipo_chave_pix_')]
class TipoChavePixController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private GenericTipoService $tipoService;

    public function __construct(EntityManagerInterface $entityManager, GenericTipoService $tipoService)
    {
        $this->entityManager = $entityManager;
        $this->tipoService = $tipoService;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(TiposChavesPix::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_chave_pix/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoChavePix = new TiposChavesPix();
        $form = $this->createForm(TipoChavePixType::class, $tipoChavePix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tipoService->criar($tipoChavePix);
                $this->addFlash('success', 'Tipo de chave PIX criado com sucesso!');
                return $this->redirectToRoute('app_tipo_chave_pix_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de chave PIX: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_chave_pix/new.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposChavesPix $tipoChavePix): Response
    {
        return $this->render('tipo_chave_pix/show.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposChavesPix $tipoChavePix, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoChavePixType::class, $tipoChavePix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tipoService->atualizar();
                $this->addFlash('success', 'Tipo de chave PIX atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_chave_pix_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_chave_pix/edit.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposChavesPix $tipoChavePix, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoChavePix->getId(), $request->request->get('_token'))) {
            $this->tipoService->deletar($tipoChavePix);
            $this->addFlash('success', 'Tipo de chave PIX excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_chave_pix_index');
    }
}