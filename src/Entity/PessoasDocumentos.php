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
    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa')]
    private ?Pessoas $pessoa = null;
    #[ORM\ManyToOne(targetEntity: TiposDocumentos::class)]
    #[ORM\JoinColumn(name: 'id_tipo_documento', referencedColumnName: 'id')]
    private ?TiposDocumentos $tipoDocumento = null;
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

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): self
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getTipoDocumento(): ?TiposDocumentos
    {
        return $this->tipoDocumento;
    }

    public function setTipoDocumento(?TiposDocumentos $tipoDocumento): self
    {
        $this->tipoDocumento = $tipoDocumento;
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
