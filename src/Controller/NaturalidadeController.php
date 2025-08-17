<?php

namespace App\Controller;

use App\Entity\Naturalidade;
use App\Form\NaturalidadeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/naturalidade', name: 'app_naturalidade_')]
class NaturalidadeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $naturalidades = $entityManager->getRepository(Naturalidade::class)->findAll();

        return $this->render('naturalidade/index.html.twig', [
            'naturalidades' => $naturalidades,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $naturalidade = new Naturalidade();
        $form = $this->createForm(NaturalidadeType::class, $naturalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($naturalidade);
                $entityManager->flush();
                $this->addFlash('success', 'Naturalidade criada com sucesso!');
                return $this->redirectToRoute('app_naturalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Naturalidade: ' . $e->getMessage());
            }
        }

        return $this->render('naturalidade/new.html.twig', [
            'naturalidade' => $naturalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Naturalidade $naturalidade): Response
    {
        return $this->render('naturalidade/show.html.twig', [
            'naturalidade' => $naturalidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Naturalidade $naturalidade, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NaturalidadeType::class, $naturalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Naturalidade atualizada com sucesso!');
                return $this->redirectToRoute('app_naturalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('naturalidade/edit.html.twig', [
            'naturalidade' => $naturalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Naturalidade $naturalidade, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$naturalidade->getId(), $request->request->get('_token'))) {
            $entityManager->remove($naturalidade);
            $entityManager->flush();
            $this->addFlash('success', 'Naturalidade excluída com sucesso!');
        }

        return $this->redirectToRoute('app_naturalidade_index');
    }
}
