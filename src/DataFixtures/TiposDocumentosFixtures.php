<?php

namespace App\DataFixtures;

use App\Entity\TiposDocumentos;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TiposDocumentosFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tiposDocumentos = [
            'CPF' => 'Cadastro de Pessoa Física',
            'CNPJ' => 'Cadastro Nacional de Pessoa Jurídica', 
            'RG' => 'Registro Geral',
            'IE' => 'Inscrição Estadual'
        ];

        foreach ($tiposDocumentos as $sigla => $descricao) {
            // Verificar se já existe
            $existing = $manager->getRepository(TiposDocumentos::class)
                ->findOneBy(['tipo' => $sigla]);
            
            if (!$existing) {
                $tipoDocumento = new TiposDocumentos();
                $tipoDocumento->setTipo($sigla);
                
                $manager->persist($tipoDocumento);
                
                // Criar referência para usar em outros fixtures
                $this->addReference('tipo_documento_' . strtolower($sigla), $tipoDocumento);
            } else {
                // Adicionar referência para o existente
                $this->addReference('tipo_documento_' . strtolower($sigla), $existing);
            }
        }

        $manager->flush();
    }
}
