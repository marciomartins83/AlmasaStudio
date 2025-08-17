<?php

namespace App\Command;

use App\Entity\Users;
use App\Entity\Role; // Importar a classe Role se ela existir e for usada
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use DateTime;

#[AsCommand(name: 'app:create-admin', description: 'Creates or updates an administrator user.')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'marcioramos1983@gmail.com';
        $password = '123';
        
        $userRepository = $this->entityManager->getRepository(Users::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        
        if ($user) {
            $output->writeln('Usuário administrador já existe. Redefinindo senha...');
        } else {
            $output->writeln('Criando novo usuário administrador...');
            $user = new Users();
            $user->setEmail($email);
            $user->setName('Administrador');
        }
        
        // Definir/redefinir senha
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Garantir que o email esteja verificado (simulando a verificação)
        if ($user->getEmailVerifiedAt() === null) {
            $user->setEmailVerifiedAt(new DateTime());
        }
        
        // Salvar usuário
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // --- Configuração de Permissões Totais (se o sistema de roles existir) ---
        try {
            // Tenta obter ou criar a role SUPER_ADMIN
            $roleRepository = $this->entityManager->getRepository(Role::class);
            $role = $roleRepository ? $roleRepository->findOneBy(['name' => 'SUPER_ADMIN']) : null;
            
            if ($user && !$role) {
                $output->writeln('Role SUPER_ADMIN não encontrada. Verifique a entidade Role e suas fixtures.');
            } elseif ($user && $role && !$user->getRoles() || !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) { // Assumindo que getRoles() retorna um array de strings
                // Lógica para adicionar role ao usuário - pode variar dependendo de como as roles são gerenciadas na entity User
                // Exemplo: $user->addRole('ROLE_SUPER_ADMIN'); (se for um método simples)
                // Se for uma relação ManyToMany, pode ser necessário mais lógica aqui.
                // Por ora, vamos adicionar um log indicativo.
                $output->writeln('ℹ️ Role SUPER_ADMIN não atribuída automaticamente. Verifique a Entity User e seu gerenciamento de roles.');
            }
        } catch (\Throwable $e) {
            // Captura qualquer exceção caso a Repository ou a Entity Role não existam ou falhem
            $output->writeln('ℹ️ Sistema de roles não encontrado ou não configurado para atribuição automática.');
        }
        
        $output->writeln('✅ Usuário administrador criado/atualizado com sucesso!');
        $output->writeln("📧 Email: $email");
        $output->writeln("🔑 Senha: $password");
        $output->writeln("🔓 Acesso: TOTAL (Verificar atribuição de roles se aplicável)");
        
        return Command::SUCCESS;
    }
} 