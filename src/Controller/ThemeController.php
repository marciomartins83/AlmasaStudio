<?php

namespace App\Controller;

use App\Entity\Pessoa;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/theme')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ThemeController extends AbstractController
{
    #[Route('/toggle_theme', name: 'toggle_theme', methods: ['POST'])]
    public function toggleTheme(Security $security, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Usuário não autenticado'], 403);
        }

        // Buscar a pessoa vinculada ao usuário
        $pessoa = $entityManager->getRepository(Pessoa::class)->findOneBy(['user' => $user]);

        if (!$pessoa) {
            return new JsonResponse(['error' => 'Pessoa não encontrada'], 404);
        }

        // Alternar o tema e salvar no banco
        $pessoa->setThemeLight(!$pessoa->isThemeLight());
        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'theme' => $pessoa->isThemeLight() ? 'light' : 'dark'
        ]);
    }
}
