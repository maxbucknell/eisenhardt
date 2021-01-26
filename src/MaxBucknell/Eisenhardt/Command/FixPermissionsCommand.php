<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt fix-permissions
 *
 * Fix the local permissions of a Magento 2 installation.
 */
class FixPermissionsCommand extends Command
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
            ->setName('fix-permissions')
            ->setDescription('Fix permissions of your Magento 2 installation')
            ->setHelp(<<<TEXT
The <info>fix-permissions</> command repairs the permissions of the
Magento 2 installation, setting appropriate ownership and permissions
on all files and folders.
TEXT
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
        $this->project = ProjectFactory::findFromWorkingDirectory($logger);

        $this->project->repairPermissions();

        $output->writeln('All finished!');
    }
}
