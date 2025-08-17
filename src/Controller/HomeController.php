<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * Silencia requisições automáticas do Chrome DevTools
     */
    public function chromeDevTools(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT, [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
