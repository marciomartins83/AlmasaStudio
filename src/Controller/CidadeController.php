<?php
namespace App\Controller;

use App\Entity\Cidades;
use App\Entity\Estados;
use App\Form\CidadeType;
use App\Repository\EstadosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cidade', name: 'app_cidade_')]
class CidadeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $cidades = $entityManager->getRepository(Cidades::class)->findAll();
        $estados = $entityManager->getRepository(Estados::class)->findAll();
        
        // Criar um mapa de estados para fácil acesso
        $estadosMap = [];
        foreach ($estados as $estado) {
            $estadosMap[$estado->getId()] = $estado;
        }

        return $this->render('cidade/index.html.twig', [
            'cidades' => $cidades,
            'estados_map' => $estadosMap,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cidade = new Cidades();
        $form = $this->createForm(CidadeType::class, $cidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($cidade);
                $entityManager->flush();
                $this->addFlash('success', 'Cidade criada com sucesso!');
                return $this->redirectToRoute('app_cidade_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar cidade: ' . $e->getMessage());
            }
        }

        return $this->render('cidade/new.html.twig', [
            'cidade' => $cidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cidades $cidade, EstadosRepository $estadosRepository): Response
    {
        $estado = $estadosRepository->find($cidade->getIdEstado());

        return $this->render('cidade/show.html.twig', [
            'cidade' => $cidade,
            'estado' => $estado,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cidades $cidade, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CidadeType::class, $cidade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("=== DEBUG CIDADE EDIT ===");
            error_log("ID: " . $cidade->getId());
            error_log("Nome: " . $cidade->getNome());
            error_log("Estado ID: " . $cidade->getIdEstado());
            error_log("Código: " . $cidade->getCodigo());
            
            try {
                // Flush simples - sem trigger deve funcionar
                $entityManager->flush();
                error_log("FLUSH EXECUTADO COM SUCESSO - SEM ROLLBACK");
                $this->addFlash('success', 'Cidade atualizada com sucesso!');
                return $this->redirectToRoute('app_cidade_index');
            } catch (\Exception $e) {
                error_log("ERRO NO FLUSH: " . $e->getMessage());
                error_log("CLASSE ERRO: " . get_class($e));
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('cidade/edit.html.twig', [
            'cidade' => $cidade,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cidades $cidade, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cidade->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cidade);
            $entityManager->flush();
            $this->addFlash('success', 'Cidade excluída com sucesso!');
        }

        return $this->redirectToRoute('app_cidade_index');
    }
}
