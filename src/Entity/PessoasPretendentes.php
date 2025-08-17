<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_pretendentes')]
class PessoasPretendentes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idTipoImovel = null;
    #[ORM\Column(nullable: true)]
    private ?int $quartosDesejados = null;
    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $aluguelMaximo = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idLogradouroDesejado = null;
    #[ORM\Column(type: 'boolean')]
    private bool $disponivel;
    #[ORM\Column(type: 'boolean')]
    private bool $procuraAluguel;
    #[ORM\Column(type: 'boolean')]
    private bool $procuraCompra;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idAtendente = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idTipoAtendimento = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataCadastro = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;

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

    public function getIdTipoImovel(): ?int
    {
        return $this->idTipoImovel;
    }

    public function setIdTipoImovel(?int $idTipoImovel): self
    {
        $this->idTipoImovel = $idTipoImovel;
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

    public function getIdLogradouroDesejado(): ?int
    {
        return $this->idLogradouroDesejado;
    }

    public function setIdLogradouroDesejado(?int $idLogradouroDesejado): self
    {
        $this->idLogradouroDesejado = $idLogradouroDesejado;
        return $this;
    }

    public function getDisponivel(): bool
    {
        return $this->disponivel;
    }

    public function setDisponivel(bool $disponivel): self
    {
        $this->disponivel = $disponivel;
        return $this;
    }

    public function getProcuraAluguel(): bool
    {
        return $this->procuraAluguel;
    }

    public function setProcuraAluguel(bool $procuraAluguel): self
    {
        $this->procuraAluguel = $procuraAluguel;
        return $this;
    }

    public function getProcuraCompra(): bool
    {
        return $this->procuraCompra;
    }

    public function setProcuraCompra(bool $procuraCompra): self
    {
        $this->procuraCompra = $procuraCompra;
        return $this;
    }

    public function getIdAtendente(): ?int
    {
        return $this->idAtendente;
    }

    public function setIdAtendente(?int $idAtendente): self
    {
        $this->idAtendente = $idAtendente;
        return $this;
    }

    public function getIdTipoAtendimento(): ?int
    {
        return $this->idTipoAtendimento;
    }

    public function setIdTipoAtendimento(?int $idTipoAtendimento): self
    {
        $this->idTipoAtendimento = $idTipoAtendimento;
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
