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
            'use App\Entity\base\SlugTrait;' => 'use',
            'use Gedmo\Mapping\Annotation as Gedmo;' => 'use',
            'use Symfony\Component\Validator\Constraints as Assert;' => 'use',
            '#[ORM\HasLifecycleCallbacks]' => '#[ORM',
            'use TimeTrait;' => '{',
            'use SlugTrait;' => '{',

        ];
        $fentity = 'src/Entity/' . ucfirst($entity) . '.php';
        $this->add_in_file($trait, $fentity);

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
        // on format le fichier
        $cmd = "vendor/bin/phpcbf /app/src/Repository/" . ucfirst($entity) . 'Repository.php';
        shell_exec($cmd);
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
    public static function updateFile(string $filename, $html, $comment = false)
    {
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
        $cmd = "vendor/bin/phpcbf /app/$filename";
        shell_exec($cmd);
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
}
