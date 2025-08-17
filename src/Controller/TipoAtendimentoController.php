<?php
namespace App\Controller;

use App\Entity\TiposAtendimento;
use App\Form\TipoAtendimentoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-atendimento', name: 'app_tipo_atendimento_')]
class TipoAtendimentoController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tiposAtendimento = $entityManager->getRepository(TiposAtendimento::class)->findAll();

        return $this->render('tipo_atendimento/index.html.twig', [
            'tipos_atendimento' => $tiposAtendimento,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoAtendimento = new TiposAtendimento();
        $form = $this->createForm(TipoAtendimentoType::class, $tipoAtendimento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoAtendimento);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de atendimento criado com sucesso!');
                return $this->redirectToRoute('app_tipo_atendimento_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de atendimento: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_atendimento/new.html.twig', [
            'tipo_atendimento' => $tipoAtendimento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposAtendimento $tipoAtendimento): Response
    {
        return $this->render('tipo_atendimento/show.html.twig', [
            'tipo_atendimento' => $tipoAtendimento,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposAtendimento $tipoAtendimento, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoAtendimentoType::class, $tipoAtendimento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de atendimento atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_atendimento_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_atendimento/edit.html.twig', [
            'tipo_atendimento' => $tipoAtendimento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposAtendimento $tipoAtendimento, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoAtendimento->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoAtendimento);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de atendimento excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_atendimento_index');
    }
}
