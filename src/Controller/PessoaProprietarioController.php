<?php
namespace App\Controller;

use App\Entity\PessoasContratantes;
use App\Form\PessoaProprietarioType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa-proprietario', name: 'app_pessoa_proprietario_')]
class PessoaProprietarioController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $proprietarios = $entityManager->getRepository(PessoasContratantes::class)->findAll();

        return $this->render('pessoa_proprietario/index.html.twig', [
            'proprietarios' => $proprietarios,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $proprietario = new PessoasContratantes();
        $form = $this->createForm(PessoaProprietarioType::class, $proprietario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($proprietario);
                $entityManager->flush();
                $this->addFlash('success', 'Proprietário criado com sucesso!');
                return $this->redirectToRoute('app_pessoa_proprietario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar proprietário: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa_proprietario/new.html.twig', [
            'proprietario' => $proprietario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PessoasContratantes $proprietario): Response
    {
        return $this->render('pessoa_proprietario/show.html.twig', [
            'proprietario' => $proprietario,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PessoasContratantes $proprietario, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaProprietarioType::class, $proprietario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Proprietário atualizado com sucesso!');
                return $this->redirectToRoute('app_pessoa_proprietario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa_proprietario/edit.html.twig', [
            'proprietario' => $proprietario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PessoasContratantes $proprietario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$proprietario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($proprietario);
            $entityManager->flush();
            $this->addFlash('success', 'Proprietário excluído com sucesso!');
        }

        return $this->redirectToRoute('app_pessoa_proprietario_index');
    }
}