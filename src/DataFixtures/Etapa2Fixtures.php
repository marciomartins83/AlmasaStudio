<?php

namespace AppDataFixtures;

use AppEntityEstado;
use AppEntityCidade;
use AppEntityBairro;
use AppEntityLogradouro;
use AppEntityTipoDocumento;
use DoctrineBundleFixturesBundleFixture;
use DoctrinePersistenceObjectManager;

class Etapa2Fixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Estados e cidades principais
        $estados = [
            'SP' => [
                'nome' => 'São Paulo',
                'cidades' => [
                    'São Paulo' => [
                        'bairros' => [
                            'Centro' => ['Rua Augusta', 'Avenida Paulista'],
                            'Jardins' => ['Alameda Santos', 'Rua Oscar Freire'],
                            'Vila Mariana' => ['Rua Domingos de Morais', 'Avenida 23 de Maio']
                        ]
                    ],
                    'Campinas' => [
                        'bairros' => [
                            'Centro' => ['Rua Quinze de Novembro', 'Avenida Anchieta'],
                            'Bosque' => ['Rua Júlio Diniz', 'Avenida John Boyd Dunlop']
                        ]
                    ]
                ]
            ],
            'RJ' => [
                'nome' => 'Rio de Janeiro',
                'cidades' => [
                    'Rio de Janeiro' => [
                        'bairros' => [
                            'Centro' => ['Rua do Ouvidor', 'Avenida Rio Branco'],
                            'Copacabana' => ['Avenida Atlântica', 'Rua Barata Ribeiro'],
                            'Ipanema' => ['Avenida Vieira Souto', 'Rua Visconde de Pirajá']
                        ]
                    ]
                ]
            ]
        ];

        foreach ($estados as $sigla => $estadoData) {
            $estado = new Estado();
            $estado->setSigla($sigla);
            $estado->setNome($estadoData['nome']);
            $estado->setCodigoIbge(str_pad(mt_rand(10, 50), 2, '0', STR_PAD_LEFT));
            $manager->persist($estado);

            foreach ($estadoData['cidades'] as $cidadeNome => $cidadeData) {
                $cidade = new Cidade();
                $cidade->setNome($cidadeNome);
                $cidade->setEstado($estado);
                $cidade->setCodigoIbge(str_pad(mt_rand(1000, 9999), 7, '0', STR_PAD_LEFT));
                $manager->persist($cidade);

                foreach ($cidadeData['bairros'] as $bairroNome => $logradouros) {
                    $bairro = new Bairro();
                    $bairro->setNome($bairroNome);
                    $bairro->setCidade($cidade);
                    $manager->persist($bairro);

                    foreach ($logradouros as $logradouroNome) {
                        $logradouro = new Logradouro();
                        $logradouro->setNome($logradouroNome);
                        $logradouro->setTipo('Rua');
                        $logradouro->setCep('0' . mt_rand(1000, 9999) . '-' . mt_rand(100, 999));
                        $logradouro->setBairro($bairro);
                        $manager->persist($logradouro);
                    }
                }
            }
        }

        // Tipos de documentos
        $tiposDocumentos = [
            ['tipo' => 'Registro Geral', 'sigla' => 'RG', 'descricao' => 'Carteira de Identidade'],
            ['tipo' => 'Cadastro de Pessoa Física', 'sigla' => 'CPF', 'descricao' => 'Cadastro de Pessoa Física'],
            ['tipo' => 'Carteira Nacional de Habilitação', 'sigla' => 'CNH', 'descricao' => 'Carteira de Motorista'],
            ['tipo' => 'Carteira de Trabalho', 'sigla' => 'CTPS', 'descricao' => 'Carteira de Trabalho e Previdência Social'],
            ['tipo' => 'Título de Eleitor', 'sigla' => 'TE', 'descricao' => 'Título de Eleitor'],
            ['tipo' => 'Certificado de Reservista', 'sigla' => 'CR', 'descricao' => 'Certificado de Alistamento Militar'],
            ['tipo' => 'Passaporte', 'sigla' => 'PASS', 'descricao' => 'Passaporte'],
            ['tipo' => 'Carteira de Identidade Profissional', 'sigla' => 'CIP', 'descricao' => 'Identidade Profissional']
        ];

        foreach ($tiposDocumentos as $tipoData) {
            $tipo = new TipoDocumento();
            $tipo->setTipo($tipoData['tipo']);
            $tipo->setSigla($tipoData['sigla']);
            $tipo->setDescricao($tipoData['descricao']);
            $manager->persist($tipo);
        }

        $manager->flush();
    }
} 