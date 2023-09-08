<?php

namespace App\Command\crudmick;

use App\Service\base\ParserDocblock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Config\Definition\Exception\Exception;

#[AsCommand(
    name: 'crud:generate:voir',
    description: 'Génère le fichier voir de l\'entité',
)]
class CrudMakeVoirCommand extends Command
{
    protected $attrs;
    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::OPTIONAL, 'nom de l\entité')
            ->addOption('comment', null, InputOption::VALUE_NONE, 'Pour afficher les commentaires')
            ->addOption(
                'speed',
                's',
                InputOption::VALUE_NONE,
                'Pour passer le formatage des fichiers'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /* --------------------------------- library -------------------------------- */
        $helper = $this->getHelper('question');
        /* --------------------------------- entity --------------------------------- */
        $entity = $input->getArgument('entity');
        if (!$entity) {
            $question = new Question('Entrer le nom de l\'entité:');
            $entity = $helper->ask($input, $output, $question);
        }

        /* ------------------------- initialisation variable ------------------------ */
        $entity = strTolower($entity);
        $Entity = ucfirst($entity);
        $docs = new ParserDocblock($entity);
        /* --------------------------------- entete --------------------------------- */
        $uses = []; //content uses
        //variable
        $rows = [];
        $IDOptions = $docs->getOptions()['id'];
        foreach ($IDOptions as $IDOption => $value) {
            switch ($IDOption) {
            }
        }
        $noshows = [];
        foreach ($docs->getOptions() as $name => $options) {
            //timetrait
            if ($name == 'createdAt' && isset($IDOptions['tpl']['no_created'])) {
                continue;
            }
            if ($name == 'updatedAt' && isset($IDOptions['tpl']['no_updated'])) {
                continue;
            }
            if (!isset($options['tpl']['no_show']) && $name != 'id') {
                foreach ($docs->getSelect($name) as $select) {
                    $resattrs = isset($options['resattrs']) ? $options['resattrs'] : '';
                    switch ($select) {
                        default: {
                                $resattrs = '';
                                $rows[] = '' . "\n";
                            }
                    }
                }
            } else {
                $noshows[] = $name;
            }
        }
        //open model controller
        $fileNew = __DIR__ . '/tpl/voir.html.twig';
        if (!file_Exists($fileNew)) {
            throw new Exception("Le fichier " . $fileNew . " est introuvable", 1);
        }
        $html = CrudInitCommand::twigParser(file_get_contents($fileNew), array(
            'entity' => $entity,
            'Entity' => $Entity,
            'extends' => '/admin/base.html.twig',
            'noshow' => implode(',', $noshows)
        ));
        CrudInitCommand::updateFile("templates/" . $entity . '/voir.html.twig', $html, $input->getOption('comment'), $input->getOption('speed'));

        return Command::SUCCESS;
    }
}
