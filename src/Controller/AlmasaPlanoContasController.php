<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AlmasaPlanoContas;
use App\Form\AlmasaPlanoContasType;
use App\Repository\AlmasaPlanoContasRepository;
use App\Service\AlmasaPlanoContasService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/almasa-plano-contas', name: 'app_almasa_plano_contas_')]
class AlmasaPlanoContasController extends AbstractController
{
    public function __construct(
        private AlmasaPlanoContasService $service,
        private AlmasaPlanoContasRepository $repository
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $contas = $this->repository->findHierarquiaCompleta();

        return $this->render('almasa_plano_contas/index.html.twig', [
            'contas' => $contas,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $conta = new AlmasaPlanoContas();
        $form = $this->createForm(AlmasaPlanoContasType::class, $conta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->criar($conta);
                $this->addFlash('success', 'Conta criada com sucesso!');
                return $this->redirectToRoute('app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar conta: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_plano_contas/new.html.twig', [
            'conta' => $conta,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AlmasaPlanoContas $conta): Response
    {
        return $this->render('almasa_plano_contas/show.html.twig', [
            'conta' => $conta,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AlmasaPlanoContas $conta): Response
    {
        $form = $this->createForm(AlmasaPlanoContasType::class, $conta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->atualizar($conta);
                $this->addFlash('success', 'Conta atualizada com sucesso!');
                return $this->redirectToRoute('app_almasa_plano_contas_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar conta: ' . $e->getMessage());
            }
        }

        return $this->render('almasa_plano_contas/edit.html.twig', [
            'conta' => $conta,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, AlmasaPlanoContas $conta): Response
    {
        if ($this->isCsrfTokenValid('delete' . $conta->getId(), $request->request->get('_token'))) {
            try {
                $this->service->deletar($conta);
                $this->addFlash('success', 'Conta excluída com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_almasa_plano_contas_index');
    }
}
