<?php
namespace App\Command;

use App\Service\ImportUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class importUserBalancesCommand extends Command
{
    private $importUser;

    public function __construct(ImportUser $importUser)
    {
        $this->importUser = $importUser;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Старт процедуры',
            '============',
            '',
        ]);
        $output->write($this->importUser->importUserBalances());
    }

    protected function configure()
    {
        $this
            ->setName('service:import-user-balances')
            ->setDescription('Импорт балансов существующих пользователей.')
            ->setHelp('Команда загрузки из excel балансов существующих пользователей')
        ;
    }
}