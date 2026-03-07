<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\ContasBancarias;
use App\Form\ContaBancariaType;
use App\Repository\ContasBancariasRepository;
use App\Service\ContaBancariaService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/conta-bancaria', name: 'app_conta_bancaria_')]
class ContaBancariaController extends AbstractController
{
    private ContaBancariaService $contaBancariaService;

    public function __construct(ContaBancariaService $contaBancariaService)
    {
        $this->contaBancariaService = $contaBancariaService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(ContasBancarias::class)->createQueryBuilder('c')
            ->leftJoin('c.idPessoa', 'p')
            ->addSelect('p')
            ->orderBy('c.id', 'DESC');

        $filters = [
            new SearchFilterDTO('conta', 'Descrição / Banco', 'text', 'c.descricao', 'LIKE', [], 'Banco, número...', 3),
            new SearchFilterDTO('titular', 'Titular (proprietário)', 'text', 'p.nome', 'LIKE', [], 'Nome do proprietário...', 3),
            new SearchFilterDTO('tipo', 'Tipo', 'select', 'c.idPessoa', 'NULL_CHECK', [
                '' => 'Todos',
                'null' => 'Almasa (próprias)',
                'not_null' => 'Proprietários',
            ], null, 3),
        ];
        $sortOptions = [
            new SortOptionDTO('codigo', 'Conta'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['c.descricao', 'c.codigo', 'p.nome'], null, $filters, $sortOptions, 'codigo', 'ASC');

        return $this->render('conta_bancaria/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $contaBancaria = new ContasBancarias();
        $form = $this->createForm(ContaBancariaType::class, $contaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->contaBancariaService->criar($contaBancaria);
                $this->addFlash('success', 'Conta Bancária criada com sucesso!');
                return $this->redirectToRoute('app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta bancária: ' . $e->getMessage());
            }
        }

        return $this->render('conta_bancaria/new.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ContasBancarias $contaBancaria): Response
    {
        return $this->render('conta_bancaria/show.html.twig', [
            'conta_bancaria' => $contaBancaria,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContasBancarias $contaBancaria): Response
    {
        $form = $this->createForm(ContaBancariaType::class, $contaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->contaBancariaService->atualizar();
                $this->addFlash('success', 'Conta Bancária atualizada com sucesso!');
                return $this->redirectToRoute('app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar conta bancária: ' . $e->getMessage());
            }
        }

        return $this->render('conta_bancaria/edit.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, ContasBancarias $contaBancaria): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contaBancaria->getId(), $request->request->get('_token'))) {
            try {
                $this->contaBancariaService->deletar($contaBancaria);
                $this->addFlash('success', 'Conta Bancária excluída com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir conta bancária: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_conta_bancaria_index');
    }
} 