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
        // Mapeamento: plano_contas.codigo => almasa_plano_contas.codigo
        $mapeamento = [
            '2001' => '1.1.01', // Taxa de Administração
            '2051' => '1.1.02', // Tx. Administração Condominio
            '2046' => '1.1.03', // Taxa de Locação
            '1030' => '1.1.01', // Tx. Administração (Almasa)
            '1035' => '1.1.04', // Honorarios - Juridico
            '1011' => '1.1.03', // Taxa de Locação
            '1026' => '1.1.05', // Comissões Recebidas
            '1041' => '1.2.01', // Juros
            '1013' => '1.3.01', // Receitas Diversas
            '2029' => '2.5.01', // Comissões Pagas
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
