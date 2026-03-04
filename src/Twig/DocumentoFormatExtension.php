<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DocumentoFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_documento', [$this, 'formatDocumento']),
        ];
    }

    public function formatDocumento(?string $documento): string
    {
        if (empty($documento)) {
            return '';
        }

        // Remove caracteres não numéricos
        $numeros = preg_replace('/[^0-9]/', '', $documento);

        if (empty($numeros)) {
            return '';
        }

        $tamanho = strlen($numeros);

        // Detecta o tipo de documento e aplica formatação
        return match ($tamanho) {
            11 => $this->formatarCpf($numeros),
            14 => $this->formatarCnpj($numeros),
            default => $this->formatarRg($documento),
        };
    }

    private function formatarCpf(string $cpf): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2)
        );
    }

    private function formatarCnpj(string $cnpj): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2)
        );
    }

    private function formatarRg(string $documento): string
    {
        // Extrai apenas dígitos e o último caractere que pode ser X
        // Preserva X maiúsculo como dígito verificador
        $limpo = preg_replace('/[^0-9X]/i', '', $documento);

        if (empty($limpo)) {
            return '';
        }

        $tamanho = strlen($limpo);

        // RG brasileiro: X.XXX.XXX-D onde D pode ser número ou X
        // O dígito verificador é sempre o último caractere

        if ($tamanho === 8) {
            // 7 dígitos + 1 DV: X.XXX.XXX-D
            return sprintf(
                '%s.%s.%s-%s',
                substr($limpo, 0, 1),
                substr($limpo, 1, 3),
                substr($limpo, 4, 3),
                substr($limpo, 7, 1)
            );
        }

        if ($tamanho === 9) {
            // 8 dígitos + 1 DV: XX.XXX.XXX-D
            return sprintf(
                '%s.%s.%s-%s',
                substr($limpo, 0, 2),
                substr($limpo, 2, 3),
                substr($limpo, 5, 3),
                substr($limpo, 8, 1)
            );
        }

        if ($tamanho >= 10) {
            // 9+ dígitos + 1 DV: XXX.XXX.XXX-D
            return sprintf(
                '%s.%s.%s-%s',
                substr($limpo, 0, 3),
                substr($limpo, 3, 3),
                substr($limpo, 6, 3),
                substr($limpo, 9, 1)
            );
        }

        // Menos de 8 dígitos: retorna sem formatação
        return $limpo;
    }
}