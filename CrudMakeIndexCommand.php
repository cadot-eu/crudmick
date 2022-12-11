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
    name: 'crud:generate:index',
    description: 'Génère le fichier index de l\'entité',
)]
class CrudMakeIndexCommand extends Command
{
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
        $th = []; //contient les th pour l'entete du tableau
        $docs = new ParserDocblock($entity);
        foreach ($docs->getOptions() as $name => $options) {
            //creation des th
            if (!isset($options['tpl']['no_index']) && $name != 'deletedAt' && $name != 'createdAt' && $name != 'updatedAt') {
                if (isset($docs->getOptions()['id']['order']) || in_array($docs->getType($name), ['manytomany', 'onetomany'])) {
                    $th[] = "<th >$name";
                } else {
                    $th[] = "<th {{pagination.isSorted('a.$name')?\"class='sorted'\"}}>
                {{ knp_pagination_sortable(pagination, '$name', 'a.$name') }}";
                }
            }
        }
        /* ------------------------- creation des idoptions ------------------------- */
        $IDOptions = $docs->getOptions()['id'];
        //gestion du timetrait
        $thtime = '';
        if (!isset($IDOptions['tpl']['no_deleted']))
            $thtime .= <<<'EOT'
        {%if action=="deleted" %}
            <th>
            <a class="btn btn-outline-primary {{ app.request.query.get(" tri") == 'deletedAt' ? 'active' }} " href='?tri=deletedAt&&ordre={{ app.request.query.get("ordre")=="DESC" ? "ASC":"DESC" }}'>effacé</a>
            
        {% endif %}
        EOT;
        /* ------------------------- datetime avec knpsorted ------------------------ */
        if (!isset($IDOptions['tpl']['no_created']))
            $th[] = "<th {{pagination.isSorted('a.createdAt')?\"class='sorted'\"}}>
        {{ knp_pagination_sortable(pagination, 'créé', 'a.createdAt') }}";
        if (!isset($IDOptions['tpl']['no_updated']))
            if (isset($docs->getOptions()['id']['order'])) {
                $th[] = "<th >Mis à jour";
            } else {
                $th[] = "{{pagination.isSorted('a.updatedAt')?\"class='sorted'\"}}>
        {{ knp_pagination_sortable(pagination, 'mis à jour', 'a.updatedAt') }}";
            }
        /* ---------------------------------- body ---------------------------------- */
        $tableauChoice = '';
        foreach ($docs->getOptions() as $name => $options) {
            $class = []; //contient les class à insérer

            /* ----------------------------- ajout des class ---------------------------- */
            if (isset($options['class'])) {
                $class[] = implode(' ', array_keys($options['class']));
            }
            /* ---------------------------- gestion des twigs --------------------------- */
            $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|raw';
            /* ----------------------------- création des td ---------------------------- */
            if (!isset($options['tpl']['no_index'])) {
                switch ($select = $docs->getSelect($name)) {
                    case 'generatedvalue': //id
                    case 'hiddenroot':
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" > {{' . "$Entity.$name$twig" . '}}' . "\n";;
                        break;
                    case 'simple':
                    case 'simplelanguage':
                    case 'full':
                    case 'normal':
                    case 'text':
                        $twig = isset($options['twig']) ?  $twig : '|striptags|u.truncate(20, \'...\')';
                        $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                        break;
                    case '':
                    case 'string':
                    case 'email':
                        $twig = isset($options['twig']) ?  $twig : '|striptags|u.truncate(40, \'...\')';
                        $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                        break;
                    case 'drapeau':
                        ///node_modules/flag-icons/flags/1x1/fr.svg
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name" . '}}">' . "{% if $Entity.$name %} <img src=\"/build/flags/1x1/{{ $Entity.$name }}.svg\" style=\"max-height:2rem\"> {% endif %} \n";
                        break;
                    case 'manytoone':
                        $champ = isset($options['options']['champ']) ? $options['options']['champ'] : 'id';
                        $twig = isset($options['twig']) ?  $twig : '|striptags|u.truncate(40, \'...\')';
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name?$Entity.$name.$champ" . '}}"> {{' . "$Entity.$name? $Entity.$name.$champ$twig" . '}}' . "\n";
                        break;
                    case 'money':
                    case 'choice':
                        $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                        $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                        break;
                    case 'integer':
                        if (isset($IDOptions['order']) && array_key_exists($name, $IDOptions['order'])) {
                            $actions = ['top' => 'arrow-bar-up', 'up' => 'arrow-up', 'down' => 'arrow-down', 'bottom' => 'arrow-bar-down'];
                            $chaine = '';
                            foreach ($actions as $action => $icon) {
                                $chaine .= "
                                    <a href=\"{{path('change_ordre',{'entity':'$Entity','id':$Entity.id,'ordre':'$Entity.$name','action':'$action'})}}\" class=\"text-decoration-none\" title=\"$action\">
                                    <i class=\"bi bi-$icon\"></i>
                                    </a>
                                ";
                            }
                            $td[] = " <td class='my-auto " . implode(' ', $class) . "' >" . $chaine . "";
                        } else {
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" > {{' . "$Entity.$name$twig" . '}}' . "\n";
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twig" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                        }
                        break;
                    case 'image':
                        $tdtemp = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twig" . '}}"> ';
                        if (isset($options['tpl']) && isset($options['tpl']['index_FileImage'])) {
                            //retourne une miniature
                            $tdtemp .= "{% if $Entity.$name is not empty %}<span title=\"{{TBgetFilename($Entity.$name)$twig}}\" data-controller='base--bigpicture' data-base--bigpicture-options-value='{\"imgSrc\": \"{{asset($Entity.$name)}}\"}'><img src=\"{{asset($Entity.$name)|imagine_filter('icone')}}\" class=\"img-fluid\"></span> {% endif %}";
                        } elseif (isset($options['tpl']) && isset($options['tpl']['index_FileImageNom'])) {
                            //retourne une miniature et le nom du fichier
                            $tdtemp .= "{% if $Entity.$name is not empty %}<span data-controller='base--bigpicture' data-base--bigpicture-options-value='{\"imgSrc\": \"{{asset($Entity.$name)}}\"}'><img src=\"{{asset($Entity.$name)|imagine_filter('icone')}}\" class=\"img-fluid me-2\">{{TBgetFilename($Entity.$name)$twig}}</span> {% endif %}";
                        } else {
                            //retoune que le nom du fichier
                            $tdtemp .= "{% if $Entity.$name is not empty %}<span data-controller='base--bigpicture' data-base--bigpicture-options-value='{\"imgSrc\": \"{{asset($Entity.$name)}}\"}'>{{TBgetFilename($Entity.$name)$twig}}</span> {% endif %}";
                        }
                        $td[] = $tdtemp . '' . "\n";
                        break;
                    case 'fichier':
                        $tdtemp = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twig" . '}}"> ';
                        //retoune que le nom du fichier
                        $tdtemp .= "{% if $Entity.$name is not empty %}<a href=\"{{asset($Entity.$name)}}\" title=\"{{TBgetFilename($Entity.$name)}}\" target=\"_blank\">{{TBgetFilename($Entity.$name)$twig|u.truncate(50, \"...\")}}</span> {% endif %}</a>";
                        $td[] = $tdtemp . '' . "\n";
                        break;
                    case 'choiceenplace':
                        $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '';
                        $tableauChoice .= '{% set choice_' . $name . '=' . json_encode($options['options']) . ' %}' . "\n";
                        //création de la ligne
                        $td[] = "<td class=\"my-auto\">
                                {% set retour=0 %}
                                {% for test,value in choice_$name %}
                                    {% if test==$Entity.$name %}
                                        {% set retour=loop.index0 %}
                                    {% endif %}
                                {% endfor %}
                                {% if retour+1==choice_$name|length %}
                                    {% set numr=0 %}
                                {% else %}
                                    {% set numr=retour+1 %}
                                {% endif %}
                                <a href=\"{{path('" . $entity . "_champ',{'id':$Entity.id,'type':'" . $name . "','valeur':choice_" . $name . "|keys[numr]})}}\" style='font-size:2rem;'  title='{{ choice_" . $name . "|keys[retour]}}'> {{ choice_" . $name . "[ choice_" . $name . "|keys[retour]]$twig|raw}}</a>\n";
                        break;
                    case 'onechoiceenplace':
                        $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '';
                        $tableauChoice .= '{% set choice_' . $name . '=' . json_encode($options['options']) . ' %}' . "\n";
                        //création de la ligne
                        $td[] = "<td class=\"my-auto\">
                                    {% set retour=0 %}
                                    {% for test,value in choice_$name %}
                                        {% if test==$Entity.$name %}
                                            {% set retour=loop.index0 %}
                                        {% endif %}
                                    {% endfor %}
                                    {% if retour+1==choice_$name|length %}
                                        {% set numr=0 %}
                                    {% else %}
                                        {% set numr=retour+1 %}
                                    {% endif %}
                                    <a href=\"{{path('" . $entity . "_champ',{'one':true,'id':$Entity.id,'type':'" . $name . "','valeur':choice_" . $name . "|keys[numr]})}}\" style='font-size:2rem;'  title='{{ choice_" . $name . "|keys[retour]}}'> {{ choice_" . $name . "[ choice_" . $name . "|keys[retour]]$twig|raw}}</a>\n";
                        break;
                    case 'color':
                        $td[] = '<td class="my-auto"><div class="boxcolor" style="background-color:{{' . $Entity . '.' . $name . '}}"></div>' . "\n";
                        break;
                    case 'collection':
                        //field for show
                        $return = isset($options['label']) ? $options['label'] : 'id';
                        //for separate field
                        $separation = isset($options['separation']) ? $options['separation'] : ';';
                        $td[] = '<td class="my-auto">' . "{% for " . $name . "_item in " . $Entity . ".$name %}\n{{" . $name . "_item.$return$twig}}{{loop.last?'':'$separation'}}\n{% endfor %}" . "\n";
                        break;
                    case 'entity':
                        //field for show
                        $return = isset($options['options']['label']) ? $options['options']['label'] : 'id';
                        if ($docs->getType($name) == 'manytomany'  || $docs->getType($name) == 'onetomany') {
                            //for separate field
                            $separation = isset($options['separation']) ? $options['separation'] : ';';
                            $td[] = '<td class="my-auto">' . "{% for " . $name . "_item in " . $Entity . ".$name %}\n{{" . $name . "_item.$return$twig}}{{loop.last?'':'$separation'}}\n{% endfor %}" . "\n";
                        } else {
                            $td[] = '<td class="my-auto">' . '{{ ' . $Entity . '.' . $name . '.' . $return . ' is defined ? ' . $Entity . '.' . $name . '.' . $return . '}}' . "\n";
                        }
                        break;
                    case 'array':
                        $return = isset($options['label']) ? array_keys($options['label'])[0] : 'id';
                        $separation = isset($options['separation']) ? $options['separation'] : ';';
                        $td[] = '<td class="my-auto">' . "{% for " . $name . "_item in " . $Entity . ".$name %}\n{{" . $name . "_item.$return$twig}}{{loop.last?'':'$separation'}}\n{% endfor %}" . "\n";
                        break;
                    case 'json':
                        $twig = isset($options['twig']) ? $options['twig'] : '|join(",")';
                        $td[] = '<td class="my-auto text-center' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twig" . '}}"> ' . '<i class="bi bi-zoom-in"></i>' . "\n";
                        break;
                    case 'slug':
                        $td[] = '<td class="my-auto text-center clipboard' . implode(' ', $class) . '"  data-clipboard-text="{{' . "$Entity.$name$twig" . '}}" title="{{' . "$Entity.$name$twig" . '}}"> ' . '<i class="bi bi-clipboard"></i>' . "\n";
                    default:
                        if ($input->getOption('comment') != false && !in_array($name, ['updatedAt', 'createdAt', 'deletedAt'])) {
                            $output->writeln('- non géré dans makeindex:' . $select);
                        }
                        break;
                }
            }
        }
        /* -------------------------- protection par nocrud ------------------------- */
        if ((isset($IDOptions['nocrud']))) {
            $io->warning("This Entity is protected against crud");
        }
        /* --------------------------- gestion des actions -------------------------- */
        $actions = [
            // 'new' => 'icone bi bi-file-plus',
            'edit' => 'icone bi bi-pencil-square'
        ];
        foreach ($actions as $action => $title) {
            if (!isset($IDOptions['tpl']['no_action_' . $action])) {
                $resaction[$action] =  "<a class='btn btn-xs btn-primary'   data-turbo=\"false\" title='$action' href=\"{{ path('$entity" . "_$action" . "', {'id': $Entity.id }) }}\"><i class='icone $title'></i></a>";
            }
        }
        if (isset($IDOptions['tpl']['action_clone']))
            $resaction['clone'] =  "<a class='btn btn-xs btn-primary'  title='clone' href=\"{{ path('$entity" . "_clone" . "', {'id': $Entity.id }) }}\"><i class='icone  bi bi-file-earmark-plus'></i></a>";
        /* ----------------------------- timestamptable ----------------------------- */
        //timestamptable
        $timestamptable = ['createdAt', 'updatedAt', 'deletedAt'];
        foreach ($timestamptable as $time) {
            if (!isset($IDOptions['tpl']['no_' . substr($time, 0, -2)])) {
                if ($time == 'deletedAt') {
                    $td[] .= "{%if action==\"deleted\" %}<td>{{ $Entity.$time is not empty ? $Entity.$time|date('d/m à H:i', 'Europe/Paris')}}{% endif %}";
                } else {
                    $td[] .= "<td>{{ $Entity.$time is not empty ? $Entity.$time|date('d/m à H:i', 'Europe/Paris'):'---'}}";
                }
            }
        }
        /* --------------------------------- hide BY ID -------------------------------- */
        $ifhide = 'true ';
        if ((isset($IDOptions['hide']))) {
            foreach ($IDOptions['hide'] as $champ => $hide) {
                $ifhide .= "and $Entity." . $champ . "  != '" . $hide . "'";
            }
        }

        //open model controller
        $fileIndex = __DIR__ . '/tpl/index.html.twig';
        if (!file_Exists($fileIndex)) {
            throw new Exception("Le fichier " . $fileIndex . " est introuvable", 1);
        }
        $html = CrudInitCommand::twigParser(file_get_contents($fileIndex), [
            'hide' => $ifhide,
            'rows' => implode("\n", $td),
            'entete' => implode("\n", $th),
            'entity' => $entity,
            'Entity' => $Entity,
            'order' => isset($IDOptions['order']) ?: 'false',
            'no_action_edit' => implode("\n", $resaction),
            'extends' => '/admin/base.html.twig',
            'no_action_add' => !isset($IDOptions['tpl']['no_action_add']) ? "true" : "false",
            'no_access_deleted' => !isset($IDOptions['tpl']['no_action_deleted']) ? "true" : "false",
            'tableauChoice' => $tableauChoice,
        ]);
        /** @var string $html */
        CrudInitCommand::updateFile("templates/" . $entity . '/index.html.twig', $html, $input->getOption('comment'));
        return Command::SUCCESS;
    }
}
