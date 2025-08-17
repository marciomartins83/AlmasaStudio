<?php
namespace App\Controller;

use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Form\BairroType;
use App\Repository\CidadeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bairro', name: 'app_bairro_')]
class BairroController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $bairros = $entityManager->getRepository(Bairros::class)->findAll();
        $cidades = $entityManager->getRepository(Cidades::class)->findAll();
        
        // Criar um mapa de cidades para fácil acesso
        $cidadesMap = [];
        foreach ($cidades as $cidade) {
            $cidadesMap[$cidade->getId()] = $cidade;
        }

        return $this->render('bairro/index.html.twig', [
            'bairros' => $bairros,
            'cidades_map' => $cidadesMap,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bairro = new Bairros();
        
        // Buscar todas as cidades para preencher o campo de seleção
        $cidades = $entityManager->getRepository(Cidades::class)->findAll();
        
        $form = $this->createForm(BairroType::class, $bairro, [
            'cidades' => $cidades
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($bairro);
                $entityManager->flush();
                $this->addFlash('success', 'Bairro criado com sucesso!');
                return $this->redirectToRoute('app_bairro_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar bairro: ' . $e->getMessage());
            }
        }

        return $this->render('bairro/new.html.twig', [
            'bairro' => $bairro,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Bairros $bairro, CidadeRepository $cidadeRepository): Response
    {
        $cidade = $cidadeRepository->find($bairro->getIdCidade());

        return $this->render('bairro/show.html.twig', [
            'bairro' => $bairro,
            'cidade' => $cidade,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bairros $bairro, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BairroType::class, $bairro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("=== DEBUG BAIRRO EDIT ===");
            error_log("ID: " . $bairro->getId());
            error_log("Nome: " . $bairro->getNome());
            error_log("Cidade ID: " . $bairro->getIdCidade());
            error_log("Código: " . $bairro->getCodigo());
            
            try {
                // Flush simples - sem trigger deve funcionar
                $entityManager->flush();
                error_log("FLUSH EXECUTADO COM SUCESSO - SEM ROLLBACK");
                $this->addFlash('success', 'Bairro atualizado com sucesso!');
                return $this->redirectToRoute('app_bairro_index');
            } catch (\Exception $e) {
                error_log("ERRO NO FLUSH: " . $e->getMessage());
                error_log("CLASSE ERRO: " . get_class($e));
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('bairro/edit.html.twig', [
            'bairro' => $bairro,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Bairros $bairro, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bairro->getId(), $request->request->get('_token'))) {
            $entityManager->remove($bairro);
            $entityManager->flush();
            $this->addFlash('success', 'Bairro excluído com sucesso!');
        }

        return $this->redirectToRoute('app_bairro_index');
    }
}
