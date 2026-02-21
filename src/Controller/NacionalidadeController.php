<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Nacionalidade;
use App\Form\NacionalidadeType;
use App\Service\NacionalidadeService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nacionalidade', name: 'app_nacionalidade_')]
class NacionalidadeController extends AbstractController
{
    private NacionalidadeService $nacionalidadeService;

    public function __construct(NacionalidadeService $nacionalidadeService)
    {
        $this->nacionalidadeService = $nacionalidadeService;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(Nacionalidade::class)->createQueryBuilder('n')
            ->orderBy('n.id', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'n.nome', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['n.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('nacionalidade/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $nacionalidade = new Nacionalidade();
        $form = $this->createForm(NacionalidadeType::class, $nacionalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($nacionalidade);
                $entityManager->flush();
                $this->addFlash('success', 'Nacionalidade criada com sucesso!');
                return $this->redirectToRoute('app_nacionalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar Nacionalidade: ' . $e->getMessage());
            }
        }

        return $this->render('nacionalidade/new.html.twig', [
            'nacionalidade' => $nacionalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Nacionalidade $nacionalidade): Response
    {
        return $this->render('nacionalidade/show.html.twig', [
            'nacionalidade' => $nacionalidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Nacionalidade $nacionalidade, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NacionalidadeType::class, $nacionalidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Nacionalidade atualizada com sucesso!');
                return $this->redirectToRoute('app_nacionalidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('nacionalidade/edit.html.twig', [
            'nacionalidade' => $nacionalidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Nacionalidade $nacionalidade, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nacionalidade->getId(), $request->request->get('_token'))) {
            $entityManager->remove($nacionalidade);
            $entityManager->flush();
            $this->addFlash('success', 'Nacionalidade excluída com sucesso!');
        }

        return $this->redirectToRoute('app_nacionalidade_index');
    }

    /**
     * Salva nova nacionalidade via AJAX
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
            $nacionalidade = $this->nacionalidadeService->salvarNacionalidade($data['nome'] ?? '');

            return new JsonResponse([
                'success' => true,
                'nacionalidade' => [
                    'id' => $nacionalidade->getId(),
                    'nome' => $nacionalidade->getNome()
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
                'message' => 'Erro ao salvar nacionalidade: ' . $e->getMessage()
            ], 500);
        }
    }
}
