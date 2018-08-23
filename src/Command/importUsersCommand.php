<?php
namespace App\Command;

use App\Service\ImportUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class importUsersCommand extends Command
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
        $output->write($this->importUser->importUser());
    }

    protected function configure()
    {
        $this
            ->setName('service:import-users')
            ->setDescription('Импорт существующих пользователей со структурой.')
            ->setHelp('Команда загрузки из excel существующих пользователей')
        ;
    }
}