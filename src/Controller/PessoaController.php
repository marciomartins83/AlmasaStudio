<?php

namespace App\Controller;

use App\Entity\Pessoa;
use App\Entity\User;
use App\Form\PessoaType;
use App\Repository\PessoaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/pessoa')]
final class PessoaController extends AbstractController
{
    #[Route(name: 'app_pessoa', methods: ['GET'])]
    public function index(PessoaRepository $pessoaRepository): Response
    {
        dump("Entrou no index!"); // Exibir mensagem no Symfony Profiler
        dump($pessoaRepository->findAll()); // Exibir os registros retornados
        
        return $this->render('pessoa/index.html.twig', [
            'pessoas' => $pessoaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pessoa_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $pessoa = new Pessoa();
        $form = $this->createForm(PessoaType::class, $pessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persistindo a Pessoa
            $entityManager->persist($pessoa);

            // Verifica se "Criar Usuário?" foi marcado
            if ($form->get('criarUsuario')->getData()) {
                $user = new User();
                $user->setEmail($form->get('email')->getData());
                $user->setPessoa($pessoa); // Associa o usuário à pessoa
                
                // Hash da senha antes de salvar
                $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_pessoa_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pessoa/new.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pessoa_show', methods: ['GET'])]
    public function show(Pessoa $pessoa): Response
    {
        return $this->render('pessoa/show.html.twig', [
            'pessoa' => $pessoa,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pessoa_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pessoa $pessoa, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaType::class, $pessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pessoa_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pessoa/edit.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pessoa_delete', methods: ['POST'])]
    public function delete(Request $request, Pessoa $pessoa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pessoa->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pessoa);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pessoa_index', [], Response::HTTP_SEE_OTHER);
    }
}
