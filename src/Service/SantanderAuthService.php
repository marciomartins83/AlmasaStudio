<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ConfiguracoesApiBanco;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de Autenticação OAuth 2.0 com API Santander
 *
 * Gerencia autenticação mTLS com certificado A1 e cache de tokens
 */
class SantanderAuthService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private string $projectDir
    ) {}

    /**
     * Obtém access token via OAuth 2.0 com certificado mTLS
     *
     * @return array{access_token: string, expires_in: int, token_type: string}
     * @throws \RuntimeException se falhar autenticação
     */
    public function obterAccessToken(ConfiguracoesApiBanco $config): array
    {
        // 1. Verificar se token em cache ainda é válido (com margem de 5 minutos)
        if ($this->isTokenValido($config)) {
            $this->logger->debug('[SantanderAuth] Usando token em cache');
            return [
                'access_token' => $config->getAccessToken(),
                'expires_in' => max(0, $config->getTokenExpiraEm()->getTimestamp() - time()),
                'token_type' => 'Bearer'
            ];
        }

        // 2. Validar configuração
        $this->validarConfiguracao($config);

        // 3. Obter novo token
        $tokenData = $this->solicitarNovoToken($config);

        // 4. Salvar token no cache
        $this->salvarTokenCache($config, $tokenData);

        return $tokenData;
    }

    /**
     * Faz requisição autenticada para API Santander
     *
     * @return array{httpCode: int, data: array, raw: string}
     * @throws \RuntimeException em caso de erro
     */
    public function request(
        ConfiguracoesApiBanco $config,
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        // Obter token
        $tokenData = $this->obterAccessToken($config);
        $accessToken = $tokenData['access_token'];

        // Preparar URL
        $baseUrl = rtrim($config->getUrlApi(), '/');
        $endpoint = ltrim($endpoint, '/');
        $url = $baseUrl . '/' . $endpoint;

        // Preparar headers
        $defaultHeaders = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        // Adicionar X-Application-Key se configurado
        if ($config->getClientId()) {
            $defaultHeaders[] = 'X-Application-Key: ' . $config->getClientId();
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        // Log da requisição
        $this->logger->info('[SantanderAPI] Iniciando requisição', [
            'method' => $method,
            'endpoint' => $endpoint,
            'ambiente' => $config->getAmbiente()
        ]);

        // Fazer requisição
        $response = $this->executarRequisicao($config, $method, $url, $data, $allHeaders);

        // Log da resposta
        $this->logger->info('[SantanderAPI] Resposta recebida', [
            'httpCode' => $response['httpCode'],
            'endpoint' => $endpoint
        ]);

        return $response;
    }

    /**
     * Verifica se o token em cache ainda é válido
     */
    private function isTokenValido(ConfiguracoesApiBanco $config): bool
    {
        if (empty($config->getAccessToken()) || $config->getTokenExpiraEm() === null) {
            return false;
        }

        // Considera válido se expira em mais de 5 minutos
        $margemSeguranca = (new \DateTime())->modify('+5 minutes');

        return $config->getTokenExpiraEm() > $margemSeguranca;
    }

    /**
     * Valida se a configuração está completa para autenticação
     */
    private function validarConfiguracao(ConfiguracoesApiBanco $config): void
    {
        if (empty($config->getClientId())) {
            throw new \RuntimeException('Client ID não configurado');
        }

        if (empty($config->getClientSecret())) {
            throw new \RuntimeException('Client Secret não configurado');
        }

        if (empty($config->getCertificadoPath())) {
            throw new \RuntimeException('Certificado não configurado');
        }

        $certPath = $this->getCertificadoPath($config);
        if (!file_exists($certPath)) {
            throw new \RuntimeException('Arquivo de certificado não encontrado: ' . $certPath);
        }

        if (!$config->isCertificadoValido()) {
            throw new \RuntimeException('Certificado expirado');
        }
    }

    /**
     * Solicita novo token OAuth
     */
    private function solicitarNovoToken(ConfiguracoesApiBanco $config): array
    {
        $url = $config->getUrlAutenticacao();
        $certPath = $this->getCertificadoPath($config);
        $certPassword = $config->getCertificadoSenha() ?? '';

        $this->logger->info('[SantanderAuth] Solicitando novo token', [
            'url' => $url,
            'ambiente' => $config->getAmbiente()
        ]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $config->getClientId(),
                'client_secret' => $config->getClientSecret(),
            ]),
            // Certificado mTLS
            CURLOPT_SSLCERT => $certPath,
            CURLOPT_SSLCERTPASSWD => $certPassword,
            CURLOPT_SSLCERTTYPE => 'P12',
            // SSL
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        // Tratar erro de conexão
        if ($error) {
            $this->logger->error('[SantanderAuth] Erro cURL', [
                'error' => $error,
                'errno' => $errno
            ]);
            throw new \RuntimeException('Erro de conexão com API Santander: ' . $error);
        }

        // Parse da resposta
        $data = json_decode($response, true);

        // Tratar erro HTTP
        if ($httpCode !== 200) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? $data['message'] ?? 'Erro desconhecido';
            $this->logger->error('[SantanderAuth] Erro de autenticação', [
                'httpCode' => $httpCode,
                'error' => $errorMsg,
                'response' => $response
            ]);
            throw new \RuntimeException('Erro de autenticação Santander: ' . $errorMsg . ' (HTTP ' . $httpCode . ')');
        }

        // Validar resposta
        if (empty($data['access_token'])) {
            throw new \RuntimeException('Resposta de autenticação inválida: access_token não encontrado');
        }

        $this->logger->info('[SantanderAuth] Token obtido com sucesso', [
            'expires_in' => $data['expires_in'] ?? 'N/A'
        ]);

        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
            'token_type' => $data['token_type'] ?? 'Bearer'
        ];
    }

    /**
     * Salva token no cache (banco de dados)
     */
    private function salvarTokenCache(ConfiguracoesApiBanco $config, array $tokenData): void
    {
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
        $expiraEm = (new \DateTime())->modify("+{$expiresIn} seconds");

        $config->setAccessToken($tokenData['access_token']);
        $config->setTokenExpiraEm($expiraEm);
        $config->setUpdatedAt(new \DateTime());

        $this->em->flush();

        $this->logger->debug('[SantanderAuth] Token salvo no cache', [
            'expira_em' => $expiraEm->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Executa requisição HTTP com certificado mTLS
     */
    private function executarRequisicao(
        ConfiguracoesApiBanco $config,
        string $method,
        string $url,
        array $data,
        array $headers
    ): array {
        $certPath = $this->getCertificadoPath($config);
        $certPassword = $config->getCertificadoSenha() ?? '';

        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            // Certificado mTLS
            CURLOPT_SSLCERT => $certPath,
            CURLOPT_SSLCERTPASSWD => $certPassword,
            CURLOPT_SSLCERTTYPE => 'P12',
            // SSL
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
        ];

        switch (strtoupper($method)) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;

            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;

            case 'PATCH':
                $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;

            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;

            case 'GET':
            default:
                if (!empty($data)) {
                    $options[CURLOPT_URL] = $url . '?' . http_build_query($data);
                }
                break;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('[SantanderAPI] Erro cURL na requisição', [
                'url' => $url,
                'error' => $error,
                'errno' => $errno
            ]);
            throw new \RuntimeException('Erro de conexão: ' . $error);
        }

        $responseData = json_decode($response, true);

        // Log em caso de erro HTTP
        if ($httpCode >= 400) {
            $this->logger->warning('[SantanderAPI] Resposta com erro', [
                'httpCode' => $httpCode,
                'url' => $url,
                'response' => substr($response, 0, 500)
            ]);
        }

        return [
            'httpCode' => $httpCode,
            'data' => $responseData ?? [],
            'raw' => $response,
        ];
    }

    /**
     * Retorna caminho absoluto do certificado
     */
    private function getCertificadoPath(ConfiguracoesApiBanco $config): string
    {
        $path = $config->getCertificadoPath();

        // Se já for caminho absoluto, retorna direto
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Senão, concatena com project dir
        return $this->projectDir . '/' . ltrim($path, '/');
    }

    /**
     * Testa conexão com a API (útil para validar configuração)
     */
    public function testarConexao(ConfiguracoesApiBanco $config): array
    {
        $resultado = [
            'sucesso' => false,
            'mensagem' => '',
            'detalhes' => []
        ];

        try {
            // Validar configuração
            $this->validarConfiguracao($config);
            $resultado['detalhes']['configuracao'] = 'OK';

            // Tentar obter token
            $tokenData = $this->obterAccessToken($config);
            $resultado['detalhes']['autenticacao'] = 'OK';
            $resultado['detalhes']['token_expira_em'] = $tokenData['expires_in'] . ' segundos';

            $resultado['sucesso'] = true;
            $resultado['mensagem'] = 'Conexão com API Santander estabelecida com sucesso';

        } catch (\Exception $e) {
            $resultado['mensagem'] = $e->getMessage();
            $resultado['detalhes']['erro'] = $e->getMessage();
        }

        return $resultado;
    }
}
