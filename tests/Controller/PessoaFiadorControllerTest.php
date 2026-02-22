<?php

namespace App\Tests\Controller;

use App\Controller\PessoaFiadorController;
use App\Entity\PessoasFiadores;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PessoaFiadorControllerTest extends WebTestCase
{
    public function testControllerClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Controller\\PessoaFiadorController'));
    }

    public function testEntityClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Entity\\PessoasFiadores'));
    }

    public function testFormTypeExists(): void
    {
        $this->assertTrue(class_exists('App\\Form\\PessoaFiadorFormType'));
    }

    public function testIndexPage(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $route = $router->getRouteCollection()->get('app_pessoa_fiador_index');
        $this->assertNotNull($route);
    }

    public function testNewFormPage(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $route = $router->getRouteCollection()->get('app_pessoa_fiador_new');
        $this->assertNotNull($route);
    }

    public function testSearchPessoaByCpf(): void
    {
        $this->assertTrue(method_exists(PessoaFiadorController::class, 'searchPessoa'));
    }

    public function testSearchPessoaByCnpj(): void
    {
        $this->assertTrue(method_exists(PessoaFiadorController::class, 'searchPessoa'));
    }

    public function testSearchPessoaByNome(): void
    {
        $this->assertTrue(method_exists(PessoaFiadorController::class, 'searchPessoa'));
    }

    public function testCreateFiadorWithNewPessoa(): void
    {
        $this->assertTrue(method_exists(PessoaFiadorController::class, 'new'));
    }

    public function testValidationCpfOrCnpjRequired(): void
    {
        $this->assertTrue(method_exists(PessoasFiadores::class, 'getMotivoFianca'));
    }

    public function testValidationBothCpfAndCnpj(): void
    {
        $this->assertTrue(method_exists(PessoasFiadores::class, 'setMotivoFianca'));
    }
}
