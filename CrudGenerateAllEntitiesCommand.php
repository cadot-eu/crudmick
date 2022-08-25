<?php

namespace App\Command\base;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\base\ParserDocblock;

#[AsCommand(
    name: 'crud:generateAll',
    description: 'génère toutes les entités ',
)]
class CrudGenerateAllEntitiesCommand extends Command
{
    protected $entity;

    protected function configure(): void
    {
        $this->addOption('comment', null, InputOption::VALUE_NONE, 'Pour afficher les commentaires');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /* -------------------------------- constant -------------------------------- */

        //secure $this->entity in minus
        foreach (array_diff(scandir('src/Entity'), array('..', '.', 'ResetPasswordRequest.php', 'base')) as $entity) {
            $tentitie = false;
            $entity = strTolower(substr($entity, 0, -strlen('.php')));
            $Finput = new ArrayInput([
                'entity' => $entity,
                '--comment' => $input->getOption('comment'),
            ]);
            $docs = new ParserDocblock($entity);
            $options = $docs->getOptions();
            if ($input->getOption('comment')) {
                $io->note('Création des fichiers de l\'entité ' . $entity . ' :');
            }
            if (!in_array('nocrud', $options['id'])) {
                $init = $this->getApplication()->find('crud:init');
                $init->run($Finput, $output);

                $type = $this->getApplication()->find('crud:generate:type');
                $type->run($Finput, $output);
                if (!in_array('onlytype', $options['id'])) {
                    $new = $this->getApplication()->find('crud:generate:new');
                    $new->run($Finput, $output);

                    $index = $this->getApplication()->find('crud:generate:index');
                    $index->run($Finput, $output);

                    $controller = $this->getApplication()->find('crud:generate:controller');
                    $controller->run($Finput, $output);
                } else $tentitie = $entity . '(Type)';
            } else
                $tentitie = $entity . '(noCrud)';
            $tabentities[] = $tentitie ?: $entity;
        }
        $io->success('Tous les fichiers ont été générés:' . implode(', ', $tabentities));
        return Command::SUCCESS;
    }
}
