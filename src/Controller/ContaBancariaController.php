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
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/conta-bancaria', name: 'app_conta_bancaria_')]
class ContaBancariaController extends AbstractController
{
    use PaginationRedirectTrait;
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

        // Filtro titular: busca em p.nome, c.titular e c.descricao (OR)
        $titularBusca = trim($request->query->get('titular', ''));
        if ($titularBusca !== '') {
            $qb->andWhere('LOWER(p.nome) LIKE LOWER(:titular_q) OR LOWER(c.titular) LIKE LOWER(:titular_q) OR LOWER(c.descricao) LIKE LOWER(:titular_q)')
               ->setParameter('titular_q', '%' . $titularBusca . '%');
        }

        $filters = [
            new SearchFilterDTO('conta', 'Descrição / Banco', 'text', 'c.descricao', 'LIKE', [], 'Banco, número...', 3),
            new SearchFilterDTO('titular', 'Titular / Descrição', 'text', null, 'NONE', [], 'Nome, titular ou descrição...', 3),
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
                $pessoaId  = $form->get('idPessoa')->getData();
                $bancoId   = $form->get('idBanco')->getData();
                $agenciaId = $form->get('idAgencia')->getData();

                $this->contaBancariaService->resolverAutocompletes($contaBancaria, $pessoaId, $bancoId, $agenciaId);
                $this->contaBancariaService->criar($contaBancaria);
                $this->addFlash('success', 'Conta Bancária criada com sucesso!');
                return $this->redirectToIndex($request, 'app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta bancária: ' . $e->getMessage());
            }
        }

        // Preservar preloads dos campos unmapped (para não perder dados após erro)
        $preloads = [];
        if ($form->isSubmitted()) {
            $pessoaId  = $form->get('idPessoa')->getData();
            $bancoId   = $form->get('idBanco')->getData();
            $agenciaId = $form->get('idAgencia')->getData();

            if ($pessoaId) {
                $p = $this->contaBancariaService->buscarPessoa((int) $pessoaId);
                if ($p) { $preloads['pessoa'] = $p->getNome(); }
            }
            if ($bancoId) {
                $b = $this->contaBancariaService->buscarBanco((int) $bancoId);
                if ($b) { $preloads['banco'] = $b->getNome(); }
            }
            if ($agenciaId) {
                $a = $this->contaBancariaService->buscarAgencia((int) $agenciaId);
                if ($a) { $preloads['agencia'] = $a->getCodigo() . ' — ' . ($a->getNome() ?? ''); }
            }
        }

        return $this->render('conta_bancaria/new.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
            'preloads' => $preloads,
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
                // Só altera relação se o campo veio preenchido; vazio = mantém existente
                $pessoaId  = $form->get('idPessoa')->getData() ?: ($contaBancaria->getIdPessoa()?->getIdpessoa() ? (string) $contaBancaria->getIdPessoa()->getIdpessoa() : null);
                $bancoId   = $form->get('idBanco')->getData() ?: ($contaBancaria->getIdBanco()?->getId() ? (string) $contaBancaria->getIdBanco()->getId() : null);
                $agenciaId = $form->get('idAgencia')->getData() ?: ($contaBancaria->getIdAgencia()?->getId() ? (string) $contaBancaria->getIdAgencia()->getId() : null);

                $this->contaBancariaService->resolverAutocompletes($contaBancaria, $pessoaId, $bancoId, $agenciaId);
                $this->contaBancariaService->atualizar();
                $this->addFlash('success', 'Conta Bancária atualizada com sucesso!');
                return $this->redirectToIndex($request, 'app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar conta bancária: ' . $e->getMessage());
            }
        }

        // Preloads para autocomplete — do entity (GET) ou do form submitted (POST com erro)
        $preloads = [];
        if ($form->isSubmitted()) {
            $pessoaId  = $form->get('idPessoa')->getData();
            $bancoId   = $form->get('idBanco')->getData();
            $agenciaId = $form->get('idAgencia')->getData();

            if ($pessoaId) {
                $p = $this->contaBancariaService->buscarPessoa((int) $pessoaId);
                if ($p) { $preloads['pessoa'] = $p->getNome(); }
            }
            if ($bancoId) {
                $b = $this->contaBancariaService->buscarBanco((int) $bancoId);
                if ($b) { $preloads['banco'] = $b->getNome(); }
            }
            if ($agenciaId) {
                $a = $this->contaBancariaService->buscarAgencia((int) $agenciaId);
                if ($a) { $preloads['agencia'] = $a->getCodigo() . ' — ' . ($a->getNome() ?? ''); }
            }
        } else {
            // GET request — preload from existing entity
            $pessoa  = $contaBancaria->getIdPessoa();
            $banco   = $contaBancaria->getIdBanco();
            $agencia = $contaBancaria->getIdAgencia();

            if ($pessoa) { $preloads['pessoa'] = $pessoa->getNome(); }
            if ($banco)  { $preloads['banco']  = $banco->getNome(); }
            if ($agencia) { $preloads['agencia'] = $agencia->getCodigo() . ' — ' . ($agencia->getNome() ?? ''); }
        }

        return $this->render('conta_bancaria/edit.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
            'preloads' => $preloads,
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

        return $this->redirectToIndex($request, 'app_conta_bancaria_index');
    }
} 