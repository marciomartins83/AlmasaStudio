<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Pessoas;

class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $pessoa = $this->entityManager->getRepository(Pessoas::class)->findOneBy([
            'user' => $this->getUser()
        ]);

        return $this->render('dashboard/index.html.twig', [
            'pessoa' => $pessoa,
        ]);
    }

    #[Route('/enderecos', name: 'app_dashboard_enderecos_index')]
    public function listAddresses(): Response
    {
        return $this->render('dashboard/enderecos/index.html.twig');
    }
}
