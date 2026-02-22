#!/usr/bin/env php
<?php

require __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PessoasFiadores;
use App\Entity\RelacionamentosFamiliares;
use App\Kernel;

class MigracaoConjuguesCommand extends Command
{
    protected static $defaultName = 'app:migracao-conjugues';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Migra pessoas_fiadores.id_conjuge para relacionamentos_familiares');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->em->getConnection()->beginTransaction();
        $created = 0;
        $exists = 0;
        $errors = 0;

        try {
            $fiadores = $this->em->getRepository(PessoasFiadores::class)->findAll();

            foreach ($fiadores as $fiador) {
                $idConjuge = $fiador->getIdConjuge();
                $idFiador = $fiador->getIdPessoa();

                if (!$idConjuge) {
                    continue;
                }

                $existing = $this->em->getRepository(RelacionamentosFamiliares::class)->findOneBy([
                    'idPessoaOrigem' => $idConjuge,
                    'idPessoaDestino' => $idFiador,
                    'tipoRelacionamento' => 'Cônjuge',
                    'ativo' => true,
                ]);

                if ($existing) {
                    $exists++;
                    continue;
                }

                $rel = new RelacionamentosFamiliares();
                $rel->setIdPessoaOrigem($idConjuge);
                $rel->setIdPessoaDestino($idFiador);
                $rel->setTipoRelacionamento('Cônjuge');
                $rel->setAtivo(true);
                $rel->setDataInicio(new \DateTime());

                $this->em->persist($rel);
                $created++;
            }

            $this->em->flush();
            $this->em->getConnection()->commit();

            $output->writeln("Criados: $created, Existentes: $exists, Erros: $errors");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln("Erro: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

$kernel = new App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool)($_SERVER['APP_DEBUG'] ?? true));
$application = new Application($kernel);
$application->add(new MigracaoConjuguesCommand($kernel->getContainer()->get('doctrine')->getManager()));
$application->run();
