<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Logradouros;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Form\LogradouroType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LogradouroController extends AbstractController
{
    public function index(EntityManagerInterface $em, PaginationService $paginator, Request $request): Response
    {
        $qb = $em->getRepository(Logradouros::class)->createQueryBuilder('l');

        $filters = [
            new SearchFilterDTO('logradouro', 'Logradouro', 'text', 'l.logradouro', 'LIKE', [], 'Logradouro...', 4),
            new SearchFilterDTO('cep', 'CEP', 'text', 'l.cep', 'LIKE', [], 'CEP...', 3),
        ];
        $sortOptions = [
            new SortOptionDTO('logradouro', 'Logradouro'),
            new SortOptionDTO('cep', 'CEP'),
            new SortOptionDTO('id', 'ID', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['l.logradouro', 'l.cep'], null, $filters, $sortOptions, 'logradouro', 'ASC');

        return $this->render('logradouro/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $logradouro = new Logradouros();
        $form = $this->createForm(LogradouroType::class, $logradouro);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $em->persist($logradouro);
                    $em->flush();
                    $this->addFlash('success', 'Logradouro criado com sucesso!');
                    return $this->redirectToRoute('app_logradouro_index');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erro ao salvar logradouro: '.$e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Por favor, corrija os erros no formulário.');
            }
        }

        return $this->render('logradouro/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function show(Logradouros $logradouro): Response
    {
        return $this->render('logradouro/show.html.twig', [
            'logradouro' => $logradouro,
        ]);
    }

    public function edit(Request $request, Logradouros $logradouro, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LogradouroType::class, $logradouro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Logradouro atualizado com sucesso!');
            return $this->redirectToRoute('app_logradouro_index');
        }

        return $this->render('logradouro/edit.html.twig', [
            'form' => $form->createView(),
            'logradouro' => $logradouro,
        ]);
    }

    public function delete(Request $request, Logradouros $logradouro, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $logradouro->getId(), $request->request->get('_token'))) {
            $em->remove($logradouro);
            $em->flush();
            $this->addFlash('success', 'Logradouro excluído com sucesso!');
        }

        return $this->redirectToRoute('app_logradouro_index');
    }

    #[Route('/cep-lookup/{cep}', name: 'app_cep_lookup')]
    public function cepLookup(string $cep): JsonResponse
    {
        try {
            // Validar formato do CEP
            $cep = preg_replace('/[^\d]/', '', $cep);
            
            if (strlen($cep) !== 8) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'CEP deve conter 8 dígitos'
                ], 400);
            }

            // Sua lógica de busca aqui
            // Exemplo:
            // $endereco = $this->cepService->buscarEndereco($cep);
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'cep' => $cep,
                    'logradouro' => 'Exemplo de Rua',
                    'bairro' => 'Exemplo de Bairro',
                    'cidade' => 'São Paulo',
                    'uf' => 'SP'
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erro ao buscar CEP: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/buscar-cep-local', name: 'app_buscar_cep_local')]
    public function buscarCepLocal(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $cep = $request->query->get('cep');
            if (!$cep) {
                return new JsonResponse(['encontrado' => false, 'erro' => 'CEP não fornecido']);
            }
            
            $cep = preg_replace('/[^0-9]/', '', $cep);
            if (strlen($cep) !== 8) {
                return new JsonResponse(['encontrado' => false, 'erro' => 'CEP inválido']);
            }
            
            // Busca no banco de dados
            $logradouro = $em->getRepository(Logradouros::class)->findOneBy(['cep' => $cep]);
            
            if ($logradouro) {
                // Verifica relações para evitar erros
                $bairro = $logradouro->getBairro();
                if (!$bairro) {
                    return new JsonResponse(['encontrado' => false, 'erro' => 'Bairro não encontrado']);
                }
                
                $cidade = $bairro->getCidade();
                if (!$cidade) {
                    return new JsonResponse(['encontrado' => false, 'erro' => 'Cidade não encontrada']);
                }
                
                $estado = $cidade->getEstado();
                if (!$estado) {
                    return new JsonResponse(['encontrado' => false, 'erro' => 'Estado não encontrado']);
                }
                
                return new JsonResponse([
                    'encontrado' => true,
                    'logradouro' => $logradouro->getNome(),
                    'bairro' => $bairro->getNome(),
                    'cidade' => $cidade->getNome(),
                    'estado' => $estado->getUf()
                ]);
            }
            
            return new JsonResponse(['encontrado' => false]);
        } catch (\Exception $e) {
            // Log do erro para debug
            error_log('Erro em buscarCepLocal: ' . $e->getMessage());
            return new JsonResponse([
                'encontrado' => false,
                'erro' => 'Erro interno ao processar a requisição'
            ], 500);
        }
    }

    #[Route('/buscar-cep-api', name: 'app_buscar_cep_api')]
    public function buscarCepApi(Request $request): JsonResponse
    {
        $cep = $request->query->get('cep');
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        try {
            // Simulação de chamada à API dos Correios
            // Em produção, substituir por chamada real à API
            $dadosApi = [
                'cep' => $cep,
                'logradouro' => 'Rua Exemplo',
                'bairro' => 'Centro',
                'cidade' => 'São Paulo',
                'estado' => 'SP'
            ];
            
            return new JsonResponse([
                'encontrado' => true,
                ...$dadosApi
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'encontrado' => false,
                'erro' => 'Falha ao consultar API'
            ]);
        }
    }

    #[Route('/salvar-cep', name: 'app_salvar_cep')]
    public function salvarCep(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            // Verifica se o estado já existe
            $estado = $em->getRepository(Estados::class)->findOneBy(['uf' => $data['estado']]);
            if (!$estado) {
                $estado = new Estados();
                $estado->setNome($data['estado']); // Nome completo do estado
                $estado->setUf($data['estado']);
                $em->persist($estado);
            }
            
            // Verifica se a cidade já existe
            $cidade = $em->getRepository(Cidades::class)->findOneBy([
                'nome' => $data['cidade'],
                'estado' => $estado
            ]);
            if (!$cidade) {
                $cidade = new Cidades();
                $cidade->setNome($data['cidade']);
                $cidade->setEstado($estado);
                $em->persist($cidade);
            }
            
            // Verifica se o bairro já existe
            $bairro = $em->getRepository(Bairros::class)->findOneBy([
                'nome' => $data['bairro'],
                'cidade' => $cidade
            ]);
            if (!$bairro) {
                $bairro = new Bairros();
                $bairro->setNome($data['bairro']);
                $bairro->setCidade($cidade);
                $em->persist($bairro);
            }
            
            // Cria o novo logradouro
            $logradouro = new Logradouros();
            $logradouro->setCep($data['cep']);
            $logradouro->setLogradouro($data['logradouro']);
            $logradouro->setBairro($bairro);
            $em->persist($logradouro);
            
            $em->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
