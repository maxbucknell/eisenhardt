<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\ProjectFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SyncCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Sync host files into container volume')
            ->setHelp(<<<EOT
The <info>sync</> command starts a unison command that copies files
from your host into the container.

The following directories are not synced:

* .eisenhardt
* .git
* dev
* generated
* lib
* node_modules
* pub/static
* setup
* var/cache
* var/view_preprocessed
* vendor
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $project = ProjectFactory::findFromWorkingDirectory($logger);

        $containerCommands = [
            [
                'unison',
                '/mnt/right',
                '/mnt/left',
                '-batch',
                '-repeat',
                '5',
                '-ignore',
                'Path .eisenhardt',
                '-ignore',
                'Path .git',
                '-ignore',
                'Path dev',
                '-ignore',
                'Path generated',
                '-ignore',
                'Path lib',
                '-ignore',
                'Path pub/media',
                '-ignore',
                'Path pub/static',
                '-ignore',
                'Path setup',
                '-ignore',
                'Path var/cache',
                '-ignore',
                'Path var/view_preprocessed',
                '-ignore',
                'Path vendor',
                '-ignore',
                'Name node_modules'
            ]
        ];

        $uid = \getmyuid();
        $userString = "{$uid}:10118";

        $dockerCommand = [
            'docker',
            'run',
            '--rm',
            '-it',
            "-u{$userString}",
            "-eHOME=/tmp",
            "-v{$project->getInstallationDirectory()}:/mnt/left",
            "-v{$project->getVolumeName()}:/mnt/right",
            "maxbucknell/sync"
        ];

        foreach ($containerCommands as $containerCommand) {
            $command = [];
            \array_push(
                $command,
                ...$dockerCommand,
                ...$containerCommand
            );

            var_dump($command);

            $process = new Process($command);

            $process->setTty(true);

            $process->run();
        }
    }


}
