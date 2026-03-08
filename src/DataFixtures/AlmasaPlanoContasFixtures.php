<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AlmasaPlanoContas;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AlmasaPlanoContasFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $estrutura = [
            // NIVEL 1 — GRUPOS
            ['codigo' => '1', 'descricao' => 'Receitas', 'tipo' => 'receita', 'nivel' => 1, 'pai' => null, 'aceita' => false],
            ['codigo' => '2', 'descricao' => 'Despesas', 'tipo' => 'despesa', 'nivel' => 1, 'pai' => null, 'aceita' => false],

            // NIVEL 2 — SUBGRUPOS RECEITA
            ['codigo' => '1.1', 'descricao' => 'Receitas Operacionais', 'tipo' => 'receita', 'nivel' => 2, 'pai' => '1', 'aceita' => false],
            ['codigo' => '1.2', 'descricao' => 'Receitas Financeiras', 'tipo' => 'receita', 'nivel' => 2, 'pai' => '1', 'aceita' => false],
            ['codigo' => '1.3', 'descricao' => 'Receitas Diversas', 'tipo' => 'receita', 'nivel' => 2, 'pai' => '1', 'aceita' => false],

            // NIVEL 2 — SUBGRUPOS DESPESA
            ['codigo' => '2.1', 'descricao' => 'Despesas com Pessoal', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '2', 'aceita' => false],
            ['codigo' => '2.2', 'descricao' => 'Despesas Administrativas', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '2', 'aceita' => false],
            ['codigo' => '2.3', 'descricao' => 'Despesas com Veículos', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '2', 'aceita' => false],
            ['codigo' => '2.4', 'descricao' => 'Despesas com Imóvel (escritório)', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '2', 'aceita' => false],
            ['codigo' => '2.5', 'descricao' => 'Comissões Pagas', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '2', 'aceita' => false],

            // NIVEL 3 — CONTAS RECEITA
            ['codigo' => '1.1.01', 'descricao' => 'Taxa de Administração - Locação', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.02', 'descricao' => 'Taxa de Administração - Condomínio', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.03', 'descricao' => 'Taxa de Locação', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.04', 'descricao' => 'Honorários - Jurídico', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.05', 'descricao' => 'Comissões - Vendas', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.1', 'aceita' => true],
            ['codigo' => '1.2.01', 'descricao' => 'Juros Bancários', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.2', 'aceita' => true],
            ['codigo' => '1.3.01', 'descricao' => 'Receitas Diversas', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '1.3', 'aceita' => true],

            // NIVEL 3 — CONTAS DESPESA
            ['codigo' => '2.1.01', 'descricao' => 'Salários', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.1', 'aceita' => true],
            ['codigo' => '2.1.02', 'descricao' => 'Encargos Sociais', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.1', 'aceita' => true],
            ['codigo' => '2.1.03', 'descricao' => 'Sindicato', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.1', 'aceita' => true],
            ['codigo' => '2.2.01', 'descricao' => 'Material Escritório', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.02', 'descricao' => 'Material Limpeza / Cozinha', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.03', 'descricao' => 'Publicidade', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.04', 'descricao' => 'Condução / Correio', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.05', 'descricao' => 'Taxas Bancárias', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.06', 'descricao' => 'Despesas Diversas', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.2', 'aceita' => true],
            ['codigo' => '2.3.01', 'descricao' => 'Combustível', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.3', 'aceita' => true],
            ['codigo' => '2.3.02', 'descricao' => 'Manutenção Veículo', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.3', 'aceita' => true],
            ['codigo' => '2.4.01', 'descricao' => 'Seguro do Prédio', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.4', 'aceita' => true],
            ['codigo' => '2.4.02', 'descricao' => 'Manutenção do Prédio', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.4', 'aceita' => true],
            ['codigo' => '2.4.03', 'descricao' => 'Manutenção Máquinas/Equipamentos', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.4', 'aceita' => true],
            ['codigo' => '2.5.01', 'descricao' => 'Comissões Pagas - Corretor', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '2.5', 'aceita' => true],
        ];

        $entidades = [];

        foreach ($estrutura as $item) {
            $conta = new AlmasaPlanoContas();
            $conta->setCodigo($item['codigo']);
            $conta->setDescricao($item['descricao']);
            $conta->setTipo($item['tipo']);
            $conta->setNivel($item['nivel']);
            $conta->setAceitaLancamentos($item['aceita']);
            $conta->setAtivo(true);

            if ($item['pai'] !== null && isset($entidades[$item['pai']])) {
                $conta->setPai($entidades[$item['pai']]);
            }

            $manager->persist($conta);
            $entidades[$item['codigo']] = $conta;
            $this->addReference('almasa-pc-' . $item['codigo'], $conta);
        }

        $manager->flush();
    }
}
