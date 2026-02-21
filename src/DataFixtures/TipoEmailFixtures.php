<?php

namespace App\DataFixtures;

use App\Entity\TipoEmail;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TipoEmailFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tipos = [
            'Pessoal',
            'Profissional',
            'Acadêmico',
            'Temporário'
        ];

        foreach ($tipos as $tipo) {
            $tipoEmail = new TipoEmail();
            $tipoEmail->setTipo($tipo);
            $manager->persist($tipoEmail);
        }

        $manager->flush();
    }
} 