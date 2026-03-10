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
            // =====================================================================
            // 1. ATIVO
            // =====================================================================
            ['codigo' => '1', 'descricao' => 'ATIVO', 'tipo' => 'ativo', 'nivel' => 1, 'pai' => null, 'aceita' => false],
            ['codigo' => '1.1', 'descricao' => 'Ativo Circulante', 'tipo' => 'ativo', 'nivel' => 2, 'pai' => '1', 'aceita' => false],

            // 1.1.01 Disponivel
            ['codigo' => '1.1.01', 'descricao' => 'Disponível', 'tipo' => 'ativo', 'nivel' => 3, 'pai' => '1.1', 'aceita' => false],
            ['codigo' => '1.1.01.001', 'descricao' => 'Caixa', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.01', 'aceita' => true],
            ['codigo' => '1.1.01.002', 'descricao' => 'Banco Conta Movimento', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.01', 'aceita' => true],
            ['codigo' => '1.1.01.003', 'descricao' => 'Aplicações Financeiras', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.01', 'aceita' => true],

            // 1.1.02 Contas a Receber
            ['codigo' => '1.1.02', 'descricao' => 'Contas a Receber', 'tipo' => 'ativo', 'nivel' => 3, 'pai' => '1.1', 'aceita' => false],
            ['codigo' => '1.1.02.001', 'descricao' => 'Aluguéis a Receber', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.02', 'aceita' => true],
            ['codigo' => '1.1.02.002', 'descricao' => 'Taxa de Administração a Receber', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.02', 'aceita' => true],
            ['codigo' => '1.1.02.003', 'descricao' => 'Multas Contratuais a Receber', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.02', 'aceita' => true],
            ['codigo' => '1.1.02.004', 'descricao' => 'Juros de Mora a Receber', 'tipo' => 'ativo', 'nivel' => 4, 'pai' => '1.1.02', 'aceita' => true],

            // =====================================================================
            // 2. PASSIVO
            // =====================================================================
            ['codigo' => '2', 'descricao' => 'PASSIVO', 'tipo' => 'passivo', 'nivel' => 1, 'pai' => null, 'aceita' => false],
            ['codigo' => '2.1', 'descricao' => 'Passivo Circulante', 'tipo' => 'passivo', 'nivel' => 2, 'pai' => '2', 'aceita' => false],

            // 2.1.01 Obrigacoes com Proprietarios
            ['codigo' => '2.1.01', 'descricao' => 'Obrigações com Proprietários', 'tipo' => 'passivo', 'nivel' => 3, 'pai' => '2.1', 'aceita' => false],
            ['codigo' => '2.1.01.01', 'descricao' => 'Conta Corrente de Proprietários', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.01', 'aceita' => false],
            ['codigo' => '2.1.01.01.001', 'descricao' => 'Proprietário João da Silva', 'tipo' => 'passivo', 'nivel' => 5, 'pai' => '2.1.01.01', 'aceita' => true],
            ['codigo' => '2.1.01.01.002', 'descricao' => 'Proprietário Maria Oliveira', 'tipo' => 'passivo', 'nivel' => 5, 'pai' => '2.1.01.01', 'aceita' => true],
            ['codigo' => '2.1.01.01.003', 'descricao' => 'Proprietário Empresa ABC', 'tipo' => 'passivo', 'nivel' => 5, 'pai' => '2.1.01.01', 'aceita' => true],

            // 2.1.02 Obrigacoes Trabalhistas
            ['codigo' => '2.1.02', 'descricao' => 'Obrigações Trabalhistas', 'tipo' => 'passivo', 'nivel' => 3, 'pai' => '2.1', 'aceita' => false],
            ['codigo' => '2.1.02.001', 'descricao' => 'Salários a Pagar', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.02', 'aceita' => true],
            ['codigo' => '2.1.02.002', 'descricao' => 'INSS a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.02', 'aceita' => true],
            ['codigo' => '2.1.02.003', 'descricao' => 'FGTS a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.02', 'aceita' => true],
            ['codigo' => '2.1.02.004', 'descricao' => 'Férias a Pagar', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.02', 'aceita' => true],
            ['codigo' => '2.1.02.005', 'descricao' => '13º Salário', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.02', 'aceita' => true],

            // 2.1.03 Obrigacoes Tributarias
            ['codigo' => '2.1.03', 'descricao' => 'Obrigações Tributárias', 'tipo' => 'passivo', 'nivel' => 3, 'pai' => '2.1', 'aceita' => false],
            ['codigo' => '2.1.03.001', 'descricao' => 'ISS a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.03', 'aceita' => true],
            ['codigo' => '2.1.03.002', 'descricao' => 'PIS a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.03', 'aceita' => true],
            ['codigo' => '2.1.03.003', 'descricao' => 'COFINS a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.03', 'aceita' => true],
            ['codigo' => '2.1.03.004', 'descricao' => 'IRPJ a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.03', 'aceita' => true],
            ['codigo' => '2.1.03.005', 'descricao' => 'CSLL a Recolher', 'tipo' => 'passivo', 'nivel' => 4, 'pai' => '2.1.03', 'aceita' => true],

            // =====================================================================
            // 3. PATRIMONIO LIQUIDO
            // =====================================================================
            ['codigo' => '3', 'descricao' => 'PATRIMÔNIO LÍQUIDO', 'tipo' => 'patrimonio_liquido', 'nivel' => 1, 'pai' => null, 'aceita' => false],

            // 3.1 Capital Social
            ['codigo' => '3.1', 'descricao' => 'Capital Social', 'tipo' => 'patrimonio_liquido', 'nivel' => 2, 'pai' => '3', 'aceita' => false],
            ['codigo' => '3.1.01', 'descricao' => 'Capital Social Integralizado', 'tipo' => 'patrimonio_liquido', 'nivel' => 3, 'pai' => '3.1', 'aceita' => true],

            // 3.2 Conta Corrente de Socios
            ['codigo' => '3.2', 'descricao' => 'Conta Corrente de Sócios', 'tipo' => 'patrimonio_liquido', 'nivel' => 2, 'pai' => '3', 'aceita' => false],
            ['codigo' => '3.2.01', 'descricao' => 'Conta Corrente Sócios', 'tipo' => 'patrimonio_liquido', 'nivel' => 3, 'pai' => '3.2', 'aceita' => false],
            ['codigo' => '3.2.01.001', 'descricao' => 'Sócio – Celestino Almeida Silva', 'tipo' => 'patrimonio_liquido', 'nivel' => 4, 'pai' => '3.2.01', 'aceita' => true],
            ['codigo' => '3.2.01.002', 'descricao' => 'Sócio – (outro sócio)', 'tipo' => 'patrimonio_liquido', 'nivel' => 4, 'pai' => '3.2.01', 'aceita' => true],

            // =====================================================================
            // 4. RECEITAS
            // =====================================================================
            ['codigo' => '4', 'descricao' => 'RECEITAS', 'tipo' => 'receita', 'nivel' => 1, 'pai' => null, 'aceita' => false],

            // 4.1 Receitas de Administracao
            ['codigo' => '4.1', 'descricao' => 'Receitas de Administração', 'tipo' => 'receita', 'nivel' => 2, 'pai' => '4', 'aceita' => false],
            ['codigo' => '4.1.01', 'descricao' => 'Taxa de Administração de Aluguel', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.1', 'aceita' => true],
            ['codigo' => '4.1.02', 'descricao' => 'Comissão de Locação', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.1', 'aceita' => true],
            ['codigo' => '4.1.03', 'descricao' => 'Taxa de Renovação de Contrato', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.1', 'aceita' => true],
            ['codigo' => '4.1.04', 'descricao' => 'Taxa de Cadastro', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.1', 'aceita' => true],

            // 4.2 Outras Receitas
            ['codigo' => '4.2', 'descricao' => 'Outras Receitas', 'tipo' => 'receita', 'nivel' => 2, 'pai' => '4', 'aceita' => false],
            ['codigo' => '4.2.01', 'descricao' => 'Multas Contratuais', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.2', 'aceita' => true],
            ['codigo' => '4.2.02', 'descricao' => 'Juros de Mora', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.2', 'aceita' => true],
            ['codigo' => '4.2.03', 'descricao' => 'Serviços de Vistoria', 'tipo' => 'receita', 'nivel' => 3, 'pai' => '4.2', 'aceita' => true],

            // =====================================================================
            // 5. DESPESAS OPERACIONAIS
            // =====================================================================
            ['codigo' => '5', 'descricao' => 'DESPESAS OPERACIONAIS', 'tipo' => 'despesa', 'nivel' => 1, 'pai' => null, 'aceita' => false],

            // 5.1 Despesas Administrativas
            ['codigo' => '5.1', 'descricao' => 'Despesas Administrativas', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '5', 'aceita' => false],
            ['codigo' => '5.1.01', 'descricao' => 'Aluguel do Escritório', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.02', 'descricao' => 'Energia Elétrica', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.03', 'descricao' => 'Água', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.04', 'descricao' => 'Internet', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.05', 'descricao' => 'Telefone', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.06', 'descricao' => 'Material de Escritório', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.07', 'descricao' => 'Software Imobiliário', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],
            ['codigo' => '5.1.08', 'descricao' => 'Contabilidade', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.1', 'aceita' => true],

            // 5.2 Despesas com Pessoal
            ['codigo' => '5.2', 'descricao' => 'Despesas com Pessoal', 'tipo' => 'despesa', 'nivel' => 2, 'pai' => '5', 'aceita' => false],
            ['codigo' => '5.2.01', 'descricao' => 'Salários', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.2', 'aceita' => true],
            ['codigo' => '5.2.02', 'descricao' => 'Pró-labore', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.2', 'aceita' => true],
            ['codigo' => '5.2.03', 'descricao' => 'INSS', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.2', 'aceita' => true],
            ['codigo' => '5.2.04', 'descricao' => 'FGTS', 'tipo' => 'despesa', 'nivel' => 3, 'pai' => '5.2', 'aceita' => true],
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
