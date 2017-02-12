<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Redbox Docker initialization command.
 *
 * Initializes a new Redbox Docker environment in the current project
 * directory.
 */
class InitCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new Redbox Docker environment')
            ->setHelp(<<<EOT
The <info>init</> command creates a directory inside your project root
called <info>.rd/</>, which contains various Docker related config.
EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $cwd = getcwd();
        $location = __DIR__;
        $destination = "{$cwd}/.rd";
        $src = "{$location}/../../../../project-template";

        shell_exec("cp -r {$src} {$destination}");

        $output->writeln("Initializing Redbox Docker project in <info>{$destination}</>");
    }
}
