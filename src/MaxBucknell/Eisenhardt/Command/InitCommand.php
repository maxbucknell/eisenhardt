<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

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
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'php-version',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'PHP version to use with this project.',
                        '7.2'
                    ),

                ])
            )
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
        $version = $input->getOption('php-version');

        $copyCommand = "cp -r {$src} {$destination}";
        $output->writeln(
            "Running: `{$copyCommand}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec($copyCommand);

        $templateCommand = "find {$destination} -type f -exec sed -i 's/{{version}}/{$version}/' {} \;";
        $output->writeln(
            "Running `{$templateCommand}`",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec($templateCommand);

        $output->writeln("Initializing Eisenhardt project in <info>{$destination}</>");
    }
}
