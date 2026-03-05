<?php

namespace App\Controller;

use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ThemeController - Thin Controller
 * Apenas recebe Request, chama Service e retorna Response
 */
#[Route('/theme')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ThemeController extends AbstractController
{
    private ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    #[Route('/toggle_theme', name: 'toggle_theme', methods: ['POST'])]
    public function toggleTheme(): JsonResponse
    {
        $result = $this->themeService->toggleTheme();

        if (!$result['success']) {
            $statusCode = $result['error'] === 'Usuário não autenticado' ? 403 : 404;
            return new JsonResponse(['error' => $result['error']], $statusCode);
        }

        return new JsonResponse([
            'success' => true,
            'theme' => $result['theme']
        ]);
    }
}
