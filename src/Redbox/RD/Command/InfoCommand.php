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
use Symfony\Component\Console\Helper\Table;
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

        $result = shell_exec(<<<CMD
docker-compose \
  -f .rd/base.yml \
  -f .rd/dev.yml \
  -f .rd/appvolumes.yml \
  -f .rd/dbvolumes.yml \
  ps
CMD
        );

        $rows = explode("\n", $result);
        $containers = array_slice($rows, 2, -1);
        $rows = array_map(
            function ($container) use ($output) {
                $output->writeln($container);
                $row = [];
                $isUp = strpos($container, 'Up') !== false;

                $row['name'] = explode(" ", $container)[0];
                $row['status'] = $isUp ? '<bg=green;fg=black> UP </>' : '<bg=red;fg=white;options=bold>DOWN</>';
                $row['ip'] = $isUp ? $this->getIPAddress($row['name']) : '<not running>';

                return $row;
            },
            $containers
        );

        $infoTable = new Table($output);
        $infoTable
            ->setHeaders(['Container', 'Status', 'IP Address'])
            ->setRows(array_map(
                function ($row) {
                    return [
                        $row['name'],
                        $row['status'],
                        $row['ip']
                    ];
                },
                $rows
            ))
            ->render();
    }

    private function getIPAddress($containerName)
    {
        $infoJson = shell_exec(<<<CMD
docker inspect {$containerName}
CMD
        );

        $info = json_decode($infoJson, true);

        return $info[0]['NetworkSettings']['Networks']['rd_magento']['IPAddress'];
    }
}
