<?php

namespace App\Command\base;

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

#[AsCommand(
    name: 'crud:generate:type',
    description: 'Génère le fichier type de l\'entité',
)]
class CrudMakeTypeCommand extends Command
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
            $tab = [];
            //timetrait
            if ($name == 'createdAt' && isset($IDOptions['tpl']['no_created']))
                continue;
            if ($name == 'updatedAt' && isset($IDOptions['tpl']['no_updated']))
                continue;
            if ((!isset($options['tpl']['no_form']) && $name != 'id')) {
                switch ($select = $docs->getSelect($name)) {
                    case 'json':
                        $transform[] = "\$builder->get('keywords')\n->addModelTransformer(new CallbackTransformer(\n" .
                            "function (\$keywordsAsArray) {\n" .
                            "return implode(', ', \$keywordsAsArray);\n" .
                            "},\n" .
                            "function (\$keywordsAsString) {\n" .
                            "return explode(', ', \$keywordsAsString);\n" .
                            "}\n" .
                            "));\n";
                        $uses[] = "use Symfony\Component\Form\CallbackTransformer;";
                        break;
                    case 'text':
                    case 'simple':
                        $attrs['data-controller'] = 'base--ckeditor';
                        $attrs['data-base--ckeditor-toolbar-value'] = 'simple';
                        break;
                    case 'simplelanguage':
                        $attrs['data-base--ckeditor-toolbar-value'] = 'simplelanguage';
                        $attrs['data-controller'] = 'base--ckeditor';
                        break;
                    case 'full':
                        $attrs['data-controller'] = 'base--ckeditor';
                        $attrs['data-base--ckeditor-upload-value'] = $entity . '_simpleupload';
                        $attrs['data-base--ckeditor-toolbar-value'] = 'full';
                        break;
                    case 'normal':
                        $attrs['data-controller'] = 'base--ckeditor';
                        $attrs['data-base--ckeditor-toolbar-value'] = 'normal';
                        $attrs['data-base--ckeditor-upload-value'] = $entity . '_simpleupload';
                        break;
                    case 'password':
                        $tempadds = "->add('$name',RepeatedType::class,";
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\RepeatedType;";
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\PasswordType;";
                        $opts['type'] = 'PasswordType::class';
                        $opts['mapped'] = false;
                        $opts['first_options'] = array('label' => 'Mot de passe');
                        $opts['second_options'] = array('label' => 'Répétez le');
                        $opts['invalid_message'] = 'Les mots de passe ne correspondent pas';
                        break;
                    case 'fichier':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\FileType;";
                        $tempadds = "\n->add('$name',FileType::class,";
                        $opts['mapped'] = false;
                        $opts['required'] = false;
                        break;
                    case 'image':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\FileType;";
                        $tempadds = "\n->add('$name',FileType::class,";
                        $attrs['accept'] = 'image/*';
                        $opts['mapped'] = false;
                        $opts['required'] = false;
                        break;
                    case 'hidden':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\HiddenType;";
                        $tempadds = "\n->add('$name',HiddenType::class,";
                        break;
                    case 'hiddenroot':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\HiddenType;";
                        $tempadds = "\n->add('$name',HiddenType::class,";
                        $attrs['data-controller'] = 'base--hiddenroot';
                        $attrs['data-base--hiddenroot-code-value'] = "§\$AtypeOption[\"username\"]§";
                        $vars['username'] = "''";
                        $resolver['hiddenroot'] = '$resolver->setAllowedTypes(\'username\', \'string\')'; //mis le nom pour ne pas avoir de doublon
                        break;
                    case 'money':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\MoneyType;";
                        $tempadds = "\n->add('$name',MoneyType::class,";
                        break;
                    case 'collection':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\CollectionType;";
                        $target = substr($docs->getArgumentOfAttributes($name, 0, 'targetEntity'), strlen('App\Entity\\'));
                        $uses[] = "use App\Form\\" . $target . "Type;";
                        $tempadds = "->add('$name',CollectionType::class,";
                        $opts['entry_type!'] = $target . "Type::class";
                        $attrs['data-controller'] = 'base--collection';
                        $attrs['data-base--collection-valeurs-value!'] = 'json_encode($' . $name . ')';
                        //for entry use xtra for add option
                        if (isset($options['xtra'])) {
                            foreach ($options['xtra'] as $clef => $entry) {
                                $opts[$clef] = $entry;
                            }
                        }
                        $opts['by_reference'] = false;
                        $boucle[] = '$' . $name . ' = [];' . "\n" .
                            'foreach ($AtypeOption[\'data\']->get' . ucfirst($name) . '() as $prod) {' . "\n" .
                            '$' . $name . '[] = $prod->get' . ucfirst($options['options']['field']) . '();' . "\n" . '}';
                        break;
                    case 'choice':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\ChoiceType;";
                        $tempadds = "->add('$name',ChoiceType::class,";
                        $finalOpts = [];
                        if (ArrayHelper::isAssoc($options['options'])) {
                            foreach ($options['options'] as $key => $value) {
                                $finalOpts[$key] = $value;
                            }
                        } else {
                            foreach ($options['options'] as  $value) {
                                $finalOpts[$value] = $value;
                            }
                        }
                        $opts['choices'] =  $finalOpts;
                        break;
                    case 'choiceenplace':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\ChoiceType;";
                        $tempadds = "\n->add('$name',ChoiceType::class,";
                        //on garde que ce qui est affiché
                        $finalOpts = [];
                        foreach ($options['options'] as $key => $value) {
                            $finalOpts[$key] = $key;
                        }
                        $opts['choices'] =  $finalOpts;
                        break;
                    case 'color':
                        $uses[] = "use Symfony\Component\Form\Extension\Core\Type\ColorType;";
                        $tempadds = "\n->add('$name',ColorType::class,";
                        break;
                    case 'entity':
                        //get name of entity
                        $target = $docs->getArgumentOfAttributes($name, 0, 'targetEntity');
                        $EntityTarget = array_reverse(explode('\\', $target))[0];
                        $uses[] = "use Symfony\Bridge\Doctrine\Form\Type\EntityType;";
                        $uses[] = "use Doctrine\ORM\EntityRepository;";
                        $uses[] = "use $target;";
                        $tempadds = "\n->add('$name',EntityType::class,";
                        $opts['class'] = "¤$EntityTarget::class¤";
                        $opts['query_builder'] = "¤
                        function (EntityRepository \$er)";
                        if ($options)
                            $opts['query_builder'] .= 'use ($AtypeOption) ';

                        $opts['query_builder'] .= "{
                            return \$er->createQueryBuilder(\"u\")
                                ->orderBy(\"u.nom\", \"ASC\")
                                ->andwhere(\"u.deletedAt IS  NULL\")";
                        //si on a un formoptions
                        if (isset($options['form'])) {
                            $opts['query_builder'] .= "\n->andWhere(\"u." . $options['form'] . " = :user_id\")\n->setParameter(\"user_id\", \$AtypeOption[\"" . $options['form'] . "_id\"])";
                            $vars[$options['form'] . "_id"] = 0;
                            $resolver[] = '$resolver->setAllowedTypes(\'' . $options['form'] . '_id\', \'int\')';
                        }
                        $opts['query_builder'] .= ";}
                        ¤";
                        if (substr($docs->getAttributes($name)[0]->getName(), -4) == 'Many') {
                            $opts['multiple'] = true;
                        }
                        $opts['choice_label'] = array_keys($options['label'])[0];
                        break;
                    case 'generatedvalue': //id

                        break;
                    case 'datetime':
                        $opts['widget'] = 'single_text';
                        if ($name == 'createdAt') {
                            $uses[] = "use Symfony\Component\Form\Extension\Core\Type\HiddenType;";
                            $tempadds = "\n->add('$name',HiddenType::class,";
                        }
                        if ($name == 'updatedAt') {
                            $uses[] = "use Symfony\Component\Form\Extension\Core\Type\HiddenType;";
                            $opts['help'] = "Vide pour la date et l'heure d'enregistrement";
                            $adds[] = "->add('exupdatedAt',HiddenType::class,\narray ('mapped'=>false,'data'=>\$AtypeOption['data']->getupdatedAt()?\$AtypeOption['data']->getupdatedAt()->format('Y-m-d H:i:s'):null,\n'attr' =>\narray (\n),\n))";
                        }
                        break;
                    case 'integer':


                        break;
                    default: {
                            dump('non géré dans maketype:' . $select . '[' . $name . ']');
                        }
                }
                //surcharge opt
                $finalOpts = isset($options['opt']) ? array_merge($options['opt'], $opts) : $opts;
                $finalAttrs = isset($options['attr']) ? array_merge($options['attr'], $attrs) : $attrs;
                //add attrs in opt
                if (isset($finalAttrs)) {
                    $finalOpts['attr'] = $finalAttrs;
                }
                $tempopts = isset($finalOpts) ? CrudInitCommand::ArrayToKeyValue($finalOpts) : "";

                $chaine = $tempadds .  "\n" . $tempopts . ')';
                //on modifie les champs qui doivent ne pas être entre apostrophe
                $pos = 0;
                foreach (explode("\n", $chaine) as $key => $ligne) {
                    $pos = strpos($ligne, "!' => '");
                    if ($pos !== false) {
                        $ligne = str_replace("!' => '", "' =>", $ligne);
                        $ligne = substr($ligne, 0, -2) . ",";
                    }
                    $tab[] = $ligne;
                }

                $adds[] = implode("\n", $tab);
            }
        }
        $Lvars = '';
        foreach ($vars as $key => $value) {
            $Lvars .= "'$key'" . '=>' . $value . ',';
        }
        $fileType = dirname(__FILE__) . '/tpl/type.incphp';
        $html = CrudInitCommand::twigParser(
            file_get_contents($fileType),
            [
                'entity' => $entity,
                'Entity' => $Entity,
                'extends' => '/admin/base.html.twig',
                'sdir' => '',
                'adds' => ' $builder' . implode("\n", $adds),
                'uses' => implode("\n", array_unique($uses)),
                'vars' => isset($Lvars) ? $Lvars : '',
                'resolver' => isset($resolver) ? implode("\n,", $resolver) : '',
                'boucle' => isset($boucle) ? implode("\n", $boucle) : '',
                'transform' => isset($transform) ? implode("\n", $transform) : ''
            ]
        );
        /* ------------------------------ RETURN BLOCKS ----------------------------- */
        $html = str_replace(["'§", "§'", '"§', '§"'], '', $html);
        $blocks = (explode('//BLOCK', $html));
        CrudInitCommand::updateFile("src/Form/" . $Entity . 'Type.php', $blocks, $input->getOption('force'));
        return Command::SUCCESS;
    }
}
