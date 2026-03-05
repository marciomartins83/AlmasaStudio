<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Form\BairroType;
use App\Repository\CidadeRepository;
use App\Service\BairroService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bairro', name: 'app_bairro_')]
class BairroController extends AbstractController
{
    private BairroService $bairroService;

    public function __construct(BairroService $bairroService)
    {
        $this->bairroService = $bairroService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(Bairros::class)->createQueryBuilder('b');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'b.nome', 'LIKE', [], 'Nome do bairro...', 4),
            new SearchFilterDTO('codigo', 'Código', 'text', 'b.codigo', 'LIKE', [], 'Código...', 3),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['b.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('bairro/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bairro = new Bairros();

        // Buscar todas as cidades para preencher o campo de seleção
        $cidades = $entityManager->getRepository(Cidades::class)->findAll();

        $form = $this->createForm(BairroType::class, $bairro, [
            'cidades' => $cidades
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bairroService->criar($bairro);
                $this->addFlash('success', 'Bairro criado com sucesso!');
                return $this->redirectToRoute('app_bairro_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar bairro: ' . $e->getMessage());
            }
        }

        return $this->render('bairro/new.html.twig', [
            'bairro' => $bairro,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bairros $bairro, CidadeRepository $cidadeRepository): Response
    {
        $cidade = $cidadeRepository->find($bairro->getIdCidade());

        return $this->render('bairro/show.html.twig', [
            'bairro' => $bairro,
            'cidade' => $cidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bairros $bairro, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BairroType::class, $bairro, [
            'cidades' => $entityManager->getRepository(Cidades::class)->findAll()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bairroService->atualizar();
                $this->addFlash('success', 'Bairro atualizado com sucesso!');
                return $this->redirectToRoute('app_bairro_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar bairro: ' . $e->getMessage());
            }
        }

        return $this->render('bairro/edit.html.twig', [
            'bairro' => $bairro,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bairros $bairro): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bairro->getId(), $request->request->get('_token'))) {
            try {
                $this->bairroService->deletar($bairro);
                $this->addFlash('success', 'Bairro excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir bairro: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_bairro_index');
    }
}
