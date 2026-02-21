<?php
namespace App\Controller;

use App\Entity\Bancos;
use App\Form\BancoType;
use App\Repository\BancosRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/banco', name: 'app_banco_')]
class BancoController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BancosRepository $bancosRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $bancosRepository->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['b.nome', 'b.numero']);

        return $this->render('banco/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $banco = new Bancos();
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($banco);
                $entityManager->flush();
                $this->addFlash('success', 'Banco criado com sucesso!');
                return $this->redirectToRoute('app_banco_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar banco: ' . $e->getMessage());
            }
        }

        return $this->render('banco/new.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bancos $banco): Response
    {
        return $this->render('banco/show.html.twig', [
            'banco' => $banco,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bancos $banco, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Banco atualizado com sucesso!');
                return $this->redirectToRoute('app_banco_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar banco: ' . $e->getMessage());
            }
        }

        return $this->render('banco/edit.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bancos $banco, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $banco->getId(), $request->request->get('_token'))) {
            $entityManager->remove($banco);
            $entityManager->flush();
            $this->addFlash('success', 'Banco excluído com sucesso!');
        }

        return $this->redirectToRoute('app_banco_index');
    }
}
