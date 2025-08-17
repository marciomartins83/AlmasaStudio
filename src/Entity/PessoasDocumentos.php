<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_documentos')]
class PessoasDocumentos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(name: 'id_pessoa', type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(name: 'id_tipo_documento', type: 'bigint')]
    private int $idTipoDocumento;
    #[ORM\Column(name: 'numero_documento')]
    private string $numeroDocumento;
    #[ORM\Column(name: 'data_emissao', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataEmissao = null;
    #[ORM\Column(name: 'data_vencimento', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataVencimento = null;
    #[ORM\Column(name: 'orgao_emissor', nullable: true)]
    private ?string $orgaoEmissor = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPessoa(): int
    {
        return $this->idPessoa;
    }

    public function setIdPessoa(int $idPessoa): self
    {
        $this->idPessoa = $idPessoa;
        return $this;
    }

    public function getIdTipoDocumento(): int
    {
        return $this->idTipoDocumento;
    }

    public function setIdTipoDocumento(int $idTipoDocumento): self
    {
        $this->idTipoDocumento = $idTipoDocumento;
        return $this;
    }

    public function getNumeroDocumento(): string
    {
        return $this->numeroDocumento;
    }

    public function setNumeroDocumento(string $numeroDocumento): self
    {
        $this->numeroDocumento = $numeroDocumento;
        return $this;
    }

    public function getDataEmissao(): ?\DateTimeInterface
    {
        return $this->dataEmissao;
    }

    public function setDataEmissao(?\DateTimeInterface $dataEmissao): self
    {
        $this->dataEmissao = $dataEmissao;
        return $this;
    }

    public function getDataVencimento(): ?\DateTimeInterface
    {
        return $this->dataVencimento;
    }

    public function setDataVencimento(?\DateTimeInterface $dataVencimento): self
    {
        $this->dataVencimento = $dataVencimento;
        return $this;
    }

    public function getOrgaoEmissor(): ?string
    {
        return $this->orgaoEmissor;
    }

    public function setOrgaoEmissor(?string $orgaoEmissor): self
    {
        $this->orgaoEmissor = $orgaoEmissor;
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

    public function getAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): self
    {
        $this->ativo = $ativo;
        return $this;
    }

}
