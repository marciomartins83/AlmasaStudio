<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposRemessa;
use App\Form\TipoRemessaType;
use App\Service\GenericTipoService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-remessa', name: 'app_tipo_remessa_')]
class TipoRemessaController extends AbstractController
{
    private GenericTipoService $genericTipoService;

    public function __construct(GenericTipoService $genericTipoService)
    {
        $this->genericTipoService = $genericTipoService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(TiposRemessa::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_remessa/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tipoRemessa = new TiposRemessa();
        $form = $this->createForm(TipoRemessaType::class, $tipoRemessa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->genericTipoService->criar($tipoRemessa);
                $this->addFlash('success', 'Tipo de remessa criado com sucesso!');
                return $this->redirectToRoute('app_tipo_remessa_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de remessa: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_remessa/new.html.twig', [
            'tipo_remessa' => $tipoRemessa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposRemessa $tipoRemessa): Response
    {
        return $this->render('tipo_remessa/show.html.twig', [
            'tipo_remessa' => $tipoRemessa,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposRemessa $tipoRemessa): Response
    {
        $form = $this->createForm(TipoRemessaType::class, $tipoRemessa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->genericTipoService->atualizar();
                $this->addFlash('success', 'Tipo de remessa atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_remessa_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar tipo de remessa: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_remessa/edit.html.twig', [
            'tipo_remessa' => $tipoRemessa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposRemessa $tipoRemessa): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoRemessa->getId(), $request->request->get('_token'))) {
            try {
                $this->genericTipoService->deletar($tipoRemessa);
                $this->addFlash('success', 'Tipo de remessa excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir tipo de remessa: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_tipo_remessa_index');
    }
}
