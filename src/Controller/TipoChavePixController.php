<?php
namespace App\Controller;

use App\Entity\TiposChavesPix;
use App\Form\TipoChavePixType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-chave-pix', name: 'app_tipo_chave_pix_')]
class TipoChavePixController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tiposChavesPix = $entityManager->getRepository(TiposChavesPix::class)->findAll();

        return $this->render('tipo_chave_pix/index.html.twig', [
            'tipos_chaves_pix' => $tiposChavesPix,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoChavePix = new TiposChavesPix();
        $form = $this->createForm(TipoChavePixType::class, $tipoChavePix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoChavePix);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de chave PIX criado com sucesso!');
                return $this->redirectToRoute('app_tipo_chave_pix_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de chave PIX: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_chave_pix/new.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposChavesPix $tipoChavePix): Response
    {
        return $this->render('tipo_chave_pix/show.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposChavesPix $tipoChavePix, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoChavePixType::class, $tipoChavePix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de chave PIX atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_chave_pix_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_chave_pix/edit.html.twig', [
            'tipo_chave_pix' => $tipoChavePix,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposChavesPix $tipoChavePix, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoChavePix->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoChavePix);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de chave PIX excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_chave_pix_index');
    }
}