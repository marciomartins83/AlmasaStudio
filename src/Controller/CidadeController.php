<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Form\CidadeType;
use App\Repository\EstadosRepository;
use App\Service\CidadeService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cidade', name: 'app_cidade_')]
class CidadeController extends AbstractController
{
    private CidadeService $cidadeService;

    public function __construct(CidadeService $cidadeService)
    {
        $this->cidadeService = $cidadeService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(Cidades::class)->createQueryBuilder('c');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'c.nome', 'LIKE', [], 'Nome da cidade...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['c.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('cidade/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $cidade = new Cidades();
        $form = $this->createForm(CidadeType::class, $cidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cidadeService->criar($cidade);
                $this->addFlash('success', 'Cidade criada com sucesso!');
                return $this->redirectToRoute('app_cidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar cidade: ' . $e->getMessage());
            }
        }

        return $this->render('cidade/new.html.twig', [
            'cidade' => $cidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cidades $cidade, EstadosRepository $estadosRepository): Response
    {
        $estado = $cidade->getEstado();
        $idEstado = $estado ? $estado->getId() : null;

        $estado = $estadosRepository->find($idEstado);

        return $this->render('cidade/show.html.twig', [
            'cidade' => $cidade,
            'estado' => $estado,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cidades $cidade): Response
    {
        $form = $this->createForm(CidadeType::class, $cidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cidadeService->atualizar();
                $this->addFlash('success', 'Cidade atualizada com sucesso!');
                return $this->redirectToRoute('app_cidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar cidade: ' . $e->getMessage());
            }
        }

        return $this->render('cidade/edit.html.twig', [
            'cidade' => $cidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cidades $cidade): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cidade->getId(), $request->request->get('_token'))) {
            try {
                $this->cidadeService->deletar($cidade);
                $this->addFlash('success', 'Cidade excluída com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir cidade: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_cidade_index');
    }
}
