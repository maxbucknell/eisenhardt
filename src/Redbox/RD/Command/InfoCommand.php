<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Redbox\RD\Project;
use Redbox\RD\ProjectFactory;

/**
 * Get information about the Redbox Docker project.
 */
class InfoCommand extends Command
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
            ->setName('info')
            ->setDescription('Get information about Redbox Docker installation')
            ->setHelp(<<<EOT
The <info>info</> command tells you IP addresses, port mappings, and
enabled configuration inside the current Redbox Docker installation.
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
        $workingDirectory = $this->project->getInstallationDirectory();
        chdir($workingDirectory);

        passthru(<<<CMD
docker-compose \
  -p rd \
  -f .rd/base.yml \
  -f .rd/dev.yml \
  -f .rd/appvolumes.yml \
  -f .rd/dbvolumes.yml \
  -f .rd/ports.yml \
  ps
CMD
        );
    }
}
