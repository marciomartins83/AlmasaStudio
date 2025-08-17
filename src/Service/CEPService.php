<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CEPService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function buscarCEP(string $cep): array
    {
        // Remove caracteres não numéricos
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cep) !== 8) {
            return ['success' => false, 'message' => 'CEP inválido'];
        }

        try {
            $response = $this->httpClient->request('GET', "https://viacep.com.br/ws/{$cep}/json/");
            $data = $response->toArray();

            if (isset($data['erro'])) {
                return ['success' => false, 'message' => 'CEP não encontrado'];
            }

            return [
                'success' => true,
                'cep' => $data['cep'],
                'logradouro' => $data['logradouro'],
                'complemento' => $data['complemento'],
                'bairro' => $data['bairro'],
                'cidade' => $data['localidade'],
                'estado' => $data['uf']
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar CEP'];
        }
    }
}
