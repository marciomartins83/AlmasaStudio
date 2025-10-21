<?php

namespace App\Entity;

use App\Repository\PessoasPretendentesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PessoasPretendentesRepository::class)]
#[ORM\Table(name: 'pessoas_pretendentes')]
class PessoasPretendentes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: false)]
    private ?Pessoas $pessoa = null;

    #[ORM\ManyToOne(targetEntity: TiposImoveis::class)]
    #[ORM\JoinColumn(name: 'id_tipo_imovel', referencedColumnName: 'id')]
    private ?TiposImoveis $tipoImovel = null;

    #[ORM\Column(name: 'quartos_desejados', nullable: true)]
    private ?int $quartosDesejados = null;

    #[ORM\Column(name: 'aluguel_maximo', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $aluguelMaximo = null;

    #[ORM\ManyToOne(targetEntity: Logradouros::class)]
    #[ORM\JoinColumn(name: 'id_logradouro_desejado', referencedColumnName: 'id')]
    private ?Logradouros $logradouroDesejado = null;

    #[ORM\Column]
    private ?bool $disponivel = null;

    #[ORM\Column(name: 'procura_aluguel')]
    private ?bool $procuraAluguel = null;

    #[ORM\Column(name: 'procura_compra')]
    private ?bool $procuraCompra = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'id_atendente', referencedColumnName: 'id')]
    private ?Users $atendente = null;

    #[ORM\ManyToOne(targetEntity: TiposAtendimento::class)]
    #[ORM\JoinColumn(name: 'id_tipo_atendimento', referencedColumnName: 'id')]
    private ?TiposAtendimento $tipoAtendimento = null;

    #[ORM\Column(name: 'data_cadastro', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataCadastro = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

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

    public function getTipoImovel(): ?TiposImoveis
    {
        return $this->tipoImovel;
    }

    public function setTipoImovel(?TiposImoveis $tipoImovel): self
    {
        $this->tipoImovel = $tipoImovel;
        return $this;
    }

    public function getQuartosDesejados(): ?int
    {
        return $this->quartosDesejados;
    }

    public function setQuartosDesejados(?int $quartosDesejados): self
    {
        $this->quartosDesejados = $quartosDesejados;
        return $this;
    }

    public function getAluguelMaximo(): ?string
    {
        return $this->aluguelMaximo;
    }

    public function setAluguelMaximo(?string $aluguelMaximo): self
    {
        $this->aluguelMaximo = $aluguelMaximo;
        return $this;
    }

    public function getLogradouroDesejado(): ?Logradouros
    {
        return $this->logradouroDesejado;
    }

    public function setLogradouroDesejado(?Logradouros $logradouroDesejado): self
    {
        $this->logradouroDesejado = $logradouroDesejado;
        return $this;
    }

    public function isDisponivel(): ?bool
    {
        return $this->disponivel;
    }

    public function setDisponivel(bool $disponivel): self
    {
        $this->disponivel = $disponivel;
        return $this;
    }

    public function isProcuraAluguel(): ?bool
    {
        return $this->procuraAluguel;
    }

    public function setProcuraAluguel(bool $procuraAluguel): self
    {
        $this->procuraAluguel = $procuraAluguel;
        return $this;
    }

    public function isProcuraCompra(): ?bool
    {
        return $this->procuraCompra;
    }

    public function setProcuraCompra(bool $procuraCompra): self
    {
        $this->procuraCompra = $procuraCompra;
        return $this;
    }

    public function getAtendente(): ?Users
    {
        return $this->atendente;
    }

    public function setAtendente(?Users $atendente): self
    {
        $this->atendente = $atendente;
        return $this;
    }

    public function getTipoAtendimento(): ?TiposAtendimento
    {
        return $this->tipoAtendimento;
    }

    public function setTipoAtendimento(?TiposAtendimento $tipoAtendimento): self
    {
        $this->tipoAtendimento = $tipoAtendimento;
        return $this;
    }

    public function getDataCadastro(): ?\DateTimeInterface
    {
        return $this->dataCadastro;
    }

    public function setDataCadastro(?\DateTimeInterface $dataCadastro): self
    {
        $this->dataCadastro = $dataCadastro;
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
}
