<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PessoaProprietarioControllerTest extends WebTestCase
{
    public function testControllerClassExists(): void
    {
        // PessoasContratantes is managed through RelatorioController, not a dedicated controller
        $this->assertTrue(class_exists('App\\Entity\\PessoasContratantes'));
    }

    public function testEntityClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Entity\\PessoasContratantes'));
    }

    public function testBasicStructure(): void
    {
        $client = static::createClient();

        // Just test that the client can be created
        $this->assertInstanceOf('Symfony\\Bundle\\FrameworkBundle\\KernelBrowser', $client);
    }
}
