<?php
namespace App\Controller;

use App\Entity\PessoasPretendentes;
use App\Form\PessoaLocatarioType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa-locatario', name: 'app_pessoa_locatario_')]
class PessoaLocatarioController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $locatarios = $entityManager->getRepository(PessoasPretendentes::class)->findAll();

        return $this->render('pessoa_locatario/index.html.twig', [
            'locatarios' => $locatarios,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $locatario = new PessoasPretendentes();
        $form = $this->createForm(PessoaLocatarioType::class, $locatario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($locatario);
                $entityManager->flush();
                $this->addFlash('success', 'Locatário criado com sucesso!');
                return $this->redirectToRoute('app_pessoa_locatario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar locatário: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa_locatario/new.html.twig', [
            'locatario' => $locatario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PessoasPretendentes $locatario): Response
    {
        return $this->render('pessoa_locatario/show.html.twig', [
            'locatario' => $locatario,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PessoasPretendentes $locatario, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaLocatarioType::class, $locatario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Locatário atualizado com sucesso!');
                return $this->redirectToRoute('app_pessoa_locatario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa_locatario/edit.html.twig', [
            'locatario' => $locatario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, PessoasPretendentes $locatario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$locatario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($locatario);
            $entityManager->flush();
            $this->addFlash('success', 'Locatário excluído com sucesso!');
        }

        return $this->redirectToRoute('app_pessoa_locatario_index');
    }
}