<?php

namespace App\DataFixtures;

use App\Entity\ContasBancarias;
use App\Entity\Pessoas;
use App\Entity\Bancos;
use App\Entity\Agencias;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ContaBancariaTestFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Criar dependências para ContaBancaria
        $pessoa = (new Pessoas())->setNome('Pessoa para Fixture');
        $banco = (new Bancos())->setNumeroBanco('002')->setNomeBanco('Banco Fixture');
        $agencia = (new Agencias())->setCodigo('FIXTURE002')->setNome('Agencia Fixture')->setBanco($banco);

        $manager->persist($pessoa);
        $manager->persist($banco);
        $manager->persist($agencia);
        $manager->flush();

        // Criar a ContaBancaria e associar os relacionamentos
        $contaBancaria = (new ContasBancarias())
            ->setNumeroConta('99887-7')
            ->setDigito('8')
            ->setPrincipal(true)
            ->setAtivo(true)
            ->setCodigoBanco('002')
            ->setPessoa($pessoa)
            ->setBanco($banco)
            ->setAgencia($agencia);
        $manager->persist($contaBancaria);

        $manager->flush();
    }
} 