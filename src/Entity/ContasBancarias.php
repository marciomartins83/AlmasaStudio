<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contas_bancarias')]
class ContasBancarias
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa')]
    private ?Pessoas $idPessoa = null;

    #[ORM\ManyToOne(targetEntity: Bancos::class)]
    #[ORM\JoinColumn(name: 'id_banco', referencedColumnName: 'id')]
    private ?Bancos $idBanco = null;

    #[ORM\ManyToOne(targetEntity: Agencias::class)]
    #[ORM\JoinColumn(name: 'id_agencia', referencedColumnName: 'id')]
    private ?Agencias $idAgencia = null;

    #[ORM\Column]
    private string $codigo;

    #[ORM\Column(nullable: true)]
    private ?string $digitoConta = null;

    #[ORM\ManyToOne(targetEntity: TiposContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_tipo_conta', referencedColumnName: 'id')]
    private ?TiposContasBancarias $idTipoConta = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idRazaoConta = null;
    #[ORM\Column(nullable: true)]
    private ?string $titular = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idDocumentoTitular = null;
    #[ORM\Column(type: 'boolean')]
    private bool $principal;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;
    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $codigoCedente = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idTipoCarteira = null;
    #[ORM\Column(nullable: true)]
    private ?int $numeroInicial = null;
    #[ORM\Column(nullable: true)]
    private ?int $numeroFinal = null;
    #[ORM\Column(nullable: true)]
    private ?int $numeroUsado = null;
    #[ORM\Column(type: 'boolean')]
    private bool $registrada;
    #[ORM\Column(type: 'boolean')]
    private bool $aceitaMultipag;
    #[ORM\Column(nullable: true)]
    private ?string $convenioSicredi = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idTipoRemessa = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idLayoutRemessa = null;
    #[ORM\Column(type: 'boolean')]
    private bool $usaEnderecoCobranca;
    #[ORM\Column(type: 'boolean')]
    private bool $cobrancaCompartilhada;
    #[ORM\Column(nullable: true)]
    private ?string $descricao = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPessoa(): ?Pessoas
    {
        return $this->idPessoa;
    }

    public function setIdPessoa(?Pessoas $idPessoa): self
    {
        $this->idPessoa = $idPessoa;
        return $this;
    }

    public function getIdBanco(): ?Bancos
    {
        return $this->idBanco;
    }

    public function setIdBanco(?Bancos $idBanco): self
    {
        $this->idBanco = $idBanco;
        return $this;
    }

    public function getIdAgencia(): ?Agencias
    {
        return $this->idAgencia;
    }

    public function setIdAgencia(?Agencias $idAgencia): self
    {
        $this->idAgencia = $idAgencia;
        return $this;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getDigitoConta(): ?string
    {
        return $this->digitoConta;
    }

    public function setDigitoConta(?string $digitoConta): self
    {
        $this->digitoConta = $digitoConta;
        return $this;
    }

    public function getIdTipoConta(): ?TiposContasBancarias
    {
        return $this->idTipoConta;
    }

    public function setIdTipoConta(?TiposContasBancarias $idTipoConta): self
    {
        $this->idTipoConta = $idTipoConta;
        return $this;
    }

    public function getIdRazaoConta(): ?int
    {
        return $this->idRazaoConta;
    }

    public function setIdRazaoConta(?int $idRazaoConta): self
    {
        $this->idRazaoConta = $idRazaoConta;
        return $this;
    }

    public function getTitular(): ?string
    {
        return $this->titular;
    }

    public function setTitular(?string $titular): self
    {
        $this->titular = $titular;
        return $this;
    }

    public function getIdDocumentoTitular(): ?int
    {
        return $this->idDocumentoTitular;
    }

    public function setIdDocumentoTitular(?int $idDocumentoTitular): self
    {
        $this->idDocumentoTitular = $idDocumentoTitular;
        return $this;
    }

    public function getPrincipal(): bool
    {
        return $this->principal;
    }

    public function setPrincipal(bool $principal): self
    {
        $this->principal = $principal;
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

    public function getCodigoCedente(): ?string
    {
        return $this->codigoCedente;
    }

    public function setCodigoCedente(?string $codigoCedente): self
    {
        $this->codigoCedente = $codigoCedente;
        return $this;
    }

    public function getIdTipoCarteira(): ?int
    {
        return $this->idTipoCarteira;
    }

    public function setIdTipoCarteira(?int $idTipoCarteira): self
    {
        $this->idTipoCarteira = $idTipoCarteira;
        return $this;
    }

    public function getNumeroInicial(): ?int
    {
        return $this->numeroInicial;
    }

    public function setNumeroInicial(?int $numeroInicial): self
    {
        $this->numeroInicial = $numeroInicial;
        return $this;
    }

    public function getNumeroFinal(): ?int
    {
        return $this->numeroFinal;
    }

    public function setNumeroFinal(?int $numeroFinal): self
    {
        $this->numeroFinal = $numeroFinal;
        return $this;
    }

    public function getNumeroUsado(): ?int
    {
        return $this->numeroUsado;
    }

    public function setNumeroUsado(?int $numeroUsado): self
    {
        $this->numeroUsado = $numeroUsado;
        return $this;
    }

    public function getRegistrada(): bool
    {
        return $this->registrada;
    }

    public function setRegistrada(bool $registrada): self
    {
        $this->registrada = $registrada;
        return $this;
    }

    public function getAceitaMultipag(): bool
    {
        return $this->aceitaMultipag;
    }

    public function setAceitaMultipag(bool $aceitaMultipag): self
    {
        $this->aceitaMultipag = $aceitaMultipag;
        return $this;
    }

    public function getConvenioSicredi(): ?string
    {
        return $this->convenioSicredi;
    }

    public function setConvenioSicredi(?string $convenioSicredi): self
    {
        $this->convenioSicredi = $convenioSicredi;
        return $this;
    }

    public function getIdTipoRemessa(): ?int
    {
        return $this->idTipoRemessa;
    }

    public function setIdTipoRemessa(?int $idTipoRemessa): self
    {
        $this->idTipoRemessa = $idTipoRemessa;
        return $this;
    }

    public function getIdLayoutRemessa(): ?int
    {
        return $this->idLayoutRemessa;
    }

    public function setIdLayoutRemessa(?int $idLayoutRemessa): self
    {
        $this->idLayoutRemessa = $idLayoutRemessa;
        return $this;
    }

    public function getUsaEnderecoCobranca(): bool
    {
        return $this->usaEnderecoCobranca;
    }

    public function setUsaEnderecoCobranca(bool $usaEnderecoCobranca): self
    {
        $this->usaEnderecoCobranca = $usaEnderecoCobranca;
        return $this;
    }

    public function getCobrancaCompartilhada(): bool
    {
        return $this->cobrancaCompartilhada;
    }

    public function setCobrancaCompartilhada(bool $cobrancaCompartilhada): self
    {
        $this->cobrancaCompartilhada = $cobrancaCompartilhada;
        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }

}
