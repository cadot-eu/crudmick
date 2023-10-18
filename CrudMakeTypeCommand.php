<?php

namespace App\Command\crudmick;

use App\Service\base\ArrayHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\base\ParserDocblock;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\ORM\EntityManagerInterface;

#[
    AsCommand(
        name: 'crud:generate:type',
        description: 'Génère le fichier type de l\'entité'
    )
]
class CrudMakeTypeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            'entity',
            InputArgument::OPTIONAL,
            'nom de l\entité'
        )->addOption(
            'comment',
            null,
            InputOption::VALUE_NONE,
            'Pour afficher les commentaires'
        )
            ->addOption(
                'speed',
                's',
                InputOption::VALUE_NONE,
                'Pour passer le formatage des fichiers'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
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
        $adds = [];
        $IDOptions = $docs->getOptions()['id'];
        $vars = [];
        $resolver = [];
        foreach ($docs->getOptions() as $name => $options) {
            $tempadds = '->add(\'' . $name . '\',null,';
            $opts = [];
            $attrs = [];
            $rowattrs = [];
            $tab = [];
            //timetrait
            if ($name == 'createdAt' && isset($IDOptions['tpl']['no_created'])) {
                continue;
            }
            if ($name == 'updatedAt' && isset($IDOptions['tpl']['no_updated'])) {
                continue;
            }
            if ($name == 'id' && !isset($IDOptions['tpl']['id'])) {
                continue;
            }
            if (!isset($options['tpl']['no_form'])) {
                foreach ($docs->getSelect($name) as $select) {
                    switch ($select) {
                        case 'json':
                            $transform[] =
                                "\$builder->get('$name')\n->addModelTransformer(new CallbackTransformer(\n" .
                                "function (\$keywordsAsArray) {\n" .
                                "return implode(',', \$keywordsAsArray);\n" .
                                "},\n" .
                                "function (\$keywordsAsString) {\n" .
                                "return explode(',', \$keywordsAsString);\n" .
                                "}\n" .
                                "));\n";
                            $uses[] = 'use Symfony\Component\Form\CallbackTransformer;';
                            break;
                        case 'simple':
                            $attrs['data-controller'] = 'base--suneditor';
                            $attrs['data-base--suneditor-toolbar-value'] = 'simple';
                            if (
                                !isset(
                                    $options['attr']['data-base--suneditor-upload-value']
                                )
                            ) {
                                $attrs['data-base--suneditor-upload-value'] = $entity;
                            }
                            break;
                        case 'stars':
                            //$uses[] =
                            //'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                            //$tempadds = "\n->add('$name',HiddenType::class,";
                            //$rowattrs['class'] = 'd-none mb-3 text-warning';
                            $attrs['class'] = 'd-none';
                            $stars = isset($options['options']) ? key($options['options']) : 5;
                            $attrs['data-controller'] = 'base--stars';
                            $attrs['data-base--stars-max-value'] = $stars;

                            break;
                        case 'drag':
                            $attrs['data-controller'] = 'base--drag';
                            if (\in_array('entity', $docs->getSelect($name))) {
                                $target = $docs->getArgumentOfAttributes($name, 0, 'targetEntity');
                                $EntityTarget = array_reverse(explode('\\', $target))[0];
                                $attrs['data-base--drag-entity-value'] = $EntityTarget;
                            } else
                                $attrs['data-base--drag-entity-value'] = $entity;
                            $attrs['data-base--drag-queryid-value'] = 'input.value';
                            break;
                        case 'vide':
                            $attrs['data-controller'] = 'base--suneditor';
                            $attrs['data-base--suneditor-toolbar-value'] = 'vide';
                            break;
                        case 'simplelanguage':
                            $attrs['data-base--suneditor-toolbar-value'] =
                                'simplelanguage';
                            $attrs['data-controller'] = 'base--suneditor';
                            if (isset($options['options']['init'])) $attrs['data-base--suneditor-init-value'] = json_encode($options['options']['init']);
                            break;
                        case 'annonce':
                        case 'full':
                        case 'normal':
                            $attrs['data-controller'] = 'base--suneditor';
                            $attrs['data-base--suneditor-toolbar-value'] = $select;
                            if (
                                !isset(
                                    $options['attr']['data-base--suneditor-upload-value']
                                )
                            ) {
                                $attrs['data-base--suneditor-upload-value'] = $entity;
                            }
                            if (isset($options['options']['init'])) $attrs['data-base--suneditor-init-value'] = json_encode($options['options']['init']);
                            break;
                        case 'password':
                            $tempadds = "->add('$name',RepeatedType::class,";
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\RepeatedType;';
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\PasswordType;';
                            $opts['type'] = 'PasswordType::class';
                            $opts['mapped'] = false;
                            $opts['first_options'] = ['label' => 'Mot de passe'];
                            $opts['second_options'] = ['label' => 'Répétez le'];
                            $opts['invalid_message'] =
                                'Les mots de passe ne correspondent pas';
                            break;
                        case 'fichier':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\FileType;';
                            $tempadds = "->add('$name',FileType::class,";
                            $attrs['accept'] =
                                'image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,application/x-7z-compressed';
                            $opts['mapped'] = false;
                            $opts['required'] = false;
                            break;
                        case 'video':
                        case 'image':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\FileType;';
                            $tempadds = "->add('$name',FileType::class,";
                            $attrs['accept'] = $select . '/*';
                            $opts['mapped'] = false;
                            $opts['required'] = false;
                            break;

                        case 'email':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\EmailType;';
                            $tempadds = "->add('$name',EmailType::class,";
                            break;
                        case 'siret':
                        case 'iban':
                        case 'bic':
                            $attrs['data-controller'] = 'base--mask';
                            $attrs['data-base--mask-alias-value'] = $select;
                            break;
                        case 'id':
                            if (isset($IDOptions['tpl']['id'])) {
                                $uses[] =
                                    'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                                $tempadds = "->add('$name',HiddenType::class,";
                                $rowattrs['class'] = 'd-none ';
                            }
                            break;
                        case 'hidden':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                            $tempadds = "->add('$name',HiddenType::class,";
                            break;
                        case 'hiddenroot':
                            $rowattrs['class'] = 'd-none mb-3 text-warning';
                            $attrs['class'] = 'form-control';
                            $attrs['data-controller'] = 'base--hiddenroot';
                            $attrs['data-base--hiddenroot-code-value'] =
                                "§\$AtypeOption[\"username\"]§";
                            $vars['username'] = "''";
                            $resolver['hiddenroot'] =
                                '$resolver->setAllowedTypes(\'username\', \'string\')';
                            //mis le nom pour ne pas avoir de doublon
                            break;
                        case 'readonlyroot':
                            $rowattrs['class'] = 'd-none ';
                            $attrs['data-controller'] = 'base--readonlyroot';
                            $attrs['data-base--readonlyroot-code-value'] =
                                "§\$AtypeOption[\"username\"]§";
                            $vars['username'] = "''";
                            $resolver['hiddenroot'] =
                                '$resolver->setAllowedTypes(\'username\', \'string\')';
                            //mis le nom pour ne pas avoir de doublon
                            break;
                        case 'adresse':
                            $attrs['data-controller'] = 'base--adresse';
                            break;
                        case 'money':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\MoneyType;';
                            $tempadds = "->add('$name',MoneyType::class,";
                            $attrs['step'] = '0.01';
                            $opts['currency'] = 'EUR';
                            $opts['html5'] = true;
                            break;
                        case 'nombre':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\NumberType;';
                            $tempadds = "->add('$name',NumberType::class,";
                            break;
                        case 'telephone':
                            $attrs['data-controller'] = 'base--mask';
                            $attrs['data-base--mask-alias-value'] = 'telephone';
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\TelType;';
                            $tempadds = "->add('$name',TelType::class,";
                            break;
                        case 'collection':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\CollectionType;';
                            $target = substr(
                                $docs->getArgumentOfAttributes($name, 0, 'targetEntity'),
                                strlen('App\Entity\\')
                            );
                            $uses[] = 'use App\Form\\' . $target . 'Type;';
                            $tempadds = "->add('$name',CollectionType::class,";
                            $opts['entry_type!'] = $target . 'Type::class';
                            //for entry use xtra for add option
                            if (isset($options['xtra'])) {
                                foreach ($options['xtra'] as $clef => $entry) {
                                    $opts[$clef] = $entry;
                                }
                            }
                            $opts['by_reference'] = false;
                            $boucle[] = '$' . $name . ' = [];' . "\n" . 'foreach ($AtypeOption[\'data\']->get' . ucfirst($name) . '() as $prod) {' . "\n" . '$' . $name . '[] = $prod->get' . ucfirst($options['options']['field']) . '();' . "\n" . '}';
                            break;
                        case 'choice':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\ChoiceType;';
                            $tempadds = "->add('$name',ChoiceType::class,";
                            $finalOpts = [];
                            //si on a options
                            if (isset($options['options'])) {
                                if (ArrayHelper::isAssoc($options['options'])) {
                                    foreach ($options['options'] as $key => $value) {
                                        $finalOpts[$key] = $value;
                                    }
                                } else {
                                    foreach ($options['options'] as $value) {
                                        $finalOpts[$value] = $value;
                                    }
                                }
                            }
                            //si on a extra et que l'on veut envoyer un array par un repository
                            if (isset($options['xtra'])) {
                                $method = $options['xtra']['method'];
                                $entite = $options['xtra']['entity'];
                                //on renvoie le retour de la method par entitymanagerinterface
                                $finalOpts = $this->em->getRepository($entite)->$method();
                            }
                            $opts['choices'] = $finalOpts;
                            break;
                        case 'onechoiceenplace':
                        case 'choiceenplace':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\ChoiceType;';
                            $tempadds = "->add('$name',ChoiceType::class,";
                            //on garde que ce qui est affiché
                            $finalOpts = [];
                            foreach ($options['options'] as $key => $value) {
                                $finalOpts[$key] = $key;
                            }
                            $opts['choices'] = $finalOpts;
                            break;
                        case 'color':
                            $uses[] =
                                'use Symfony\Component\Form\Extension\Core\Type\ColorType;';
                            $tempadds = "->add('$name',ColorType::class,";
                            break;
                        case 'entity':
                            //get name of entity
                            $target = $docs->getArgumentOfAttributes($name, 0, 'targetEntity');
                            if (!$target) dd('pas de target entity pour ' . $name);
                            $EntityTarget = array_reverse(explode('\\', $target))[0];
                            $uses[] = 'use Symfony\Bridge\Doctrine\Form\Type\EntityType;';
                            $uses[] = 'use Doctrine\ORM\EntityRepository;';
                            $uses[] = "use $target;";
                            $tempadds = "->add('$name',EntityType::class,";
                            $opts['class'] = "¤$EntityTarget::class¤";
                            $opts['query_builder'] = "¤function (EntityRepository \$er)";
                            if ($options) {
                                $opts['query_builder'] .= 'use ($AtypeOption) ';
                            }
                            $ordre = isset($options['ordre']) ? key($options['ordre']) : 'id';
                            $opts['query_builder'] .= "{
                            return \$er->createQueryBuilder(\"u\")
                                ->orderBy(\"u.$ordre\", \"ASC\")
                                ->andwhere(\"u.deletedAt IS  NULL\")";
                            if (strpos($docs->getAttributes($name)[0]->getName(), 'OneToMany') !== false) {
                                $mappedby = ($docs->getAttributes($name)[0]->getArguments()['mappedBy']);
                                $opts['query_builder'] .= "->andWhere(\"u." .
                                    $mappedby . " IS NULL\")";
                            }
                            //si on a un formoptions
                            if (isset($options['form'])) {
                                $opts['query_builder'] .= "\n->andWhere(\"u." . $options['form'] . " = :user_id\")\n->setParameter(\"user_id\", \$AtypeOption[\"" . $options['form'] . "_id\"])";
                                $vars[$options['form'] . '_id'] = 0;
                                $resolver[] = '$resolver->setAllowedTypes(\'' . $options['form'] . '_id\', \'int\')';
                            }
                            $opts['query_builder'] .= ";}
                        ¤";
                            if (substr($docs->getAttributes($name)[0]->getName(), -4) == 'Many') {
                                $opts['multiple'] = true;
                            }
                            if (!$options['label']) {
                                die('Il manque l\'option label pour le champ ' . $name . ' de l\'entité ' . $entity . "\n");
                            } else {
                                $opts['choice_label'] = key($options['label']);
                            }
                            break;
                        case 'generatedvalue': //id
                            break;
                        case 'datetime':
                            //$opts['widget'] = 'single_text';
                            if ($name == 'createdAt') {
                                $uses[] =
                                    'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                                $tempadds = "->add('$name',HiddenType::class,";
                            }
                            if ($name == 'updatedAt' && !isset($IDOptions['tpl']['no_updated'])) {
                                $uses[] =
                                    'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                                $opts['help'] =
                                    "Vide pour la date et l'heure d'enregistrement";
                                $adds[] =
                                    "->add('exupdatedAt',HiddenType::class,\narray ('mapped'=>false,'data'=>\$AtypeOption['data']->getupdatedAt()?\$AtypeOption['data']->getupdatedAt()->format('Y-m-d H:i:s'):null,\n'attr' =>\narray (\n),\n))";
                            }
                            break;
                        case 'integer':
                        case 'string':
                            break;
                        case 'pass':
                            break;
                        default:
                            if ($input->getOption('comment') != false && !in_array($name, ['updatedAt', 'createdAt', 'deletedAt'])) {
                                $output->writeln('- non géré dans maketype:' . $select . '[' . $name . ']');
                            }
                    }
                }
                //gestion de certain par les noms de champs
                if (!in_array($name, ['updatedAt', 'createdAt', 'deletedAt'])) {
                    switch ($name) {
                        case 'slug':
                            $opts['required'] = false;
                            // $uses[] =
                            //  'use Symfony\Component\Form\Extension\Core\Type\HiddenType;';
                            // $tempadds = "\n->add('$name',HiddenType::class,";
                            $attrs['data-controller'] = 'base--resetinput';
                            //ajoute l'id de l'entité
                            $opts['help'] = "Vide pour auto-génération du slug";

                            break;
                        default:
                            //dans le cas ou on a pas de type donné ni de nom connu
                            if ($docs->getSelect($name) == '') {
                            }
                            break;
                    }
                }
                //surcharge opt
                $finalOpts = (isset($options['opt']) and $options['opt'] != []) ? array_merge($options['opt'], $opts) : $opts;
                $finalAttrs = isset($options['attr']) ? array_merge($options['attr'], $attrs) : $attrs;
                $finalRowAttrs = isset($options['row_attr']) ? array_merge($options['row_attr'], $rowattrs) : $rowattrs;
                //add attrs in opt
                if (isset($finalAttrs) and $finalAttrs != []) {
                    $finalOpts['attr'] = $finalAttrs;
                }
                //add row_attrs in opt
                if (isset($finalAttrs) and $finalRowAttrs != []) {
                    $finalOpts['row_attr'] = $finalRowAttrs;
                }
                $tempopts = (isset($finalOpts) and $finalOpts != []) ? CrudInitCommand::ArrayToKeyValue($finalOpts) : '';
                if (substr($tempadds, -6) == ',null,' and $tempopts == '')
                    $tempadds = substr($tempadds, 0, -6);
                $chaine = $tempadds .  $tempopts . ')';

                //on modifie les champs qui doivent ne pas être entre apostrophe
                $pos = 0;
                foreach (explode("\n", $chaine) as $key => $ligne) {
                    $pos = strpos($ligne, "!' => '");
                    if ($pos !== false) {
                        $ligne = str_replace("!' => '", "' =>", $ligne);
                        $ligne = substr($ligne, 0, -2) . ',';
                    }
                    $tab[] = $ligne;
                }

                $adds[] = implode("", $tab);
            }
        }
        $Lvars = '';
        foreach ($vars as $key => $value) {
            $Lvars .= "'$key'" . '=>' . $value . ',';
        }
        $fileType = dirname(__FILE__) . '/tpl/type.incphp';
        $html = CrudInitCommand::twigParser(file_get_contents($fileType), [
            'entity' => $entity,
            'Entity' => $Entity,
            'extends' => '/admin/base.html.twig',
            'sdir' => '',
            'adds' => ' $builder' . "\n" . implode("\n", $adds),
            'uses' => implode("\n", array_unique($uses)),
            'vars' => isset($Lvars) ? $Lvars : '',
            'resolver' => isset($resolver) ? implode("\n,", $resolver) : '',
            'boucle' => isset($boucle) ? implode("\n", $boucle) : '',
            'transform' => isset($transform) ? implode("\n", $transform) : '',
        ]);
        //pour supprimler les " dans le fichier
        //pour les champs "§\$AtypeOption[\"username\"]§" => \$AtypeOption[\"username\"]
        $html = str_replace(["'§", "§'"], '', $html);
        /* ------------------------------ RETURN BLOCKS ----------------------------- */
        CrudInitCommand::updateFile('src/Form/' . $Entity . 'Type.php', $html, $input->getOption('comment'), $input->getOption('speed'));
        return Command::SUCCESS;
    }
}
