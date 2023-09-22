<?php

namespace App\Command\crudmick;

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
        /* ----------------------- on récupère tous les champs ---------------------- */
        $docs = new ParserDocblock($entity);
        $gets = [];
        $formoptions = [];
        $search = [];
        foreach ($options = $docs->getOptions() as $name => $options) {
            if ($name != 'id') {
                switch ($select = $docs->getSelect($name)) {
                    case 'manytoone':
                        if ($name == 'user') {
                            $gets[] = '$' . $entity . '->setUser($this->getUser());';
                        }
                        //else
                        //$gets[] = '$' . $entity . '->set' . ucfirst($name) . '($' . $entity . ');';
                        break;
                    case '':
                        break;
                    case 'entity':
                        // $nom = substr($name, -1) == 's' ? substr($name, 0, -1) : $name;
                        // $gets[] = '$' . $entity . '->add' . $nom . '($' . $entity . ');';
                        if (isset($options['form'])) {
                            $formoptions['user_id'] = '$this->getUser()->getId()';
                        }
                        break;
                    case 'hiddenroot':
                        $formoptions['username'] = '$this->getUser()->getEmail()';
                        break;
                }
            }
            //création des recherche
            $search[] = 'a.' . $name . ' LIKE \'%" . $request->query->get(\'filterValue\') . "%\'';
        }
        $IDOptions = $docs->getOptions()['id'];
        $searchString = '." AND (' . implode(' OR ', $search) . ')"';
        $fieldslug = isset($options['slug']) ? $docs->getArgumentOfAttributes('slug', "Gedmo\Mapping\Annotation\Slug", 'fields')[0] : '';
        $fileController = __DIR__ . '/tpl/controller.incphp';
        $Lformoptions = '';
        foreach ($formoptions as $key => $value) {
            $Lformoptions .= "'$key'" . '=>' . $value . ',';
        }

        $fields = isset($IDOptions['search']) ? array_key_first($IDOptions['search']) : "[" . "'" . implode("', '", \array_keys($docs->getAllAlias())) . "'" . "]";
        $fields = 'fields:' . $fields;
        /* ------------------------- on limite la recherche ------------------------- */
        $limitSearch = null;
        if (isset($IDOptions['limit'])) {
            $limitSearch =  key($IDOptions['limit']);
        }
        /* -------------------- fin de la détection de limitation ------------------- */

        if (isset($IDOptions['ordre'])) {
            $limitVirgule = isset($limitSearch) ? ',' . $limitSearch : '';
            $search = '$dql= $' . $entity . 'Repository->findby([\'deletedAt\'=>null' . $limitSearch . '],[\'ordre\'=>\'ASC\']);';
            $paginator = "1, 1000";
        } else {
            $limitCrochets = isset($limitSearch) ? (',conditions:[' . $limitSearch . ']') : '';
            $search = '$dql = $' . $entity . 'Repository->index(search:$request->query->get(\'filterValue\', \'\'),' . $fields . $limitCrochets . ', sort:$request->query->get(\'sort\'),direction:$request->query->get(\'direction\'),deleted:false);';
            $paginator = "\$request->query->getInt('page', 1)";
        }
        $html = CrudInitCommand::twigParser(file_get_contents($fileController), [
            'partie' => "/admin//",
            'fieldslug' => $fieldslug,
            'entity' => $entity,
            'Entity' => $Entity,
            'extends' => '/admin/base.html.twig',
            'paginator' => $paginator,
            'limit' => isset($IDOptions['limit']) ? str_replace("'", "", 'if (isset($' . $entity . ') && $' . $entity . '->get' . ucfirst(str_replace(['=>', "'"], ['()!=', ""], key($IDOptions['limit']))) . ') {'  . '$this->addFlash("danger","Accès non autorisé");'  . 'return $this->redirectToRoute("' . $entity . '_index");' . '}') : null,
            'sdir' =>  '',
            'ssdir' => '',
            'search' => $search,
            'filter' => isset($IDOptions['tpl']['filter']) ?  '"' . $IDOptions['tpl']['filter'] . '"' : null,
            'fields' => $fields,
            'delete' => isset($docs->getOptions()['id']['delete']) ?  array_key_first($docs->getOptions()['id']['delete']) : null,
            'gets' => isset($gets) ? implode("\n", $gets) : '',
            'formoptions' => isset($Lformoptions) ? $Lformoptions : '',
            'slug' => isset($IDOptions['slug']) ? ucfirst(array_key_first($IDOptions['slug'])) : 'Id',

        ]);
        //open model controller
        $fileController = __DIR__ . '/tpl/controller.incphp';
        if (!file_Exists($fileController)) {
            throw new Exception("Le fichier " . $fileController . ' est introuvable', 1);
        }
        //create file
        CrudInitCommand::updateFile("src/Controller/" . $Entity . 'Controller.php', $html, $input->getOption('comment'), $input->getOption('speed'));
        return Command::SUCCESS;
    }
}
