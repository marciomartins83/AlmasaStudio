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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/almasa-plano-contas', name: 'app_almasa_plano_contas_')]
class AlmasaPlanoContasController extends AbstractController
{
    use PaginationRedirectTrait;
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
            ->addSelect("CASE a.nivel WHEN 1 THEN '' WHEN 2 THEN SUBSTRING(a.codigo, 1, 1) WHEN 3 THEN SUBSTRING(a.codigo, 1, 3) WHEN 4 THEN SUBSTRING(a.codigo, 1, 6) ELSE SUBSTRING(a.codigo, 1, 10) END AS HIDDEN parentPath")
            ->orderBy("LENGTH(SPLIT_PART(a.codigo, '.', 1))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 1)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 2))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 2)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 3))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 3)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 4))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 4)", 'ASC');

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
            new SortOptionDTO('codigo', 'Codigo', 'ASC', [
                ["LENGTH(SPLIT_PART(a.codigo, '.', 1))", 'ASC'],
                ["SPLIT_PART(a.codigo, '.', 1)", 'ASC'],
                ["LENGTH(SPLIT_PART(a.codigo, '.', 2))", 'ASC'],
                ["SPLIT_PART(a.codigo, '.', 2)", 'ASC'],
                ["LENGTH(SPLIT_PART(a.codigo, '.', 3))", 'ASC'],
                ["SPLIT_PART(a.codigo, '.', 3)", 'ASC'],
                ["LENGTH(SPLIT_PART(a.codigo, '.', 4))", 'ASC'],
                ["SPLIT_PART(a.codigo, '.', 4)", '{DIR}'],
            ]),
            new SortOptionDTO('descricao', 'Descricao', 'ASC', [
                ['parentPath', 'ASC'],
                ['a.nivel', 'ASC'],
                ['a.descricao', '{DIR}'],
            ]),
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
                $paiId = $form->get('pai')->getData();
                if ($paiId) {
                    $pai = $this->repository->find((int) $paiId);
                    $conta->setPai($pai);
                }
                $this->service->criar($conta);
                $this->addFlash('success', 'Conta criada com sucesso!');
                return $this->redirectToRoute('app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta: ' . $e->getMessage());
            }
        }

        $preloads = [];
        if ($form->isSubmitted()) {
            $paiId = $form->get('pai')->getData();
            if ($paiId) {
                $pai = $this->repository->find((int) $paiId);
                if ($pai) {
                    $preloads['pai'] = $pai->getCodigo() . ' — ' . $pai->getDescricao();
                }
            }
        }

        return $this->render('almasa_plano_contas/new.html.twig', [
            'conta' => $conta,
            'form' => $form,
            'preloads' => $preloads,
        ]);
    }

    #[Route('/api/busca', name: 'api_busca', methods: ['GET'])]
    public function apiBusca(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        // Filtro por natureza contabil:
        //   debito  -> somente contas tipo despesa
        //   credito -> somente contas tipo receita
        $natureza = $request->query->get('natureza', '');
        $tiposPermitidos = match ($natureza) {
            'debito'  => ['despesa'],
            'credito' => ['receita'],
            default   => null,
        };

        $conn = $this->repository->createQueryBuilder('a')
            ->getEntityManager()
            ->getConnection();

        $where = "ativo = true AND aceita_lancamentos = true";
        $params = [];
        $types  = [];

        if ($q !== '') {
            $where .= " AND (unaccent(LOWER(descricao)) LIKE unaccent(LOWER(:q))
                          OR LOWER(codigo) LIKE LOWER(:q))";
            $params['q'] = '%' . $q . '%';
        }

        if ($tiposPermitidos !== null) {
            $where .= ' AND tipo IN (:tipos)';
            $params['tipos'] = $tiposPermitidos;
            $types['tipos']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        // Sem busca: retorna ate 200 (lupa "mostrar todas"); com busca: 20
        $limit = $q === '' ? 200 : 20;

        $rows = $conn->fetchAllAssociative(
            "SELECT id, codigo, descricao, tipo
             FROM almasa_plano_contas
             WHERE {$where}
             ORDER BY codigo ASC
             LIMIT {$limit}",
            $params,
            $types
        );

        return new JsonResponse($rows);
    }

    #[Route('/api/proximo-codigo/{paiId}', name: 'api_proximo_codigo', methods: ['GET'])]
    public function proximoCodigo(int $paiId): JsonResponse
    {
        $pai = $this->repository->find($paiId);
        if (!$pai) {
            return new JsonResponse(['error' => 'Nao encontrado'], 404);
        }

        $prefixo = $pai->getCodigo() . '.';
        $nivelFilho = $pai->getNivel() + 1;

        $padLen = match ($nivelFilho) {
            2 => 1,
            3 => 2,
            4 => 3,
            default => 3,
        };

        $filhos = $this->repository->createQueryBuilder('a')
            ->where('a.pai = :pai')
            ->setParameter('pai', $pai)
            ->orderBy('a.codigo', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($filhos)) {
            $nextNum = 1;
        } else {
            $lastCode = $filhos[0]->getCodigo();
            $suffix = substr($lastCode, strlen($prefixo));
            $nextNum = intval($suffix) + 1;
        }

        $sufixo = str_pad((string) $nextNum, $padLen, '0', STR_PAD_LEFT);

        return new JsonResponse([
            'prefixo' => $prefixo,
            'sufixo' => $sufixo,
            'codigo' => $prefixo . $sufixo,
            'padLen' => $padLen,
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

        // Pre-set pai hidden field with current value before handleRequest
        if (!$request->isMethod('POST') && $conta->getPai()) {
            $form->get('pai')->setData((string) $conta->getPai()->getId());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $paiId = $form->get('pai')->getData();
                if ($paiId) {
                    $pai = $this->repository->find((int) $paiId);
                    $conta->setPai($pai);
                } else {
                    $conta->setPai(null);
                }
                $this->service->atualizar($conta);
                $this->addFlash('success', 'Conta atualizada com sucesso!');
                return $this->redirectToIndex($request, 'app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar conta: ' . $e->getMessage());
            }
        }

        $preloads = [];
        if ($form->isSubmitted()) {
            $paiId = $form->get('pai')->getData();
            if ($paiId) {
                $pai = $this->repository->find((int) $paiId);
                if ($pai) {
                    $preloads['pai'] = $pai->getCodigo() . ' — ' . $pai->getDescricao();
                }
            }
        } elseif ($conta->getPai()) {
            $preloads['pai'] = $conta->getPai()->getCodigo() . ' — ' . $conta->getPai()->getDescricao();
        }

        return $this->render('almasa_plano_contas/edit.html.twig', [
            'conta' => $conta,
            'form' => $form,
            'preloads' => $preloads,
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

        return $this->redirectToIndex($request, 'app_almasa_plano_contas_index');
    }
}
