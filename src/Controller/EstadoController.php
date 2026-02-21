<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Estados;
use App\Form\EstadoType;
use App\Repository\EstadosRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/estado', name: 'app_estado_')]
class EstadoController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EstadosRepository $estadosRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $estadosRepository->createQueryBuilder('e');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'e.nome', 'LIKE', [], 'Nome...', 4),
            new SearchFilterDTO('uf', 'UF', 'text', 'e.uf', 'LIKE', [], 'UF...', 2),
        ];
        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('uf', 'UF'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['e.uf', 'e.nome'], null, $filters, $sortOptions, 'nome', 'ASC');

        return $this->render('estado/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $estado = new Estados();
        $form = $this->createForm(EstadoType::class, $estado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($estado);
                $entityManager->flush();
                $this->addFlash('success', 'Estado criado com sucesso!');
                return $this->redirectToRoute('app_estado_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar estado: ' . $e->getMessage());
            }
        }

        return $this->render('estado/new.html.twig', [
            'estado' => $estado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Estados $estado): Response
    {
        return $this->render('estado/show.html.twig', [
            'estado' => $estado,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Estados $estado, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EstadoType::class, $estado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("=== DEBUG ESTADO EDIT ===");
            error_log("ID: " . $estado->getId());
            error_log("Nome: " . $estado->getNome());
            error_log("UF: " . $estado->getUf());
            
            try {
                // Flush simples - sem trigger deve funcionar
                $entityManager->flush();
                error_log("FLUSH EXECUTADO COM SUCESSO - SEM ROLLBACK");
                $this->addFlash('success', 'Estado atualizado com sucesso!');
                return $this->redirectToRoute('app_estado_index');
            } catch (\Exception $e) {
                error_log("ERRO NO FLUSH: " . $e->getMessage());
                error_log("CLASSE ERRO: " . get_class($e));
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('estado/edit.html.twig', [
            'estado' => $estado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Estados $estado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$estado->getId(), $request->request->get('_token'))) {
            $entityManager->remove($estado);
            $entityManager->flush();
            $this->addFlash('success', 'Estado excluído com sucesso!');
        }

        return $this->redirectToRoute('app_estado_index');
    }
} 