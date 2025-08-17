<?php
namespace App\Controller;

use App\Entity\TiposContasBancarias;
use App\Form\TipoContaBancariaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-conta-bancaria', name: 'app_tipo_conta_bancaria_')]
class TipoContaBancariaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tiposContasBancarias = $entityManager->getRepository(TiposContasBancarias::class)->findAll();

        return $this->render('tipo_conta_bancaria/index.html.twig', [
            'tipos_contas_bancarias' => $tiposContasBancarias,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoContaBancaria = new TiposContasBancarias();
        $form = $this->createForm(TipoContaBancariaType::class, $tipoContaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoContaBancaria);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de conta bancária criado com sucesso!');
                return $this->redirectToRoute('app_tipo_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de conta bancária: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_conta_bancaria/new.html.twig', [
            'tipo_conta_bancaria' => $tipoContaBancaria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposContasBancarias $tipoContaBancaria): Response
    {
        return $this->render('tipo_conta_bancaria/show.html.twig', [
            'tipo_conta_bancaria' => $tipoContaBancaria,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposContasBancarias $tipoContaBancaria, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoContaBancariaType::class, $tipoContaBancaria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de conta bancária atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_conta_bancaria_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_conta_bancaria/edit.html.twig', [
            'tipo_conta_bancaria' => $tipoContaBancaria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposContasBancarias $tipoContaBancaria, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoContaBancaria->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoContaBancaria);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de conta bancária excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_conta_bancaria_index');
    }
}