<?php

namespace App\DTO;

class SortOptionDTO
{
    public function __construct(
        public readonly string $field,
        public readonly string $label,
        public readonly string $defaultDir = 'ASC',
    ) {}
}
