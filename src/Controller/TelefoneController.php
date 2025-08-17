<?php
namespace App\Controller;

use App\Entity\Telefones;
use App\Form\TelefoneType;
use App\Repository\TelefoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/telefone')]
class TelefoneController extends AbstractController
{
    #[Route('/', name: 'app_telefone_index', methods: ['GET'])]
    public function index(TelefoneRepository $telefoneRepository): Response
    {
        return $this->render('telefone/index.html.twig', [
            'telefones' => $telefoneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_telefone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $telefone = new Telefones();
        $form = $this->createForm(TelefoneType::class, $telefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($telefone);
            $entityManager->flush();

            $this->addFlash('success', 'Telefone criado com sucesso!');
            return $this->redirectToRoute('app_telefone_index');
        }

        return $this->render('telefone/new.html.twig', [
            'telefone' => $telefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_telefone_show', methods: ['GET'])]
    public function show(Telefones $telefone): Response
    {
        return $this->render('telefone/show.html.twig', [
            'telefone' => $telefone,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_telefone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Telefones $telefone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TelefoneType::class, $telefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Telefone atualizado!');
            return $this->redirectToRoute('app_telefone_index');
        }

        return $this->render('telefone/edit.html.twig', [
            'telefone' => $telefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_telefone_delete', methods: ['POST'])]
    public function delete(Request $request, Telefones $telefone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$telefone->getId(), $request->request->get('_token'))) {
            $entityManager->remove($telefone);
            $entityManager->flush();
            $this->addFlash('success', 'Telefone excluído!');
        }
        return $this->redirectToRoute('app_telefone_index');
    }
} 