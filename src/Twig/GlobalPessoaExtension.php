<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Pessoas;
use App\Entity\Users;

class GlobalPessoaExtension extends AbstractExtension implements GlobalsInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return ['pessoa' => null];
        }

        // Buscar a Pessoa pelo email do usuário logado (ou outra lógica que faça sentido)
        $pessoa = $this->entityManager->getRepository(Pessoas::class)->findOneBy(['user' => $user]);

        return [
            'pessoa' => $pessoa,
        ];
    }
}
