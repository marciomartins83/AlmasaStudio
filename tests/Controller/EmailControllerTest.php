<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmailControllerTest extends WebTestCase
{
    public function testControllerClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Controller\\EmailController'));
    }

    public function testEntityClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Entity\\Emails'));
    }

    public function testFormTypeExists(): void
    {
        $this->assertTrue(class_exists('App\\Form\\EmailType'));
    }

    public function testBasicStructure(): void
    {
        $client = static::createClient();
        
        // Just test that the client can be created
        $this->assertInstanceOf('Symfony\\Bundle\\FrameworkBundle\\KernelBrowser', $client);
    }
}