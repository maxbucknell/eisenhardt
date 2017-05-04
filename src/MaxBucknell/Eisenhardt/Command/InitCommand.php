<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Eisenhardt initialization command.
 *
 * Initializes a new Eisenhardt environment in the current project
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
            ->setDescription('Initialize a new Eisenhardt environment')
            ->setHelp(<<<EOT
The <info>init</> command creates a directory inside your project root
called <info>.eisenhardt/</>, which contains various Docker related config.
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
        $destination = "{$cwd}/.eisenhardt";
        $src = "{$location}/../../../../project-template";
        $command = "cp -r {$src} {$destination}";

        $output->writeln(
            "Running: `{$command}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec($command);

        $output->writeln("Initializing Eisenhardt project in <info>{$destination}</>");
    }
}
