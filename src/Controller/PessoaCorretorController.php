<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\PessoasCorretores;
use App\Form\PessoaCorretorType;
use App\Repository\PessoaCorretorRepository;
use App\Repository\PessoaRepository;
use App\Service\PaginationService;
use App\Service\PessoaCorretorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/pessoa-corretor', name: 'app_pessoa_corretor_')]
class PessoaCorretorController extends AbstractController
{
    use PaginationRedirectTrait;
    private PessoaCorretorService $pessoaCorretorService;
    private PessoaRepository $pessoaRepository;

    public function __construct(PessoaCorretorService $pessoaCorretorService, PessoaRepository $pessoaRepository)
    {
        $this->pessoaCorretorService = $pessoaCorretorService;
        $this->pessoaRepository = $pessoaRepository;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaCorretorRepository $pessoaCorretorRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $pessoaCorretorRepository->createQueryBuilder('c')
            ->join('c.pessoa', 'p')
            ->orderBy('c.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'p.nome', 'LIKE', [], 'Nome do corretor...', 4),
            new SearchFilterDTO('ativo', 'Status', 'select', 'c.ativo', 'BOOL', [
                '1' => 'Ativo',
                '0' => 'Inativo',
            ]),
        ];
        $sortOptions = [
            new SortOptionDTO('p.nome', 'Nome'),
            new SortOptionDTO('c.id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['p.nome'], null, $filters, $sortOptions, 'c.id', 'DESC');

        return $this->render('pessoa_corretor/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pessoaCorretor = new PessoasCorretores();
        $form = $this->createForm(PessoaCorretorType::class, $pessoaCorretor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Resolver pessoa autocomplete
            $pessoaId = $form->get('pessoa')->getData();
            if ($pessoaId) {
                $pessoa = $this->pessoaRepository->find((int) $pessoaId);
                if ($pessoa) {
                    $pessoaCorretor->setPessoa($pessoa);
                }
            }
            $this->pessoaCorretorService->criar($pessoaCorretor);

            $this->addFlash('success', 'Pessoa Corretor criada com sucesso!');
            return $this->redirectToRoute('app_pessoa_corretor_index');
        }

        return $this->render('pessoa_corretor/new.html.twig', [
            'pessoa_corretor' => $pessoaCorretor,
            'form' => $form,
            'preloads' => [],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PessoasCorretores $pessoaCorretor): Response
    {
        return $this->render('pessoa_corretor/show.html.twig', [
            'pessoa_corretor' => $pessoaCorretor,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PessoasCorretores $pessoaCorretor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaCorretorType::class, $pessoaCorretor);

        // Pre-fill hidden pessoa field
        $form->get('pessoa')->setData($pessoaCorretor->getPessoa()?->getIdpessoa());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Resolver pessoa autocomplete
            $pessoaId = $form->get('pessoa')->getData();
            if ($pessoaId) {
                $pessoa = $this->pessoaRepository->find((int) $pessoaId);
                if ($pessoa) {
                    $pessoaCorretor->setPessoa($pessoa);
                }
            }
            $this->pessoaCorretorService->atualizar();
            $this->addFlash('success', 'Pessoa Corretor atualizada com sucesso!');
            return $this->redirectToIndex($request, 'app_pessoa_corretor_index');
        }

        $preloads = [
            'pessoa' => $pessoaCorretor->getPessoa()?->getNome(),
        ];

        return $this->render('pessoa_corretor/edit.html.twig', [
            'pessoa_corretor' => $pessoaCorretor,
            'form' => $form,
            'preloads' => $preloads,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PessoasCorretores $pessoaCorretor): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pessoaCorretor->getId(), $request->request->get('_token'))) {
            $this->pessoaCorretorService->deletar($pessoaCorretor);
            $this->addFlash('success', 'Pessoa Corretor excluída com sucesso!');
        }

        return $this->redirectToIndex($request, 'app_pessoa_corretor_index');
    }
} 