<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Telefones;
use App\Form\TelefoneType;
use App\Repository\TelefoneRepository;
use App\Service\PaginationService;
use App\Service\TelefoneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/telefone')]
class TelefoneController extends AbstractController
{
    use PaginationRedirectTrait;
    private TelefoneService $telefoneService;

    public function __construct(TelefoneService $telefoneService)
    {
        $this->telefoneService = $telefoneService;
    }
    #[Route('/', name: 'app_telefone_index', methods: ['GET'])]
    public function index(TelefoneRepository $telefoneRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $telefoneRepository->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('numero', 'Número', 'text', 't.numero', 'LIKE', [], 'Número...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('numero', 'Número'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.numero'], null, $filters, $sortOptions, 'numero', 'ASC');

        return $this->render('telefone/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_telefone_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $telefone = new Telefones();
        $form = $this->createForm(TelefoneType::class, $telefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->telefoneService->criar($telefone);
                $this->addFlash('success', 'Telefone criado com sucesso!');
                return $this->redirectToRoute('app_telefone_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar telefone: ' . $e->getMessage());
            }
        }

        return $this->render('telefone/new.html.twig', [
            'telefone' => $telefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_telefone_show', methods: ['GET'])]
    public function show(Telefones $telefone): Response
    {
        return $this->render('telefone/show.html.twig', [
            'telefone' => $telefone,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_telefone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Telefones $telefone): Response
    {
        $form = $this->createForm(TelefoneType::class, $telefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->telefoneService->atualizar();
                $this->addFlash('success', 'Telefone atualizado com sucesso!');
                return $this->redirectToIndex($request, 'app_telefone_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar telefone: ' . $e->getMessage());
            }
        }

        return $this->render('telefone/edit.html.twig', [
            'telefone' => $telefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_telefone_delete', methods: ['POST'])]
    public function delete(Request $request, Telefones $telefone): Response
    {
        if ($this->isCsrfTokenValid('delete'.$telefone->getId(), $request->request->get('_token'))) {
            try {
                $this->telefoneService->deletar($telefone);
                $this->addFlash('success', 'Telefone excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir telefone: ' . $e->getMessage());
            }
        }
        return $this->redirectToIndex($request, 'app_telefone_index');
    }
} 