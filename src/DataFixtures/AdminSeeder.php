<?php

namespace App\DataFixtures;

use App\Entity\Pessoas;
use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\EstadoCivil;
use App\Entity\Nacionalidade;
use App\Entity\Naturalidade;
// Faker não está instalado, usando dados estáticos realistas
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * DataFixture para criar um administrador do sistema Almasa
 * 
 * Este seeder cria um usuário administrador com dados completos
 * incluindo relacionamento OneToOne entre Users e Pessoas
 * 
 * Dados do administrador:
 * - Email: marcioramos1983@gmail.com
 * - Senha: 123 (hashada)
 * - Nome: Marcio Ramos
 * - Tipo pessoa: 1 (administrador)
 * - Status: true (ativo)
 */
class AdminSeeder extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Dados estáticos realistas para pessoa física
        
        // Iniciar transação para garantir integridade dos dados
        $manager->getConnection()->beginTransaction();
        
        try {
            // Criar entidade Users (administrador)
            $user = new Users();
            $user->setName('Marcio Ramos');
            $user->setEmail('marcioramos1983@gmail.com');
            $user->setEmailVerifiedAt(new \DateTime());
            
            // Hash da senha usando PasswordHasherInterface
            $hashedPassword = $this->passwordHasher->hashPassword($user, '123');
            $user->setPassword($hashedPassword);
            
            // Persistir o usuário primeiro para obter o ID
            $manager->persist($user);
            $manager->flush();
            
            // Criar entidade Pessoas com dados realistas
            $pessoa = new Pessoas();
            $pessoa->setNome('Marcio Ramos');
            $pessoa->setDtCadastro(new \DateTime());
            $pessoa->setTipoPessoa(1); // Administrador
            $pessoa->setStatus(true); // Ativo
            $pessoa->setFisicaJuridica('fisica');
            $pessoa->setThemeLight(true);
            
            // Dados estáticos realistas para pessoa física
            $pessoa->setDataNascimento(new \DateTime('1983-05-15')); // Data de nascimento realista
            
            // Criar ou obter EstadoCivil
            $estadoCivil = $manager->getRepository(EstadoCivil::class)->findOneBy(['nome' => 'Casado']);
            if (!$estadoCivil) {
                $estadoCivil = new EstadoCivil();
                $estadoCivil->setNome('Casado');
                $manager->persist($estadoCivil);
            }
            $pessoa->setEstadoCivil($estadoCivil);

            // Criar ou obter Nacionalidade
            $nacionalidade = $manager->getRepository(Nacionalidade::class)->findOneBy(['nome' => 'Brasileira']);
            if (!$nacionalidade) {
                $nacionalidade = new Nacionalidade();
                $nacionalidade->setNome('Brasileira');
                $manager->persist($nacionalidade);
            }
            $pessoa->setNacionalidade($nacionalidade);

            // Criar ou obter Naturalidade
            $naturalidade = $manager->getRepository(Naturalidade::class)->findOneBy(['nome' => 'São Paulo']);
            if (!$naturalidade) {
                $naturalidade = new Naturalidade();
                $naturalidade->setNome('São Paulo');
                $manager->persist($naturalidade);
            }
            $pessoa->setNaturalidade($naturalidade);

            $pessoa->setNomePai('João Carlos Ramos');
            $pessoa->setNomeMae('Maria Aparecida Silva');
            $pessoa->setRenda('8500.00');
            $pessoa->setObservacoes('Administrador do sistema');
            
            // Estabelecer relacionamento OneToOne
            $pessoa->setUser($user);
            $user->setPessoa($pessoa);
            
            // Persistir a pessoa
            $manager->persist($pessoa);
            
            // Commit da transação
            $manager->flush();
            $manager->getConnection()->commit();
            
            echo "✅ Administrador criado com sucesso!\n";
            echo "📧 Email: marcioramos1983@gmail.com\n";
            echo "🔑 Senha: 123\n";
            echo "👤 Nome: Marcio Ramos\n";
            echo "🏢 Tipo: Administrador\n";
            
        } catch (\Exception $e) {
            // Rollback em caso de erro
            $manager->getConnection()->rollBack();
            throw $e;
        }
    }
}
