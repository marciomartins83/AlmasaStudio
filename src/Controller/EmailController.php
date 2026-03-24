<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Emails;
use App\Form\EmailType;
use App\Repository\EmailRepository;
use App\Service\EmailService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Trait\PaginationRedirectTrait;

#[Route('/email')]
class EmailController extends AbstractController
{
    use PaginationRedirectTrait;
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    #[Route('/', name: 'app_email_index', methods: ['GET'])]
    public function index(EmailRepository $emailRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $emailRepository->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        $filters = [
            new SearchFilterDTO('email', 'Email', 'text', 'e.email', 'LIKE', [], 'Email...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('email', 'Email'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['e.email'], null, $filters, $sortOptions, 'email', 'ASC');

        return $this->render('email/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_email_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $email = new Emails();
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->emailService->criar($email);
                $this->addFlash('success', 'Email criado com sucesso!');
                return $this->redirectToRoute('app_email_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar email: ' . $e->getMessage());
            }
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
    public function edit(Request $request, Emails $email): Response
    {
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->emailService->atualizar();
                $this->addFlash('success', 'Email atualizado com sucesso!');
                return $this->redirectToIndex($request, 'app_email_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar email: ' . $e->getMessage());
            }
        }

        return $this->render('email/edit.html.twig', [
            'email' => $email,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_email_delete', methods: ['POST'])]
    public function delete(Request $request, Emails $email): Response
    {
        if ($this->isCsrfTokenValid('delete'.$email->getId(), $request->request->get('_token'))) {
            try {
                $this->emailService->deletar($email);
                $this->addFlash('success', 'Email excluído com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao excluir email: ' . $e->getMessage());
            }
        }

        return $this->redirectToIndex($request, 'app_email_index');
    }
}
