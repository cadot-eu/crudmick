<?php

namespace App\Command\crudmick;

use App\Service\base\ParserDocblock;
use PhpParser\Node\Stmt\Break_;
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
    name: 'crud:generate:new',
    description: 'Génère le fichier new de l\'entité',
)]
class CrudMakeNewCommand extends Command
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
                    //ajoute selectandcopyelement_controlller
                case 'select':
                    $rows[] = '
                    <div data-controller="base--SelectAndCopyElement" 
                    data-base--SelectAndCopyElement-entitie-value="' . $value['entitie'] . '" 
                    data-base--SelectAndCopyElement-affichage-value="' . $value['affichage'] . '"
                    data-base--SelectAndCopyElement-champs-value="' . $value['champs'] . '"
                    data-base--SelectAndCopyElement-limit-value="' . $value['limit'] . '"
                    data-base--SelectAndCopyElement-copy-value="' . $value['copy'] . '"  
                    data-base--SelectAndCopyElement-copyurl-value="' . $value['copyurl'] . '"  
                    ></div>';
                    break;
            }
        }

        foreach ($docs->getOptions() as $name => $options) {
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
                    $resattrs = isset($options['resattrs']) ? $options['resattrs'] : '';
                    switch ($select) {
                        case 'fichier':
                        case 'image':
                            $texterow =  '<div class="mb-3 row"> 
                        <label class="col-form-label col-sm-2" for="' . $entity . '_' . $name . '">
                        {{form_label(form.' . $name . ')}}
                        </label>
                        <div class="col-sm-8">
                        ';
                            if (isset($options['opt']['help'])) {
                                $texterow .= '{{form_help(form.' . $name . ')}}';
                            } else {
                                $texterow .= '<p class="form-text mb-0 help-text"><i>pensez à nommer le fichier pour le SEO (accents , majuscule et minuscule, espace, -_. conservés)</i></p>';
                            }
                            $texterow .= '{{form_widget(form.' . $name . ')}}
                        {% if ' . $entity . '.' . $name . ' %}
                        <p data-controller="base--resetfile" data-base--resetfile-nom-value="' . $entity . '_' . $name . '" id="' . $entity . '_' . $name . '_help" class="form-text mb-0 help-text"><a target="_blank" href="' . '/{{form.vars.value.' . $name . '}}">{{form.vars.value.' . $name . '}}</a></p>
                        {% endif %}
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">';
                            if ($select == 'image') {
                                $texterow .= ' {% if form.vars.value.' . $name . ' %}<img  src="{{asset(form.vars.value.' . $name . ')}}" class="img-fluid border " data-controller="base--bigpicture" ' . "
                            data-base--bigpicture-options-value='{\"imgSrc\": \"{{asset(form.vars.value.$name)}}\"}'  />{% endif %}";
                            }
                            $texterow .= '</div>
                </div>';
                            $rows[] = $texterow;
                            break;

                            // case 'readonlyroot': {
                            //         $resattrs = '';
                            //         $rows[] = '{% if app.user.email=="m@cadot.eu" %}{% set disabled=false %}{% else %} {% set disabled=true %}{% endif %}{{ form_row(form.' . $name . $resattrs . ',{"disabled":disabled}) }}' . "\n";
                            //     }
                            //     break;

                        case 'pass':
                            break;
                        case 'hidden':
                            if (isset($options['value']))
                                $rows[] = '{{ form_row(form.' . $name . ',{"attr":{\'value\':' . key($options['value']) . '}}) }}' . "\n";
                            break;
                            // case 'invisible':
                            //     $rows[] = '{{ form_row(form.' . $name  . ',{"attr":{\'hidden\':""}}) }}' . "\n";
                            //     break;
                        case 'collection':
                            //on utilise ce stratagème pour récupérer les noms de fichiers qui sont ensuite ajouter par collection.js si on a des fichiers
                            $rows[] = '
                        {% for item in  form.vars.value.' . $name . '  %}
                        {% if item.fichier is defined %}
			<input type="hidden" champ="' . $entity . '_' . $name . '_{{loop.index0}}_fichier" class="ex_valeurs_fichiers" value="{{item.fichier}}"/>
                        {% endif %}
		{% endfor %}
        {{ form_row(form.' . $name . $resattrs . ') }}' . "\n";
                            break;
                        case 'entity':
                            $rows[] = '
                        <div class="mb-3 row">
                            <label class="col-form-label col-sm-2" for="' . $entity . '_' . $name . '">
                            {{form_label(form.' . $name . ')}}
                            </label>
                            <div class="col-sm-10" >
                            {{form_widget(form.' . $name . ',{"attr":{"class":"d-flex justify-content-between flex-wrap"} }) }}
                            <div id="' . $entity . '_' . $name . '_help" class="form-text mb-0 help-text">{{form_help(form.' . $name . ')}}</div>
                            </div>
                            
                        </div>';
                            break;
                        case 'drag':
                            break;

                        default: {
                                if (!isset($options['resattrs'])) $resattrs = ''; // count($attrs) > 1 ? ", { 'attr':{\n" . implode(",\n", $attrs) . "\n}\n}" : '';
                                $rows[] = '{{ form_row(form.' . $name . $resattrs . ') }}' . "\n";
                            }
                    }
                }
            }
        }
        //open model controller
        $fileNew = __DIR__ . '/tpl/new.html.twig';
        if (!file_Exists($fileNew)) {
            throw new Exception("Le fichier " . $fileNew . " est introuvable", 1);
        }
        $html = CrudInitCommand::twigParser(file_get_contents($fileNew), array(
            'form_rows' => implode("\n\n", $rows),
            'entity' => $entity,
            'Entity' => $Entity,
            'viewerUrl' => isset($IDOptions['viewer']) ? $IDOptions['viewer']['url'] : "false",
            'viewerChamp' => isset($IDOptions['viewer']) ? $IDOptions['viewer']['champ'] : "false",
            'extends' => '/admin/base.html.twig',
            'sdir' => ''
        ));
        CrudInitCommand::updateFile("templates/" . $entity . '/new.html.twig', $html, $input->getOption('comment'), $input->getOption('speed'));

        return Command::SUCCESS;
    }
}
