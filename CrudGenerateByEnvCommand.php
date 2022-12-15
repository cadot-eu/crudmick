<?php

namespace App\Command\crudmick;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'crud:generateByEnv',
    description: 'génère tout de l\'entité à partir du env ',
)]
class CrudGenerateByEnvCommand extends Command
{
    protected $entity;

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Pour passer les erreurs et continuer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = new ArrayInput([
            '--force' => $input->getOption('force'),
        ]);
        //secure $this->entity in minus
        $this->entity = strTolower($this->entity);

        dd(getenv('CRUD'));
        $init = $this->getApplication()->find('crud:init');
        $init->run($force, $output);

        $type = $this->getApplication()->find('crud:generate:type');
        $type->run($force, $output);



        $io->success('Tous les fichiers ont été générés');
        return Command::SUCCESS;
    }
}
