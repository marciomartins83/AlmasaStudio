<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\FaixaImpostoRenda;
use App\Form\FaixaImpostoRendaType;
use App\Service\TabelaImpostoRendaService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/tabela-imposto-renda', name: 'app_tabela_imposto_renda_')]
class FaixaImpostoRendaController extends AbstractController
{
    use PaginationRedirectTrait;
    private TabelaImpostoRendaService $tabelaImpostoRendaService;

    public function __construct(TabelaImpostoRendaService $tabelaImpostoRendaService)
    {
        $this->tabelaImpostoRendaService = $tabelaImpostoRendaService;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(FaixaImpostoRenda::class)->createQueryBuilder('f')
            ->orderBy('f.dataVigencia', 'DESC')
            ->addOrderBy('f.valorInicial', 'ASC');

        $filters = [
            new SearchFilterDTO('ativo', 'Status', 'boolean', 'f.ativo', 'BOOL', [], 'Todos', 4),
        ];
        $sortOptions = [
            new SortOptionDTO('dataVigencia', 'Data de Vigência', 'DESC'),
            new SortOptionDTO('valorInicial', 'Valor Inicial'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, [], null, $filters, $sortOptions, 'dataVigencia', 'DESC');

        return $this->render('tabela_imposto_renda/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $faixa = new FaixaImpostoRenda();
        $form = $this->createForm(FaixaImpostoRendaType::class, $faixa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tabelaImpostoRendaService->criar($faixa);
                $this->addFlash('success', 'Faixa de Imposto de Renda criada com sucesso!');
                return $this->redirectToRoute('app_tabela_imposto_renda_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar faixa de IR: ' . $e->getMessage());
            }
        }

        return $this->render('tabela_imposto_renda/new.html.twig', [
            'faixa_imposto_renda' => $faixa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(FaixaImpostoRenda $faixaImpostoRenda): Response
    {
        return $this->render('tabela_imposto_renda/show.html.twig', [
            'faixa_imposto_renda' => $faixaImpostoRenda,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FaixaImpostoRenda $faixaImpostoRenda): Response
    {
        $form = $this->createForm(FaixaImpostoRendaType::class, $faixaImpostoRenda);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tabelaImpostoRendaService->atualizar();
                $this->addFlash('success', 'Faixa de Imposto de Renda atualizada com sucesso!');
                return $this->redirectToIndex($request, 'app_tabela_imposto_renda_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar faixa de IR: ' . $e->getMessage());
            }
        }

        return $this->render('tabela_imposto_renda/edit.html.twig', [
            'faixa_imposto_renda' => $faixaImpostoRenda,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, FaixaImpostoRenda $faixaImpostoRenda): Response
    {
        if ($this->isCsrfTokenValid('delete'.$faixaImpostoRenda->getId(), $request->request->get('_token'))) {
            try {
                $this->tabelaImpostoRendaService->deletar($faixaImpostoRenda);
                $this->addFlash('success', 'Faixa de Imposto de Renda excluída com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir faixa de IR: ' . $e->getMessage());
            }
        }

        return $this->redirectToIndex($request, 'app_tabela_imposto_renda_index');
    }
}
