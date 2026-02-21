<?php

namespace App\Controller;

use App\Entity\ContasBancarias;
use App\Form\ContaBancariaType;
use App\Repository\ContasBancariasRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/conta-bancaria', name: 'app_conta_bancaria_')]
class ContaBancariaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(ContasBancarias::class)->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['c.codigo', 'c.titular']);

        return $this->render('conta_bancaria/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contaBancaria = new ContasBancarias();
        $form = $this->createForm(ContaBancariaType::class, $contaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($contaBancaria);
                $entityManager->flush();
                $this->addFlash('success', 'Conta Bancária criada com sucesso!');
                return $this->redirectToRoute('app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta bancária: ' . $e->getMessage());
            }
        }

        return $this->render('conta_bancaria/new.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
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
    public function edit(Request $request, ContasBancarias $contaBancaria, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContaBancariaType::class, $contaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Conta Bancária atualizada com sucesso!');
                return $this->redirectToRoute('app_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('conta_bancaria/edit.html.twig', [
            'conta_bancaria' => $contaBancaria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, ContasBancarias $contaBancaria, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contaBancaria->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contaBancaria);
            $entityManager->flush();
            $this->addFlash('success', 'Conta Bancária excluída com sucesso!');
        }

        return $this->redirectToRoute('app_conta_bancaria_index');
    }
} 