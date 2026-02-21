<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposEmails;
use App\Form\TipoEmailType;
use App\Repository\TipoEmailRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-email', name: 'app_tipo_email_')]
class TipoEmailController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(TiposEmails::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_email/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoEmail = new TiposEmails();
        $form = $this->createForm(TipoEmailType::class, $tipoEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de email criado com sucesso!');
                return $this->redirectToRoute('app_tipo_email_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de email: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_email/new.html.twig', [
            'tipo_email' => $tipoEmail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposEmails $tipoEmail): Response
    {
        return $this->render('tipo_email/show.html.twig', [
            'tipo_email' => $tipoEmail,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposEmails $tipoEmail, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoEmailType::class, $tipoEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de email atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_email_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_email/edit.html.twig', [
            'tipo_email' => $tipoEmail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposEmails $tipoEmail, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoEmail->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoEmail);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de email excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_email_index');
    }
} 