<?php
namespace App\Controller;

use App\Entity\TiposTelefones;
use App\Form\TipoTelefoneType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-telefone', name: 'app_tipo_telefone_')]
class TipoTelefoneController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tiposTelefones = $entityManager->getRepository(TiposTelefones::class)->findAll();

        return $this->render('tipo_telefone/index.html.twig', [
            'tipos_telefones' => $tiposTelefones,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoTelefone = new TiposTelefones();
        $form = $this->createForm(TipoTelefoneType::class, $tipoTelefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoTelefone);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de telefone criado com sucesso!');
                return $this->redirectToRoute('app_tipo_telefone_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de telefone: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_telefone/new.html.twig', [
            'tipo_telefone' => $tipoTelefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposTelefones $tipoTelefone): Response
    {
        return $this->render('tipo_telefone/show.html.twig', [
            'tipo_telefone' => $tipoTelefone,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposTelefones $tipoTelefone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoTelefoneType::class, $tipoTelefone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de telefone atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_telefone_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_telefone/edit.html.twig', [
            'tipo_telefone' => $tipoTelefone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposTelefones $tipoTelefone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoTelefone->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoTelefone);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de telefone excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_telefone_index');
    }
}