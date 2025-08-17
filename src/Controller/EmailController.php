<?php

namespace App\Controller;

use App\Entity\Emails;
use App\Form\EmailType;
use App\Repository\EmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/email')]
class EmailController extends AbstractController
{
    #[Route('/', name: 'app_email_index', methods: ['GET'])]
    public function index(EmailRepository $emailRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        
        $queryBuilder = $emailRepository->createQueryBuilder('e');
            
        if ($search) {
            $queryBuilder->where('e.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }
        
        $queryBuilder->orderBy('e.id', 'DESC');
        
        $emails = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * 10)
            ->setMaxResults(10)
            ->getResult();

        return $this->render('email/index.html.twig', [
            'emails' => $emails,
            'search' => $search,
            'page' => $page,
        ]);
    }

    #[Route('/new', name: 'app_email_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = new Emails();
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($email);
            $entityManager->flush();

            $this->addFlash('success', 'Email criado com sucesso!');
            return $this->redirectToRoute('app_email_index');
        }

        return $this->render('email/new.html.twig', [
            'email' => $email,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_email_show', methods: ['GET'])]
    public function show(Emails $email): Response
    {
        return $this->render('email/show.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_email_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Emails $email, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Email atualizado com sucesso!');
            return $this->redirectToRoute('app_email_index');
        }

        return $this->render('email/edit.html.twig', [
            'email' => $email,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_email_delete', methods: ['POST'])]
    public function delete(Request $request, Emails $email, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$email->getId(), $request->request->get('_token'))) {
            $entityManager->remove($email);
            $entityManager->flush();
            $this->addFlash('success', 'Email excluído com sucesso!');
        }

        return $this->redirectToRoute('app_email_index');
    }
} 