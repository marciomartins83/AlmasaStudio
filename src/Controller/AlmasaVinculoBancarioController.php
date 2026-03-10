<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\AlmasaVinculoBancario;
use App\Form\AlmasaVinculoBancarioType;
use App\Repository\AlmasaVinculoBancarioRepository;
use App\Service\AlmasaVinculoBancarioService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/almasa-vinculo-bancario', name: 'app_almasa_vinculo_bancario_')]
class AlmasaVinculoBancarioController extends AbstractController
{
    public function __construct(
        private AlmasaVinculoBancarioService $service,
        private AlmasaVinculoBancarioRepository $repository,
        private PaginationService $paginator
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $qb = $this->repository->createQueryBuilder('v')
            ->leftJoin('v.contaBancaria', 'cb')
            ->leftJoin('cb.idPessoa', 'p')
            ->leftJoin('cb.idBanco', 'b')
            ->leftJoin('cb.idAgencia', 'ag')
            ->leftJoin('v.almasaPlanoConta', 'pc')
            ->addSelect('cb', 'p', 'b', 'ag', 'pc');

        $filters = [
            new SearchFilterDTO('pessoa', 'Pessoa/Titular', 'text', 'p.nome', 'LIKE', [], null, 3),
            new SearchFilterDTO('banco', 'Banco', 'text', 'b.nome', 'LIKE', [], null, 2),
            new SearchFilterDTO('contaCodigo', 'Nro Conta', 'text', 'cb.codigo', 'LIKE', [], null, 2),
            new SearchFilterDTO('planoConta', 'Plano de Contas', 'text', 'pc.descricao', 'LIKE', [], null, 3),
            new SearchFilterDTO('ativo', 'Ativo', 'select', 'v.ativo', 'BOOL', [
                '1' => 'Sim',
                '0' => 'Nao',
            ], null, 2),
        ];

        $sortOptions = [
            new SortOptionDTO('v.id', 'ID', 'ASC'),
            new SortOptionDTO('p.nome', 'Pessoa', 'ASC'),
            new SortOptionDTO('b.nome', 'Banco', 'ASC'),
            new SortOptionDTO('cb.codigo', 'Conta', 'ASC'),
            new SortOptionDTO('pc.codigo', 'Plano de Contas', 'ASC'),
            new SortOptionDTO('v.ativo', 'Ativo', 'DESC'),
            new SortOptionDTO('v.createdAt', 'Data Criacao', 'DESC'),
        ];

        $pagination = $this->paginator->paginate(
            $qb,
            $request,
            null,
            [],
            'v.id',
            $filters,
            $sortOptions,
            'p.nome',
            'ASC'
        );

        return $this->render('almasa_vinculo_bancario/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $vinculo = new AlmasaVinculoBancario();
        $form = $this->createForm(AlmasaVinculoBancarioType::class, $vinculo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->criar($vinculo);
                $this->addFlash('success', 'Vinculo bancario criado com sucesso!');
                return $this->redirectToRoute('app_almasa_vinculo_bancario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar vinculo: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_vinculo_bancario/new.html.twig', [
            'vinculo' => $vinculo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AlmasaVinculoBancario $vinculo): Response
    {
        return $this->render('almasa_vinculo_bancario/show.html.twig', [
            'vinculo' => $vinculo,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AlmasaVinculoBancario $vinculo): Response
    {
        $form = $this->createForm(AlmasaVinculoBancarioType::class, $vinculo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->atualizar($vinculo);
                $this->addFlash('success', 'Vinculo bancario atualizado com sucesso!');
                return $this->redirectToRoute('app_almasa_vinculo_bancario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar vinculo: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_vinculo_bancario/edit.html.twig', [
            'vinculo' => $vinculo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, AlmasaVinculoBancario $vinculo): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vinculo->getId(), $request->request->get('_token'))) {
            try {
                $this->service->deletar($vinculo);
                $this->addFlash('success', 'Vinculo bancario excluido com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_almasa_vinculo_bancario_index');
    }
}
