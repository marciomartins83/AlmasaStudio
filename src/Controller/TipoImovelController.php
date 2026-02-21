<?php

namespace App\Controller;

use App\Entity\TiposImoveis;
use App\Form\TipoImovelType;
use App\Repository\TiposImoveisRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-imovel')]
class TipoImovelController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TiposImoveisRepository $tipoImovelRepository;

    public function __construct(EntityManagerInterface $entityManager, TiposImoveisRepository $tipoImovelRepository)
    {
        $this->entityManager = $entityManager;
        $this->tipoImovelRepository = $tipoImovelRepository;
    }

    #[Route('/', name: 'app_tipo_imovel_index', methods: ['GET'])]
    public function index(Request $request, PaginationService $paginator): Response
    {
        $qb = $this->tipoImovelRepository->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo']);

        return $this->render('tipo_imovel/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_tipo_imovel_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tipoImovel = new TiposImoveis();
        $form = $this->createForm(TipoImovelType::class, $tipoImovel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($tipoImovel);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_tipo_imovel_index');
        }

        return $this->render('tipo_imovel/new.html.twig', [
            'tipo_imovel' => $tipoImovel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_imovel_show', methods: ['GET'])]
    public function show(TiposImoveis $tipoImovel): Response
    {
        return $this->render('tipo_imovel/show.html.twig', [
            'tipo_imovel' => $tipoImovel,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_imovel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposImoveis $tipoImovel): Response
    {
        $form = $this->createForm(TipoImovelType::class, $tipoImovel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_tipo_imovel_index');
        }

        return $this->render('tipo_imovel/edit.html.twig', [
            'tipo_imovel' => $tipoImovel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_imovel_delete', methods: ['POST'])]
    public function delete(Request $request, TiposImoveis $tipoImovel): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoImovel->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($tipoImovel);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_tipo_imovel_index');
    }
}
