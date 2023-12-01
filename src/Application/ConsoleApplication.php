<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Contracts\Service\Attribute\Required;

#[Autoconfigure(public: true)]
class ConsoleApplication extends Application
{
    protected readonly iterable $commands;

    #[Required]
    public function injectCommands(#[TaggedIterator('app.command')] iterable $commands): void
    {
        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
