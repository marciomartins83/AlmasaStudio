<?php

namespace App\DataFixtures;

use App\Entity\Pessoas;
use App\Entity\RelacionamentosFamiliares;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RelacionamentosFamiliaresFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Busca até 3 pessoas existentes no banco
        $pessoas = $manager->getRepository(Pessoas::class)->findBy([], null, 3);

        if (count($pessoas) < 3) {
            throw new \RuntimeException('Não há pessoas suficientes para criar relacionamentos.');
        }

        // Cria relacionamentos de cônjuge entre cada par de pessoas
        $relacionamentos = [];

        // 1ª pessoa com 2ª
        $rel1 = new RelacionamentosFamiliares();
        $rel1->setIdPessoaOrigem($pessoas[0]->getIdpessoa())
             ->setIdPessoaDestino($pessoas[1]->getIdpessoa())
             ->setTipoRelacionamento('Cônjuge')
             ->setAtivo(true)
             ->setDataInicio(new \DateTime());
        $relacionamentos[] = $rel1;

        // 2ª pessoa com 3ª
        $rel2 = new RelacionamentosFamiliares();
        $rel2->setIdPessoaOrigem($pessoas[1]->getIdpessoa())
             ->setIdPessoaDestino($pessoas[2]->getIdpessoa())
             ->setTipoRelacionamento('Cônjuge')
             ->setAtivo(true)
             ->setDataInicio(new \DateTime());
        $relacionamentos[] = $rel2;

        // 1ª pessoa com 3ª
        $rel3 = new RelacionamentosFamiliares();
        $rel3->setIdPessoaOrigem($pessoas[0]->getIdpessoa())
             ->setIdPessoaDestino($pessoas[2]->getIdpessoa())
             ->setTipoRelacionamento('Cônjuge')
             ->setAtivo(true)
             ->setDataInicio(new \DateTime());
        $relacionamentos[] = $rel3;

        // Persiste todos os relacionamentos
        foreach ($relacionamentos as $rel) {
            $manager->persist($rel);
        }

        // Flush apenas uma vez
        $manager->flush();
    }
}
