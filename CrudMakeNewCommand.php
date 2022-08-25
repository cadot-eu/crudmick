<?php

namespace App\Command\base;

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
            ->addOption('comment', null, InputOption::VALUE_NONE, 'Pour afficher les commentaires');
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
        foreach ($docs->getOptions() as $name => $options) {
            //timetrait
            if ($name == 'createdAt' && isset($IDOptions['tpl']['no_created']))
                continue;
            if ($name == 'updatedAt' && isset($IDOptions['tpl']['no_updated']))
                continue;
            if (!isset($options['tpl']['no_form']) && $name != 'id') {
                switch ($select = $docs->getSelect($name)) {
                    case 'image':
                        $rows[] = '<div class="mb-3 row"> 
                        <label class="col-form-label col-sm-2" for="' . $entity . '_' . $name . '">
                        {{form_label(form.' . $name . ')}}
                        </label>
                        <div class="col-sm-8">
                        <p class="form-text mb-0 help-text"><i>pensez à nommer le fichier pour le SEO (accents , majuscule et minuscule, espace, -_. conservés)</i></p>
                        {{form_widget(form.' . $name . ')}}
                        {% if ' . $entity . '.' . $name . ' %}
                        <p data-controller="base--resetfile" data-base--resetfile-nom-value="' . $entity . '_' . $name . '" id="' . $entity . '_' . $name . '_help" class="form-text mb-0 help-text">{{form.vars.value.' . $name . '}}</p>
                        {% endif %}
                        </div>
                        <div class="col-sm-2 ">
                    {% if ' . $entity . '.' . $name . ' %}
                        <img  title="{{asset(form.vars.value.' . $name . ')}}" class="img-fluid" data-controller="base--bigpicture" ' . "
                        data-base--bigpicture-options-value='{\"imgSrc\": \"{{asset(form.vars.value.$name)}}\"}' alt=\"\" src='{{asset(form.vars.value.$name)|imagine_filter(\"petit\")}}' />" . '
                    {% endif %}
                        </div>
                </div>';
                        break;
                    case 'fichier':
                        $rows[] = '<div class="mb-3 row">
                        <label class="col-form-label col-sm-2" for="' . $entity . '_' . $name . '">
                        {{form_label(form.' . $name . ')}}
                        </label>
                        <div class="col-sm-10" >
                        <p class="form-text mb-0 help-text"><i>pensez à nommer le fichier pour le SEO (accents , majuscule et minuscule, espace, -_. conservés)</i></p>
                        {{form_widget(form.' . $name . ')}}
                        {% if ' . $entity . '.' . $name . ' %}
                        <p id="' . $entity . '_' . $name . '_help" class="form-text mb-0 help-text" data-controller="base--resetfile" data-base--resetfile-nom-value="' . $entity . '_' . $name . '">{{form.vars.value.' . $name . '}}</p>
                        {% endif %}
                        </div>
                       
                    </div>

                    </div>';
                        break;
                        // case 'readonlyroot': {
                        //         $resattrs = '';
                        //         $rows[] = '{% if app.user.email=="m@cadot.eu" %}{% set disabled=false %}{% else %} {% set disabled=true %}{% endif %}{{ form_row(form.' . $name . $resattrs . ',{"disabled":disabled}) }}' . "\n";
                        //     }
                        //     break;
                    default: {
                            $resattrs = ''; // count($attrs) > 1 ? ", { 'attr':{\n" . implode(",\n", $attrs) . "\n}\n}" : '';
                            $rows[] = '{{ form_row(form.' . $name . $resattrs . ') }}' . "\n";
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
            'form_rows' => implode("\n{#BLOCK#}\n", $rows),
            'entity' => $entity,
            'Entity' => $Entity,
            'extends' => '/admin/base.html.twig',
            'sdir' => ''
        ));
        CrudInitCommand::updateFile("templates/" . $entity . '/new.html.twig', $html, $input->getOption('comment'));
        return Command::SUCCESS;
    }
}
