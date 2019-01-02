<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\ModuleFactory;
use MaxBucknell\Eisenhardt\ProjectFactory;
use MaxBucknell\Eisenhardt\RunParams;
use MaxBucknell\Eisenhardt\StandupParams;
use MaxBucknell\Eisenhardt\StartParams;
use MaxBucknell\Eisenhardt\Util\MagentoInstallation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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
        $logger = new ConsoleLogger($output);
        $module = ModuleFactory::findFromWorkingDirectory($logger);

        $standupName = $module->standUpMagentoInstance(new StandupParams(
            $input->getOption('sample-data'),
            $input->getOption('magento-version') ?? '2.3',
            $input->getOption('commerce') ? 'enterprise' : 'community',
            '7.2'
        ));

        $project = ProjectFactory::createInDirectory(
            "{$module->getStandupDirectory()}/{$standupName}",
            '7.2',
            $logger,
            "{$standupName}.{$module->getModuleName()}.loc"
        );

        $project->installContribFile(__DIR__ . '/../../../../standup.yml');

        $startParams = new StartParams(
            false,
            true
        );
        $project->start($startParams);

        $project->repairPermissions();

        $project->run(
            new RunParams([
                'composer',
                'install',
            ])
        );

        $project->run(
            new RunParams([
                'composer',
                'config',
                'minimum-stability',
                'dev'
            ])
        );

        $project->run(
            new RunParams([
                'composer',
                'config',
                'repositories.local',
                'path',
                '/mnt/module'
            ])
        );

        $project->run(
            new RunParams([
                'composer',
                'require',
                "{$module->getModuleName()}:*"
            ])
        );

        $project->run(
            new RunParams([
                'mysql',
                '-uroot',
                '-proot',
                '-hdatabase',
                "-ecreate database {$standupName};"
            ])
        );
    }
}
