<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\RunParams;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt run command.
 *
 * Run an administrative command inside a Eisenhardt environment.
 */
class RunCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run a command inside a Eisenhardt environment')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'php-version',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'PHP version to run task as',
                        null
                    ),
                    new InputOption(
                        'debug',
                        'x',
                        null,
                        'Run container with Xdebug configured'
                    ),
                    new InputOption(
                        'dry-run',
                        '',
                        null,
                        'Outputs the command, but will not execute anything'
                    ),
                    new InputArgument(
                        'container_command',
                        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                        'Command to run in container',
                        [ 'bash' ]
                    )
                ])
            )
            ->setHelp(<<<EOT
The <info>run</> command creates an ephemeral container based on the
maxbucknell/php:*-console image, mounts the volumes and joins the
networks in the current Eisenhardt project, and executes the given
command.

If no command is supplied, an interactive terminal is opened.
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
        $project = ProjectFactory::findFromWorkingDirectory($logger);

        $relativeDirectory = $project->getRelativeDirectory(getcwd());
        $workingDirectory = "/mnt/magento/{$relativeDirectory}";

        $params = new RunParams(
            $input->getArgument('container_command'),
            $workingDirectory,
            $input->getOption('debug'),
            $input->getOption('php-version')
        );

        $project->run($params);
    }
}
