<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt start command
 *
 * Starts the servers of a Eisenhardt installation.
 */
class StopCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stop the Eisenhardt project')
            ->setHelp(<<<EOT
The <info>stop</> command starts the Eisenhardt environment, as if
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
        $project = ProjectFactory::findFromWorkingDirectory();
        $project->stop();
    }
}
