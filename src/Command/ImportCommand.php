<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Command;

use CoStack\MysqlLoader\ImportConfiguration;
use CoStack\MysqlLoader\Service\Importer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.command')]
class ImportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('import')
             ->addOption('host', 'H', InputOption::VALUE_REQUIRED, 'MySQL host', 'localhost')
             ->addOption('port', 'P', InputOption::VALUE_REQUIRED, 'MySQL port', 3306)
             ->addOption('user', 'u', InputOption::VALUE_REQUIRED)
             ->addOption('password', 'p', InputOption::VALUE_REQUIRED)
             ->addOption('dbname', 'D', InputOption::VALUE_REQUIRED)
             ->addOption('file', 'f', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $user = $input->getOption('user');
        $password = $input->getOption('password');
        $dbname = $input->getOption('dbname');
        $file = $input->getOption('file');

        $dumpConfiguration = new ImportConfiguration(
            $host,
            $port,
            $user,
            $password,
            $dbname,
            $file,
        );

        $dumper = new Importer();
        $dumper->import($dumpConfiguration);

        return self::SUCCESS;
    }
}
