<?php

namespace App\Controller;

use App\Entity\PessoasCorretores;
use App\Form\PessoaCorretorType;
use App\Repository\PessoaCorretorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa-corretor', name: 'app_pessoa_corretor_')]
class PessoaCorretorController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaCorretorRepository $pessoaCorretorRepository): Response
    {
        return $this->render('pessoa_corretor/index.html.twig', [
            'pessoa_corretores' => $pessoaCorretorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pessoaCorretor = new PessoasCorretores();
        $form = $this->createForm(PessoaCorretorType::class, $pessoaCorretor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pessoaCorretor);
            $entityManager->flush();

            $this->addFlash('success', 'Pessoa Corretor criada com sucesso!');
            return $this->redirectToRoute('app_pessoa_corretor_index');
        }

        return $this->render('pessoa_corretor/new.html.twig', [
            'pessoa_corretor' => $pessoaCorretor,
            'form' => $form,
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa Corretor atualizada com sucesso!');
            return $this->redirectToRoute('app_pessoa_corretor_index');
        }

        return $this->render('pessoa_corretor/edit.html.twig', [
            'pessoa_corretor' => $pessoaCorretor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PessoasCorretores $pessoaCorretor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pessoaCorretor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pessoaCorretor);
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa Corretor excluída com sucesso!');
        }

        return $this->redirectToRoute('app_pessoa_corretor_index');
    }
} 