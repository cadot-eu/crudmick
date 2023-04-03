<?php

namespace App\Command\crudmick;

use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WW\Faker\Provider\Picture;

#[AsCommand(
    name: 'fixtures:picsumImages',
    description: 'create photos in assets/fixtures from picsum photo',
)]
class FixturesPicsumImagesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'number of photos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');
        @mkdir('/app/assets/public/fixtures/');
        /* Creating a random image and saving it to a file. */
        $faker = Factory::create();
        $faker->addProvider(new Picture($faker));
        $number = $arg1 !== null ? $arg1 : 10;
        for ($i = 0; $i < $number; $i++) {
            $url = $faker->pictureUrl(
                640,    // width (px)
                480,    // height (px)
                false,    // grayscale (boolean)
                0,        // blur (0 = no blur, 10 = max blur)
            );
            // create curl resource
            $ch = curl_init();

            // set url
            curl_setopt($ch, CURLOPT_URL, $url);

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

            // $output contains the output string
            $output = curl_exec($ch);
            file_put_contents('/app/assets/public/fixtures/' . $i . '.jpg', $output);
            // close curl resource to free up system resources
            curl_close($ch);
        }
        $io->success('photos creates');

        return Command::SUCCESS;
    }
}
