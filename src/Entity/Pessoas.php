<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\PessoaRepository')]
#[ORM\Table(name: 'pessoas')]
class Pessoas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idpessoa')]
    private ?int $idpessoa = null;
    
    #[ORM\Column]
    private string $nome;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dtCadastro;
    
    #[ORM\Column]
    private int $tipoPessoa;
    
    #[ORM\Column(type: 'boolean')]
    private bool $status;
    
    #[ORM\Column]
    private string $fisicaJuridica;
    
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataNascimento = null;
    
    #[ORM\ManyToOne(targetEntity: EstadoCivil::class)]
    #[ORM\JoinColumn(name: 'estado_civil_id', referencedColumnName: 'id', nullable: true)]
    private ?EstadoCivil $estadoCivil = null;
    
    #[ORM\ManyToOne(targetEntity: Nacionalidade::class)]
    #[ORM\JoinColumn(name: 'nacionalidade_id', referencedColumnName: 'id', nullable: true)]
    private ?Nacionalidade $nacionalidade = null;
    
    #[ORM\ManyToOne(targetEntity: Naturalidade::class)]
    #[ORM\JoinColumn(name: 'naturalidade_id', referencedColumnName: 'id', nullable: true)]
    private ?Naturalidade $naturalidade = null;
    
    #[ORM\Column(nullable: true)]
    private ?string $nomePai = null;
    
    #[ORM\Column(nullable: true)]
    private ?string $nomeMae = null;
    
    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $renda = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;
    
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $themeLight = true;
    
    #[ORM\OneToOne(targetEntity: Users::class, inversedBy: 'pessoa')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $user = null;

    public function getIdpessoa(): ?int
    {
        return $this->idpessoa;
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

    public function getDtCadastro(): \DateTimeInterface
    {
        return $this->dtCadastro;
    }

    public function setDtCadastro(\DateTimeInterface $dtCadastro): self
    {
        $this->dtCadastro = $dtCadastro;
        return $this;
    }

    public function getTipoPessoa(): int
    {
        return $this->tipoPessoa;
    }

    public function setTipoPessoa(int $tipoPessoa): self
    {
        $this->tipoPessoa = $tipoPessoa;
        return $this;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getFisicaJuridica(): string
    {
        return $this->fisicaJuridica;
    }

    public function setFisicaJuridica(string $fisicaJuridica): self
    {
        $this->fisicaJuridica = $fisicaJuridica;
        return $this;
    }

    public function getDataNascimento(): ?\DateTimeInterface
    {
        return $this->dataNascimento;
    }

    public function setDataNascimento(?\DateTimeInterface $dataNascimento): self
    {
        $this->dataNascimento = $dataNascimento;
        return $this;
    }

    public function getEstadoCivil(): ?EstadoCivil
    {
        return $this->estadoCivil;
    }

    public function setEstadoCivil(?EstadoCivil $estadoCivil): self
    {
        $this->estadoCivil = $estadoCivil;
        return $this;
    }

    public function getNacionalidade(): ?Nacionalidade
    {
        return $this->nacionalidade;
    }

    public function setNacionalidade(?Nacionalidade $nacionalidade): self
    {
        $this->nacionalidade = $nacionalidade;
        return $this;
    }

    public function getNaturalidade(): ?Naturalidade
    {
        return $this->naturalidade;
    }

    public function setNaturalidade(?Naturalidade $naturalidade): self
    {
        $this->naturalidade = $naturalidade;
        return $this;
    }

    public function getNomePai(): ?string
    {
        return $this->nomePai;
    }

    public function setNomePai(?string $nomePai): self
    {
        $this->nomePai = $nomePai;
        return $this;
    }

    public function getNomeMae(): ?string
    {
        return $this->nomeMae;
    }

    public function setNomeMae(?string $nomeMae): self
    {
        $this->nomeMae = $nomeMae;
        return $this;
    }

    public function getRenda(): ?string
    {
        return $this->renda;
    }

    public function setRenda(?string $renda): self
    {
        $this->renda = $renda;
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

    public function getThemeLight(): bool
    {
        return $this->themeLight;
    }

    public function isThemeLight(): bool
    {
        return $this->themeLight;
    }

    public function setThemeLight(bool $themeLight): self
    {
        $this->themeLight = $themeLight;
        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Busca o CPF da pessoa através dos documentos
     */
    public function getCpf(): ?string
    {
        // Este método será implementado via repository/service
        // Aqui retornamos null como placeholder
        return null;
    }

    /**
     * Busca o CNPJ da pessoa através dos documentos
     */
    public function getCnpj(): ?string
    {
        // Este método será implementado via repository/service
        // Aqui retornamos null como placeholder
        return null;
    }

    /**
     * Retorna true se a pessoa é física (baseado no campo fisicaJuridica)
     */
    public function isPessoaFisica(): bool
    {
        return $this->fisicaJuridica === 'fisica';
    }

    /**
     * Retorna true se a pessoa é jurídica (baseado no campo fisicaJuridica)
     */
    public function isPessoaJuridica(): bool
    {
        return $this->fisicaJuridica === 'juridica';
    }

}
