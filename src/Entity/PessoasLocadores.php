<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_locadores')]
class PessoasLocadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idFormaRetirada = null;
    #[ORM\Column]
    private int $dependentes;
    #[ORM\Column(nullable: true)]
    private ?int $diaRetirada = null;
    #[ORM\Column(type: 'boolean')]
    private bool $cobrarCpmf;
    #[ORM\Column]
    private int $situacao;
    #[ORM\Column(nullable: true)]
    private ?int $codigoContabil = null;
    #[ORM\Column(type: 'boolean')]
    private bool $etiqueta;
    #[ORM\Column(type: 'boolean')]
    private bool $cobrarTarifaRec;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataFechamento = null;
    #[ORM\Column]
    private int $carencia;
    #[ORM\Column(type: 'boolean')]
    private bool $multaItau;
    #[ORM\Column(type: 'boolean')]
    private bool $moraDiaria;
    #[ORM\Column]
    private int $protesto;
    #[ORM\Column]
    private int $diasProtesto;
    #[ORM\Column(type: 'boolean')]
    private bool $naoGerarJudicial;
    #[ORM\Column(type: 'boolean')]
    private bool $enderecoCobranca;
    #[ORM\Column(type: 'boolean')]
    private bool $condominioConta;
    #[ORM\Column(type: 'boolean')]
    private bool $extEmail;

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

    public function getIdFormaRetirada(): ?int
    {
        return $this->idFormaRetirada;
    }

    public function setIdFormaRetirada(?int $idFormaRetirada): self
    {
        $this->idFormaRetirada = $idFormaRetirada;
        return $this;
    }

    public function getDependentes(): int
    {
        return $this->dependentes;
    }

    public function setDependentes(int $dependentes): self
    {
        $this->dependentes = $dependentes;
        return $this;
    }

    public function getDiaRetirada(): ?int
    {
        return $this->diaRetirada;
    }

    public function setDiaRetirada(?int $diaRetirada): self
    {
        $this->diaRetirada = $diaRetirada;
        return $this;
    }

    public function getCobrarCpmf(): bool
    {
        return $this->cobrarCpmf;
    }

    public function setCobrarCpmf(bool $cobrarCpmf): self
    {
        $this->cobrarCpmf = $cobrarCpmf;
        return $this;
    }

    public function getSituacao(): int
    {
        return $this->situacao;
    }

    public function setSituacao(int $situacao): self
    {
        $this->situacao = $situacao;
        return $this;
    }

    public function getCodigoContabil(): ?int
    {
        return $this->codigoContabil;
    }

    public function setCodigoContabil(?int $codigoContabil): self
    {
        $this->codigoContabil = $codigoContabil;
        return $this;
    }

    public function getEtiqueta(): bool
    {
        return $this->etiqueta;
    }

    public function setEtiqueta(bool $etiqueta): self
    {
        $this->etiqueta = $etiqueta;
        return $this;
    }

    public function getCobrarTarifaRec(): bool
    {
        return $this->cobrarTarifaRec;
    }

    public function setCobrarTarifaRec(bool $cobrarTarifaRec): self
    {
        $this->cobrarTarifaRec = $cobrarTarifaRec;
        return $this;
    }

    public function getDataFechamento(): ?\DateTimeInterface
    {
        return $this->dataFechamento;
    }

    public function setDataFechamento(?\DateTimeInterface $dataFechamento): self
    {
        $this->dataFechamento = $dataFechamento;
        return $this;
    }

    public function getCarencia(): int
    {
        return $this->carencia;
    }

    public function setCarencia(int $carencia): self
    {
        $this->carencia = $carencia;
        return $this;
    }

    public function getMultaItau(): bool
    {
        return $this->multaItau;
    }

    public function setMultaItau(bool $multaItau): self
    {
        $this->multaItau = $multaItau;
        return $this;
    }

    public function getMoraDiaria(): bool
    {
        return $this->moraDiaria;
    }

    public function setMoraDiaria(bool $moraDiaria): self
    {
        $this->moraDiaria = $moraDiaria;
        return $this;
    }

    public function getProtesto(): int
    {
        return $this->protesto;
    }

    public function setProtesto(int $protesto): self
    {
        $this->protesto = $protesto;
        return $this;
    }

    public function getDiasProtesto(): int
    {
        return $this->diasProtesto;
    }

    public function setDiasProtesto(int $diasProtesto): self
    {
        $this->diasProtesto = $diasProtesto;
        return $this;
    }

    public function getNaoGerarJudicial(): bool
    {
        return $this->naoGerarJudicial;
    }

    public function setNaoGerarJudicial(bool $naoGerarJudicial): self
    {
        $this->naoGerarJudicial = $naoGerarJudicial;
        return $this;
    }

    public function getEnderecoCobranca(): bool
    {
        return $this->enderecoCobranca;
    }

    public function setEnderecoCobranca(bool $enderecoCobranca): self
    {
        $this->enderecoCobranca = $enderecoCobranca;
        return $this;
    }

    public function getCondominioConta(): bool
    {
        return $this->condominioConta;
    }

    public function setCondominioConta(bool $condominioConta): self
    {
        $this->condominioConta = $condominioConta;
        return $this;
    }

    public function getExtEmail(): bool
    {
        return $this->extEmail;
    }

    public function setExtEmail(bool $extEmail): self
    {
        $this->extEmail = $extEmail;
        return $this;
    }

}
