<?php

namespace App\Controller;

use App\Entity\Nacionalidade;
use App\Form\NacionalidadeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nacionalidade', name: 'app_nacionalidade_')]
class NacionalidadeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $nacionalidades = $entityManager->getRepository(Nacionalidade::class)->findAll();

        return $this->render('nacionalidade/index.html.twig', [
            'nacionalidades' => $nacionalidades,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $nacionalidade = new Nacionalidade();
        $form = $this->createForm(NacionalidadeType::class, $nacionalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($nacionalidade);
                $entityManager->flush();
                $this->addFlash('success', 'Nacionalidade criada com sucesso!');
                return $this->redirectToRoute('app_nacionalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Nacionalidade: ' . $e->getMessage());
            }
        }

        return $this->render('nacionalidade/new.html.twig', [
            'nacionalidade' => $nacionalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Nacionalidade $nacionalidade): Response
    {
        return $this->render('nacionalidade/show.html.twig', [
            'nacionalidade' => $nacionalidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Nacionalidade $nacionalidade, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NacionalidadeType::class, $nacionalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Nacionalidade atualizada com sucesso!');
                return $this->redirectToRoute('app_nacionalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('nacionalidade/edit.html.twig', [
            'nacionalidade' => $nacionalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Nacionalidade $nacionalidade, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nacionalidade->getId(), $request->request->get('_token'))) {
            $entityManager->remove($nacionalidade);
            $entityManager->flush();
            $this->addFlash('success', 'Nacionalidade excluída com sucesso!');
        }

        return $this->redirectToRoute('app_nacionalidade_index');
    }
}
