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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\KernelBrowser;
use Symfony\Contracts\HttpClient\HttpClientInterface;



#[AsCommand(
    name: 'links:checker',
    description: 'Initialise une entity',
)]
class LinksCheckerCommand extends Command
{
    protected static $defaultName = 'links:checker';
    private $client;
    public function __construct(HttpClientInterface $client)
    {
        parent::__construct();
        $this->client = $client;
    }
    protected function configure(): void
    {
        $this
            ->addArgument('adress', InputArgument::OPTIONAL, 'adress de la page')
            ->addOption(
                'pass',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'les valeurs à passer',
                ['#', 'https:', 'http:', 'mailto:', 'www']
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $base = 'http://localhost' . $input->getArgument('adress');

        $pass = [];
        /** @var DomElement $link */
        foreach ($this->returnAllLinks($base, 1) as $lien) {
            if ($this->pass($lien, $input->getOption('pass'))) {
                $response = $this->client->request(
                    'GET',
                    $base . $lien->link()->getUri()
                );
                if (!in_array($response->getStatusCode(), [200, 301, 302])) {
                    $io->error($base . $lien->link()->getUri());
                }
            } else {
                $pass[] = array((substr($lien->link()->getUri(),  strlen($base)) ?: 'vide'), $this->clean(substr($lien->link()->getNode()->textContent, 0, 30)) ?: $this->clean(substr($lien->link()->getNode()->parentNode->textContent, 0, 100)));
            }
        }

        $io->Table(['url', 'valeur'], $pass);




        return Command::SUCCESS;
    }
    function returnAllLinks($base, $descent = 0,  $links = [])
    {
        $html = $this->client->request('GET', $base)->getContent();
        $crawler = new Crawler($html, $base);
        $crawler->filter('a')->each(function (Crawler $node) use ($base, $descent, &$links) {
            $links[] = $node;
            if ($descent > 0) {
                $links = self::returnAllLinks($node->link()->getUri(), $descent - 1, $links);
            }
        });
        return $links;
    }


    static function pass($link, array $pass): bool
    {

        if ($link->link()->getUri() == '') {
            return false;
        }
        foreach ($pass as $val => $pas) {
            if (substr(self::clean($link->link()->getUri()), 0, strlen($pas)) == $pas) {
                return false;
            }
        }
        return true;
    }

    static function  clean($string)
    {
        return trim(str_replace(["\n", "\t"], "", $string));
    }
}
