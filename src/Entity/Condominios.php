<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CondominiosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CondominiosRepository::class)]
#[ORM\Table(name: 'condominios')]
class Condominios
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $nome;

    #[ORM\ManyToOne(targetEntity: Enderecos::class)]
    #[ORM\JoinColumn(name: 'id_endereco', referencedColumnName: 'id', nullable: false)]
    private Enderecos $endereco;

    #[ORM\Column(type: Types::STRING, length: 18, nullable: true)]
    private ?string $cnpj = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telefone = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'nome_sindico', type: Types::STRING, length: 100, nullable: true)]
    private ?string $nomeSindico = null;

    #[ORM\Column(name: 'valor_condominio', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorCondominio = null;

    #[ORM\Column(name: 'dia_vencimento', type: Types::INTEGER, nullable: true)]
    private ?int $diaVencimento = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, Imoveis>
     */
    #[ORM\OneToMany(targetEntity: Imoveis::class, mappedBy: 'condominio')]
    private Collection $imoveis;

    public function __construct()
    {
        $this->imoveis = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;
        return $this;
    }

    public function getEndereco(): Enderecos
    {
        return $this->endereco;
    }

    public function setEndereco(Enderecos $endereco): self
    {
        $this->endereco = $endereco;
        return $this;
    }

    public function getCnpj(): ?string
    {
        return $this->cnpj;
    }

    public function setCnpj(?string $cnpj): self
    {
        $this->cnpj = $cnpj;
        return $this;
    }

    public function getTelefone(): ?string
    {
        return $this->telefone;
    }

    public function setTelefone(?string $telefone): self
    {
        $this->telefone = $telefone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNomeSindico(): ?string
    {
        return $this->nomeSindico;
    }

    public function setNomeSindico(?string $nomeSindico): self
    {
        $this->nomeSindico = $nomeSindico;
        return $this;
    }

    public function getValorCondominio(): ?string
    {
        return $this->valorCondominio;
    }

    public function setValorCondominio(?string $valorCondominio): self
    {
        $this->valorCondominio = $valorCondominio;
        return $this;
    }

    public function getDiaVencimento(): ?int
    {
        return $this->diaVencimento;
    }

    public function setDiaVencimento(?int $diaVencimento): self
    {
        $this->diaVencimento = $diaVencimento;
        return $this;
    }

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public function setObservacoes(?string $observacoes): self
    {
        $this->observacoes = $observacoes;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Imoveis>
     */
    public function getImoveis(): Collection
    {
        return $this->imoveis;
    }

    public function addImovel(Imoveis $imovel): self
    {
        if (!$this->imoveis->contains($imovel)) {
            $this->imoveis->add($imovel);
            $imovel->setCondominio($this);
        }

        return $this;
    }

    public function removeImovel(Imoveis $imovel): self
    {
        if ($this->imoveis->removeElement($imovel)) {
            if ($imovel->getCondominio() === $this) {
                $imovel->setCondominio(null);
            }
        }

        return $this;
    }
}
