<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\ModuleFactory;
use MaxBucknell\Eisenhardt\ProjectFactory;
use MaxBucknell\Eisenhardt\Util\MagentoInstallation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Eisenhardt standup command.
 *
 * Scaffold a temporary installation of Magento to test a module.
 */
class StandupCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('standup')
            ->setDescription('Scaffold an installation of Magento to test a module')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'sample-data',
                        's',
                        InputOption::VALUE_NONE,
                        'Install sample data'
                    ),
                    new InputOption(
                        'magento-version',
                        'm',
                        InputOption::VALUE_REQUIRED,
                        'Version of Magento to install'
                    ),
                    new InputOption(
                        'commerce',
                        'c',
                        InputOption::VALUE_NONE,
                        'Install Magento Commerce (default open source)'
                    )
                ])
            )
            ->setHelp(<<<EOT
The <info>standup</> command creates an installation of Magento and installs
the module in the current working directory into it, allowing testing of
modules in an isolated environment.

The standup command needs to be run in a Magento module directory.
EOT
);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = ModuleFactory::findFromWorkingDirectory();
        $location = $module->getModuleDirectory();
        $output->writeln(
            "Found module in <info>{$location}</>",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $installationName = \md5((string)\mt_rand());
        $directory = $module->getStandupDirectory() . "/{$installationName}";

        $output->writeln(
            "Initialising installation in <info>{$directory}</>",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $output->writeln(
            "Creating Magento Composer project from metapackage"
        );
        $projectCreateOutput = MagentoInstallation::createProject(
            $directory,
            $input->getOption('magento-version') ?? '2.3.0',
            $input->getOption('commerce') ? 'enterprise' : 'community'
        );

        $output->writeln(
            "Output: {$projectCreateOutput}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $output->writeln(
            "Initialising Eisenhardt Project."
        );

        $project = ProjectFactory::createInDirectory(
            $directory,
            '7.2'
        );
    }
}
