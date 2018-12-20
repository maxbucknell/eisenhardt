<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Eisenhardt initialization command.
 *
 * Initializes a new Eisenhardt environment in the current project
 * directory.
 */
class InitCommand extends Command
{
    /**
     * @var Project
     */
    private $project;

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
                    new InputArgument(
                        'hostname',
                        InputArgument::OPTIONAL,
                        'Host name this installation will run on. Required for TLS'
                    )
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
        $logger = new ConsoleLogger($output);
        $this->project = ProjectFactory::createInDirectory(
            \getcwd(),
            $input->getOption('php-version'),
            $logger,
            $input->getArgument('hostname')
        );

        $output->writeln("Initializing Eisenhardt project in <info>{$this->project->getEisenhardtDirectory()}</>");
    }
}
