<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PessoaLocadorRepository;

#[ORM\Entity(repositoryClass: PessoaLocadorRepository::class)]
#[ORM\Table(name: 'pessoas_locadores')]
class PessoasLocadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: false)]
    private ?Pessoas $pessoa = null;

    #[ORM\ManyToOne(targetEntity: FormasRetirada::class)]
    #[ORM\JoinColumn(name: 'id_forma_retirada', referencedColumnName: 'id')]
    private ?FormasRetirada $formaRetirada = null;

    #[ORM\Column]
    private ?int $dependentes = 0;

    #[ORM\Column(nullable: true, name: 'dia_retirada')]
    private ?int $diaRetirada = null;

    #[ORM\Column(name: 'cobrar_cpmf')]
    private ?bool $cobrarCpmf = false;

    #[ORM\Column]
    private ?int $situacao = 0;

    #[ORM\Column(nullable: true, name: 'codigo_contabil')]
    private ?int $codigoContabil = null;

    #[ORM\Column]
    private ?bool $etiqueta = true;

    #[ORM\Column(name: 'cobrar_tarifa_rec')]
    private ?bool $cobrarTarifaRec = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, name: 'data_fechamento')]
    private ?\DateTimeInterface $dataFechamento = null;

    #[ORM\Column]
    private ?int $carencia = 0;

    #[ORM\Column(name: 'multa_itau')]
    private ?bool $multaItau = false;

    #[ORM\Column(name: 'mora_diaria')]
    private ?bool $moraDiaria = false;

    #[ORM\Column]
    private ?int $protesto = 0;

    #[ORM\Column(name: 'dias_protesto')]
    private ?int $diasProtesto = 0;

    #[ORM\Column(name: 'nao_gerar_judicial')]
    private ?bool $naoGerarJudicial = false;

    #[ORM\Column(name: 'endereco_cobranca')]
    private ?bool $enderecoCobranca = false;

    #[ORM\Column(name: 'condominio_conta')]
    private ?bool $condominioConta = false;

    #[ORM\Column(name: 'ext_email')]
    private ?bool $extEmail = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): static
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getFormaRetirada(): ?FormasRetirada
    {
        return $this->formaRetirada;
    }

    public function setFormaRetirada(?FormasRetirada $formaRetirada): static
    {
        $this->formaRetirada = $formaRetirada;
        return $this;
    }

    public function getDependentes(): ?int
    {
        return $this->dependentes;
    }

    public function setDependentes(int $dependentes): static
    {
        $this->dependentes = $dependentes;
        return $this;
    }

    public function getDiaRetirada(): ?int
    {
        return $this->diaRetirada;
    }

    public function setDiaRetirada(?int $diaRetirada): static
    {
        $this->diaRetirada = $diaRetirada;
        return $this;
    }

    public function isCobrarCpmf(): ?bool
    {
        return $this->cobrarCpmf;
    }

    public function setCobrarCpmf(bool $cobrarCpmf): static
    {
        $this->cobrarCpmf = $cobrarCpmf;
        return $this;
    }

    public function getSituacao(): ?int
    {
        return $this->situacao;
    }

    public function setSituacao(int $situacao): static
    {
        $this->situacao = $situacao;
        return $this;
    }

    public function getCodigoContabil(): ?int
    {
        return $this->codigoContabil;
    }

    public function setCodigoContabil(?int $codigoContabil): static
    {
        $this->codigoContabil = $codigoContabil;
        return $this;
    }

    public function isEtiqueta(): ?bool
    {
        return $this->etiqueta;
    }

    public function setEtiqueta(bool $etiqueta): static
    {
        $this->etiqueta = $etiqueta;
        return $this;
    }

    public function isCobrarTarifaRec(): ?bool
    {
        return $this->cobrarTarifaRec;
    }

    public function setCobrarTarifaRec(bool $cobrarTarifaRec): static
    {
        $this->cobrarTarifaRec = $cobrarTarifaRec;
        return $this;
    }

    public function getDataFechamento(): ?\DateTimeInterface
    {
        return $this->dataFechamento;
    }

    public function setDataFechamento(?\DateTimeInterface $dataFechamento): static
    {
        $this->dataFechamento = $dataFechamento;
        return $this;
    }

    public function getCarencia(): ?int
    {
        return $this->carencia;
    }

    public function setCarencia(int $carencia): static
    {
        $this->carencia = $carencia;
        return $this;
    }

    public function isMultaItau(): ?bool
    {
        return $this->multaItau;
    }

    public function setMultaItau(bool $multaItau): static
    {
        $this->multaItau = $multaItau;
        return $this;
    }

    public function isMoraDiaria(): ?bool
    {
        return $this->moraDiaria;
    }

    public function setMoraDiaria(bool $moraDiaria): static
    {
        $this->moraDiaria = $moraDiaria;
        return $this;
    }

    public function getProtesto(): ?int
    {
        return $this->protesto;
    }

    public function setProtesto(int $protesto): static
    {
        $this->protesto = $protesto;
        return $this;
    }

    public function getDiasProtesto(): ?int
    {
        return $this->diasProtesto;
    }

    public function setDiasProtesto(int $diasProtesto): static
    {
        $this->diasProtesto = $diasProtesto;
        return $this;
    }

    public function isNaoGerarJudicial(): ?bool
    {
        return $this->naoGerarJudicial;
    }

    public function setNaoGerarJudicial(bool $naoGerarJudicial): static
    {
        $this->naoGerarJudicial = $naoGerarJudicial;
        return $this;
    }

    public function isEnderecoCobranca(): ?bool
    {
        return $this->enderecoCobranca;
    }

    public function setEnderecoCobranca(bool $enderecoCobranca): static
    {
        $this->enderecoCobranca = $enderecoCobranca;
        return $this;
    }

    public function isCondominioConta(): ?bool
    {
        return $this->condominioConta;
    }

    public function setCondominioConta(bool $condominioConta): static
    {
        $this->condominioConta = $condominioConta;
        return $this;
    }

    public function isExtEmail(): ?bool
    {
        return $this->extEmail;
    }

    public function setExtEmail(bool $extEmail): static
    {
        $this->extEmail = $extEmail;
        return $this;
    }
}
