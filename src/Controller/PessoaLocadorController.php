<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\PessoasLocadores;
use App\Form\PessoaLocadorType;
use App\Repository\PessoaLocadorRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa-locador', name: 'app_pessoa_locador_')]
class PessoaLocadorController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaLocadorRepository $pessoaLocadorRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $pessoaLocadorRepository->createQueryBuilder('l')
            ->join('l.pessoa', 'p')
            ->orderBy('l.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'p.nome', 'LIKE', [], 'Nome do locador...', 4),
            new SearchFilterDTO('situacao', 'Situação', 'select', 'l.situacao', 'EXACT', [
                '0' => 'Ativo',
                '1' => 'Inativo',
            ]),
        ];
        $sortOptions = [
            new SortOptionDTO('p.nome', 'Nome'),
            new SortOptionDTO('l.id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['p.nome'], null, $filters, $sortOptions, 'l.id', 'DESC');

        return $this->render('pessoa_locador/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pessoaLocador = new PessoasLocadores();
        $form = $this->createForm(PessoaLocadorType::class, $pessoaLocador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pessoaLocador);
            $entityManager->flush();

            $this->addFlash('success', 'Pessoa Locador criada com sucesso!');
            return $this->redirectToRoute('app_pessoa_locador_index');
        }

        return $this->render('pessoa_locador/new.html.twig', [
            'pessoa_locador' => $pessoaLocador,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PessoasLocadores $pessoaLocador): Response
    {
        return $this->render('pessoa_locador/show.html.twig', [
            'pessoa_locador' => $pessoaLocador,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PessoasLocadores $pessoaLocador, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaLocadorType::class, $pessoaLocador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa Locador atualizada com sucesso!');
            return $this->redirectToRoute('app_pessoa_locador_index');
        }

        return $this->render('pessoa_locador/edit.html.twig', [
            'pessoa_locador' => $pessoaLocador,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PessoasLocadores $pessoaLocador, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pessoaLocador->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pessoaLocador);
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa Locador excluída com sucesso!');
        }

        return $this->redirectToRoute('app_pessoa_locador_index');
    }
} 