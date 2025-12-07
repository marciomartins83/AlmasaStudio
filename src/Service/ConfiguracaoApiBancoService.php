<?php

namespace App\Service;

use App\Entity\ConfiguracoesApiBanco;
use App\Repository\ConfiguracoesApiBancoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfiguracaoApiBancoService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ConfiguracoesApiBancoRepository $repository;
    private string $certificatesDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ConfiguracoesApiBancoRepository $repository,
        string $projectDir
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->certificatesDir = $projectDir . '/var/certificates';
    }

    /**
     * Salva uma configuração de API bancária
     */
    public function salvar(ConfiguracoesApiBanco $config, ?UploadedFile $certificado = null): ConfiguracoesApiBanco
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            if ($certificado !== null) {
                $this->processarCertificado($config, $certificado);
            }

            $config->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($config);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('[ConfiguracaoApiBanco] Configuração salva com sucesso', [
                'id' => $config->getId(),
                'ambiente' => $config->getAmbiente(),
                'convenio' => $config->getConvenio()
            ]);

            return $config;

        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('[ConfiguracaoApiBanco] Erro ao salvar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lista todas as configurações
     */
    public function listar(): array
    {
        return $this->repository->findAllWithRelations();
    }

    /**
     * Busca configuração por ID
     */
    public function buscarPorId(int $id): ?ConfiguracoesApiBanco
    {
        return $this->repository->find($id);
    }

    /**
     * Busca configuração por conta bancária
     */
    public function buscarPorContaBancaria(int $contaBancariaId): ?ConfiguracoesApiBanco
    {
        return $this->repository->findByContaBancaria($contaBancariaId);
    }

    /**
     * Busca configuração por conta bancária e ambiente
     */
    public function buscarPorContaBancariaEAmbiente(int $contaBancariaId, string $ambiente): ?ConfiguracoesApiBanco
    {
        return $this->repository->findByContaBancariaEAmbiente($contaBancariaId, $ambiente);
    }

    /**
     * Deleta uma configuração
     */
    public function deletar(int $id): bool
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $config = $this->repository->find($id);

            if (!$config) {
                return false;
            }

            if ($config->getCertificadoPath()) {
                $this->removerArquivoCertificado($config->getCertificadoPath());
            }

            $this->entityManager->remove($config);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('[ConfiguracaoApiBanco] Configuração deletada', ['id' => $id]);

            return true;

        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('[ConfiguracaoApiBanco] Erro ao deletar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida um certificado A1 (.pfx ou .p12)
     *
     * @return array{valido: bool, validade: ?\DateTime, titular: ?string, erro: ?string}
     */
    public function validarCertificado(string $path, string $senha): array
    {
        $resultado = [
            'valido' => false,
            'validade' => null,
            'titular' => null,
            'erro' => null
        ];

        if (!file_exists($path)) {
            $resultado['erro'] = 'Arquivo de certificado não encontrado';
            return $resultado;
        }

        $pfxContent = file_get_contents($path);
        $certs = [];

        if (!openssl_pkcs12_read($pfxContent, $certs, $senha)) {
            $resultado['erro'] = 'Senha do certificado inválida ou arquivo corrompido';
            return $resultado;
        }

        $certInfo = openssl_x509_parse($certs['cert']);

        if (!$certInfo) {
            $resultado['erro'] = 'Não foi possível ler as informações do certificado';
            return $resultado;
        }

        $validTo = $certInfo['validTo_time_t'] ?? null;

        if ($validTo) {
            $validadeDate = (new \DateTime())->setTimestamp($validTo);
            $resultado['validade'] = $validadeDate;
            $resultado['valido'] = $validadeDate > new \DateTime();
        }

        $subject = $certInfo['subject'] ?? [];
        $titular = $subject['CN'] ?? $subject['O'] ?? 'Não identificado';
        $resultado['titular'] = $titular;

        if (!$resultado['valido'] && $resultado['validade']) {
            $resultado['erro'] = 'Certificado expirado em ' . $resultado['validade']->format('d/m/Y');
        }

        return $resultado;
    }

    /**
     * Valida certificado a partir de UploadedFile
     *
     * @return array{valido: bool, validade: ?\DateTime, titular: ?string, erro: ?string}
     */
    public function validarCertificadoUpload(UploadedFile $arquivo, string $senha): array
    {
        $tempPath = $arquivo->getPathname();
        return $this->validarCertificado($tempPath, $senha);
    }

    /**
     * Busca configurações com certificados próximos de expirar
     */
    public function buscarCertificadosExpirando(int $dias = 30): array
    {
        return $this->repository->findCertificadosExpirando($dias);
    }

    /**
     * Atualiza o token de acesso
     */
    public function atualizarToken(ConfiguracoesApiBanco $config, string $token, int $expiresIn): void
    {
        $expiraEm = (new \DateTime())->modify("+{$expiresIn} seconds");

        $config->setAccessToken($token);
        $config->setTokenExpiraEm($expiraEm);
        $config->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->logger->info('[ConfiguracaoApiBanco] Token atualizado', [
            'id' => $config->getId(),
            'expira_em' => $expiraEm->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verifica se existe configuração duplicada (mesma conta + ambiente)
     */
    public function existeConfiguracaoDuplicada(int $contaBancariaId, string $ambiente, ?int $excludeId = null): bool
    {
        $existente = $this->repository->findByContaBancariaEAmbiente($contaBancariaId, $ambiente);

        if (!$existente) {
            return false;
        }

        if ($excludeId !== null && $existente->getId() === $excludeId) {
            return false;
        }

        return true;
    }

    /**
     * Processa upload do certificado
     */
    private function processarCertificado(ConfiguracoesApiBanco $config, UploadedFile $certificado): void
    {
        $this->garantirDiretorioCertificados();

        $validacao = $this->validarCertificadoUpload($certificado, $config->getCertificadoSenha() ?? '');

        if (!$validacao['valido'] && $validacao['erro']) {
            throw new \RuntimeException($validacao['erro']);
        }

        if ($config->getCertificadoPath()) {
            $this->removerArquivoCertificado($config->getCertificadoPath());
        }

        $novoNome = sprintf(
            'cert_%s_%s.%s',
            $config->getContaBancaria()->getId(),
            uniqid(),
            $certificado->guessExtension() ?? 'pfx'
        );

        $certificado->move($this->certificatesDir, $novoNome);

        $config->setCertificadoPath($this->certificatesDir . '/' . $novoNome);
        $config->setCertificadoValidade($validacao['validade']);

        $this->logger->info('[ConfiguracaoApiBanco] Certificado processado', [
            'arquivo' => $novoNome,
            'validade' => $validacao['validade']?->format('Y-m-d'),
            'titular' => $validacao['titular']
        ]);
    }

    /**
     * Garante que o diretório de certificados existe
     */
    private function garantirDiretorioCertificados(): void
    {
        if (!is_dir($this->certificatesDir)) {
            if (!mkdir($this->certificatesDir, 0700, true)) {
                throw new \RuntimeException('Não foi possível criar o diretório de certificados');
            }
        }

        $htaccess = $this->certificatesDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    /**
     * Remove arquivo de certificado do sistema de arquivos
     */
    private function removerArquivoCertificado(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
            $this->logger->info('[ConfiguracaoApiBanco] Certificado removido', ['path' => $path]);
        }
    }
}
