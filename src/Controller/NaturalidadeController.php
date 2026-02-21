<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Naturalidade;
use App\Form\NaturalidadeType;
use App\Service\NaturalidadeService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/naturalidade', name: 'app_naturalidade_')]
class NaturalidadeController extends AbstractController
{
    private NaturalidadeService $naturalidadeService;

    public function __construct(NaturalidadeService $naturalidadeService)
    {
        $this->naturalidadeService = $naturalidadeService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(Naturalidade::class)->createQueryBuilder('n')
            ->orderBy('n.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'n.nome', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['n.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('naturalidade/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $naturalidade = new Naturalidade();
        $form = $this->createForm(NaturalidadeType::class, $naturalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($naturalidade);
                $entityManager->flush();
                $this->addFlash('success', 'Naturalidade criada com sucesso!');
                return $this->redirectToRoute('app_naturalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Naturalidade: ' . $e->getMessage());
            }
        }

        return $this->render('naturalidade/new.html.twig', [
            'naturalidade' => $naturalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Naturalidade $naturalidade): Response
    {
        return $this->render('naturalidade/show.html.twig', [
            'naturalidade' => $naturalidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Naturalidade $naturalidade, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NaturalidadeType::class, $naturalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Naturalidade atualizada com sucesso!');
                return $this->redirectToRoute('app_naturalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('naturalidade/edit.html.twig', [
            'naturalidade' => $naturalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Naturalidade $naturalidade, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$naturalidade->getId(), $request->request->get('_token'))) {
            $entityManager->remove($naturalidade);
            $entityManager->flush();
            $this->addFlash('success', 'Naturalidade excluída com sucesso!');
        }

        return $this->redirectToRoute('app_naturalidade_index');
    }

    /**
     * Salva nova naturalidade via AJAX
     */
    #[Route('/salvar', name: 'salvar', methods: ['POST'])]
    public function salvar(Request $request): JsonResponse
    {
        // ✅ Validação de CSRF Token
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF inválido'
            ], 403);
        }

        try {
            $data = json_decode($request->getContent(), true);

            // ✅ Thin Controller: Delega para Service
            $naturalidade = $this->naturalidadeService->salvarNaturalidade($data['nome'] ?? '');

            return new JsonResponse([
                'success' => true,
                'naturalidade' => [
                    'id' => $naturalidade->getId(),
                    'nome' => $naturalidade->getNome()
                ]
            ]);

        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erro ao salvar naturalidade: ' . $e->getMessage()
            ], 500);
        }
    }
}
