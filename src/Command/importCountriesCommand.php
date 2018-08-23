<?php
namespace App\Command;

use App\Service\ImportCountry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class importCountriesCommand extends Command
{
    private $importCountry;

    public function __construct(ImportCountry $importCountry)
    {
        $this->importCountry = $importCountry;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Старт процедуры',
            '============',
            '',
        ]);
        $output->write($this->importCountry->importCountry());
    }

    protected function configure()
    {
        $this
            ->setName('service:import-countries')
            ->setDescription('Импорт существующих стран.')
            ->setHelp('Команда загрузки из excel существующих стран')
        ;
    }
}