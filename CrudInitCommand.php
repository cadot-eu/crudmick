<?php

namespace App\Command\base;

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
		foreach ($trait as $test => $comment) {
			$Sentity = file_get_contents($fentity);
			if (strpos($Sentity, $test) === false) {
				$insd = strpos($Sentity, $comment);
				$insf = strpos($Sentity, "\n", $insd);
				file_put_contents(
					$fentity,
					substr($Sentity, 0, $insf) .
						"\n" .
						$test .
						substr($Sentity, $insf)
				);
				$io->info("Paramètre `$test` ajouter dans la partie $comment ");
			}
		}

		$find = <<<'EOT'
  public function index($search, $fields, $sort, $direction ,$categorie=null, $deleted = false, $etat = null)
  {
      $sort = is_null($sort) ? 'a.id' : $sort;
      $qb = $this->createQueryBuilder('a');
      if ($deleted) {
          $qb->where($qb->expr()->isNotNull('a.deletedAt'));
      } else {
          $qb->where($qb->expr()->isNull('a.deletedAt'));
      }
      if ($etat != null) {
          $qb->andwhere($qb->expr()->eq('a.etat', ':etat'))
              ->setParameter('etat', $etat);
      }
      $ORX = $qb->expr()->orx();
      foreach ($fields as $field) {
          $ors = [];
          foreach (explode(' ', $search) as $s) {
              $s = str_replace("'", "''", $s);
              $ors[] = $qb->expr()->orx("a.$field LIKE '%$s%' ");
          }
          $ORX->add(join(' AND ', $ors));
      }
      $qb->andWhere($ORX);
      if($categorie !=null)
          $qb->andwhere($qb->expr()->isMemberOf(':categorie', 'a.categories'))->setParameter('categorie', $categorie);

      return $qb->orderBy($sort, strtoupper($direction))
          ->getQuery()
          ->getResult();
  }
EOT;

		$repo = file_get_contents(
			'/app/src/Repository/' . ucfirst($entity) . 'Repository.php'
		);
		//pon ajoute bycategorie si on a categorietrait
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

		$find .= "\n" . '//fin index' . "\n";

		//suppression de l'ancien index
		if (($deb = strpos($repo, 'public function index($search')) !== false) {
			$end = strpos($repo, '//fin index', $deb);
			$str =
				str_replace(
					substr($repo, $deb, $end - $deb + strlen('//fin index')),
					$find,
					$repo
				);
		} else {
			$end = strrpos($repo, '}');
			$deb = $end;
			$str = substr($repo, 0, $deb)  . $find . substr($repo, $end);
		}
		$str = preg_replace('/^[ \t]*[\r\n]+/m', '', $str);
		file_put_contents(
			'/app/src/Repository/' . ucfirst($entity) . 'Repository.php',
			$str
		);
		//file_put_contents('/app/src/Repository/' . ucfirst($entity) . 'Repository.php', substr($repo, 0, $deb) . "\n" . $find . substr($repo, $end));

		return Command::SUCCESS;
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
