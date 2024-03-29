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
    name: 'crud:generate:index',
    description: 'Génère le fichier index de l\'entité',
)]
class CrudMakeIndexCommand extends Command
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
        $th = []; //contient les th pour l'entete du tableau

        $docs = new ParserDocblock($entity);
        //gestion de l'ordertrait
        $order = false;
        if (isset($docs->getOptions()['id']['ordre']))
            $order = true;

        foreach ($docs->getOptions() as $name => $options) {
            if (isset($options['opt']['label'])) {
                $textname = $options['opt']['label'];
            } else {
                $textname = $name;
            }
            //creation des th
            if (!isset($options['tpl']['no_index']) && $name != 'deletedAt' && $name != 'createdAt' && $name != 'updatedAt' && $name != 'slug') {
                if ($order || in_array($docs->getType($name), ['manytomany', 'onetomany'])) {
                    $th[] = "<th >" . $textname;
                } else {
                    $th[] = '<th {{pagination.isSorted("a.' . $name . '")?"class=\'sorted\'"}}>
                {{ knp_pagination_sortable(pagination, "' . $textname . '", "a.' . $name . '") }}';
                }
            } elseif ($name == 'slug' && isset($IDOptions['tpl']['no_slug'])) {
                $th[] = "<th >" . $textname;
            }
        }
        /* ------------------------- creation des idoptions ------------------------- */
        $IDOptions = $docs->getOptions()['id'];
        //gestion du timetrait
        $thtime = '';
        if (!isset($IDOptions['tpl']['no_deleted'])) {
            $thtime .= <<<'EOT'
        {%if action=="deleted" %}
            <th>
            <a class="btn btn-outline-primary {{ app.request.query.get(" tri") == 'deletedAt' ? 'active' }} " href='?tri=deletedAt&&ordre={{ app.request.query.get("ordre")=="DESC" ? "ASC":"DESC" }}'>effacé</a>
            
        {% endif %}
        EOT;
        }
        /* ------------------------- datetime avec knpsorted ------------------------ */
        if (isset($IDOptions['tpl']['created'])) {
            if ($order)
                $th[] = "<th>créé";
            else $th[] = "<th {{pagination.isSorted('a.createdAt')?\"class='sorted'\"}}>
        {{ knp_pagination_sortable(pagination, 'créé', 'a.createdAt') }}";
        }
        if (!isset($IDOptions['tpl']['no_updated'])) {
            if ($order) {
                $th[] = "<th>{{action=='deleted' ? 'effacé' : 'mis à jour'}}";
            } else {
                $th[] = "{%if action=='deleted' %} <th {{pagination.isSorted('a.deletedAt')?\"class='sorted'\"}}>{{ knp_pagination_sortable(pagination, 'effacé', 'a.deleteddAt') }}{% else %}<th {{pagination.isSorted('a.updatedAt')?\"class='sorted'\"}}>{{ knp_pagination_sortable(pagination, 'mis à jour', 'a.updatedAt') }}{% endif %}";
            }
        }
        /* ---------------------------------- body ---------------------------------- */
        $tableauChoice = '';
        $td = []; //contient les td pour le corps du tableau


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
                foreach ($docs->getSelect($name) as $select) {
                    switch ($select) {
                        case 'generatedvalue': //id
                            //si on a un slug on ajoute un tooltip pour voir le slug
                            if ($docs->propertyExist('slug')) {
                                $td[] = '<td  data-toggle="tooltip" 
                            title="{{' . "$Entity.slug" . '}}"' .
                                    ' {{ ' . "$Entity.slug" . '!=""? "data-clipboard-text="~' . "$Entity.slug" . '~' . "\" class=clipboard \""   .
                                    ':"class=my-auto"}} > {{' . "$Entity.$name$twig" . '}}' . "\n";
                            } else {
                                $td[] = '<td class="my-auto ' . implode(' ', $class) . '" > {{' . "$Entity.$name$twig" . '}}' . "\n";
                            }
                            break;
                        case 'hiddenroot':
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" > {{' . "$Entity.$name$twig" . '}}' . "\n";;
                            break;
                        case 'vide':
                        case 'simple':
                        case 'simplelanguage':
                        case 'full':
                        case 'normal':
                        case 'annonce':
                        case 'text':
                        case 'hidden':
                        case 'siret':
                        case 'string':
                        case 'readonlyroot':
                        case 'email':
                        case 'id':
                            $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) :  '|striptags|u.truncate(40, \'...\')';
                            $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                            break;

                        case 'money':
                            $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) :  "|number_format(2,'.',' ')";
                            $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : "|number_format(2,'.',' ')";
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                            break;
                        case 'integer':
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twig" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                            break;

                        case 'adresse':
                            $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) :  '|striptags|u.truncate(40, \'...\')';
                            $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                            break;
                        case 'drapeau':
                            ///node_modules/flag-icons/flags/1x1/fr.svg
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name" . '}}">' . "{% if $Entity.$name %} <img src=\"/build/flags/1x1/{{ $Entity.$name }}.svg\" style=\"max-height:2rem\"> {% endif %} \n";
                            break;
                        case 'telephone':
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name" . '}}">' . "{{ $Entity.$name }}\n";
                            break;
                        case 'manytoone':
                            $champ = isset($options['options']['champ']) ? $options['options']['champ'] : 'id';
                            $twig = isset($options['twig']) ?  $twig : '|striptags|u.truncate(40, \'...\')';
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name?$Entity.$name.$champ" . '}}"> {{' . "$Entity.$name? $Entity.$name.$champ$twig" . '}}' . "\n";
                            break;

                        case 'choice':
                            $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
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
                            $tableauChoice .= '{% set choice_' . $name . '=' . json_encode($options['options'], JSON_UNESCAPED_UNICODE) . ' %}' . "\n";
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
                                <a href=\"{{path('" . $entity . "_champ',{'id':$Entity.id,'type':'" . $name . "','valeur':choice_" . $name . "|keys[numr]})}}\" style='font-size:2rem;' data-turbo=\"false\"  title='{{ choice_" . $name . "|keys[retour]}}'> {{ choice_" . $name . "[ choice_" . $name . "|keys[retour]]$twig|raw}}</a>\n";
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
                        case 'importance':
                            $select = "<select class='form-control'   data-controller='base--importance' data-base--importance-url-value='{{path('" . $entity . "_champ',{'id':$Entity.id,'type':'importance'})}}'>";
                            for ($i = 0; $i <= 10; $i++) {
                                $select .= '<option value="' . $i . '" {% if ' . $Entity . '.' . $name . '==' . $i . ' %}selected{% endif %}>' . $i . '</option>';
                            }
                            $select .= '</select>';

                            $td[] = '<td class="my-auto">' . $select . "\n";
                            break;
                        case 'color':
                            $td[] = '<td class="my-auto"><div class="boxcolor" style="background-color:{{' . $Entity . '.' . $name . '}}"></div>' . "\n";
                            break;
                        case 'stars':
                            $stars = isset($options['options']) ? key($options['options']) : 5;
                            $td[] = '<td class="my-auto">
                        {% set rating = ' . $Entity . '.' . $name . ' %}
                        <div class="star-rating">
                        {% for i in 1..' . $stars . ' %}
                            {% if i <= rating %}
                            <span class="bi bi-star-fill starsTwigFilled"></span>
                            {% else %}
                            {% if rating > i - 1 and rating < i %}
                                <span class="bi bi-star-half starsTwigHalf"></span>
                            {% else %}
                                <span class="bi bi-star starsTwigEmpty"></span>
                            {% endif %}
                            {% endif %}
                        {% endfor %}
                        </div>
                        ' . "\n";
                            break;
                        case 'onetomany':
                        case 'collection':
                            //field for show
                            $return = isset($options['options']['champ']) ? $options['options']['champ'] : 'id';
                            //for separate field
                            $separation = isset($options['separation']) ? $options['separation'] : ';';
                            $td[] = '<td class="my-auto">' . "{% for " . $name . "_item in " . $Entity . ".$name %}\n{{" . $name . "_item.$return$twig}}{{loop.last?'':'$separation'}}\n{% endfor %}" . "\n";
                            break;
                        case 'entity':
                            //field for show
                            $return = isset($options['label']) ? key($options['label']) : 'id';
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
                        case 'pass':
                        case 'datetime':
                            if (in_array($name, ['updatedAt', 'createdAt', 'deletedAt']))
                                if (!isset($IDOptions['tpl']['no_' . substr($name, 0, -2)])) {
                                    if ($name == 'createdAt' && isset($IDOptions['tpl'][substr($name, 0, -2)]) || $name != 'createdAt') {
                                        $td[] = "{% if (action=='deleted' and '$name'=='deletedAt') or (action!='deleted' and '$name'!='deletedAt') %}<td>{{ $Entity.$name ? $Entity.$name|date('d/m à H:i', 'Europe/Paris'):'---'}}{% endif %}";
                                    }
                                }


                            break;

                        case 'drag':

                            break;
                        default:

                            if (!in_array($name, ['updatedAt', 'createdAt', 'deletedAt', 'slug'])) {
                                $output->writeln('- non géré dans makeindex(' . $Entity . '.' . $name . '):' . $select);
                                $twig = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) :  '|striptags|u.truncate(40, \'...\')';
                                $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                                $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
                            }
                            break;
                    }
                }
            }
            //gestion de certain par les noms de champs
            if (!in_array($name, ['updatedAt', 'createdAt', 'deletedAt'])) {
                switch ($name) {
                    case 'slug':
                        if (isset($IDOptions['tpl']['no_slug'])) {
                            $td[] = '<td class="my-auto text-center clipboard' . implode(' ', $class) . '"  data-clipboard-text="{{' . "$Entity.$name$twig" . '}}" title="{{' . "$Entity.$name$twig" . '}}"> ' . '<i class="bi bi-clipboard"></i>' . "\n";
                        }

                        break;
                    default:
                        //dans le cas ou on a pas de type donné ni de nom connu
                        if (!isset($options['tpl']['no_index']) && $docs->getSelect($name) == '') {
                            $twig = isset($options['twig']) ?  $twig : '|striptags|u.truncate(40, \'...\')';
                            $twigtitle = isset($options['twig']) ? '|' . implode('|', array_keys($options['twig'])) : '|striptags|u.truncate(200, \'...\')';
                            $td[] = '<td class="my-auto ' . implode(' ', $class) . '" title="{{' . "$Entity.$name$twigtitle" . '}}"> {{' . "$Entity.$name$twig" . '}}' . "\n";
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
        if (isset($IDOptions['tpl']['action_clone'])) {
            $resaction['clone'] =  "<a class='btn btn-xs btn-primary'  title='clone' href=\"{{ path('$entity" . "_clone" . "', {'id': $Entity.id }) }}\"><i class='icone  bi bi-file-earmark-plus'></i></a>";
        }

        /* --------------------------------- hide BY ID -------------------------------- */
        $ifhide = [];
        if ((isset($IDOptions['hide']))) {
            foreach ($IDOptions['hide'] as $champ => $hide) {
                $userhide = "$Entity." . $champ . "  != '" . $hide . "'";
                $ifhide[] = $userhide;
            }
        }

        /* -------------------------- création des actions -------------------------- */
        $actions = [];
        if (isset($IDOptions['actions'])) {
            foreach ($IDOptions['actions'] as $key => $action) {
                $actions[] = "<a class='btn btn-xs btn-primary'   data-turbo='false' title='" . $key . "' href='{{ path('" . $action['route'] . "', {'id': " . $Entity . ".id }) }}'><i class='icone icone bi bi-" . $action["icon"] . "'></i></a>";
            }
        }

        //open model controller
        $fileIndex = __DIR__ . '/tpl/index.html.twig';
        if (!file_Exists($fileIndex)) {
            throw new Exception("Le fichier " . $fileIndex . " est introuvable", 1);
        }
        $html = CrudInitCommand::twigParser(file_get_contents($fileIndex), [
            'userhide' => isset($userhide) ? $userhide : 'true',
            'hide' => isset($ifhide) ? "true" : ('(' . implode(' and ', $ifhide) . ') or is_granted(\'ROLE_SUPERADMIN\')'),
            'rows' => implode("\n", $td),
            'entete' => implode("\n", $th),
            'entity' => $entity,
            'drag' => isset($IDOptions['tpl']['drag']) ? 'data-controller="base--drag" data-base--drag-query-value="tr" data-base--drag-entity-value="' . $entity . '"' : '',
            'Entity' => $Entity,
            'no_action_edit' => isset($resaction) ? implode("\n", $resaction) : '',
            'order' => !isset($IDOptions['tpl']['search']) && $order ?: 'false',
            'extends' => '/admin/base.html.twig',
            'no_action_add' => !isset($IDOptions['tpl']['no_action_add']) ? "true" : "false",
            'no_access_deleted' => !isset($IDOptions['tpl']['no_action_deleted']) ? "true" : "false",
            'tableauChoice' => $tableauChoice,
            'viewerUrl' => isset($IDOptions['viewer']) ? $IDOptions['viewer']['url'] : "false",
            'viewerChamp' => isset($IDOptions['viewer']) ? $IDOptions['viewer']['champ'] : "false",
            'actions' => isset($actions) ? implode("\n", $actions) : ''
        ]);
        /** @var string $html */
        CrudInitCommand::updateFile("templates/" . $entity . '/index.html.twig', $html, $input->getOption('comment'), $input->getOption('speed'));
        return Command::SUCCESS;
    }
}
