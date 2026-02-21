<?php
namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\TiposDocumentos;
use App\Form\TipoDocumentoType;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-documento', name: 'app_tipo_documento_')]
class TipoDocumentoController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(TiposDocumentos::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'text', 't.tipo', 'LIKE', [], 'Buscar...', 6),
        ];
        $sortOptions = [
            new SortOptionDTO('tipo', 'Tipo'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];
        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo'], null, $filters, $sortOptions, 'tipo', 'ASC');

        return $this->render('tipo_documento/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoDocumento = new TiposDocumentos();
        $form = $this->createForm(TipoDocumentoType::class, $tipoDocumento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoDocumento);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de documento criado com sucesso!');
                return $this->redirectToRoute('app_tipo_documento_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de documento: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_documento/new.html.twig', [
            'tipo_documento' => $tipoDocumento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposDocumentos $tipoDocumento): Response
    {
        return $this->render('tipo_documento/show.html.twig', [
            'tipo_documento' => $tipoDocumento,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposDocumentos $tipoDocumento, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoDocumentoType::class, $tipoDocumento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de documento atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_documento_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_documento/edit.html.twig', [
            'tipo_documento' => $tipoDocumento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposDocumentos $tipoDocumento, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoDocumento->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoDocumento);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de documento excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_documento_index');
    }
}