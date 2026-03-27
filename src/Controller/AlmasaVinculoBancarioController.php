<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\AlmasaVinculoBancario;
use App\Form\AlmasaVinculoBancarioType;
use App\Repository\AlmasaPlanoContasRepository;
use App\Repository\AlmasaVinculoBancarioRepository;
use App\Repository\ContasBancariasRepository;
use App\Repository\PessoaRepository;
use App\Service\AlmasaVinculoBancarioService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/almasa-vinculo-bancario', name: 'app_almasa_vinculo_bancario_')]
class AlmasaVinculoBancarioController extends AbstractController
{
    public function __construct(
        private AlmasaVinculoBancarioService $service,
        private AlmasaVinculoBancarioRepository $repository,
        private PaginationService $paginator,
        private ContasBancariasRepository $contaBancariaRepo,
        private AlmasaPlanoContasRepository $planoContasRepo
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

    #[Route('/pessoa-autocomplete', name: 'pessoa_autocomplete', methods: ['GET'])]
    public function pessoaAutocomplete(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) {
            return $this->json([]);
        }

        $pessoas = $pessoaRepository->findByNome($q);
        $result = array_map(fn($p) => [
            'id'   => $p->getIdpessoa(),
            'nome' => $p->getNome(),
            'cod'  => $p->getCod(),
        ], array_slice($pessoas, 0, 20));

        return $this->json($result);
    }

    #[Route('/api/contas-por-pessoa/{pessoaId}', name: 'api_contas_por_pessoa', methods: ['GET'])]
    public function contasPorPessoa(int $pessoaId, EntityManagerInterface $em): JsonResponse
    {
        $contas = $em->createQueryBuilder()
            ->select('cb.id', 'cb.codigo', 'cb.digitoConta', 'b.nome AS bancoNome', 'ag.codigo AS agenciaCodigo')
            ->from('App\Entity\ContasBancarias', 'cb')
            ->leftJoin('cb.idBanco', 'b')
            ->leftJoin('cb.idAgencia', 'ag')
            ->where('cb.idPessoa = :pessoaId')
            ->andWhere('cb.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->orderBy('b.nome', 'ASC')
            ->addOrderBy('cb.codigo', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = array_map(function ($c) {
            $digito = $c['digitoConta'] ? '-' . $c['digitoConta'] : '';
            $agencia = $c['agenciaCodigo'] ?? '';
            return [
                'id'    => $c['id'],
                'label' => ($c['bancoNome'] ?? '') . ' Ag:' . $agencia . ' Cc:' . $c['codigo'] . $digito,
            ];
        }, $contas);

        return $this->json($result);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $vinculo = new AlmasaVinculoBancario();
        $form = $this->createForm(AlmasaVinculoBancarioType::class, $vinculo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Resolver autocomplete IDs
                $contaBancariaId = $form->get('contaBancaria')->getData();
                if ($contaBancariaId) {
                    $contaBancaria = $this->contaBancariaRepo->find((int) $contaBancariaId);
                    if ($contaBancaria) {
                        $vinculo->setContaBancaria($contaBancaria);
                    }
                }
                $planoContaId = $form->get('almasaPlanoConta')->getData();
                if ($planoContaId) {
                    $planoConta = $this->planoContasRepo->find((int) $planoContaId);
                    if ($planoConta) {
                        $vinculo->setAlmasaPlanoConta($planoConta);
                    }
                }

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
            'pessoaPreload' => null,
            'preloads' => [],
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

        // Pre-fill hidden autocomplete fields
        $form->get('contaBancaria')->setData($vinculo->getContaBancaria()?->getId());
        $form->get('almasaPlanoConta')->setData($vinculo->getAlmasaPlanoConta()?->getId());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Resolver autocomplete IDs
                $contaBancariaId = $form->get('contaBancaria')->getData();
                if ($contaBancariaId) {
                    $contaBancaria = $this->contaBancariaRepo->find((int) $contaBancariaId);
                    if ($contaBancaria) {
                        $vinculo->setContaBancaria($contaBancaria);
                    }
                }
                $planoContaId = $form->get('almasaPlanoConta')->getData();
                if ($planoContaId) {
                    $planoConta = $this->planoContasRepo->find((int) $planoContaId);
                    if ($planoConta) {
                        $vinculo->setAlmasaPlanoConta($planoConta);
                    }
                }

                $this->service->atualizar($vinculo);
                $this->addFlash('success', 'Vinculo bancario atualizado com sucesso!');
                return $this->redirectToRoute('app_almasa_vinculo_bancario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar vinculo: ' . $e->getMessage());
            }
        }

        $pessoa = $vinculo->getContaBancaria()?->getIdPessoa();
        $pessoaPreload = $pessoa ? ['id' => $pessoa->getIdpessoa(), 'nome' => $pessoa->getNome()] : null;

        $cb = $vinculo->getContaBancaria();
        $pc = $vinculo->getAlmasaPlanoConta();
        $preloads = [
            'contaBancaria' => $cb ? $cb->getDescricao() : '',
            'almasaPlanoConta' => $pc ? ($pc->getCodigo() . ' - ' . $pc->getDescricao()) : '',
        ];

        return $this->render('almasa_vinculo_bancario/edit.html.twig', [
            'vinculo' => $vinculo,
            'form' => $form,
            'pessoaPreload' => $pessoaPreload,
            'preloads' => $preloads,
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
