<?php

namespace App\DTO;

class SearchFilterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
        public readonly ?string $dqlField = null,
        public readonly string $operator = 'LIKE',
        public readonly array $choices = [],
        public readonly ?string $placeholder = null,
        public readonly int $colSize = 2,
        public readonly bool $multiple = false,
    ) {}
}
