<?php

namespace App\Controller;

use App\Entity\EstadoCivil;
use App\Form\EstadoCivilType;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/estado-civil', name: 'app_estado_civil_')]
class EstadoCivilController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(EstadoCivil::class)->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['e.nome']);

        return $this->render('estado_civil/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $estadoCivil = new EstadoCivil();
        $form = $this->createForm(EstadoCivilType::class, $estadoCivil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($estadoCivil);
                $entityManager->flush();
                $this->addFlash('success', 'Estado Civil criado com sucesso!');
                return $this->redirectToRoute('app_estado_civil_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Estado Civil: ' . $e->getMessage());
            }
        }

        return $this->render('estado_civil/new.html.twig', [
            'estado_civil' => $estadoCivil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(EstadoCivil $estadoCivil): Response
    {
        return $this->render('estado_civil/show.html.twig', [
            'estado_civil' => $estadoCivil,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EstadoCivil $estadoCivil, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EstadoCivilType::class, $estadoCivil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Estado Civil atualizado com sucesso!');
                return $this->redirectToRoute('app_estado_civil_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('estado_civil/edit.html.twig', [
            'estado_civil' => $estadoCivil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, EstadoCivil $estadoCivil, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$estadoCivil->getId(), $request->request->get('_token'))) {
            $entityManager->remove($estadoCivil);
            $entityManager->flush();
            $this->addFlash('success', 'Estado Civil excluído com sucesso!');
        }

        return $this->redirectToRoute('app_estado_civil_index');
    }
}
