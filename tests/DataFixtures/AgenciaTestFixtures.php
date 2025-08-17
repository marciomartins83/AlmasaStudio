<?php

namespace App\DataFixtures;

use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\Endereco;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AgenciaTestFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Criar um Banco para a Agência
        $banco = (new Bancos())
            ->setNumeroBanco('001')
            ->setNomeBanco('Banco Exemplo Fixture');
        $manager->persist($banco);

        // Criar um Endereço para a Agência
        $endereco = (new Endereco())
            ->setLogradouro('Rua das Testes')
            ->setNumero('123')
            ->setBairro('Centro')
            ->setCidade('Teste City')
            ->setEstado('TS')
            ->setCep('11111-111');
        $manager->persist($endereco);

        // Criar a Agência e associar com Banco e Endereço
        $agencia = (new Agencias())
            ->setCodigo('FIXTURE001')
            ->setNome('Agencia Teste Fixture')
            ->setBanco($banco)
            ->setEndereco($endereco);
        $manager->persist($agencia);

        $manager->flush();
    }
} 