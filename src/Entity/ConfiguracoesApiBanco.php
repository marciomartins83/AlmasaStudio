<?php

namespace App\Entity;

use App\Repository\ConfiguracoesApiBancoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfiguracoesApiBancoRepository::class)]
#[ORM\Table(
    name: 'configuracoes_api_banco',
    indexes: [
        new ORM\Index(name: 'idx_config_api_banco_banco', columns: ['banco_id']),
        new ORM\Index(name: 'idx_config_api_banco_conta', columns: ['conta_bancaria_id']),
        new ORM\Index(name: 'idx_config_api_banco_ambiente', columns: ['ambiente']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'idx_config_api_banco_unique', columns: ['banco_id', 'conta_bancaria_id', 'ambiente']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class ConfiguracoesApiBanco
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bancos::class)]
    #[ORM\JoinColumn(name: 'banco_id', referencedColumnName: 'id', nullable: false)]
    private ?Bancos $banco = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'conta_bancaria_id', referencedColumnName: 'id', nullable: false)]
    private ?ContasBancarias $contaBancaria = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientSecret = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $workspaceId = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $certificadoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificadoSenha = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $certificadoValidade = null;

    #[ORM\Column(length: 20)]
    private string $convenio;

    #[ORM\Column(length: 10)]
    private string $carteira = '101';

    #[ORM\Column(length: 20)]
    private string $ambiente = 'sandbox';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlAutenticacao = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlApi = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiraEm = null;

    #[ORM\Column(type: 'boolean')]
    private bool $ativo = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->setUrlsPorAmbiente();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBanco(): ?Bancos
    {
        return $this->banco;
    }

    public function setBanco(?Bancos $banco): self
    {
        $this->banco = $banco;
        return $this;
    }

    public function getContaBancaria(): ?ContasBancarias
    {
        return $this->contaBancaria;
    }

    public function setContaBancaria(?ContasBancarias $contaBancaria): self
    {
        $this->contaBancaria = $contaBancaria;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getWorkspaceId(): ?string
    {
        return $this->workspaceId;
    }

    public function setWorkspaceId(?string $workspaceId): self
    {
        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function getCertificadoPath(): ?string
    {
        return $this->certificadoPath;
    }

    public function setCertificadoPath(?string $certificadoPath): self
    {
        $this->certificadoPath = $certificadoPath;
        return $this;
    }

    public function getCertificadoSenha(): ?string
    {
        return $this->certificadoSenha;
    }

    public function setCertificadoSenha(?string $certificadoSenha): self
    {
        $this->certificadoSenha = $certificadoSenha;
        return $this;
    }

    public function getCertificadoValidade(): ?\DateTimeInterface
    {
        return $this->certificadoValidade;
    }

    public function setCertificadoValidade(?\DateTimeInterface $certificadoValidade): self
    {
        $this->certificadoValidade = $certificadoValidade;
        return $this;
    }

    public function getConvenio(): string
    {
        return $this->convenio;
    }

    public function setConvenio(string $convenio): self
    {
        $this->convenio = $convenio;
        return $this;
    }

    public function getCarteira(): string
    {
        return $this->carteira;
    }

    public function setCarteira(string $carteira): self
    {
        $this->carteira = $carteira;
        return $this;
    }

    public function getAmbiente(): string
    {
        return $this->ambiente;
    }

    public function setAmbiente(string $ambiente): self
    {
        $this->ambiente = $ambiente;
        $this->setUrlsPorAmbiente();
        return $this;
    }

    public function getUrlAutenticacao(): ?string
    {
        return $this->urlAutenticacao;
    }

    public function setUrlAutenticacao(?string $urlAutenticacao): self
    {
        $this->urlAutenticacao = $urlAutenticacao;
        return $this;
    }

    public function getUrlApi(): ?string
    {
        return $this->urlApi;
    }

    public function setUrlApi(?string $urlApi): self
    {
        $this->urlApi = $urlApi;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getTokenExpiraEm(): ?\DateTimeInterface
    {
        return $this->tokenExpiraEm;
    }

    public function setTokenExpiraEm(?\DateTimeInterface $tokenExpiraEm): self
    {
        $this->tokenExpiraEm = $tokenExpiraEm;
        return $this;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): self
    {
        $this->ativo = $ativo;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Verifica se o token de acesso ainda é válido
     */
    public function isTokenValido(): bool
    {
        if (empty($this->accessToken) || $this->tokenExpiraEm === null) {
            return false;
        }

        return $this->tokenExpiraEm > new \DateTime();
    }

    /**
     * Verifica se o certificado está válido (não expirado)
     */
    public function isCertificadoValido(): bool
    {
        if ($this->certificadoValidade === null) {
            return false;
        }

        return $this->certificadoValidade > new \DateTime();
    }

    /**
     * Retorna a URL base da API baseada no ambiente
     */
    public function getUrlBase(): string
    {
        return $this->urlApi ?? '';
    }

    /**
     * Define as URLs automaticamente baseado no ambiente (Santander)
     */
    private function setUrlsPorAmbiente(): void
    {
        if ($this->ambiente === 'producao') {
            $this->urlAutenticacao = 'https://trust.api.santander.com.br/auth/oauth/v2/token';
            $this->urlApi = 'https://trust.api.santander.com.br/collection_bill_management/v2';
        } else {
            $this->urlAutenticacao = 'https://trust-sandbox.api.santander.com.br/auth/oauth/v2/token';
            $this->urlApi = 'https://trust-sandbox.api.santander.com.br/collection_bill_management/v2';
        }
    }

    /**
     * Atualiza o timestamp de updated_at
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
