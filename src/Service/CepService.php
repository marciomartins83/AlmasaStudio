<?php

namespace App\Service;

use App\Entity\Estados;
use App\Entity\Cidades;
use App\Entity\Bairros;
use App\Entity\Logradouros;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CepService
{
    private $entityManager;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
    }

    public function buscarEpersistirEndereco(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);
        
        // Verificar se já existe no banco
        $logradouro = $this->entityManager->getRepository(Logradouros::class)->findOneBy(['cep' => $cep]);
        
        if ($logradouro) {
            $bairro = $logradouro->getBairro();
            $cidade = $bairro->getCidade();
            $estado = $cidade->getEstado();
            
            return [
                'logradouro' => $logradouro->getLogradouro(),
                'bairro' => $bairro->getNome(),
                'cidade' => $cidade->getNome(),
                'estado' => $estado->getUf(),
                'idLogradouro' => $logradouro->getId(),
                'idBairro' => $bairro->getId(),
                'idCidade' => $cidade->getId(),
                'idEstado' => $estado->getId(),
            ];
        }
        
        // Buscar na API ViaCEP se não encontrado localmente
        $response = $this->httpClient->request('GET', "https://viacep.com.br/ws/{$cep}/json/");
        $data = $response->toArray();
        
        if (isset($data['erro']) || empty($data)) {
            throw new \Exception('CEP não encontrado');
        }
        
        // Persistir estado
        $estado = $this->entityManager->getRepository(Estados::class)->findOneBy(['uf' => $data['uf']]);
        if (!$estado) {
            $estado = new Estados();
            $estado->setUf($data['uf']);
            $estado->setNome($data['uf']); // Considerar criar uma tabela de estados com nomes completos
            $this->entityManager->persist($estado);
        }
        
        // Persistir cidade
        $cidade = $this->entityManager->getRepository(Cidades::class)->findOneBy([
            'nome' => $data['localidade'],
            'estado' => $estado
        ]);
        if (!$cidade) {
            $cidade = new Cidades();
            $cidade->setNome($data['localidade']);
            $cidade->setEstado($estado);
            $this->entityManager->persist($cidade);
        }
        
        // Persistir bairro
        $bairro = $this->entityManager->getRepository(Bairros::class)->findOneBy([
            'nome' => $data['bairro'],
            'cidade' => $cidade
        ]);
        if (!$bairro) {
            $bairro = new Bairros();
            $bairro->setNome($data['bairro']);
            $bairro->setCidade($cidade);
            $this->entityManager->persist($bairro);
        }
        
        // Persistir logradouro
        $logradouro = new Logradouros();
        $logradouro->setLogradouro($data['logradouro']);
        $logradouro->setCep($cep);
        $logradouro->setBairro($bairro);
        $this->entityManager->persist($logradouro);
        
        $this->entityManager->flush();
        
        return [
            'logradouro' => $data['logradouro'],
            'bairro' => $data['bairro'],
            'cidade' => $data['localidade'],
            'estado' => $data['uf'],
            'idLogradouro' => $logradouro->getId(),
            'idBairro' => $bairro->getId(),
            'idCidade' => $cidade->getId(),
            'idEstado' => $estado->getId(),
        ];
    }
}
