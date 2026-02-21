<?php
namespace App\Controller;

use App\Entity\TiposPessoas;
use App\Form\TipoPessoaType;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo-pessoa', name: 'app_tipo_pessoa_')]
class TipoPessoaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginationService $paginator, Request $request): Response
    {
        $qb = $entityManager->getRepository(TiposPessoas::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['t.tipo']);

        return $this->render('tipo_pessoa/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoPessoa = new TiposPessoas();
        $form = $this->createForm(TipoPessoaType::class, $tipoPessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($tipoPessoa);
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de pessoa criado com sucesso!');
                return $this->redirectToRoute('app_tipo_pessoa_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar tipo de pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_pessoa/new.html.twig', [
            'tipo_pessoa' => $tipoPessoa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(TiposPessoas $tipoPessoa): Response
    {
        return $this->render('tipo_pessoa/show.html.twig', [
            'tipo_pessoa' => $tipoPessoa,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TiposPessoas $tipoPessoa, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoPessoaType::class, $tipoPessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Tipo de pessoa atualizado com sucesso!');
                return $this->redirectToRoute('app_tipo_pessoa_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('tipo_pessoa/edit.html.twig', [
            'tipo_pessoa' => $tipoPessoa,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, TiposPessoas $tipoPessoa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoPessoa->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoPessoa);
            $entityManager->flush();
            $this->addFlash('success', 'Tipo de pessoa excluído com sucesso!');
        }

        return $this->redirectToRoute('app_tipo_pessoa_index');
    }
}
