<?php

namespace App\DTO;

class SortOptionDTO
{
    /**
     * @param string      $field       Campo para identificar a ordenação
     * @param string      $label       Label exibido no sort panel
     * @param string      $defaultDir  Direção padrão (ASC/DESC)
     * @param array|null  $expressions Ordenação composta: [['dqlExpr', 'ASC|DESC|{DIR}']], null = simples
     */
    public function __construct(
        public readonly string $field,
        public readonly string $label,
        public readonly string $defaultDir = 'ASC',
        public readonly ?array $expressions = null,
    ) {}
}
