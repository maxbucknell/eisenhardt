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
use Redbox\RD\Project;
use Redbox\RD\ProjectFactory;

/**
 * Redbox Docker start command
 *
 * Starts the servers of a Redbox Docker installation.
 */
class StopCommand extends Command
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
            ->setName('stop')
            ->setDescription('Stop the Redbox Docker project')
            ->setHelp(<<<EOT
The <info>stop</> command starts the Redbox Docker environment, as if
you turned off your servers.
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
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $workingDirectory = $this->project->getInstallationDirectory();
        $output->writeln(
            "Found project in `{$workingDirectory}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );
        chdir($workingDirectory);

        $projectName = $this->project->getProjectName();

        $command = <<<CMD
docker-compose \
  -f .rd/base.yml \
  -f .rd/dev.yml \
  -f .rd/appvolumes.yml \
  -f .rd/dbvolumes.yml \
  -p {$projectName} \
  stop
CMD
        ;

        $output->writeln(
            "Running: {$command}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        passthru($command);
    }
}
