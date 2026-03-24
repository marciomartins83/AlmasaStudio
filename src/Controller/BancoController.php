<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Bancos;
use App\Form\BancoType;
use App\Repository\BancosRepository;
use App\Service\BancoService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/banco', name: 'app_banco_')]
class BancoController extends AbstractController
{
    use PaginationRedirectTrait;
    private BancoService $bancoService;

    public function __construct(BancoService $bancoService)
    {
        $this->bancoService = $bancoService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BancosRepository $bancosRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $bancosRepository->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'b.nome', 'LIKE', [], 'Nome do banco...', 4),
            new SearchFilterDTO('numero', 'Número', 'text', 'b.numero', 'LIKE', [], 'Número...', 3),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('numero', 'Número'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['b.nome', 'b.numero'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('banco/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $banco = new Bancos();
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bancoService->criar($banco);
                $this->addFlash('success', 'Banco criado com sucesso!');
                return $this->redirectToRoute('app_banco_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar banco: ' . $e->getMessage());
            }
        }

        return $this->render('banco/new.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bancos $banco): Response
    {
        return $this->render('banco/show.html.twig', [
            'banco' => $banco,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bancos $banco): Response
    {
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bancoService->atualizar();
                $this->addFlash('success', 'Banco atualizado com sucesso!');
                return $this->redirectToIndex($request, 'app_banco_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar banco: ' . $e->getMessage());
            }
        }

        return $this->render('banco/edit.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bancos $banco): Response
    {
        if ($this->isCsrfTokenValid('delete' . $banco->getId(), $request->request->get('_token'))) {
            try {
                $this->bancoService->deletar($banco);
                $this->addFlash('success', 'Banco excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir banco: ' . $e->getMessage());
            }
        }

        return $this->redirectToIndex($request, 'app_banco_index');
    }
}
