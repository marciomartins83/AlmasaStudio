<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposCarteiras;
use App\Form\TipoCarteiraType;
use App\Service\GenericTipoService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-carteira', name: 'app_tipo_carteira_')]
class TipoCarteiraController extends AbstractController
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
        $qb = $entityManager->getRepository(TiposCarteiras::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_carteira/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoCarteira = new TiposCarteiras();
        $form = $this->createForm(TipoCarteiraType::class, $tipoCarteira);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tipoService->criar($tipoCarteira);
                $this->addFlash('success', 'Tipo de carteira criado com sucesso!');
                return $this->redirectToRoute('app_tipo_carteira_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de carteira: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_carteira/new.html.twig', [
            'tipo_carteira' => $tipoCarteira,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposCarteiras $tipoCarteira): Response
    {
        return $this->render('tipo_carteira/show.html.twig', [
            'tipo_carteira' => $tipoCarteira,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposCarteiras $tipoCarteira, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoCarteiraType::class, $tipoCarteira);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tipoService->atualizar();
                $this->addFlash('success', 'Tipo de carteira atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_carteira_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_carteira/edit.html.twig', [
            'tipo_carteira' => $tipoCarteira,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposCarteiras $tipoCarteira, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoCarteira->getId(), $request->request->get('_token'))) {
            $this->tipoService->deletar($tipoCarteira);
            $this->addFlash('success', 'Tipo de carteira excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_carteira_index');
    }
}
