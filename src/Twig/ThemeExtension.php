<?php

namespace App\Twig;

use App\Service\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class ThemeExtension extends AbstractExtension implements GlobalsInterface
{
    private ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function getGlobals(): array
    {
        return [
            'theme' => $this->themeService->getCurrentTheme(),
            'is_light_theme' => $this->themeService->isLightTheme(),
            'is_dark_theme' => $this->themeService->isDarkTheme(),
        ];
    }
}