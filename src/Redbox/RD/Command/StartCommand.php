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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Redbox\RD\Project;
use Redbox\RD\ProjectFactory;

/**
 * Redbox Docker start command
 *
 * Starts the servers of a Redbox Docker installation.
 */
class StartCommand extends Command
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
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $this
            ->setName('start')
            ->setDescription('Start the Redbox Docker project')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'map-ports',
                        'p'
                    )
                ])
            )
            ->setHelp(<<<EOT
The <info>start</> command starts the Redbox Docker environment, as if
you turned on your servers.
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
        $p = $input->getOption('map-ports');
        $portInclude = $p ? '-f .rd/ports.yml' : '';
        $workingDirectory = $this->project->getInstallationDirectory();
        chdir($workingDirectory);

        $projectName = $this->project->getProjectName();

        passthru(<<<CMD
docker-compose \
  -f .rd/base.yml \
  -f .rd/dev.yml \
  -f .rd/appvolumes.yml \
  -f .rd/dbvolumes.yml \
  -p {$projectName} \
  {$portInclude} \
  up -d
CMD
        );
    }
}
