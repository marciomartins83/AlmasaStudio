<?php

namespace App\Controller;

use App\Entity\Agencias;
use App\Form\AgenciaType;
use App\Repository\AgenciaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/agencia')]
class AgenciaController extends AbstractController
{
    #[Route('/', name: 'app_agencia_index', methods: ['GET'])]
    public function index(AgenciaRepository $agenciaRepository): Response
    {
        return $this->render('agencia/index.html.twig', [
            'agencias' => $agenciaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_agencia_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $agencia = new Agencias();
        $form = $this->createForm(AgenciaType::class, $agencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($agencia);
            $entityManager->flush();

            $this->addFlash('success', 'Agência criada com sucesso!');
            return $this->redirectToRoute('app_agencia_index');
        }

        return $this->render('agencia/new.html.twig', [
            'agencia' => $agencia,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agencia_show', methods: ['GET'])]
    public function show(Agencias $agencia): Response
    {
        return $this->render('agencia/show.html.twig', [
            'agencia' => $agencia,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agencia_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Agencias $agencia, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AgenciaType::class, $agencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Agência atualizada com sucesso!');
            return $this->redirectToRoute('app_agencia_index');
        }

        return $this->render('agencia/edit.html.twig', [
            'agencia' => $agencia,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agencia_delete', methods: ['POST'])]
    public function delete(Request $request, Agencias $agencia, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$agencia->getId(), $request->request->get('_token'))) {
            $entityManager->remove($agencia);
            $entityManager->flush();
            $this->addFlash('success', 'Agência excluída com sucesso!');
        }

        return $this->redirectToRoute('app_agencia_index');
    }
} 