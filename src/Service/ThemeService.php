<?php

namespace App\Service;

use App\Entity\Pessoas;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ThemeService
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * Retorna o tema atual do usuário logado.
     * Retorna 'dark' como padrão se o usuário não estiver logado ou não tiver pessoa vinculada.
     *
     * @return string 'light' ou 'dark'
     */
    public function getCurrentTheme(): string
    {
        $user = $this->security->getUser();

        if (!$user) {
            return 'dark';
        }

        $pessoa = $this->entityManager->getRepository(Pessoas::class)->findOneBy(['user' => $user]);

        if (!$pessoa) {
            return 'dark';
        }

        return $pessoa->isThemeLight() ? 'light' : 'dark';
    }

    /**
     * Verifica se o tema atual é claro.
     *
     * @return bool
     */
    public function isLightTheme(): bool
    {
        return $this->getCurrentTheme() === 'light';
    }

    /**
     * Verifica se o tema atual é escuro.
     *
     * @return bool
     */
    public function isDarkTheme(): bool
    {
        return $this->getCurrentTheme() === 'dark';
    }

    /**
     * Alterna o tema do usuário logado (light/dark).
     *
     * @return array ['success' => bool, 'theme' => string, 'error' => string|null]
     */
    public function toggleTheme(): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return ['success' => false, 'theme' => 'dark', 'error' => 'Usuário não autenticado'];
        }

        $pessoa = $this->entityManager->getRepository(Pessoas::class)->findOneBy(['user' => $user]);

        if (!$pessoa) {
            return ['success' => false, 'theme' => 'dark', 'error' => 'Pessoa não encontrada'];
        }

        // Alternar o tema e salvar no banco
        $pessoa->setThemeLight(!$pessoa->isThemeLight());
        $this->entityManager->flush();

        return [
            'success' => true,
            'theme' => $pessoa->isThemeLight() ? 'light' : 'dark',
            'error' => null
        ];
    }
}