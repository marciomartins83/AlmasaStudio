<?php

namespace AppDataFixtures;

use AppEntityTipoEmail;
use DoctrineBundleFixturesBundleFixture;
use DoctrinePersistenceObjectManager;

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