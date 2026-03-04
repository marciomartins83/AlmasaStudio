<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DocumentoMaskExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mask_documento', [$this, 'maskDocumento']),
        ];
    }

    /**
     * Mascara documentos sensíveis (CPF, RG, CNPJ)
     * 
     * @param string|null $documento Número do documento
     * @param string|null $tipo Tipo do documento (cpf, rg, cnpj) - opcional, detectado automaticamente
     * @return string Documento mascarado ou string vazia
     */
    public function maskDocumento(?string $documento, ?string $tipo = null): string
    {
        if (empty($documento)) {
            return '';
        }

        // Remove máscara existente (pontos, barras, hífens)
        $numeros = preg_replace('/[^0-9]/', '', $documento);
        
        if (empty($numeros)) {
            return '';
        }

        // Detecta o tipo se não informado
        $tipoDetectado = $tipo ?? $this->detectType($numeros);

        return match ($tipoDetectado) {
            'cpf' => $this->maskCpf($numeros),
            'cnpj' => $this->maskCnpj($numeros),
            'rg' => $this->maskRg($numeros),
            default => $documento, // Retorna original se não conseguir identificar
        };
    }

    /**
     * Detecta o tipo de documento baseado no número de dígitos
     */
    private function detectType(string $numeros): string
    {
        $length = strlen($numeros);

        if ($length === 11) {
            return 'cpf';
        }

        if ($length === 14) {
            return 'cnpj';
        }

        // RG pode ter entre 7 e 10 dígitos (varia por estado)
        if ($length >= 7 && $length <= 10) {
            return 'rg';
        }

        return 'unknown';
    }

    /**
     * Mascara CPF: ***.123.456-**
     */
    private function maskCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf('***.%s.%s-**', 
            substr($cpf, 3, 3),
            substr($cpf, -2)
        );
    }

    /**
     * Mascara CNPJ: **.123.456/0001-**
     */
    private function maskCnpj(string $cnpj): string
    {
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return sprintf('**.%s.%s/%s-**',
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, -2)
        );
    }

    /**
     * Mascara RG: *.123.456-*
     */
    private function maskRg(string $rg): string
    {
        $length = strlen($rg);
        
        if ($length < 7) {
            return $rg;
        }

        // Pega primeiro dígito
        $primeiro = $rg[0];
        // Pega 3 dígitos do meio
        $meio = substr($rg, floor($length / 2) - 1, 3);
        // Pega último dígito
        $ultimo = substr($rg, -1);

        return sprintf('%s.%s.%s-%s', $primeiro, $meio, $ultimo, '*');
    }
}