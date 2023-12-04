<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Command;

use CoStack\MysqlLoader\DumpConfiguration;
use CoStack\MysqlLoader\Service\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\Required;

#[AutoconfigureTag('app.command')]
class DumpCommand extends Command
{
    protected readonly Dumper $dumper;
    #[Required]
    public function injectDumper(#[Autowire(lazy: true)] Dumper $dumper): void
    {
        $this->dumper = $dumper;
    }

    protected function configure(): void
    {
        $this->setName('dump')
             ->addOption('host', 'H', InputOption::VALUE_REQUIRED, 'MySQL host', 'localhost')
             ->addOption('port', 'P', InputOption::VALUE_REQUIRED, 'MySQL port', 3306)
             ->addOption('user', 'u', InputOption::VALUE_REQUIRED)
             ->addOption('password', 'p', InputOption::VALUE_REQUIRED)
             ->addOption('dbname', 'D', InputOption::VALUE_REQUIRED)
             ->addOption('folder', 'f', InputOption::VALUE_REQUIRED)
             ->addOption('exclude', 'x', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED)
             ->addOption('recreate-tables', 'r', InputOption::VALUE_NONE)
             ->addOption('truncate-instead-of-recreate', 'R', InputOption::VALUE_NONE)
             ->addOption('truncate-ignored-tables', 't', InputOption::VALUE_NONE)
             ->addOption('zip', 'z', InputOption::VALUE_NONE)
             ->addOption('filter-query', 'Q', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $user = $input->getOption('user');
        $password = $input->getOption('password');
        $dbname = $input->getOption('dbname');
        $folder = $input->getOption('folder');
        $excludedTablesPatterns = $input->getOption('exclude');
        $recreateTables = $input->getOption('recreate-tables');
        $truncateInsteadOfRecreate = $input->getOption('truncate-instead-of-recreate');
        $truncateIgnoredTables = $input->getOption('truncate-ignored-tables');
        $zip = $input->getOption('zip');
        $filterQuery = $input->getOption('filter-query');

        $dumpConfiguration = new DumpConfiguration(
            $host,
            $port,
            $user,
            $password,
            $dbname,
            $folder,
            $excludedTablesPatterns,
            $recreateTables,
            $truncateInsteadOfRecreate,
            $truncateIgnoredTables,
            $zip,
            $filterQuery,
        );

        $this->dumper->dump($dumpConfiguration);

        return self::SUCCESS;
    }
}
