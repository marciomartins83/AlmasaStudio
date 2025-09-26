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
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]  // EXIGE QUE O USUÁRIO ESTEJA LOGADO
    public function index(EntityManagerInterface $entityManager): Response
    {
        $pessoa = $this->entityManager->getRepository(Pessoas::class)->findOneBy([
            'user' => $this->getUser()
        ]);

        return $this->render('dashboard/index.html.twig', [
            'pessoa' => $pessoa, // Enviando para o Twig
        ]);
    }

    #[Route('/enderecos', name: 'app_dashboard_enderecos_index')]
    public function listAddresses(): Response
    {
        // Implement logic to fetch and display addresses here
        return $this->render('dashboard/enderecos/index.html.twig');
    }
}
