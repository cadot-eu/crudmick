<?php

namespace App\Command\crudmick;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;
use Psy\Formatter\CodeFormatter;
use jetstreamlabs\PHPIndent\PHPIndent;
use Tidy;
use HTMLModule;
use ReflectionClass;

#[AsCommand(name: 'crud:init', description: 'Initialise une entity')]
class CrudInitCommand extends Command
{
    protected static $defaultName = 'crud:init';
    protected function configure(): void
    {
        $this->addArgument(
            'entity',
            InputArgument::OPTIONAL,
            'nom de l\'entity'
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
        //secure $entity in minus
        $entity = strTolower($entity);
        $sdir = '';
        //création des répertoires
        @mkdir("src/Form/$sdir/", 0777, true);
        @mkdir("src/Controller/$sdir/", 0777, true);
        @mkdir("templates/$sdir/$entity", 0777, true);
        //control des paramètres et ajout si nécessaires
        $trait = [
            'use App\Entity\base\TimeTrait;' => 'use',
            //'use App\Entity\base\SlugTrait;' => 'use',
            'use Gedmo\Mapping\Annotation as Gedmo;' => 'use',
            'use Symfony\Component\Validator\Constraints as Assert;' => 'use',
            '#[ORM\HasLifecycleCallbacks]' => '#[ORM',
            'use TimeTrait;' => '{',
            //'use SlugTrait;' => '{',

        ];
        $fentity = 'src/Entity/' . ucfirst($entity) . '.php';
        $this->add_in_file($trait, $fentity);
        //on vérifie que l'on a SEARCH
        //on récupère les commentaires de id dans l'entité
        $class = 'App\Entity\\' . ucfirst($entity);
        $reflexion = new ReflectionClass($class);
        $idcomments = ($reflexion->getProperty('id')->getDocComment());
        $expIdComments = explode("\n", $idcomments);

        if (strpos($idcomments, 'SEARCH:[') === false) {
            $save = $expIdComments[count($expIdComments) - 1];
            $expIdComments[count($expIdComments) - 1] = "* SEARCH:['id']";
            $expIdComments[] = $save;
            if ($idcomments)
                $changement = \str_replace($idcomments, "\n" . implode("\n", $expIdComments) . "\n", \file_get_contents($fentity));
            else // si on a pas de commentaire pour id
                $changement = \str_replace('private ?int $id', "/**\n" . implode("\n", $expIdComments) . '*/' . "\n" . 'private ?int $id', \file_get_contents($fentity));
            \file_put_contents($fentity, $changement);
        }

        //protection contre l'erreur de mettre un ligne vide juste après une annontation
        //ouvrir le fichier $fentity et supprimer les lignes vides et réenregistrer le  fichier
        // Lire le contenu du fichier
        $fileContent = file_get_contents($fentity);

        //on ajoute une ligne pour séparer les parties
        $fileContent = $this->addEmptyLineAfterPrivate($fileContent);
        // Supprimer les lignes vides
        $fileContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $fileContent);
        // Réécrire le fichier avec le contenu modifié
        file_put_contents($fentity, $fileContent);
        //iem pour le repository
        $trait = [
            'use App\Repository\base\SearchRepositoryTrait;' => 'use',
            'use SearchRepositoryTrait;' => '{',

        ];
        $frepository = 'src/Repository/' . ucfirst($entity) . 'Repository.php';
        $this->add_in_file($trait, $frepository);

        $repo = file_get_contents($frepository);
        //on ajoute bycategorie si on a categorietrait dans l'entity
        $objetEntity = 'App\Entity\\' . ucfirst($entity);
        if (property_exists($objetEntity, 'categories')) {
            //on ajoute le use si pas présent
            if (strpos($repo, '') === false) {
                $repo = str_replace(
                    'namespace App\Repository;',
                    'namespace App\Repository;' . "\n",
                    $repo
                );
            }
        }

        //supression de l'ancien index
        $deb = strpos($repo, '* This is a PHP function that searches and filters results based on various');
        $end = strpos($repo, '//fin index', $deb);
        if ($deb !== false) {
            $deb = $deb - 22;
            $end = $end + 12;
            $repo = str_replace(substr($repo, $deb, $end - $deb), '', $repo);
        }
        $str = preg_replace('/^[ \t]*[\r\n]+/m', '', $repo);
        file_put_contents('/app/src/Repository/' . ucfirst($entity) . 'Repository.php', $str);

        // on format le fichier au besoin
        if (!$input->getOption('speed')) {
            $cmd = "vendor/bin/phpcbf --standard=PSR12 /app/src/Repository/" . ucfirst($entity) . 'Repository.php';
            shell_exec($cmd);
        }
        return Command::SUCCESS;
    }
    private function add_in_file($trait, $fentity)
    {
        foreach ($trait as $test => $comment) {
            $Sentity = file_get_contents($fentity);
            if (strpos($Sentity, $test) === false) {
                $insd = strpos($Sentity, $comment);
                $insf = strpos($Sentity, "\n", $insd);
                // on format le fichier
                file_put_contents($fentity, substr($Sentity, 0, $insf) . "\n" . $test . substr($Sentity, $insf));
                $cmd = "vendor/bin/phpcbf /app/" . $fentity;
                shell_exec($cmd);
            }
        }
    }



    public static function ArrayToKeyValue(array $array): string
    {
        $vars = var_export($array, true);
        return str_replace(['¤\'', '\'¤'], '', $vars);
    }
    /**
     * It takes a filename and an array of blocks, and updates the file with the blocks
     *
     * @param string filename The name of the file to be updated.
     * @param array blocks an array of blocks of code
     */
    public static function updateFile(string $filename, $html, $comment = false, $speed = false)
    {
        //si le fichier est un twig
        if (strpos($filename, '.html.twig') !== false) {
            //on vérifie si on a des balises if inutile et un commentaire au dessus pour retrouver le end if
            $finds = ['{% if not false %}', '{% if true %}', '{% true !="false" %}'];
            $lines = explode(PHP_EOL, $html);
            $indicesToRemove = [];
            foreach ($finds as $find) {
                for ($i = 0; $i < count($lines); $i++) {
                    $line = trim($lines[$i]);
                    if ($line === $find) {
                        if (isset($lines[$i - 1]) && trim($lines[$i - 1]) === trim($lines[$i - 1])) {
                            $else = null;
                            for ($j = $i + 1; $j < count($lines); $j++) {
                                if (trim($lines[$j]) === trim($lines[$i - 1]) and trim($lines[$j + 1]) === trim('{% else %}')) {
                                    $else = $j;
                                }
                                if (trim($lines[$j]) === trim($lines[$i - 1]) and trim($lines[$j + 1]) === '{% endif %}') {
                                    $indicesToRemove[] = $i - 1;  // Marquer la ligne de commentaire précédente pour suppression
                                    $indicesToRemove[] = $i;  // Marquer la ligne `{% if not false %}` pour suppression
                                    //si on a pas de else
                                    $indicesToRemove[] = $j;  // Marquer le commentaire pour 
                                    $indicesToRemove[] = $j + 1;  // Marquer la ligne `{% endif %}` pour suppression
                                    if ($else != null) {
                                        for ($k = $else; $k < $j; $k++) {
                                            $indicesToRemove[] = $k;  // Marquer les lignes entre else et endif
                                        }
                                    }
                                    break;  // Sortir de la boucle interne
                                }
                            }
                        }
                    }
                }
            }
            // Supprimez les lignes à partir des indices recueillis
            foreach ($indicesToRemove as $index) {
                unset($lines[$index]);
            }

            $html = implode(PHP_EOL, $lines);





            // Si vous souhaitez sauvegarder le résultat dans un fichier:
            // file_put_contents('path_to_output_twig_file.twig', $cleanedHtml);


        }
        // save new file
        $retour = file_put_contents($filename, $html);
        if ($retour === false) {
            throw new Exception(
                'Erreur sur la création du fichier:' . $filename,
                1
            );
        }
        if (file_exists($filename)) {
            if ($comment) {
                echo 'File ' . $filename . ' généré ' . "\n";
            }
        }
        // on format le fichier au besoin
        if (!$speed) {
            $cmd = "vendor/bin/phpcbf /app/$filename";
            shell_exec($cmd);
        }
    }
    /**
     * Method twigParser
     *
     * @param $html string twig with ¤...¤ for replacement
     * @param $tab array tableau des clefs à rechercher entre {{}} et à remplacer par value
     */
    public static function twigParser($html, $tab): string
    {
        foreach ($tab as $key => $value) {
            $html = str_replace('//¤' . $key . '¤', $value, $html); // that in first
            $html = str_replace('¤' . $key . '¤', $value, $html);
        }
        return $html;
    }

    /**
     * Given an array, find the key of an element, and another key to move before the first key.
     *
     * Return the array with the second key moved before the first key
     *
     * @param arr The array to be manipulated.
     * @param find The key of the element to be moved.
     * @param move The key of the element to be moved.
     *
     * @return The array with the element moved.
     */
    public static function moveKeyBefore($arr, $find, $move)
    {
        if (!isset($arr[$find], $arr[$move])) {
            return $arr;
        }

        $elem = [
            $move => $arr[$move],
        ]; // cache the element to be moved
        $start = array_splice($arr, 0, array_search($find, array_keys($arr)));
        unset($start[$move]); // only important if $move is in $start
        return $start + $elem + $arr;
    }

    /**
     * clean block without spaces... by trim
     *
     * @param  mixed $string
     */
    public static function clean($string): string
    {
        return str_replace(
            ["\t", "\n", "\r", "\0", "\x0B", "\n", "\r", ' '],
            '',
            $string
        );
    }
    //function qui ajoute créé une ligne de commentaire après une ligne qui commence par private
    public function addEmptyLineAfterPrivate($contenu)
    {
        $sigle = "    //_____________________________________________";
        //on supprime dans contenu les anciennes lignes avec sigle
        $contenu = str_replace($sigle, '', $contenu);
        $newLines = [];
        foreach (explode("\n", $contenu) as $line) {
            $newLines[] = $line;
            if (strpos(trim($line), 'private') === 0) {
                $newLines[] = $sigle;
            }
        }
        return implode("\n", $newLines);
    }
}
