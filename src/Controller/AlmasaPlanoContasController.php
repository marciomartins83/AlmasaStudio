<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\AlmasaPlanoContas;
use App\Form\AlmasaPlanoContasType;
use App\Repository\AlmasaPlanoContasRepository;
use App\Service\AlmasaPlanoContasService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/almasa-plano-contas', name: 'app_almasa_plano_contas_')]
class AlmasaPlanoContasController extends AbstractController
{
    public function __construct(
        private AlmasaPlanoContasService $service,
        private AlmasaPlanoContasRepository $repository,
        private PaginationService $paginator
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $qb = $this->repository->createQueryBuilder('a')
            ->leftJoin('a.pai', 'pai')
            ->orderBy('a.codigo', 'ASC');

        $filters = [
            new SearchFilterDTO('codigo', 'Codigo', 'text', 'a.codigo', 'LIKE', [], null, 2),
            new SearchFilterDTO('descricao', 'Descricao', 'text', 'a.descricao', 'LIKE', [], null, 3),
            new SearchFilterDTO('tipo', 'Tipo', 'select', 'a.tipo', 'EXACT', [
                AlmasaPlanoContas::TIPO_ATIVO => 'Ativo',
                AlmasaPlanoContas::TIPO_PASSIVO => 'Passivo',
                AlmasaPlanoContas::TIPO_PATRIMONIO_LIQUIDO => 'Patrimônio Líquido',
                AlmasaPlanoContas::TIPO_RECEITA => 'Receita',
                AlmasaPlanoContas::TIPO_DESPESA => 'Despesa',
            ], null, 2),
            new SearchFilterDTO('nivel', 'Nivel', 'select', 'a.nivel', 'EXACT', [
                (string) AlmasaPlanoContas::NIVEL_CLASSE => 'Classe',
                (string) AlmasaPlanoContas::NIVEL_GRUPO => 'Grupo',
                (string) AlmasaPlanoContas::NIVEL_SUBGRUPO => 'Subgrupo',
                (string) AlmasaPlanoContas::NIVEL_CONTA => 'Conta',
                (string) AlmasaPlanoContas::NIVEL_SUBCONTA => 'Subconta',
            ], null, 2),
            new SearchFilterDTO('aceitaLancamentos', 'Aceita Lanc.', 'select', 'a.aceitaLancamentos', 'BOOL', [
                '1' => 'Sim',
                '0' => 'Nao',
            ], null, 2),
            new SearchFilterDTO('ativo', 'Ativo', 'select', 'a.ativo', 'BOOL', [
                '1' => 'Sim',
                '0' => 'Nao',
            ], null, 1),
        ];

        $sortOptions = [
            new SortOptionDTO('id', 'ID', 'ASC'),
            new SortOptionDTO('codigo', 'Codigo', 'ASC'),
            new SortOptionDTO('descricao', 'Descricao', 'ASC'),
            new SortOptionDTO('tipo', 'Tipo', 'ASC'),
            new SortOptionDTO('nivel', 'Nivel', 'ASC'),
            new SortOptionDTO('aceitaLancamentos', 'Aceita Lanc.', 'DESC'),
            new SortOptionDTO('ativo', 'Ativo', 'DESC'),
        ];

        $pagination = $this->paginator->paginate(
            $qb,
            $request,
            null,
            [],
            'a.id',
            $filters,
            $sortOptions,
            'codigo',
            'ASC'
        );

        return $this->render('almasa_plano_contas/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $conta = new AlmasaPlanoContas();
        $form = $this->createForm(AlmasaPlanoContasType::class, $conta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->criar($conta);
                $this->addFlash('success', 'Conta criada com sucesso!');
                return $this->redirectToRoute('app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_plano_contas/new.html.twig', [
            'conta' => $conta,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AlmasaPlanoContas $conta): Response
    {
        return $this->render('almasa_plano_contas/show.html.twig', [
            'conta' => $conta,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AlmasaPlanoContas $conta): Response
    {
        $form = $this->createForm(AlmasaPlanoContasType::class, $conta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->atualizar($conta);
                $this->addFlash('success', 'Conta atualizada com sucesso!');
                return $this->redirectToRoute('app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar conta: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_plano_contas/edit.html.twig', [
            'conta' => $conta,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, AlmasaPlanoContas $conta): Response
    {
        if ($this->isCsrfTokenValid('delete' . $conta->getId(), $request->request->get('_token'))) {
            try {
                $this->service->deletar($conta);
                $this->addFlash('success', 'Conta excluida com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_almasa_plano_contas_index');
    }
}
