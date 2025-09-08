<?php

namespace App\Controller;

use App\Entity\Pessoas;
use App\Form\PessoaFormType;
use App\Repository\PessoaRepository;
use App\Form\PessoaCorretorType;
use App\Form\PessoaFiadorType;
use App\Form\PessoaLocadorType;
use App\Form\PessoaPretendenteType;
use App\Form\PessoaCorretoraType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa', name: 'app_pessoa_')]
class PessoaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaRepository $pessoaRepository): Response
    {
        return $this->render('pessoa/index.html.twig', [
            'pessoas' => $pessoaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $tipoPessoa = $form->get('tipoPessoa')->getData();

            $entityManager->beginTransaction();
            try {
                // 1. Criar e Salvar a Pessoa Principal
                $pessoa = new Pessoas();
                $pessoa->setNome($data['nome']);
                $pessoa->setDataNascimento($data['dataNascimento']);
                $pessoa->setEstadoCivil($data['estadoCivil']);
                $pessoa->setNacionalidade($data['nacionalidade']);
                $pessoa->setNaturalidade($data['naturalidade']);
                $pessoa->setNomePai($data['nomePai']);
                $pessoa->setNomeMae($data['nomeMae']);
                $pessoa->setRenda($data['renda']);
                $pessoa->setObservacoes($data['observacoes']);

                // TODO: Lógica para definir fisicaJuridica com base no CPF/CNPJ
                $pessoa->setFisicaJuridica('fisica'); // Placeholder
                $pessoa->setDtCadastro(new \DateTime());
                $pessoa->setStatus(true);
                $pessoa->setTipoPessoa(1); // Placeholder

                $entityManager->persist($pessoa);

                // 2. Criar e Salvar a Entidade Específica
                if (isset($data[$tipoPessoa]) && $data[$tipoPessoa] !== null) {
                    $subEntity = $data[$tipoPessoa];
                    $reflection = new \ReflectionClass($subEntity);
                    if ($reflection->hasMethod('setPessoa')) {
                        $method = $reflection->getMethod('setPessoa');
                        $method->invoke($subEntity, $pessoa);
                    }
                    
                    $entityManager->persist($subEntity);
                }
                
                // TODO: Salvar coleções (telefones, endereços, etc.) associadas à $pessoa

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');

            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'Erro ao salvar: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Pessoas $pessoa): Response
    {
        return $this->render('pessoa/show.html.twig', [
            'pessoa' => $pessoa,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pessoas $pessoa, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class, $pessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Pessoa atualizada com sucesso!');
            return $this->redirectToRoute('app_pessoa_index');
        }

        return $this->render('pessoa/edit.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Pessoas $pessoa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pessoa->getIdpessoa(), $request->request->get('_token'))) {
            $entityManager->remove($pessoa);
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa excluída com sucesso.');
        }

        return $this->redirectToRoute('app_pessoa_index');
    }

    #[Route('/_subform', name: '_subform', methods: ['POST'])]
    public function _subform(Request $request): Response
    {
        $tipo = $request->get('tipo');
        $form = $this->createForm(PessoaFormType::class);

        $subFormType = null;
        $template = null;

        switch ($tipo) {
            case 'fiador':
                $subFormType = PessoaFiadorType::class;
                $template = 'pessoa/partials/fiador.html.twig';
                break;
            case 'corretor':
                $subFormType = PessoaCorretorType::class;
                $template = 'pessoa/partials/corretor.html.twig';
                break;
            case 'locador':
                $subFormType = PessoaLocadorType::class;
                $template = 'pessoa/partials/locador.html.twig';
                break;
            case 'corretora':
                $subFormType = PessoaCorretoraType::class;
                $template = 'pessoa/partials/corretora.html.twig';
                break;
            case 'pretendente':
                $subFormType = PessoaPretendenteType::class;
                $template = 'pessoa/partials/pretendente.html.twig';
                break;
        }

        if ($subFormType) {
            $form->add($tipo, $subFormType);
        }

        return $this->render($template ?? 'default/empty.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/search-pessoa-advanced', name: 'search_pessoa_advanced', methods: ['POST'])]
    public function searchPessoaAdvanced(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $criteria = $data['criteria'] ?? '';
            $value = $data['value'] ?? '';
            
            // Lógica de busca...
            $pessoa = $pessoaRepository->findOneBy([$criteria => $value]); // Simplificado

            if ($pessoa) {
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => [
                        'id' => $pessoa->getIdpessoa(),
                        'nome' => $pessoa->getNome(),
                        // ... outros campos necessários para preencher o formulário
                    ]
                ]);
            } else {
                return new JsonResponse(['success' => true, 'pessoa' => null]);
            }

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }
}

