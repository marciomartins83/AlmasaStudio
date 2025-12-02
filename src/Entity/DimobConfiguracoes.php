<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DimobConfiguracoesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DimobConfiguracoesRepository::class)]
#[ORM\Table(name: 'dimob_configuracoes')]
class DimobConfiguracoes
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $ano;

    #[ORM\Column(name: 'cnpj_declarante', type: Types::STRING, length: 18)]
    private string $cnpjDeclarante;

    #[ORM\Column(name: 'cpf_responsavel', type: Types::STRING, length: 14)]
    private string $cpfResponsavel;

    #[ORM\Column(name: 'codigo_cidade', type: Types::STRING, length: 10)]
    private string $codigoCidade;

    #[ORM\Column(name: 'declaracao_retificadora', type: Types::BOOLEAN)]
    private bool $declaracaoRetificadora = false;

    #[ORM\Column(name: 'situacao_especial', type: Types::BOOLEAN)]
    private bool $situacaoEspecial = false;

    #[ORM\Column(name: 'data_geracao', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataGeracao = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->ano = (int) date('Y');
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function setAno(int $ano): self
    {
        $this->ano = $ano;
        return $this;
    }

    public function getCnpjDeclarante(): string
    {
        return $this->cnpjDeclarante;
    }

    public function setCnpjDeclarante(string $cnpjDeclarante): self
    {
        $this->cnpjDeclarante = $cnpjDeclarante;
        return $this;
    }

    /**
     * Retorna CNPJ apenas números
     */
    public function getCnpjDeclaranteNumeros(): string
    {
        return preg_replace('/\D/', '', $this->cnpjDeclarante);
    }

    public function getCpfResponsavel(): string
    {
        return $this->cpfResponsavel;
    }

    public function setCpfResponsavel(string $cpfResponsavel): self
    {
        $this->cpfResponsavel = $cpfResponsavel;
        return $this;
    }

    /**
     * Retorna CPF apenas números
     */
    public function getCpfResponsavelNumeros(): string
    {
        return preg_replace('/\D/', '', $this->cpfResponsavel);
    }

    public function getCodigoCidade(): string
    {
        return $this->codigoCidade;
    }

    public function setCodigoCidade(string $codigoCidade): self
    {
        $this->codigoCidade = $codigoCidade;
        return $this;
    }

    public function isDeclaracaoRetificadora(): bool
    {
        return $this->declaracaoRetificadora;
    }

    public function setDeclaracaoRetificadora(bool $declaracaoRetificadora): self
    {
        $this->declaracaoRetificadora = $declaracaoRetificadora;
        return $this;
    }

    public function isSituacaoEspecial(): bool
    {
        return $this->situacaoEspecial;
    }

    public function setSituacaoEspecial(bool $situacaoEspecial): self
    {
        $this->situacaoEspecial = $situacaoEspecial;
        return $this;
    }

    public function getDataGeracao(): ?\DateTimeInterface
    {
        return $this->dataGeracao;
    }

    public function setDataGeracao(?\DateTimeInterface $dataGeracao): self
    {
        $this->dataGeracao = $dataGeracao;
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
     * Retorna identificador do tipo de declaração para arquivo DIMOB
     */
    public function getTipoDeclaracao(): string
    {
        return $this->declaracaoRetificadora ? 'R' : 'O';
    }

    /**
     * Retorna indicador de situação especial para arquivo DIMOB
     */
    public function getIndicadorSituacaoEspecial(): string
    {
        return $this->situacaoEspecial ? 'S' : 'N';
    }
}
