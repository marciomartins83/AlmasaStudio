<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InformesRendimentosValoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InformesRendimentosValoresRepository::class)]
#[ORM\Table(name: 'informes_rendimentos_valores')]
#[ORM\UniqueConstraint(name: 'uk_informes_valores_mes', columns: ['id_informe', 'mes'])]
class InformesRendimentosValores
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InformesRendimentos::class, inversedBy: 'valores')]
    #[ORM\JoinColumn(name: 'id_informe', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?InformesRendimentos $informe = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $mes;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valor = '0.00';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInforme(): ?InformesRendimentos
    {
        return $this->informe;
    }

    public function setInforme(?InformesRendimentos $informe): self
    {
        $this->informe = $informe;
        return $this;
    }

    public function getMes(): int
    {
        return $this->mes;
    }

    public function setMes(int $mes): self
    {
        if ($mes < 1 || $mes > 12) {
            throw new \InvalidArgumentException('Mês deve estar entre 1 e 12');
        }
        $this->mes = $mes;
        return $this;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function setValor(string $valor): self
    {
        $this->valor = $valor;
        return $this;
    }

    public function getValorFloat(): float
    {
        return (float) $this->valor;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Retorna nome do mês em português
     */
    public function getMesNome(): string
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $meses[$this->mes] ?? '';
    }

    /**
     * Retorna abreviação do mês
     */
    public function getMesAbreviado(): string
    {
        $meses = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return $meses[$this->mes] ?? '';
    }
}
