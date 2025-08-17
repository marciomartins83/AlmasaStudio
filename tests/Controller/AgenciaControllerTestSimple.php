<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AgenciaControllerTestSimple extends WebTestCase
{
    public function testAgenciaIndexPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/agencia/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Agências');
    }

    public function testAgenciaNewPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/agencia/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
