<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisRepository::class)]
#[ORM\Table(name: 'imoveis')]
class Imoveis
{
    // IDENTIFICAÇÃO
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'codigo_interno', type: Types::STRING, length: 20, unique: true, nullable: true)]
    private ?string $codigoInterno = null;

    // RELACIONAMENTOS
    #[ORM\ManyToOne(targetEntity: TiposImoveis::class, inversedBy: 'imoveis')]
    #[ORM\JoinColumn(name: 'id_tipo_imovel', referencedColumnName: 'id', nullable: false)]
    private TiposImoveis $tipoImovel;

    #[ORM\ManyToOne(targetEntity: Enderecos::class)]
    #[ORM\JoinColumn(name: 'id_endereco', referencedColumnName: 'id', nullable: false)]
    private Enderecos $endereco;

    #[ORM\ManyToOne(targetEntity: Condominios::class, inversedBy: 'imoveis')]
    #[ORM\JoinColumn(name: 'id_condominio', referencedColumnName: 'id', nullable: true)]
    private ?Condominios $condominio = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_proprietario', referencedColumnName: 'idpessoa', nullable: false)]
    private Pessoas $pessoaProprietario;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_fiador', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaFiador = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_corretor', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaCorretor = null;

    // SITUAÇÃO
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $situacao;

    #[ORM\Column(name: 'tipo_utilizacao', type: Types::STRING, length: 30, nullable: true)]
    private ?string $tipoUtilizacao = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $ocupacao = null;

    #[ORM\Column(name: 'situacao_financeira', type: Types::STRING, length: 30, nullable: true)]
    private ?string $situacaoFinanceira = null;

    #[ORM\Column(name: 'aluguel_garantido', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aluguelGarantido = false;

    #[ORM\Column(name: 'disponivel_aluguel', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $disponivelAluguel = false;

    #[ORM\Column(name: 'disponivel_venda', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $disponivelVenda = false;

    #[ORM\Column(name: 'disponivel_temporada', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $disponivelTemporada = false;

    // CARACTERÍSTICAS FÍSICAS
    #[ORM\Column(name: 'area_total', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $areaTotal = null;

    #[ORM\Column(name: 'area_construida', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $areaConstruida = null;

    #[ORM\Column(name: 'area_privativa', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $areaPrivativa = null;

    #[ORM\Column(name: 'qtd_quartos', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdQuartos = 0;

    #[ORM\Column(name: 'qtd_suites', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdSuites = 0;

    #[ORM\Column(name: 'qtd_banheiros', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdBanheiros = 0;

    #[ORM\Column(name: 'qtd_salas', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdSalas = 0;

    #[ORM\Column(name: 'qtd_vagas_garagem', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdVagasGaragem = 0;

    #[ORM\Column(name: 'qtd_pavimentos', type: Types::INTEGER, options: ['default' => 1])]
    private int $qtdPavimentos = 1;

    // CONSTRUÇÃO
    #[ORM\Column(name: 'ano_construcao', type: Types::INTEGER, nullable: true)]
    private ?int $anoConstrucao = null;

    #[ORM\Column(name: 'data_fundacao', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataFundacao = null;

    #[ORM\Column(name: 'tipo_construcao', type: Types::STRING, length: 30, nullable: true)]
    private ?string $tipoConstrucao = null;

    #[ORM\Column(name: 'aptos_por_andar', type: Types::INTEGER, nullable: true)]
    private ?int $aptosPorAndar = null;

    // VALORES
    #[ORM\Column(name: 'valor_aluguel', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorAluguel = null;

    #[ORM\Column(name: 'valor_venda', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorVenda = null;

    #[ORM\Column(name: 'valor_temporada', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorTemporada = null;

    #[ORM\Column(name: 'valor_condominio', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorCondominio = null;

    #[ORM\Column(name: 'valor_iptu_mensal', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorIptuMensal = null;

    #[ORM\Column(name: 'valor_taxa_lixo', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorTaxaLixo = null;

    #[ORM\Column(name: 'valor_mercado', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorMercado = null;

    #[ORM\Column(name: 'dia_vencimento', type: Types::INTEGER, nullable: true)]
    private ?int $diaVencimento = null;

    // COMISSÕES
    #[ORM\Column(name: 'taxa_administracao', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $taxaAdministracao = null;

    #[ORM\Column(name: 'taxa_minima', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $taxaMinima = null;

    #[ORM\Column(name: 'comissao_locacao', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $comissaoLocacao = null;

    #[ORM\Column(name: 'comissao_venda', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $comissaoVenda = null;

    #[ORM\Column(name: 'comissao_aluguel', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $comissaoAluguel = null;

    #[ORM\Column(name: 'tipo_remuneracao', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoRemuneracao = null;

    // DOCUMENTAÇÃO
    #[ORM\Column(name: 'inscricao_imobiliaria', type: Types::STRING, length: 50, nullable: true)]
    private ?string $inscricaoImobiliaria = null;

    #[ORM\Column(name: 'matricula_cartorio', type: Types::STRING, length: 30, nullable: true)]
    private ?string $matriculaCartorio = null;

    #[ORM\Column(name: 'nome_cartorio', type: Types::STRING, length: 100, nullable: true)]
    private ?string $nomeCartorio = null;

    #[ORM\Column(name: 'nome_contribuinte_iptu', type: Types::STRING, length: 100, nullable: true)]
    private ?string $nomeContribuinteIptu = null;

    // DESCRIÇÃO
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(name: 'descricao_imediacoes', type: Types::TEXT, nullable: true)]
    private ?string $descricaoImediacoes = null;

    // CHAVES
    #[ORM\Column(name: 'tem_chaves', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $temChaves = false;

    #[ORM\Column(name: 'qtd_chaves', type: Types::INTEGER, options: ['default' => 0])]
    private int $qtdChaves = 0;

    #[ORM\Column(name: 'numero_chave', type: Types::STRING, length: 20, nullable: true)]
    private ?string $numeroChave = null;

    #[ORM\Column(name: 'localizacao_chaves', type: Types::STRING, length: 100, nullable: true)]
    private ?string $localizacaoChaves = null;

    #[ORM\Column(name: 'numero_controle_remoto', type: Types::STRING, length: 30, nullable: true)]
    private ?string $numeroControleRemoto = null;

    // PUBLICAÇÃO
    #[ORM\Column(name: 'publicar_site', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $publicarSite = true;

    #[ORM\Column(name: 'publicar_zap', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $publicarZap = false;

    #[ORM\Column(name: 'publicar_vivareal', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $publicarVivareal = false;

    #[ORM\Column(name: 'publicar_gruposp', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $publicarGruposp = false;

    #[ORM\Column(name: 'ocultar_valor_site', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $ocultarValorSite = false;

    #[ORM\Column(name: 'tem_placa', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $temPlaca = false;

    // AUDITORIA
    #[ORM\Column(name: 'data_cadastro', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dataCadastro = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    // COLEÇÕES
    /**
     * @var Collection<int, ImoveisPropriedades>
     */
    #[ORM\OneToMany(targetEntity: ImoveisPropriedades::class, mappedBy: 'imovel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $propriedades;

    /**
     * @var Collection<int, ImoveisMedidores>
     */
    #[ORM\OneToMany(targetEntity: ImoveisMedidores::class, mappedBy: 'imovel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medidores;

    /**
     * @var Collection<int, ImoveisGarantias>
     */
    #[ORM\OneToMany(targetEntity: ImoveisGarantias::class, mappedBy: 'imovel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $garantias;

    /**
     * @var Collection<int, ImoveisFotos>
     */
    #[ORM\OneToMany(targetEntity: ImoveisFotos::class, mappedBy: 'imovel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $fotos;

    /**
     * @var Collection<int, ImoveisContratos>
     */
    #[ORM\OneToMany(targetEntity: ImoveisContratos::class, mappedBy: 'imovel', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contratos;

    public function __construct()
    {
        $this->propriedades = new ArrayCollection();
        $this->medidores = new ArrayCollection();
        $this->garantias = new ArrayCollection();
        $this->fotos = new ArrayCollection();
        $this->contratos = new ArrayCollection();
        $this->dataCadastro = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // GETTERS E SETTERS - IDENTIFICAÇÃO
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigoInterno(): ?string
    {
        return $this->codigoInterno;
    }

    public function setCodigoInterno(?string $codigoInterno): self
    {
        $this->codigoInterno = $codigoInterno;
        return $this;
    }

    // GETTERS E SETTERS - RELACIONAMENTOS
    public function getTipoImovel(): TiposImoveis
    {
        return $this->tipoImovel;
    }

    public function setTipoImovel(TiposImoveis $tipoImovel): self
    {
        $this->tipoImovel = $tipoImovel;
        return $this;
    }

    public function getEndereco(): Enderecos
    {
        return $this->endereco;
    }

    public function setEndereco(Enderecos $endereco): self
    {
        $this->endereco = $endereco;
        return $this;
    }

    public function getCondominio(): ?Condominios
    {
        return $this->condominio;
    }

    public function setCondominio(?Condominios $condominio): self
    {
        $this->condominio = $condominio;
        return $this;
    }

    public function getPessoaProprietario(): Pessoas
    {
        return $this->pessoaProprietario;
    }

    public function setPessoaProprietario(Pessoas $pessoaProprietario): self
    {
        $this->pessoaProprietario = $pessoaProprietario;
        return $this;
    }

    public function getPessoaFiador(): ?Pessoas
    {
        return $this->pessoaFiador;
    }

    public function setPessoaFiador(?Pessoas $pessoaFiador): self
    {
        $this->pessoaFiador = $pessoaFiador;
        return $this;
    }

    public function getPessoaCorretor(): ?Pessoas
    {
        return $this->pessoaCorretor;
    }

    public function setPessoaCorretor(?Pessoas $pessoaCorretor): self
    {
        $this->pessoaCorretor = $pessoaCorretor;
        return $this;
    }

    // GETTERS E SETTERS - SITUAÇÃO
    public function getSituacao(): string
    {
        return $this->situacao;
    }

    public function setSituacao(string $situacao): self
    {
        $this->situacao = $situacao;
        return $this;
    }

    public function getTipoUtilizacao(): ?string
    {
        return $this->tipoUtilizacao;
    }

    public function setTipoUtilizacao(?string $tipoUtilizacao): self
    {
        $this->tipoUtilizacao = $tipoUtilizacao;
        return $this;
    }

    public function getOcupacao(): ?string
    {
        return $this->ocupacao;
    }

    public function setOcupacao(?string $ocupacao): self
    {
        $this->ocupacao = $ocupacao;
        return $this;
    }

    public function getSituacaoFinanceira(): ?string
    {
        return $this->situacaoFinanceira;
    }

    public function setSituacaoFinanceira(?string $situacaoFinanceira): self
    {
        $this->situacaoFinanceira = $situacaoFinanceira;
        return $this;
    }

    public function isAluguelGarantido(): bool
    {
        return $this->aluguelGarantido;
    }

    public function setAluguelGarantido(bool $aluguelGarantido): self
    {
        $this->aluguelGarantido = $aluguelGarantido;
        return $this;
    }

    public function isDisponivelAluguel(): bool
    {
        return $this->disponivelAluguel;
    }

    public function setDisponivelAluguel(bool $disponivelAluguel): self
    {
        $this->disponivelAluguel = $disponivelAluguel;
        return $this;
    }

    public function isDisponivelVenda(): bool
    {
        return $this->disponivelVenda;
    }

    public function setDisponivelVenda(bool $disponivelVenda): self
    {
        $this->disponivelVenda = $disponivelVenda;
        return $this;
    }

    public function isDisponivelTemporada(): bool
    {
        return $this->disponivelTemporada;
    }

    public function setDisponivelTemporada(bool $disponivelTemporada): self
    {
        $this->disponivelTemporada = $disponivelTemporada;
        return $this;
    }

    // GETTERS E SETTERS - CARACTERÍSTICAS FÍSICAS
    public function getAreaTotal(): ?string
    {
        return $this->areaTotal;
    }

    public function setAreaTotal(?string $areaTotal): self
    {
        $this->areaTotal = $areaTotal;
        return $this;
    }

    public function getAreaConstruida(): ?string
    {
        return $this->areaConstruida;
    }

    public function setAreaConstruida(?string $areaConstruida): self
    {
        $this->areaConstruida = $areaConstruida;
        return $this;
    }

    public function getAreaPrivativa(): ?string
    {
        return $this->areaPrivativa;
    }

    public function setAreaPrivativa(?string $areaPrivativa): self
    {
        $this->areaPrivativa = $areaPrivativa;
        return $this;
    }

    public function getQtdQuartos(): int
    {
        return $this->qtdQuartos;
    }

    public function setQtdQuartos(int $qtdQuartos): self
    {
        $this->qtdQuartos = $qtdQuartos;
        return $this;
    }

    public function getQtdSuites(): int
    {
        return $this->qtdSuites;
    }

    public function setQtdSuites(int $qtdSuites): self
    {
        $this->qtdSuites = $qtdSuites;
        return $this;
    }

    public function getQtdBanheiros(): int
    {
        return $this->qtdBanheiros;
    }

    public function setQtdBanheiros(int $qtdBanheiros): self
    {
        $this->qtdBanheiros = $qtdBanheiros;
        return $this;
    }

    public function getQtdSalas(): int
    {
        return $this->qtdSalas;
    }

    public function setQtdSalas(int $qtdSalas): self
    {
        $this->qtdSalas = $qtdSalas;
        return $this;
    }

    public function getQtdVagasGaragem(): int
    {
        return $this->qtdVagasGaragem;
    }

    public function setQtdVagasGaragem(int $qtdVagasGaragem): self
    {
        $this->qtdVagasGaragem = $qtdVagasGaragem;
        return $this;
    }

    public function getQtdPavimentos(): int
    {
        return $this->qtdPavimentos;
    }

    public function setQtdPavimentos(int $qtdPavimentos): self
    {
        $this->qtdPavimentos = $qtdPavimentos;
        return $this;
    }

    // GETTERS E SETTERS - CONSTRUÇÃO
    public function getAnoConstrucao(): ?int
    {
        return $this->anoConstrucao;
    }

    public function setAnoConstrucao(?int $anoConstrucao): self
    {
        $this->anoConstrucao = $anoConstrucao;
        return $this;
    }

    public function getDataFundacao(): ?\DateTimeInterface
    {
        return $this->dataFundacao;
    }

    public function setDataFundacao(?\DateTimeInterface $dataFundacao): self
    {
        $this->dataFundacao = $dataFundacao;
        return $this;
    }

    public function getTipoConstrucao(): ?string
    {
        return $this->tipoConstrucao;
    }

    public function setTipoConstrucao(?string $tipoConstrucao): self
    {
        $this->tipoConstrucao = $tipoConstrucao;
        return $this;
    }

    public function getAptosPorAndar(): ?int
    {
        return $this->aptosPorAndar;
    }

    public function setAptosPorAndar(?int $aptosPorAndar): self
    {
        $this->aptosPorAndar = $aptosPorAndar;
        return $this;
    }

    // GETTERS E SETTERS - VALORES
    public function getValorAluguel(): ?string
    {
        return $this->valorAluguel;
    }

    public function setValorAluguel(?string $valorAluguel): self
    {
        $this->valorAluguel = $valorAluguel;
        return $this;
    }

    public function getValorVenda(): ?string
    {
        return $this->valorVenda;
    }

    public function setValorVenda(?string $valorVenda): self
    {
        $this->valorVenda = $valorVenda;
        return $this;
    }

    public function getValorTemporada(): ?string
    {
        return $this->valorTemporada;
    }

    public function setValorTemporada(?string $valorTemporada): self
    {
        $this->valorTemporada = $valorTemporada;
        return $this;
    }

    public function getValorCondominio(): ?string
    {
        return $this->valorCondominio;
    }

    public function setValorCondominio(?string $valorCondominio): self
    {
        $this->valorCondominio = $valorCondominio;
        return $this;
    }

    public function getValorIptuMensal(): ?string
    {
        return $this->valorIptuMensal;
    }

    public function setValorIptuMensal(?string $valorIptuMensal): self
    {
        $this->valorIptuMensal = $valorIptuMensal;
        return $this;
    }

    public function getValorTaxaLixo(): ?string
    {
        return $this->valorTaxaLixo;
    }

    public function setValorTaxaLixo(?string $valorTaxaLixo): self
    {
        $this->valorTaxaLixo = $valorTaxaLixo;
        return $this;
    }

    public function getValorMercado(): ?string
    {
        return $this->valorMercado;
    }

    public function setValorMercado(?string $valorMercado): self
    {
        $this->valorMercado = $valorMercado;
        return $this;
    }

    public function getDiaVencimento(): ?int
    {
        return $this->diaVencimento;
    }

    public function setDiaVencimento(?int $diaVencimento): self
    {
        $this->diaVencimento = $diaVencimento;
        return $this;
    }

    // GETTERS E SETTERS - COMISSÕES
    public function getTaxaAdministracao(): ?string
    {
        return $this->taxaAdministracao;
    }

    public function setTaxaAdministracao(?string $taxaAdministracao): self
    {
        $this->taxaAdministracao = $taxaAdministracao;
        return $this;
    }

    public function getTaxaMinima(): ?string
    {
        return $this->taxaMinima;
    }

    public function setTaxaMinima(?string $taxaMinima): self
    {
        $this->taxaMinima = $taxaMinima;
        return $this;
    }

    public function getComissaoLocacao(): ?string
    {
        return $this->comissaoLocacao;
    }

    public function setComissaoLocacao(?string $comissaoLocacao): self
    {
        $this->comissaoLocacao = $comissaoLocacao;
        return $this;
    }

    public function getComissaoVenda(): ?string
    {
        return $this->comissaoVenda;
    }

    public function setComissaoVenda(?string $comissaoVenda): self
    {
        $this->comissaoVenda = $comissaoVenda;
        return $this;
    }

    public function getComissaoAluguel(): ?string
    {
        return $this->comissaoAluguel;
    }

    public function setComissaoAluguel(?string $comissaoAluguel): self
    {
        $this->comissaoAluguel = $comissaoAluguel;
        return $this;
    }

    public function getTipoRemuneracao(): ?string
    {
        return $this->tipoRemuneracao;
    }

    public function setTipoRemuneracao(?string $tipoRemuneracao): self
    {
        $this->tipoRemuneracao = $tipoRemuneracao;
        return $this;
    }

    // GETTERS E SETTERS - DOCUMENTAÇÃO
    public function getInscricaoImobiliaria(): ?string
    {
        return $this->inscricaoImobiliaria;
    }

    public function setInscricaoImobiliaria(?string $inscricaoImobiliaria): self
    {
        $this->inscricaoImobiliaria = $inscricaoImobiliaria;
        return $this;
    }

    public function getMatriculaCartorio(): ?string
    {
        return $this->matriculaCartorio;
    }

    public function setMatriculaCartorio(?string $matriculaCartorio): self
    {
        $this->matriculaCartorio = $matriculaCartorio;
        return $this;
    }

    public function getNomeCartorio(): ?string
    {
        return $this->nomeCartorio;
    }

    public function setNomeCartorio(?string $nomeCartorio): self
    {
        $this->nomeCartorio = $nomeCartorio;
        return $this;
    }

    public function getNomeContribuinteIptu(): ?string
    {
        return $this->nomeContribuinteIptu;
    }

    public function setNomeContribuinteIptu(?string $nomeContribuinteIptu): self
    {
        $this->nomeContribuinteIptu = $nomeContribuinteIptu;
        return $this;
    }

    // GETTERS E SETTERS - DESCRIÇÃO
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao;
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

    public function getDescricaoImediacoes(): ?string
    {
        return $this->descricaoImediacoes;
    }

    public function setDescricaoImediacoes(?string $descricaoImediacoes): self
    {
        $this->descricaoImediacoes = $descricaoImediacoes;
        return $this;
    }

    // GETTERS E SETTERS - CHAVES
    public function isTemChaves(): bool
    {
        return $this->temChaves;
    }

    public function setTemChaves(bool $temChaves): self
    {
        $this->temChaves = $temChaves;
        return $this;
    }

    public function getQtdChaves(): int
    {
        return $this->qtdChaves;
    }

    public function setQtdChaves(int $qtdChaves): self
    {
        $this->qtdChaves = $qtdChaves;
        return $this;
    }

    public function getNumeroChave(): ?string
    {
        return $this->numeroChave;
    }

    public function setNumeroChave(?string $numeroChave): self
    {
        $this->numeroChave = $numeroChave;
        return $this;
    }

    public function getLocalizacaoChaves(): ?string
    {
        return $this->localizacaoChaves;
    }

    public function setLocalizacaoChaves(?string $localizacaoChaves): self
    {
        $this->localizacaoChaves = $localizacaoChaves;
        return $this;
    }

    public function getNumeroControleRemoto(): ?string
    {
        return $this->numeroControleRemoto;
    }

    public function setNumeroControleRemoto(?string $numeroControleRemoto): self
    {
        $this->numeroControleRemoto = $numeroControleRemoto;
        return $this;
    }

    // GETTERS E SETTERS - PUBLICAÇÃO
    public function isPublicarSite(): bool
    {
        return $this->publicarSite;
    }

    public function setPublicarSite(bool $publicarSite): self
    {
        $this->publicarSite = $publicarSite;
        return $this;
    }

    public function isPublicarZap(): bool
    {
        return $this->publicarZap;
    }

    public function setPublicarZap(bool $publicarZap): self
    {
        $this->publicarZap = $publicarZap;
        return $this;
    }

    public function isPublicarVivareal(): bool
    {
        return $this->publicarVivareal;
    }

    public function setPublicarVivareal(bool $publicarVivareal): self
    {
        $this->publicarVivareal = $publicarVivareal;
        return $this;
    }

    public function isPublicarGruposp(): bool
    {
        return $this->publicarGruposp;
    }

    public function setPublicarGruposp(bool $publicarGruposp): self
    {
        $this->publicarGruposp = $publicarGruposp;
        return $this;
    }

    public function isOcultarValorSite(): bool
    {
        return $this->ocultarValorSite;
    }

    public function setOcultarValorSite(bool $ocultarValorSite): self
    {
        $this->ocultarValorSite = $ocultarValorSite;
        return $this;
    }

    public function isTemPlaca(): bool
    {
        return $this->temPlaca;
    }

    public function setTemPlaca(bool $temPlaca): self
    {
        $this->temPlaca = $temPlaca;
        return $this;
    }

    // GETTERS E SETTERS - AUDITORIA
    public function getDataCadastro(): ?\DateTimeInterface
    {
        return $this->dataCadastro;
    }

    public function setDataCadastro(\DateTimeInterface $dataCadastro): self
    {
        $this->dataCadastro = $dataCadastro;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // GETTERS E SETTERS - COLEÇÕES
    /**
     * @return Collection<int, ImoveisPropriedades>
     */
    public function getPropriedades(): Collection
    {
        return $this->propriedades;
    }

    public function addPropriedade(ImoveisPropriedades $propriedade): self
    {
        if (!$this->propriedades->contains($propriedade)) {
            $this->propriedades->add($propriedade);
            $propriedade->setImovel($this);
        }

        return $this;
    }

    public function removePropriedade(ImoveisPropriedades $propriedade): self
    {
        if ($this->propriedades->removeElement($propriedade)) {
            if ($propriedade->getImovel() === $this) {
                $propriedade->setImovel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ImoveisMedidores>
     */
    public function getMedidores(): Collection
    {
        return $this->medidores;
    }

    public function addMedidor(ImoveisMedidores $medidor): self
    {
        if (!$this->medidores->contains($medidor)) {
            $this->medidores->add($medidor);
            $medidor->setImovel($this);
        }

        return $this;
    }

    public function removeMedidor(ImoveisMedidores $medidor): self
    {
        if ($this->medidores->removeElement($medidor)) {
            if ($medidor->getImovel() === $this) {
                $medidor->setImovel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ImoveisGarantias>
     */
    public function getGarantias(): Collection
    {
        return $this->garantias;
    }

    public function addGarantia(ImoveisGarantias $garantia): self
    {
        if (!$this->garantias->contains($garantia)) {
            $this->garantias->add($garantia);
            $garantia->setImovel($this);
        }

        return $this;
    }

    public function removeGarantia(ImoveisGarantias $garantia): self
    {
        if ($this->garantias->removeElement($garantia)) {
            if ($garantia->getImovel() === $this) {
                $garantia->setImovel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ImoveisFotos>
     */
    public function getFotos(): Collection
    {
        return $this->fotos;
    }

    public function addFoto(ImoveisFotos $foto): self
    {
        if (!$this->fotos->contains($foto)) {
            $this->fotos->add($foto);
            $foto->setImovel($this);
        }

        return $this;
    }

    public function removeFoto(ImoveisFotos $foto): self
    {
        if ($this->fotos->removeElement($foto)) {
            if ($foto->getImovel() === $this) {
                $foto->setImovel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ImoveisContratos>
     */
    public function getContratos(): Collection
    {
        return $this->contratos;
    }

    public function addContrato(ImoveisContratos $contrato): self
    {
        if (!$this->contratos->contains($contrato)) {
            $this->contratos->add($contrato);
            $contrato->setImovel($this);
        }

        return $this;
    }

    public function removeContrato(ImoveisContratos $contrato): self
    {
        if ($this->contratos->removeElement($contrato)) {
            if ($contrato->getImovel() === $this) {
                $contrato->setImovel(null);
            }
        }

        return $this;
    }
}
