<?php

namespace App\Command\crudmick;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\base\ToolsHelper;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'setslug',
    description: 'create slugs for the entity',
)]
class SetslugCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::OPTIONAL, 'entity');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $objet = $input->getArgument('entity');

        if (!$objet) {
            $io->note(sprintf('il manque l\'entité'));
            exit();
        }
        $objetRepository = $this->em->getRepository('App\Entity\\' . ucfirst($objet));
        $objetEntity = 'App\Entity\\' . ucfirst($objet);

        foreach ($objetRepository->findAll() as $art) {
            $art = ToolsHelper::setSlug($this->em, $art);
            $this->em->persist($art);
        }
        $this->em->flush();


        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
