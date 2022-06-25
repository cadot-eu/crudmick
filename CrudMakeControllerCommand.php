<?php

namespace App\Command\base;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\base\ParserDocblock;

#[AsCommand(
    name: 'crud:generate:controller',
    description: 'Génère un controller de l\'entité',
)]
class CrudMakeControllerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::OPTIONAL, 'nom de l\entité')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Pour passer les erreurs et continuer');
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
        /* ----------------------- on récupère tous les champs ---------------------- */
        $docs = new ParserDocblock($entity);
        $gets = [];
        $formoptions = [];
        $search = [];
        foreach ($options = $docs->getOptions() as $name => $options) {
            if ($name != 'id') {
                switch ($select = $docs->getSelect($name)) {
                    case 'manytoone':
                        if ($name == 'user')
                            $gets[] = '$' . $entity . '->setUser($this->getUser());';
                        //else
                        //$gets[] = '$' . $entity . '->set' . ucfirst($name) . '($' . $entity . ');';
                        break;

                    case 'entity':
                        // $nom = substr($name, -1) == 's' ? substr($name, 0, -1) : $name;
                        // $gets[] = '$' . $entity . '->add' . $nom . '($' . $entity . ');';
                        if (isset($options['form']))  $formoptions['user_id'] = '$this->getUser()->getId()';
                        break;
                    case 'hiddenroot':
                        $formoptions['username'] = '$this->getUser()->getEmail()';
                        break;
                }
            }
            //création des recherche
            $search[] = 'a.' . $name . ' LIKE \'%" . $request->query->get(\'filterValue\') . "%\'';
        }
        $searchString = '." AND (' . implode(' OR ', $search) . ')"';
        $fieldslug = isset($options['slug']) ? $docs->getArgumentOfAttributes('slug', "Gedmo\Mapping\Annotation\Slug", 'fields')[0] : '';
        $fileController = __DIR__ . '/tpl/controller.incphp';
        $Lformoptions = '';
        foreach ($formoptions as $key => $value) {
            $Lformoptions .= "'$key'" . '=>' . $value . ',';
        }
        //AND (a.titre LIKE '%" . $request->query->get('filterValue') . "%' OR a.titre LIKE '%" . $request->query->get('filterValue') . "%')
        $html = CrudInitCommand::twigParser(file_get_contents($fileController), [
            'partie' => "/admin//",
            'fieldslug' => $fieldslug,
            'entity' => $entity,
            'Entity' => $Entity,
            'extends' => '/admin/base.html.twig',
            'sdir' =>  '',
            'ssdir' => '',
            'ordre' => isset($options['id']['ORDRE']) ? $options['id']['ORDRE'] : null,
            'index' => isset($docs->getOptions()['id']['index']) ? '.' . array_key_first($docs->getOptions()['id']['index']) :  $searchString,
            'delete' => isset($docs->getOptions()['id']['delete']) ?  array_key_first($docs->getOptions()['id']['delete']) : null,
            'gets' => isset($gets) ? implode("\n", $gets) : '',
            'formoptions' => isset($Lformoptions) ? $Lformoptions : ''

        ]);
        /** @var string $html */
        $blocks = (explode('//BLOCK', $html));
        //open model controller
        $fileController = __DIR__ . '/tpl/controller.incphp';
        if (!file_Exists($fileController)) {
            throw new Exception("Le fichier " . $fileController . ' est introuvable', 1);
        }
        //create file
        CrudInitCommand::updateFile("src/Controller/" . $Entity . 'Controller.php', $blocks, $input->getOption('force'));
        return Command::SUCCESS;
    }
}
