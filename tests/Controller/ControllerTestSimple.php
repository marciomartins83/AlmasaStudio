<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ControllerTestSimple extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testDashboardRedirectsToLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/dashboard');

        // Deve redirecionar para login se não autenticado
        $this->assertResponseRedirects();
    }
}
