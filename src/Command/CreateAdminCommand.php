<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email do administrador')
            ->addArgument('password', InputArgument::OPTIONAL, 'Senha do administrador');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $password = $input->getArgument('password');
        if (!$password) {
            $helper = $this->getHelper('question');
            $question = new Question('Senha do administrador: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        if (!$password || strlen($password) < 6) {
            $output->writeln('<error>Senha deve ter no minimo 6 caracteres.</error>');
            return Command::FAILURE;
        }

        $userRepository = $this->entityManager->getRepository(Users::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $output->writeln('Usuario administrador ja existe. Redefinindo senha e roles...');
        } else {
            $output->writeln('Criando novo usuario administrador...');
            $user = new Users();
            $user->setEmail($email);
            $user->setName('Administrador');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        if ($user->getEmailVerifiedAt() === null) {
            $user->setEmailVerifiedAt(new DateTime());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Usuario administrador criado/atualizado com sucesso!</info>');
        $output->writeln("Email: $email");
        $output->writeln('Roles: ROLE_SUPER_ADMIN, ROLE_USER');

        return Command::SUCCESS;
    }
}
