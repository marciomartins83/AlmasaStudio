<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AlmasaPlanoContas;
use App\Entity\PlanoContas;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AlmasaPlanoContasVinculoFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [AlmasaPlanoContasFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // Mapeamento: plano_contas.codigo => almasa_plano_contas.codigo (v2)
        // Receitas agora no grupo 4, despesas no grupo 5
        $mapeamento = [
            '2001' => '4.1.01', // Taxa de Administração → Taxa de Administração de Aluguel
            '2051' => '4.1.01', // Tx. Administração Condominio → Taxa de Administração de Aluguel
            '2046' => '4.1.02', // Taxa de Locação → Comissão de Locação
            '1030' => '4.1.01', // Tx. Administração (Almasa) → Taxa de Administração de Aluguel
            '1035' => '4.2.01', // Honorarios - Juridico → Multas Contratuais
            '1011' => '4.1.02', // Taxa de Locação → Comissão de Locação
            '1026' => '4.1.02', // Comissões Recebidas → Comissão de Locação
            '1041' => '4.2.02', // Juros → Juros de Mora
            '1013' => '4.2.03', // Receitas Diversas → Serviços de Vistoria
            '2029' => '5.2.01', // Comissões Pagas → Salários (grupo despesa pessoal)
        ];

        $planoContasRepo = $manager->getRepository(PlanoContas::class);

        foreach ($mapeamento as $codigoCliente => $codigoAlmasa) {
            $planoCliente = $planoContasRepo->findOneBy(['codigo' => $codigoCliente]);
            if (!$planoCliente) {
                continue;
            }

            /** @var AlmasaPlanoContas $almasaConta */
            $almasaConta = $this->getReference('almasa-pc-' . $codigoAlmasa, AlmasaPlanoContas::class);
            $planoCliente->setAlmasaPlanoConta($almasaConta);
        }

        $manager->flush();
    }
}
