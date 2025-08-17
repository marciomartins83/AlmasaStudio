<?php

namespace App\DataFixtures;

use App\Entity\TiposPessoas;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TipoPessoaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tipos = [
            'contratante' => 'Pessoa que contrata serviços',
            'locador' => 'Pessoa que possui imóveis para locação',
            'corretor' => 'Corretor de imóveis',
            'fiador' => 'Pessoa que serve como garantia',
            'pretendente' => 'Interessado em alugar/comprar',
            'inquilino' => 'Pessoa que aluga imóveis'
        ];

        foreach ($tipos as $tipo => $descricao) {
            $tipoPessoa = new TiposPessoas();
            $tipoPessoa->setTipo($tipo);
            $tipoPessoa->setDescricao($descricao);
            $tipoPessoa->setAtivo(true);
            
            $manager->persist($tipoPessoa);
            $this->addReference('tipo-pessoa-' . $tipo, $tipoPessoa);
        }

        $manager->flush();
    }
}
