<?php

namespace App\Controller;

use App\Entity\Logradouro;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LogradouroController extends AbstractController
{
    public function index(EntityManagerInterface $em): Response
    {
        $logradouros = $em->getRepository(Logradouro::class)->findAll();
        
        $bairros = $em->getRepository(Bairros::class)->findAll();
        $bairrosMap = [];
        foreach ($bairros as $bairro) {
            $bairrosMap[$bairro->getId()] = $bairro;
        }

        return $this->render('logradouro/index.html.twig', [
            'logradouros' => $logradouros,
            'bairros_map' => $bairrosMap
        ]);
    }

    #[Route('/cep-lookup/{cep}', name: 'app_cep_lookup')]
    public function cepLookup(string $cep): JsonResponse
    {
        // Implementação existente
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
            $logradouro = $em->getRepository(Logradouro::class)->findOneBy(['cep' => $cep]);
            
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
            $logradouro = new Logradouro();
            $logradouro->setCep($data['cep']);
            $logradouro->setNome($data['logradouro']);
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
