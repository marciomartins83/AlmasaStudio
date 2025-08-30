<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function emptyResponse(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
