<?php

namespace App\DataFixtures;

use App\Entity\TipoImovel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class TipoImovelFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['tipo_imovel'];
    }
    
    public function load(ObjectManager $manager): void
    {
        $tipos = [
            ['tipo' => 'Casa', 'descricao' => 'Residência unifamiliar'],
            ['tipo' => 'Apartamento', 'descricao' => 'Unidade em condomínio vertical'],
            ['tipo' => 'Terreno', 'descricao' => 'Lote para construção'],
            ['tipo' => 'Comercial', 'descricao' => 'Imóvel para uso comercial'],
            ['tipo' => 'Rural', 'descricao' => 'Propriedade agrícola ou fazenda'],
        ];

        foreach ($tipos as $tipoData) {
            $tipoImovel = new TipoImovel();
            $tipoImovel->setTipo($tipoData['tipo']);
            $tipoImovel->setDescricao($tipoData['descricao']);
            $manager->persist($tipoImovel);
        }

        $manager->flush();
    }
}
