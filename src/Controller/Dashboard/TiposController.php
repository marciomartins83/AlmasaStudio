<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TiposController extends AbstractController
{
    #[Route('/dashboard/tipos', name: 'app_tipos')]
    public function index(): Response
    {
        return $this->render('dashboard/tipos/index.html.twig');
    }
}
