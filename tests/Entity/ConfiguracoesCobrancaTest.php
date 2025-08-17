<?php

namespace App\Tests\Entity;

use App\Entity\ConfiguracoesCobranca;
use PHPUnit\Framework\TestCase;

class ConfiguracoesCobrancaTest extends TestCase
{
    public function testCreateConfiguracaoCobranca(): void
    {
        $config = new ConfiguracoesCobranca();
        $this->assertInstanceOf(ConfiguracoesCobranca::class, $config);
    }

    public function testBasicMethods(): void
    {
        $config = new ConfiguracoesCobranca();
        $this->assertTrue(method_exists($config, 'getId'));
    }
}
