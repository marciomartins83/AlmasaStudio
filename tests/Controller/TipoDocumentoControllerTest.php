<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TipoDocumentoControllerTest extends WebTestCase
{
    public function testControllerClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Controller\\TipoDocumentoController'));
    }

    public function testEntityClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Entity\\TiposDocumentos'));
    }

    public function testBasicStructure(): void
    {
        $client = static::createClient();
        
        // Just test that the client can be created
        $this->assertInstanceOf('Symfony\\Bundle\\FrameworkBundle\\KernelBrowser', $client);
    }
}