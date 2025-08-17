<?php

namespace App\DataFixtures;

use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\Endereco;
use App\Entity\Pessoas;
use App\Entity\ContasBancarias;
use App\Entity\TiposTelefones;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RelationshipTestFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // --- Dados para Agência e seus relacionamentos ---
        $bancoAgencia = (new Bancos())
            ->setNumeroBanco('003')
            ->setNomeBanco('Banco Agência Fixture');
        $manager->persist($bancoAgencia);

        $enderecoAgencia = (new Endereco())
            ->setLogradouro('Rua Ag. Fixture')
            ->setNumero('321')
            ->setBairro('Centro Fixture')
            ->setCidade('Agencia City')
            ->setEstado('AG')
            ->setCep('33333-333');
        $manager->persist($enderecoAgencia);

        $agencia = (new Agencias())
            ->setCodigo('REL001')
            ->setNome('Agencia Rel Fixture')
            ->setBanco($bancoAgencia)
            ->setEndereco($enderecoAgencia);
        $manager->persist($agencia);

        // --- Dados para ContaBancaria e seus relacionamentos ---
        $pessoaConta = (new Pessoas())->setNome('Pessoa para Rel Fixture');
        $bancoConta = (new Bancos())
            ->setNumeroBanco('004')
            ->setNomeBanco('Banco Conta Fixture');
        $agenciaConta = (new Agencias())
            ->setCodigo('REL002')
            ->setNome('Agencia Conta Fixture')
            ->setBanco($bancoConta);

        $manager->persist($pessoaConta);
        $manager->persist($bancoConta);
        $manager->persist($agenciaConta);

        $contaBancaria = (new ContasBancarias())
            ->setNumeroConta('55555-5')
            ->setDigito('5')
            ->setPrincipal(true)
            ->setAtivo(true)
            ->setCodigoBanco('004')
            ->setPessoa($pessoaConta)
            ->setBanco($bancoConta)
            ->setAgencia($agenciaConta);
        $manager->persist($contaBancaria);

        // --- Dados para Pessoa e seus relacionamentos (ex: Telefones) ---
        $pessoaTelefone = (new Pessoas())->setNome('Pessoa com Telefones');
        $tipoTelefone = (new TiposTelefones())
            ->setNome('Comercial Fixture')
            ->setAtivo(true);
        $manager->persist($pessoaTelefone);
        $manager->persist($tipoTelefone);

        // Adicionando um telefone à pessoa
        $telefone = new \App\Entity\Telefone(); // Referenciando a entidade Telefone diretamente
        $telefone->setPessoa($pessoaTelefone)
                 ->setTipoTelefone($tipoTelefone)
                 ->setNumero('123456789')
                 ->setDdd('11');
        $manager->persist($telefone);

        // Persistir todas as entidades
        $manager->flush();
    }
} 