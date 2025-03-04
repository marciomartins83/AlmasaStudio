<?php

// src/Controller/EnderecoController.php
namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnderecoController extends AbstractController
{
    #[Route('/dashboard/enderecos', name: 'app_enderecos')]
    public function index(): Response
    {
        return $this->render('dashboard/enderecos/index.html.twig');
    }
}
