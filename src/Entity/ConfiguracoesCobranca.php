<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'configuracoes_cobranca')]
class ConfiguracoesCobranca
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idContaBancaria;
    #[ORM\Column]
    private int $carencia;
    #[ORM\Column]
    private int $carenciaCartorio;
    #[ORM\Column]
    private int $carenciaAdvocacia;
    #[ORM\Column(type: 'boolean')]
    private bool $multaItau;
    #[ORM\Column(type: 'boolean')]
    private bool $moraDiaria;
    #[ORM\Column]
    private int $protesto;
    #[ORM\Column]
    private int $tipoDiasProtesto;
    #[ORM\Column]
    private int $diasProtesto;
    #[ORM\Column(type: 'boolean')]
    private bool $naoGerarJudicial;
    #[ORM\Column]
    private int $tipoArquivo;
    #[ORM\Column(type: 'boolean')]
    private bool $variacaoBb;
    #[ORM\Column(type: 'boolean')]
    private bool $mudarEspecie;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdContaBancaria(): int
    {
        return $this->idContaBancaria;
    }

    public function setIdContaBancaria(int $idContaBancaria): self
    {
        $this->idContaBancaria = $idContaBancaria;
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

    public function getCarenciaCartorio(): int
    {
        return $this->carenciaCartorio;
    }

    public function setCarenciaCartorio(int $carenciaCartorio): self
    {
        $this->carenciaCartorio = $carenciaCartorio;
        return $this;
    }

    public function getCarenciaAdvocacia(): int
    {
        return $this->carenciaAdvocacia;
    }

    public function setCarenciaAdvocacia(int $carenciaAdvocacia): self
    {
        $this->carenciaAdvocacia = $carenciaAdvocacia;
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

    public function getTipoDiasProtesto(): int
    {
        return $this->tipoDiasProtesto;
    }

    public function setTipoDiasProtesto(int $tipoDiasProtesto): self
    {
        $this->tipoDiasProtesto = $tipoDiasProtesto;
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

    public function getTipoArquivo(): int
    {
        return $this->tipoArquivo;
    }

    public function setTipoArquivo(int $tipoArquivo): self
    {
        $this->tipoArquivo = $tipoArquivo;
        return $this;
    }

    public function getVariacaoBb(): bool
    {
        return $this->variacaoBb;
    }

    public function setVariacaoBb(bool $variacaoBb): self
    {
        $this->variacaoBb = $variacaoBb;
        return $this;
    }

    public function getMudarEspecie(): bool
    {
        return $this->mudarEspecie;
    }

    public function setMudarEspecie(bool $mudarEspecie): self
    {
        $this->mudarEspecie = $mudarEspecie;
        return $this;
    }

}
